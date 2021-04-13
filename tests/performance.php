#!/usr/bin/php
<?php
# run with XDEBUG_CONFIG=mode=off ./performance.php 

require_once __DIR__ . '/../vendor/autoload.php';

use EasyRdf\Graph;
use quickRdfIo\NQuadsParser;
use termTemplates\QuadTemplate;

$testFile = __DIR__ . '/puzzle4d_100k.ntriples';
$testSbj  = 'https://id.acdh.oeaw.ac.at/td-archiv/MobileObjects_Funde_E19/Inventarbuecher_Datenbanken_Tabellen_Fundzettel/Inventarbuecher/Keramik_07217-07349A/TD_Inv_4DPuzzle1936__TD_7230.tif';
$testPred = 'https://vocabs.acdh.oeaw.ac.at/schema#hasCurator';
$testObj  = 'https://id.acdh.oeaw.ac.at/sstuhec';

function printlog(int $n, string $solution, string $test, float $time,
                  ?int $tripCount): void {
    printf("%d\t%.6f\t%d\t%d\t%d\t%s\t%s\n", $n, $time, memory_get_peak_usage(true) / 1024 / 1024, memory_get_usage(true) / 1024 / 1024, $tripCount, $solution, $test);
}
$testCount = (int) ($argv[2] ?? 1);
$test      = $argv[1] ?? '';
if ($test == 'easyrdf') {
    for ($i = 0; $i < $testCount; $i++) {
        $t = microtime(true);
        $g = new Graph();
        $g->parseFile($testFile, 'application/n-triples');
        $t = microtime(true) - $t;
        printlog($i, $argv[1], "parsing", $t, $g->countTriples());

        $t = microtime(true);
        $d = $g->resource($testSbj);
        $t = microtime(true) - $t;
        printlog($i, $argv[1], "subject search", $t, null);

        $t = microtime(true);
        $d = $g->resourcesMatching($testPred);
        $t = microtime(true) - $t;
        printlog($i, $argv[1], "predicate search", $t, count($d));

        $t = microtime(true);
        $d = [];
        $objRes = $g->resource($testObj);
        foreach ($g->reversePropertyUris($testObj) as $prop) {
            $d = array_merge($d, $g->resourcesMatching($prop, $objRes));
        }
        $t = microtime(true) - $t;
        printlog($i, $argv[1], "object search", $t, count($d));

        unset($g);
    }
} else if (in_array($test, ['idxsafe', 'idxunsafe', 'safe', 'unsafe', 'simpleRdf'])) {
    if ($test === 'simpleRdf') {
        $df = new simpleRdf\DataFactory();
    } else {
        $df                      = new quickRdf\DataFactory();
        $df::$enforceConstructor = in_array($argv[1], ['idxsafe', 'safe']);
    }
    for ($i = 0; $i < $testCount; $i++) {
        $t = microtime(true);
        $p = new NQuadsParser($df, false, NQuadsParser::MODE_TRIPLES);
        if ($test === 'simpleRdf') {
            $g = new simpleRdf\Dataset();
        } else {
            $g = new quickRdf\Dataset(in_array($argv[1], ['idxsafe', 'idxunsafe']));
        }
        $f = fopen($testFile, 'r');
        if ($f !== false) {
            $g->add($p->parseStream($f));
            fclose($f);
            $t = microtime(true) - $t;
            printlog($i, $argv[1], "parsing", $t, count($g));
        }

        $t = microtime(true);
        $d = $g->copy(new QuadTemplate($df::namedNode($testSbj)));
        $t = microtime(true) - $t;
        printlog($i, $argv[1], "subject search", $t, count($d));

        $t = microtime(true);
        $d = $g->copy(new QuadTemplate(null, $df::namedNode($testPred)));
        $t = microtime(true) - $t;
        printlog($i, $argv[1], "predicate search", $t, count($d));

        $t = microtime(true);
        $d = $g->copy(new QuadTemplate(null, null, $df::namedNode($testObj)));
        $t = microtime(true) - $t;
        printlog($i, $argv[1], "object search", $t, count($d));
    }
} else {
    exit("Usage: $argv[0] easyrdf/idxsafe/idxunsafe/safe/unsafe/simpleRdf [count=1]\n");
}
