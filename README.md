# php-smtp-tester

A php CLI utility for validating SMTP Credentials using `PHPMailer` library

### Install

#### For Development

1. Clone the repository
2. Run `$ composer install` to install dependencies`
3. Execute with `$ php ./src/index.php --help`

#### Global Installation

1. Clone the repository
2. Execute `$ bash ./build/install.sh`
3. Run `$ php-smtp-mailer --help`

### Usage

```
Usage: test-smtp [OPTIONS...] [ARGUMENTS...]

Arguments:
  [connectionString]    URL Format [[SCHEME://][[USER[:PASSWORD]]@]HOST[:PORT]

Options:
  [-e|--encryption]    Encryption Type (None/TLS/SSL)
  [-f|--from]          Email address of the sender
  [-H|--help]          Show help
  [-h|--host]          SMTP Server Host. Supports
  [-p|--password]      !!Unsafe!! SMTP Server Password
  [-p|--port]          Port number [Default: 25 for None encryption, 587 for TLS/SSL]
  [-t|--to]            Recipient for test email
  [-u|--user]          SMTP Server User
  [-v|--verbosity]     Verbosity level
  [-V|--version]       Show version

Legend: <required> [optional] variadic...

```
