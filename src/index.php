<?php
if (php_sapi_name() !== 'cli') {
    echo 'This is a CLI program. Sayonara!';
    exit(1);
}

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Ahc\Cli\Application('SMTPTester', 'v0.0.1');
$app->add(new \App\Commands\TestSMTP(), 'test', true);

$app->logo('Tech.marketing');

$app->handle($_SERVER['argv']);
