<?php
use App\Commands\TestSMTP;

if (php_sapi_name() !== 'cli') {
    echo 'This is a CLI program. Sayonara!';
    exit(1);
}

if(version_compare(PHP_VERSION, '8.0.0', '<'))
{
    echo 'This program requires PHP 8.0.0 or higher. You are using ' . PHP_VERSION . PHP_EOL;
    exit(1);
}

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Ahc\Cli\Application('SMTPTester', 'v0.0.1');
$app->add(new TestSMTP(), '', true);

$banner = <<<txt

 ▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄    ╓▄▄▄,             ╓▄▄▄,        ,▄▄▄▄,        ▄▄▄▄▄
 ██████████████████████▀,████████████▄     ▓███████████▄    ▓█████        █████
 ████████████████████▀,████████████████▄ ████████████████▌  ▓█████        █████
        █████▌       ╓██████╙    └▀██████ ████╙     ▀██████ ▓█████        █████
        █████▌       ██████████▌    ██████ ─          ───── ▓██████████████████
        █████▌       ███████████     ▀█████▄                ▓██████████████████
        █████▌       ██████▀▀▀▀▀   ▄▓▄╙██████        ,▓▓▓▓▓ ▓█████▀▀▀▀▀▀▀██████
        █████▌        ███████▄▄▄▄██████ ███████▄▄▄▄▄██████▀ ▓█████        █████
        █████▌         ╨██████████████▀  ╙███████████████   ▓█████        █████
        █████▌           └▀████████▀╙       ▀█████████▀     ▓█████        █████


txt;


$app->logo($banner);
if($_SERVER['argc'] < 2)
{
    $app->handle(array_merge($_SERVER['argv'], [' ']));
} else {
    $app->handle($_SERVER['argv']);
}
