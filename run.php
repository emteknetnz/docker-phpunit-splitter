<?php

$logdir = 'phpunitlogs';

$time_start = microtime(true); 

shell_exec("rm -rf $logdir && mkdir $logdir");

$s = file_get_contents('unittests.php');
preg_match_all('%function (test[^\( ]*)%', $s, $m);
foreach ($m[1] as $funcname) {
    echo "Testing $funcname\n";
    shell_exec("docker run --name myphpunit-$funcname --rm -d -v $(pwd):/a php:cli bash -c '/a/vendor/bin/phpunit --filter=testOne /a/unittests.php > /a/phpunitlogs/$funcname.txt 2>&1'");
}

for ($i = 0; $i < 10; $i++) {
    $s = shell_exec('docker ps');
    if (preg_match('%myphpunit\-%', $s)) {
        echo "Waiting for tests to complete ...\n";
        sleep(1);
        continue;
    }
    break;
}

// TODO: something that better parses all the results in phpunitlogs
foreach (scandir($logdir) as $filename) {
    if (!preg_match('%^test.*?\.txt$%', $filename)) {
        continue;
    }
    echo "\n### $filename\n";
    $s = file_get_contents("$logdir/$filename");
    $s = preg_replace('%PHPUnit .+? by Sebastian Bergmann[^\n]*%', '', $s);
    echo $s;
}

$execution_time = round(microtime(true) - $time_start, 2);
echo "Total Execution Time: $execution_time seconds\n";
