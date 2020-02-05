<?php
// The php.ini setting phar.readonly must be set to 0

if(ini_get('phar.readonly')) {
    fwrite(STDERR, "PHAR creation failed. php ini setting phar.readonly must bet set to 0\n");
    exit(1);
}

chdir(__DIR__);

$file_name = 'php-smtp-tester.phar';

$dir = '../release';

$pharFile = $dir . '/' . $file_name;

if(!is_dir($dir)) {
    mkdir($dir);
}

// clean up
if (file_exists($pharFile)) {
    unlink($pharFile);
}
if (file_exists($pharFile . '.gz')) {
    unlink($pharFile . '.gz');
}

// create phar
$p = new Phar($pharFile);

$p->startBuffering();

$default_stub = $p->createDefaultStub('index.php', '/index.php');

// creating our library using whole directory
$p->buildFromDirectory('../src/');

$stub = "#!/usr/bin/env php\n{$default_stub}";

// plus - compressing it into gzip
$p->compress(Phar::GZ);

$p->setStub($stub);
$p->stopBuffering();


echo "$file_name successfully created";
echo "\n";
