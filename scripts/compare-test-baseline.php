<?php

/**
 * Compare a PHPUnit JUnit report against the fixed failure baseline (tests/baseline-failures.txt).
 *
 * Usage:
 *   php8.2 scripts/compare-test-baseline.php extract <junit.xml>
 *       Print the sorted failing test identifiers ("Class::method"); used to create the baseline.
 *   php8.2 scripts/compare-test-baseline.php compare <junit.xml> [baseline.txt]
 *       Print new failures, newly passing tests and baseline entries that did not run.
 *       Exit code 1 when there are new failures, 0 otherwise.
 *
 * "Failing" means the test case has a <failure> or <error> element. Skipped, incomplete and risky
 * tests are not failures. The procedure is described in CLAUDE.md ("Test baseline").
 */

function usage(): void
{
    fwrite(STDERR, "Usage: php scripts/compare-test-baseline.php extract <junit.xml>\n");
    fwrite(STDERR, "       php scripts/compare-test-baseline.php compare <junit.xml> [baseline.txt]\n");
    exit(2);
}

/** @return array{0: array<string, true>, 1: array<string, true>} [all test ids, failing test ids] */
function readJunit(string $path): array
{
    if (! is_file($path)) {
        fwrite(STDERR, "JUnit file not found: {$path}\n");
        exit(2);
    }

    $xml = simplexml_load_file($path);
    if ($xml === false) {
        fwrite(STDERR, "Cannot parse JUnit file: {$path}\n");
        exit(2);
    }

    $all = [];
    $failing = [];
    foreach ($xml->xpath('//testcase') as $case) {
        $class = (string) ($case['class'] ?: $case['classname']);
        $id = str_replace('.', '\\', $class).'::'.(string) $case['name'];
        $all[$id] = true;
        if (isset($case->failure) || isset($case->error)) {
            $failing[$id] = true;
        }
    }

    return [$all, $failing];
}

/** @return array<string, true> */
function readBaseline(string $path): array
{
    if (! is_file($path)) {
        fwrite(STDERR, "Baseline file not found: {$path}\n");
        exit(2);
    }

    $ids = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
        $line = trim($line);
        if ($line !== '' && $line[0] !== '#') {
            $ids[$line] = true;
        }
    }

    return $ids;
}

function printList(string $title, array $ids): void
{
    ksort($ids, SORT_STRING);
    echo $title.' ('.count($ids).")\n";
    foreach (array_keys($ids) as $id) {
        echo '  '.$id."\n";
    }
}

$mode = $argv[1] ?? null;
$junit = $argv[2] ?? null;
if ($junit === null || ! in_array($mode, ['extract', 'compare'], true)) {
    usage();
}

[$all, $failing] = readJunit($junit);

if ($mode === 'extract') {
    ksort($failing, SORT_STRING);
    echo implode("\n", array_keys($failing)).(count($failing) ? "\n" : '');
    exit(0);
}

$baseline = readBaseline($argv[3] ?? dirname(__DIR__).'/tests/baseline-failures.txt');

$newFailures = array_diff_key($failing, $baseline);
$newlyPassing = array_diff_key(array_intersect_key($baseline, $all), $failing);
$notRun = array_diff_key($baseline, $all);

echo 'Run: '.count($all).' tests, '.count($failing).' failing. Baseline: '.count($baseline)." failing.\n";
printList('New failures', $newFailures);
printList('Newly passing', $newlyPassing);
printList('Baseline failures not run (test removed or renamed)', $notRun);

exit(count($newFailures) > 0 ? 1 : 0);
