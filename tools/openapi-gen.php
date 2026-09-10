<?php
/**
 * Generate openapi.json for the /api/v1 surface, from the running application.
 *
 *   /opt/alt/php83/usr/bin/php tools/openapi-gen.php            # write openapi.json
 *   /opt/alt/php83/usr/bin/php tools/openapi-gen.php --sample   # also probe GET responses live
 *   /opt/alt/php83/usr/bin/php tools/openapi-gen.php --stdout   # print, write nothing
 *
 * Why this exists: the Android client and this server are built by different people, and the
 * hand-written contract drifted. `POST /quiz/submit` requires `verification_photo`, and the
 * hand-written document said the body was `{ attempt_id }` — a client following it cannot submit
 * at all. A generated contract cannot forget a required field.
 *
 * Three sources, because no single one knows everything:
 *
 *   1. The router — paths, methods, auth, access window, rate limit.
 *   2. The controller's `validate([...])` call — the request body. This project has no
 *      FormRequest classes, so off-the-shelf generators see nothing; the rules are parsed out
 *      of the method source instead.
 *   3. A live probe of GET endpoints (--sample) — response shapes. Laravel builds its responses
 *      as inline arrays, so they cannot be inferred statically. POST endpoints are never probed:
 *      they have side effects, and this runs against production.
 *
 * Output is deterministic (sorted paths and properties) so `git diff` on it means something.
 * Any validation rule this script does not understand is preserved verbatim under
 * `x-laravel-rules` rather than dropped — an unknown rule must never silently vanish.
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$sample = in_array('--sample', $argv, true);
$stdout = in_array('--stdout', $argv, true);

// ---------------------------------------------------------------- rules → schema

/** Laravel rule string -> [openapi schema, required?, leftovers]. */
function ruleToSchema(string $rules): array
{
    $parts = array_filter(array_map('trim', explode('|', $rules)));
    $schema = [];
    $required = false;
    $known = [];

    foreach ($parts as $p) {
        [$name, $arg] = array_pad(explode(':', $p, 2), 2, null);
        switch ($name) {
            case 'required': $required = true; $known[] = $p; break;
            case 'nullable': $schema['nullable'] = true; $known[] = $p; break;
            case 'string':   $schema['type'] = 'string'; $known[] = $p; break;
            case 'integer':  $schema['type'] = 'integer'; $known[] = $p; break;
            case 'numeric':  $schema['type'] = 'number'; $known[] = $p; break;
            case 'boolean':  $schema['type'] = 'boolean'; $known[] = $p; break;
            case 'array':    $schema['type'] = 'array'; $known[] = $p; break;
            case 'email':    $schema['type'] = 'string'; $schema['format'] = 'email'; $known[] = $p; break;
            case 'file':     $schema['type'] = 'string'; $schema['format'] = 'binary'; $known[] = $p; break;
            case 'max':
                if ($arg !== null) {
                    // `max` means length for a string and value for a number — the same word,
                    // two meanings, and getting it backwards makes a generated client reject
                    // valid input.
                    if (($schema['type'] ?? 'string') === 'string') $schema['maxLength'] = (int) $arg;
                    else $schema['maximum'] = (float) $arg;
                    $known[] = $p;
                }
                break;
            case 'min':
                if ($arg !== null) {
                    if (($schema['type'] ?? 'string') === 'string') $schema['minLength'] = (int) $arg;
                    else $schema['minimum'] = (float) $arg;
                    $known[] = $p;
                }
                break;
            case 'between':
                if ($arg !== null && str_contains($arg, ',')) {
                    [$lo, $hi] = explode(',', $arg, 2);
                    $schema['minimum'] = (float) $lo;
                    $schema['maximum'] = (float) $hi;
                    $known[] = $p;
                }
                break;
            case 'exists':
                $schema['type'] ??= 'integer';
                $schema['description'] = 'Must already exist: ' . $arg;
                $known[] = $p;
                break;
        }
    }

    $schema['type'] ??= 'string';
    $leftovers = array_values(array_diff($parts, $known));

    return [$schema, $required, $leftovers];
}

/** The `validate([...])` rules declared inside one controller method. */
function requestRules(string $class, string $method): array
{
    try {
        $rm = new ReflectionMethod($class, $method);
    } catch (\ReflectionException) {
        return [];
    }
    $file = $rm->getFileName();
    if (! $file) return [];

    $src = implode('', array_slice(
        file($file), $rm->getStartLine() - 1, $rm->getEndLine() - $rm->getStartLine() + 1
    ));

    // Both spellings appear in this codebase.
    if (! preg_match('/(?:\$request->validate|\$this->validate)\(\s*\[(.*?)\]\s*\)/s', $src, $m)) {
        return [];
    }

    $rules = [];
    // Single- and double-quoted rule strings; array-of-rules form is reported as unparsed.
    preg_match_all('/[\'"]([a-zA-Z_][\w.*]*)[\'"]\s*=>\s*[\'"]([^\'"]*)[\'"]/', $m[1], $f, PREG_SET_ORDER);
    foreach ($f as $x) $rules[$x[1]] = $x[2];

    return $rules;
}

// ---------------------------------------------------------------- walk the router

$paths = [];
$sampleToken = null;

if ($sample) {
    $user = App\Models\User::where('role', 'user')->first();
    if ($user) $sampleToken = $user->createToken('openapi-probe')->plainTextToken;
}
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

/** Reduce a decoded response to a schema, without inventing depth we cannot verify. */
function shapeOf(mixed $v, int $depth = 0): array
{
    if ($depth > 3) return ['description' => 'nested'];
    if (is_bool($v))   return ['type' => 'boolean'];
    if (is_int($v))    return ['type' => 'integer'];
    if (is_float($v))  return ['type' => 'number'];
    if (is_null($v))   return ['nullable' => true];
    if (is_string($v)) return ['type' => 'string'];
    if (is_array($v)) {
        if (array_is_list($v)) {
            return $v === []
                ? ['type' => 'array', 'items' => ['description' => 'unknown: the live response was empty']]
                : ['type' => 'array', 'items' => shapeOf($v[0], $depth + 1)];
        }
        $props = [];
        foreach ($v as $k => $vv) $props[(string) $k] = shapeOf($vv, $depth + 1);
        ksort($props);
        return ['type' => 'object', 'properties' => $props];
    }
    return ['description' => 'unknown'];
}

foreach (app('router')->getRoutes() as $route) {
    $uri = $route->uri();
    if (! str_starts_with($uri, 'api/v1/')) continue;

    $action = $route->getActionName();
    $mw = $route->gatherMiddleware();
    $mwFlat = implode(',', array_map(fn ($m) => is_string($m) ? $m : '', $mw));

    $tokenAuth = str_contains($mwFlat, 'auth:sanctum') || str_contains($mwFlat, 'Authenticate:sanctum');
    // Match the alias as well as the class. gatherMiddleware() does NOT resolve aliases —
    // a v1 route reports `access.window`, never `EnsureAccessWindow` — and checking only for
    // the class name reported every endpoint as unguarded.
    $window    = str_contains($mwFlat, 'access.window') || str_contains($mwFlat, 'EnsureAccessWindow');
    $throttle  = null;
    foreach ($mw as $m) {
        if (is_string($m) && preg_match('/throttle:(\S+)/', $m, $tm)) $throttle = $tm[1];
    }

    foreach ($route->methods() as $httpMethod) {
        if (in_array($httpMethod, ['HEAD', 'OPTIONS'], true)) continue;
        $verb = strtolower($httpMethod);

        $op = [
            'operationId' => $route->getName() ?: ($verb . ':' . $uri),
            'summary'     => $route->getName() ?: $uri,
            'x-auth'      => $tokenAuth ? 'sanctum-bearer' : 'public',
            'x-access-window' => $window,
            'x-rate-limit'    => $throttle ?? 'none',
        ];
        if ($tokenAuth) $op['security'] = [['bearerAuth' => []]];

        // Path parameters, including Laravel's optional {x?} form.
        if (preg_match_all('/\{(\w+)(\?)?\}/', $uri, $pm, PREG_SET_ORDER)) {
            foreach ($pm as $p) {
                $op['parameters'][] = [
                    'name' => $p[1],
                    'in' => 'path',
                    'required' => ! isset($p[2]),
                    'schema' => ['type' => 'string'],
                ];
            }
        }

        // Request body, from the controller's own validate() call.
        if (str_contains($action, '@')) {
            [$class, $method] = explode('@', $action);
            $rules = requestRules($class, $method);

            if ($rules) {
                $props = [];
                $required = [];
                $unparsed = [];
                foreach ($rules as $field => $ruleStr) {
                    [$schema, $isRequired, $left] = ruleToSchema($ruleStr);
                    $schema['x-laravel-rules'] = $ruleStr;
                    $props[$field] = $schema;
                    if ($isRequired) $required[] = $field;
                    if ($left) $unparsed[$field] = $left;
                }
                ksort($props);
                sort($required);

                $body = ['type' => 'object', 'properties' => $props];
                if ($required) $body['required'] = $required;

                if (in_array($verb, ['post', 'put', 'patch'], true)) {
                    $op['requestBody'] = [
                        'required' => (bool) $required,
                        'content' => ['application/json' => ['schema' => $body]],
                    ];
                } else {
                    // A GET that validates is reading query parameters.
                    foreach ($props as $name => $schema) {
                        $op['parameters'][] = [
                            'name' => $name, 'in' => 'query',
                            'required' => in_array($name, $required, true),
                            'schema' => $schema,
                        ];
                    }
                }
                if ($unparsed) $op['x-unparsed-rules'] = $unparsed;
            }
        }

        // Response shape, probed live. GET only: a POST here has side effects.
        // Never probe the contract endpoints: they return this very document, and embedding
        // its own shape inside itself doubled the file from 67 KB to 141 KB.
        if ($sample && $verb === 'get' && ! str_contains($uri, '{') && ! str_contains($uri, '/contract')) {
            $headers = ['HTTP_ACCEPT' => 'application/json'];
            if ($tokenAuth && $sampleToken) $headers['HTTP_AUTHORIZATION'] = 'Bearer ' . $sampleToken;
            $res = $kernel->handle(Illuminate\Http\Request::create('/' . $uri, 'GET', [], [], [], $headers));
            $decoded = json_decode($res->getContent(), true);
            if (is_array($decoded)) {
                $op['responses'][(string) $res->getStatusCode()] = [
                    'description' => 'Observed on a live probe',
                    'content' => ['application/json' => ['schema' => shapeOf($decoded)]],
                ];
            }
        }

        $op['responses'] ??= ['200' => ['description' => 'Not probed — POST endpoints are not called by the generator']];
        $paths['/' . $uri][$verb] = $op;
    }
}
ksort($paths);

if ($sampleToken) {
    App\Models\User::where('role', 'user')->first()?->tokens()->where('name', 'openapi-probe')->delete();
}

// ---------------------------------------------------------------- emit

// Fingerprint the human-written companions and carry it inside the contract.
//
// A schema can only ever describe shape. The rules that actually break a client — that the
// AR reticle is the interaction model, that submit stays open after time_expired, that a
// position goes out at most once per 10s — live in prose, and nothing told the client agent
// when that prose changed. It diffs openapi.json; so now a guide edit moves a hash inside
// openapi.json, and the change is visible in the file it already watches.
$guides = [];
foreach (['APK-BUILD-GUIDE.md', 'API-V1-CONTRACT.md', 'APK-WORK-ORDER.md'] as $g) {
    $p = __DIR__ . '/../' . $g;
    if (is_file($p)) $guides[$g] = substr(hash_file('sha256', $p), 0, 16);
}
ksort($guides);

$doc = [
    'openapi' => '3.0.3',
    'info' => [
        'title' => App\Models\BrandSetting::appName() . ' — player API',
        'version' => 'v1',
        'description' =>
            "Generated by tools/openapi-gen.php from the running router, the controllers'\n" .
            "validate() rules, and a live probe of GET responses. Do not edit by hand: the\n" .
            "hand-written contract drifted, which is why this file exists.\n\n" .
            "Semantics a schema cannot carry — that submit stays open after time_expired, that\n" .
            "fun_game answers score zero, the minimum interval between position posts — live in\n" .
            "APK-BUILD-GUIDE.md and still have to be read.",
        'x-behaviour-guides' => $guides,
    ],
    'servers' => [['url' => rtrim(config('app.url'), '/')]],
    'components' => [
        'securitySchemes' => [
            'bearerAuth' => ['type' => 'http', 'scheme' => 'bearer', 'description' => 'Laravel Sanctum personal access token'],
        ],
    ],
    'paths' => $paths,
];

// When included rather than run, hand the array back and print nothing. contract-check.php
// needs the document, not a side effect — and the `exit` below would otherwise take the
// whole process down before it could compare anything.
if (defined('OPENAPI_GEN_RETURN')) {
    return $doc;
}

$json = json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

if ($stdout) {
    echo $json;
    exit;
}

file_put_contents(__DIR__ . '/../openapi.json', $json);
printf("openapi.json written: %d paths, %.1f KB%s\n", count($paths), strlen($json) / 1024,
    $sample ? ', GET responses probed live' : ' (run with --sample to probe responses)');
