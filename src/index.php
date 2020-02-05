<?php
if (php_sapi_name() !== 'cli') {
    echo 'This is a CLI program. Sayonara!';
    exit(1);
}

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Ahc\Cli\Application('SMTPTester', 'v0.0.1');
$app->add(new \App\Commands\TestSMTP(), '', true);

$banner = <<<txt
    _______        _                            _        _   _
   |__   __|      | |                          | |      | | (_)
      | | ___  ___| |__    _ __ ___   __ _ _ __| | _____| |_ _ _ __   __ _
      | |/ _ \/ __| '_ \  | '_ ` _ \ / _` | '__| |/ / _ \ __| | '_ \ / _` |
      | |  __/ (__| | | |_| | | | | | (_| | |  |   <  __/ |_| | | | | (_| |
      |_|\___|\___|_| |_(_)_| |_| |_|\__,_|_|  |_|\_\___|\__|_|_| |_|\__, |
                                                                      __/ |
                                                                     |___/

txt;


$app->logo($banner);
if($_SERVER['argc'] < 2)
{
    $app->handle(array_merge($_SERVER['argv'], ['df']));
} else {
    $app->handle($_SERVER['argv']);
}
