<?php
/**
 * Fail if openapi.json no longer matches the code.
 *
 *   /opt/alt/php83/usr/bin/php tools/contract-check.php
 *
 * Exit 0 when they agree, 1 when they do not, and print what moved. Run it before pushing, and
 * in CI if there ever is one: the Android client is generated from this file, so a change to a
 * route, a rate limit or a validation rule that does not reach the file is a silent break in
 * someone else's build.
 *
 * Response shapes are deliberately excluded from the comparison. They come from probing the live
 * server, so they move with the data — an empty leaderboard today and a populated one tomorrow
 * would both "fail", and a check that cries wolf gets ignored. What is compared is the part that
 * is purely a function of the code: paths, methods, auth, access window, rate limit, and request
 * bodies.
 */

$root = dirname(__DIR__);
$committed = $root . '/openapi.json';

if (! is_file($committed)) {
    fwrite(STDERR, "openapi.json is missing. Generate it:\n  php tools/openapi-gen.php --sample\n");
    exit(1);
}

/** Everything that is a function of the code, and nothing that is a function of the data. */
function contractOnly(array $doc): array
{
    $out = [];
    foreach ($doc['paths'] ?? [] as $path => $ops) {
        foreach ($ops as $verb => $op) {
            unset($op['responses'], $op['summary'], $op['operationId']);
            $out["$verb $path"] = $op;
        }
    }
    ksort($out);
    return $out;
}

// Run the generator in this process, capturing its stdout.
//
// Not via exec(): `exec()` and `symlink()` are disabled in PHP on this host, so shelling out
// fails with "Call to undefined function". Including it also means the framework boots once
// instead of twice.
$argv = [$root . '/tools/openapi-gen.php'];
$argc = 1;
define('OPENAPI_GEN_RETURN', true);
$fresh = require $root . '/tools/openapi-gen.php';

if (! is_array($fresh)) {
    fwrite(STDERR, "the generator produced no usable JSON\n");
    exit(1);
}

$committedDoc = json_decode(file_get_contents($committed), true) ?: [];

// The prose companions are part of the contract even though a schema cannot express them.
$guidesOld = $committedDoc['info']['x-behaviour-guides'] ?? [];
$guidesNew = $fresh['info']['x-behaviour-guides'] ?? [];

$a = contractOnly($committedDoc);
$b = contractOnly($fresh);

$added   = array_diff_key($b, $a);
$removed = array_diff_key($a, $b);
$changed = [];
foreach (array_intersect_key($a, $b) as $k => $old) {
    if (json_encode($old) !== json_encode($b[$k])) $changed[$k] = [$old, $b[$k]];
}

$guideMoved = $guidesOld !== $guidesNew;

if (! $added && ! $removed && ! $changed && ! $guideMoved) {
    printf("openapi.json matches the code (%d operations).\n", count($b));
    exit(0);
}

echo "openapi.json is out of date.\n\n";

foreach ($guidesNew as $g => $h) {
    if (($guidesOld[$g] ?? null) !== $h) {
        echo "  GUIDE     $g changed   <-- behaviour rules moved; the client must re-read it\n";
    }
}

foreach ($added as $k => $_)   echo "  NEW       $k\n";
foreach ($removed as $k => $_) echo "  REMOVED   $k   <-- a client calling this will break\n";

foreach ($changed as $k => [$old, $new]) {
    echo "  CHANGED   $k\n";
    foreach (['x-auth', 'x-access-window', 'x-rate-limit'] as $field) {
        $o = var_export($old[$field] ?? null, true);
        $n = var_export($new[$field] ?? null, true);
        if ($o !== $n) echo "              $field: $o -> $n\n";
    }

    $of = $old['requestBody']['content']['application/json']['schema'] ?? [];
    $nf = $new['requestBody']['content']['application/json']['schema'] ?? [];
    $oldReq = $of['required'] ?? [];
    $newReq = $nf['required'] ?? [];
    foreach (array_diff($newReq, $oldReq) as $f) {
        echo "              now REQUIRED: $f   <-- older clients omitting it will 422\n";
    }
    foreach (array_diff($oldReq, $newReq) as $f) {
        echo "              no longer required: $f\n";
    }
    foreach (array_diff_key($nf['properties'] ?? [], $of['properties'] ?? []) as $f => $_) {
        echo "              new field: $f\n";
    }
    foreach (array_diff_key($of['properties'] ?? [], $nf['properties'] ?? []) as $f => $_) {
        echo "              field gone: $f\n";
    }
}

echo "\nRegenerate and commit it in the same change as the code:\n";
echo "  /opt/alt/php83/usr/bin/php tools/openapi-gen.php --sample\n";
exit(1);
