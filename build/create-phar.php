<?php
// The php.ini setting phar.readonly must be set to 0


if (ini_get('phar.readonly')) {
    fwrite(STDERR, "PHAR creation failed. php ini setting phar.readonly must bet set to 0\n");
    exit(1);
}

chdir(dirname(__DIR__));

$file_name = 'php-smtp-tester.phar';

$dir = './release';

$pharFile = $dir . '/' . $file_name;

if (!is_dir($dir)) {
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

$default_stub = $p->createDefaultStub('./src/index.php', '/src/index.php');

// creating our library using whole directory
$p->buildFromIterator(new RecursiveIteratorIterator(new AppFilesIterator('.', '.')), '.');
$stub = "#!/usr/bin/env php\n$default_stub";

// plus - compressing it into gzip
$p->compress(Phar::GZ);

$p->setStub($stub);
$p->stopBuffering();


echo "$file_name successfully created";
echo "\n";



class AppFilesIterator extends RecursiveFilterIterator
{

    protected string $path;
    protected static string|false $basePath;

    private static array $include = [
        'src',
        'vendor',
        'composer.json',
        'composer.lock',
    ];

    private static array $exclude = [
        '.git*',
        '*.md',
        'tests',
        '*.zsh',
        'VERSION',
        'LICENSE',
        '.travis.yml',
        'phpunit.xml.dist',
        '.editor*',
    ];

    public function __construct($path, $base_dir = null)
    {
        if ($base_dir) {
            self::$basePath = realpath($base_dir);
        }

        $this->path = $path instanceof SplFileInfo ? $path->getPathname() : rtrim($path, '/');
        parent::__construct(new RecursiveDirectoryIterator($this->path));
    }



    protected function isDot($path): bool
    {
        return preg_match('/^(?:.*\/)?\.{1,2}\/?$/', $path) !== false;
    }

    protected function isIncluded(SplFileInfo $file): bool
    {
        $path = $file->getRealPath();
        if (!$this->isDot($path)) {

            foreach (self::$exclude as $pattern) {
                if (fnmatch($pattern, $file->getBasename())) {
                    return false;
                }
            }

            foreach (self::$include as $filter) {
                $filter_path = realpath(rtrim(self::$basePath . '/' . $filter, '/'));

                if (
                    (is_dir($filter_path) && ($filter_path === $path || str_starts_with($path, $filter_path . '/'))) ||
                    (is_file($filter_path) && $filter_path === $path)
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function accept(): bool
    {
        $pathname = $this->current()->getPathname();
        if ($this->isDot($pathname)) {
            return false;
        }
        $in =  $this->isIncluded($this->current()); //strpos($this->current()->getPathname(), 'vendor')!==false; //$this->isIncluded($this->current());
        if ($in) {
            echo 'Adding ' . str_replace('/./', '/', $this->current()->getPathname()) . "\n";
        }
        return $in;
    }
}
