<?php

namespace App\Commands;

use Ahc\Cli\Application as App;
use Ahc\Cli\Helper\Shell;
use Ahc\Cli\Input\Command;
use Ahc\Cli\Input\Reader;
use Ahc\Cli\IO\Interactor;
use Ahc\Cli\Output\Writer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class TestSMTP extends Command {

    private static $ENCRYPTION_OPTIONS = [
        '1'=>'None',
        '2'=>'TLS',
        '3'=>'SSL'
    ];

    public function __construct() {
        parent::__construct('test-smtp', 'TestSMTP');

        $this->argument('connectionstring', 'URL Format [[SCHEME://][[USER[:PASSWORD]]@]HOST[:PORT]');

        $this->option('-h --host', 'SMTP Server Host. Supports ', 'strval');
        $this->option('-u --user', 'SMTP Server User', 'strval');
        $this->option('-p --password', '!!Unsafe!! SMTP Server Password', 'strval');
        $this->option('-e --encryption', sprintf('Encryption Type (%s)', implode('/', array_values(self::$ENCRYPTION_OPTIONS))), 'strval');
        $this->option('-p --port', 'Port number [Default: 25 for None encryption, 587 for TLS/SSL]', 'intval');
        $this->option('-f --from', 'Email address of the sender', 'strval');
        $this->option('-t, --to', 'Recipient for test email', 'strval');
    }

    protected function defaults(): Command {
        $this->option('-H, --help', 'Show help')->on([
            $this,
            'showHelp'
        ]);
        $this->option('-V, --version', 'Show version')->on([
            $this,
            'showVersion'
        ]);
        $this->option('-v, --verbosity', 'Verbosity level', NULL, 0)->on(function () {
            $this->set('verbosity', ($this->verbosity ?? 0) + 1);

            return FALSE;
        });

        $this->onExit(function ($exitCode = 0) {
            exit($exitCode);
        });
        return $this;
    }



    public function execute($connectionstring, $host, $user, $password, $encryption, $port, $from, $to) {
        /**
         * @var Interactor | Writer | Reader $io
         */
        $io = $this->app()->io();


        if($connectionstring) {
            list($host, $user, $password, $encryption, $port) = $this->parseConnectionString($connectionstring, $host, $user, $password, $encryption, $port);
        }

        $host = $this->readParam('SMTP Host', $host);
        $user = $this->readParam('SMTP User', $user);
        $password = $this->readParam('SMTP Password', $password, NULL, TRUE);
        if(!$encryption) {
            $choice = $io->choice('Encryption: ', self::$ENCRYPTION_OPTIONS, 'TLS', false);
            $encryption = array_key_exists($choice, self::$ENCRYPTION_OPTIONS) ? self::$ENCRYPTION_OPTIONS[$choice] : $choice;
        }

        $port = $this->readParam('Port', $port, $this->getDefaultPort($encryption));
        $from = $this->readParam('Send test mail FROM', $from, 'test@example.com');
        $to = $this->readParam('Send test mail TO', $to, [$this, 'getDefaultEmail']);

//        $io->write('Host: ' . $host, TRUE);
//        $io->write('User: ' . $user, TRUE);
//        $io->write('Password: ' . $password, TRUE);
//        $io->write('Encryption: ' . $encryption, TRUE);
//        $io->write('Port: ' . $port, TRUE);
//        $io->write('To: ' . $to, TRUE);
//        $io->write('From: ' . $from, TRUE);

        $mailer = new PHPMailer();
        $mailer->isSMTP();
        $mailer->SMTPAuth = true;
        $mailer->Host = $host;
        $mailer->Username = $user;
        $mailer->Password = $password;
        $mailer->Port = $port;
        $mailer->SMTPSecure = $this->getSmtpSecureValue($encryption);

        $mailer->Subject = 'Test Email from PHP SMTP Tester';
        $mailer->Body = '.';

        $mailer->clearAllRecipients();;
        $mailer->setFrom($from);
        $mailer->addAddress($to, 'SMTP Tester');

        $mailer->SMTPDebug = 2;

        try
        {
            $success = $mailer->send();
            if($success) {
                $io->writer()->green('Success!');
            } else {
                $io->writer()->red('Failure!');
            }
            $io->eol();
        } catch (Exception $e)
        {
            $io->error($e->getMessage(), true);
        }

    }

    protected function readParam($question, $value = '', $default = NULL, $secret = FALSE) {
        $io = $this->app()->io();

        if (trim($value) === '')
        {
            if (is_callable($default))
            {
                $default = call_user_func($default);
            }
            $value = trim($io->prompt($question, $default, null, 3, $secret));
        }
        return $value;
    }

    protected function getDefaultEmail() {
        $shell = new Shell('git config --global user.email');
        $shell->execute();
        return trim($shell->getOutput());
    }


    protected function getDefaultPort($encryption) {
        switch(strtolower($encryption)) {
            case 'tls':
                return 587;
            case 'ssl':
                return 465;
            case 'None':
            default:
                return 25;
        }
    }

    protected function getSmtpSecureValue($encryption) {
        $encryption = strtolower($encryption);
        switch($encryption) {
            case PHPMailer::ENCRYPTION_STARTTLS:
            case PHPMailer::ENCRYPTION_SMTPS:
                return $encryption;
        }
        return '';
    }

    private function parseConnectionString(string $connection_string, $default_host, $default_user, $default_password, $default_encryption, $default_port) {
        if(is_string($connection_string)) {

            if(strpos($connection_string, '//')===false) {
                $connection_string = '//' . $connection_string;
            }

            $url_info = parse_url($connection_string);
        } else {
            $url_info = null;
        }

        if(!is_array($url_info)) {
            return [$default_host, $default_user, $default_password, $default_encryption, $default_port];
        }

        if(count($url_info) === 1 && array_key_exists('path', $url_info)) {
            $url_info['host'] = $url_info['path'];
        }

        return [
            array_key_exists('host',$url_info) ? $url_info['host'] : $default_host,
            array_key_exists('user',$url_info) ? $url_info['user'] : $default_user,
            array_key_exists('pass',$url_info) ? $url_info['pass'] : $default_password,
            array_key_exists('scheme',$url_info) ? $url_info['scheme'] : $default_encryption,
            array_key_exists('port',$url_info) ? $url_info['port'] : $default_port,
        ];

    }
}
