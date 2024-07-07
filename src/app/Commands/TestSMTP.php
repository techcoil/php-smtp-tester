<?php

namespace App\Commands;

use Ahc\Cli\Helper\Shell;
use Ahc\Cli\Input\Command;
use Ahc\Cli\Input\Reader;
use Ahc\Cli\IO\Interactor;
use Ahc\Cli\Output\Writer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class TestSMTP extends Command
{

    private static array $encryptionOptions = [
        '1' => 'None',
        '2' => 'TLS',
        '3' => 'SSL'
    ];

    public function __construct()
    {
        parent::__construct('test-smtp-credentials');

        $this->argument('connectionString', 'URL Format [[SCHEME://][[USER[:PASSWORD]]@]HOST[:PORT]');

        $this->option('-h --host', 'SMTP Server Host. Supports ', 'strval');
        $this->option('-u --user', 'SMTP Server User', 'strval');
        $this->option('-p --password', '!!Unsafe!! SMTP Server Password', 'strval');
        $this->option('-e --encryption', sprintf('Encryption Type (%s)', implode('/', array_values(self::$encryptionOptions))), 'strval');
        $this->option('-p --port', 'Port number [Default: 25 for None encryption, 587 for TLS/SSL]', 'intval');
        $this->option('-f --from', 'Email address of the sender', 'strval');
        $this->option('-t, --to', 'Recipient for test email', 'strval');
    }

    protected function defaults(): Command
    {
        $this->option('-H, --help', 'Show help')->on([
            $this,
            'showHelp'
        ]);
        $this->option('-V, --version', 'Show version')->on([
            $this,
            'showVersion'
        ]);
        $this->option('-v, --verbosity', 'Verbosity level', null, 0)->on(function () {
            $this->set('verbosity', ($this->verbosity ?? 0) + 1);

            return false;
        });

        $this->onExit(function ($exitCode = 0) {
            exit($exitCode);
        });
        return $this;
    }

    protected static function getConnectionString($host, $user, $password, $encryption, $port): string
    {
        $scheme = self::getSmtpSecureValue($encryption);
        return sprintf(
            '%s//%s%s%s%s:%s',
            $scheme ? $scheme . ':' : '',
            $user,
            $user && $password ? ':*****' : '',
            $user ? '@' : '',
            $host,
            $port
        );
    }

    public function execute($connectionString, $host, $user, $password, $encryption, $port, $from, $to): void
    {
        /**
         * @var Interactor | Writer | Reader $io
         */
        $io = $this->app()->io();


        if ($connectionString) {
            list($host, $user, $password, $encryption, $port) = $this->parseConnectionString($connectionString, $host, $user, $password, $encryption, $port);
        }

        $host = $this->readParam('SMTP Host', $host);
        $user = $this->readParam('SMTP User', $user);
        $password = $this->readParam('SMTP Password', $password, null, true);
        if (!$encryption) {
            $choice = $io->choice('Encryption: ', self::$encryptionOptions, 'TLS');
            $encryption = array_key_exists($choice, self::$encryptionOptions) ? self::$encryptionOptions[$choice] : $choice;
        }

        $port = $this->readParam('Port', $port, $this->getDefaultPort($encryption));
        $from = $this->readParam('Send test mail FROM', $from, filter_var($user, FILTER_VALIDATE_EMAIL) ? $user : 'test@example.com');
        $to = $this->readParam('Send test mail TO', $to, [$this, 'getDefaultEmail']);

        $mailer = new PHPMailer();
        $mailer->isSMTP();
        $mailer->SMTPAuth = true;
        $mailer->Host = $host;
        $mailer->Username = $user;
        $mailer->Password = $password;
        $mailer->Port = $port;
        $mailer->SMTPSecure = self::getSmtpSecureValue($encryption);

//        $mailer->Subject = 'Test Email from PHP SMTP Tester';
//        $mailer->Body = '.';

        try {
            $mailer->clearAllRecipients();
            $mailer->setFrom($from);
            $mailer->addAddress($to, 'SMTP Tester');

            $mailer->SMTPDebug = 2;


            $io->writer()->write("Testing: " . self::getConnectionString($host, $user, $password, $encryption, $port), true);

            $success = $mailer->send();
            if ($success) {
                $io->writer()->green('Success!');
            } else {
                $io->writer()->red('Failure!');
            }
            $io->eol();
        } catch (Exception $e) {
            $io->error($e->getMessage(), true);
        }
    }

    protected function readParam($question, $value = '', $default = null, $secret = false): string
    {
        $io = $this->app()->io();

        if (!is_string($value) || trim($value) === '') {
            if (is_callable($default)) {
                $default = call_user_func($default);
            }
            $value = trim($io->prompt($question, $default, null, 3, $secret));
        }
        return $value;
    }

    protected function getDefaultEmail(): string
    {
        $shell = new Shell('git config --global user.email');
        $shell->execute();
        return trim($shell->getOutput());
    }


    protected function getDefaultPort($encryption): int
    {
        return match (strtolower($encryption)) {
            'tls' => 587,
            'ssl' => 465,
            default => 25,
        };
    }

    protected static function getSmtpSecureValue($encryption): string
    {
        $encryption = strtolower($encryption);
        if (PHPMailer::ENCRYPTION_STARTTLS || PHPMailer::ENCRYPTION_SMTPS) {
            return $encryption;
        }
        return '';
    }

    private function parseConnectionString(string $connection_string, $default_host, $default_user, $default_password, $default_encryption, $default_port): array
    {
        if (!str_contains($connection_string, '//')) {
            $connection_string = '//' . $connection_string;
        }

        $url_info = parse_url($connection_string);

        if (!is_array($url_info)) {
            return [$default_host, $default_user, $default_password, $default_encryption, $default_port];
        }

        if (count($url_info) === 1 && array_key_exists('path', $url_info)) {
            $url_info['host'] = $url_info['path'];
        }

        return [
            array_key_exists('host', $url_info) ? $url_info['host'] : $default_host,
            array_key_exists('user', $url_info) ? $url_info['user'] : $default_user,
            array_key_exists('pass', $url_info) ? $url_info['pass'] : $default_password,
            array_key_exists('scheme', $url_info) ? $url_info['scheme'] : $default_encryption,
            array_key_exists('port', $url_info) ? $url_info['port'] : $default_port,
        ];
    }
}
