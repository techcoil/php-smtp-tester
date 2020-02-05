#!/usr/bin/env bash

DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )

cd $DIR

file_name="php-smtp-tester";
release_dir="../release";

cd ../

composer dump-autoload

cd $DIR

php -d phar.readonly=0 ./create-phar.php

path="${release_dir}/${file_name}.phar"

if [ -f "$path" ]; then
  chmod +x "$path"
  echo "Copying php-smtp-tester to yout PATH"
  cp ../release/php-smtp-tester.phar /usr/local/bin/php-smtp-tester
  php-smtp-tester --help
else
  echo "Build failed" 1>&2
fi

