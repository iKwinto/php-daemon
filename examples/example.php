#!/usr/bin/env php
<?php
/**
 * Run an example or list available examples if no example name is provided.
 */

declare(ticks = 1); // needed for the daemon signal handling

include __DIR__ . '/autoload.php';

if (empty($argv[1])) {
    listExamples();
    exit;
}

$name = basename($argv[1]);
$script = getScript($name);
if (!$script) {
    print "Example does not exist: \"$name\"\n";
    listExamples();
    exit(1);
}

// run the example
echo ">> Running example: \"$name\" (Press ^C to exit)\n";
$run = require $script;
if (is_callable($run)) {
    $run();
}

function listExamples()
{
    $names = [];
    $files = glob(__DIR__ . '/src/*');
    if ($files) {
        foreach ($files as $file) {
            $info = pathinfo($file);
            unset($META);
            include getScript($info['filename']);
            $names[$info['filename']] = isset($META) ? $META : '';
        }
        ksort($names);
    } else {
        print "No examples found?\n";
        return;
    }

    $width = (getScreenSize(true) ?: 80) - 9; // account for {tab} and {nl}
    $cmd = './' . basename($GLOBALS['argv'][0]);
    print "Usage: $cmd ExampleName\n\nAvailable Examples:\n===================\n";
    foreach ($names as $name => $meta) {
        print "$name\n";
        if (isset($meta['description'])) {
            $desc = implode("\n", array_map(function ($s) { return "\t" . trim($s); }, explode("\n", wordwrap($meta['description'], $width))));
            print "$desc\n";
        }
    }
    print "\n";
}

function getScript($name)
{
    $path = __DIR__ . "/src/$name";
    switch (true) {
        case file_exists($script = $path . '/run.php'):
            break;
        case file_exists($script = $path . '.php'):
            break;
        default:
            $script = null;
    }
    return $script;
}

function getScreenSize($widthOnly = false)
{
    // non-portable way to get screen size. just for giggles...
    $output = [];
    preg_match_all("/rows.([0-9]+);.columns.([0-9]+);/", strtolower(exec('stty -a |grep columns')), $output);
    if (count($output) == 3) {
        if ($widthOnly) {
            return $output[2][0];
        } else {
            return [$output[1][0], $output[2][0]];
        }
    }
    return null;
}
