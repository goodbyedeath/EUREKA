<?php
/**
 * Walk a blade file's control structures and report the div balance of every
 * branch. A file is sound when every path through it comes out at zero.
 */
function tokens(string $src): array {
    // Drop script/style bodies: they hold strings that look like markup.
    $src = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $src);
    $src = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $src);
    $src = preg_replace('#\{\{--.*?--\}\}#s', '', $src);
    $src = preg_replace('#@php\b.*?@endphp#s', '', $src);
    $src = preg_replace('#<!--.*?-->#s', '', $src);

    $out = [];
    $re = '#@(if|elseif|else|endif|unless|endunless|isset|endisset|empty|endempty|auth|endauth|guest|endguest|forelse|endforelse|foreach|endforeach|for|endfor|while|endwhile|switch|case|default|endswitch|error|enderror|push|endpush|section|endsection|once|endonce)\b|</?div\b#i';
    preg_match_all($re, $src, $m, PREG_OFFSET_CAPTURE);
    foreach ($m[0] as $i => [$text, $off]) {
        $line = substr_count(substr($src, 0, $off), "\n") + 1;
        $out[] = [strtolower(ltrim($text, '@')), $line, $text];
    }
    return $out;
}

$OPEN  = ['if','unless','isset','empty','auth','guest','forelse','foreach','for','while','switch','error','push','section','once'];
$CLOSE = ['endif'=>'if','endunless'=>'unless','endisset'=>'isset','endempty'=>'empty','endauth'=>'auth','endguest'=>'guest',
          'endforelse'=>'forelse','endforeach'=>'foreach','endfor'=>'for','endwhile'=>'while','endswitch'=>'switch',
          'enderror'=>'error','endpush'=>'push','endsection'=>'section','endonce'=>'once'];
// Which openers branch (their sub-blocks are alternatives) vs loop (body runs 0..n times)
$BRANCHY = ['if','unless','isset','empty','auth','guest','switch','forelse','error'];
$SPLIT   = ['elseif','else','empty','case','default'];

function walk(array $toks, int &$i, ?string $ctx, array &$problems, string $file) {
    global $OPEN, $CLOSE, $BRANCHY, $SPLIT;
    // Returns the set of possible diffs for this block.
    $branches = [[ 'diff' => 0, 'line' => $toks[$i][1] ?? 0 ]];
    while ($i < count($toks)) {
        [$t, $line, $raw] = $toks[$i];
        if ($t === 'div') {
            $branches[count($branches)-1]['diff'] += ($raw[1] === '/') ? -1 : 1;
            $i++; continue;
        }
        if (isset($CLOSE[$t])) {
            if ($ctx === null) { $problems[] = "$file:$line stray @$t"; $i++; continue; }
            $i++; return $branches;
        }
        if (in_array($t, $SPLIT, true) && $ctx !== null) {
            // @empty closes a forelse loop-body but splits inside forelse; inside
            // a plain block it is an opener. Treat as split only when it fits.
            if ($t !== 'empty' || $ctx === 'forelse') {
                $branches[] = ['diff' => 0, 'line' => $line];
                $i++; continue;
            }
        }
        if (in_array($t, $OPEN, true)) {
            $open = $t; $openLine = $line; $i++;
            $sub = walk($toks, $i, $open, $problems, $file);
            $diffs = array_values(array_unique(array_column($sub, 'diff')));
            if (in_array($open, $BRANCHY, true)) {
                if (count($diffs) > 1) {
                    $d = implode('/', $diffs);
                    $problems[] = "$file:$openLine @$open branches disagree: $d";
                }
                $branches[count($branches)-1]['diff'] += $diffs[0];
            } else {
                if ($diffs !== [0]) {
                    $problems[] = "$file:$openLine @$open body is unbalanced (" . implode('/', $diffs) . ")";
                }
            }
            continue;
        }
        $i++;
    }
    return $branches;
}

$files = array_slice($argv, 1);
$bad = 0;
foreach ($files as $f) {
    $toks = tokens(file_get_contents($f));
    $i = 0; $problems = [];
    $branches = walk($toks, $i, null, $problems, basename($f));
    $diffs = array_values(array_unique(array_column($branches, 'diff')));
    if ($diffs !== [0]) $problems[] = basename($f) . ": file total " . implode('/', $diffs);
    if ($problems) { $bad++; foreach ($problems as $p) echo "  $p\n"; }
    else echo "  ok  " . basename($f) . "\n";
}
echo $bad ? "\n$bad file(s) with problems\n" : "\nall clean\n";
