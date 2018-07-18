<?php namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use ZipArchive;

class ParsePublicMethods extends Command
{
    const DOWNLOADS_PATH = 'uploads';
    const FILES_FROM_ARCHIVES_PATH = 'content';
    const PUBLIC_METHODS_PATTERN = '/public\s(static\s)?function\s(?P<methodName>(\w*))/';


    /** @var ProgressBar */
    protected $progressBar;

    /** @var OutputInterface */
    protected $output;

    /** @var array */
    protected $repositories;

    protected function configure()
    {
        $this->setName('methods:parse')
            ->setDescription('parse public methods from repositories')
            ->addArgument(
                'repositories',
                InputArgument::IS_ARRAY,
                'Repositories to download'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $repositories = $input->getArgument('repositories');

        $this->download($repositories);

        $this->getFilesFromArchive($repositories);

        $classes = $this->findClasses();

        $this->getPublicMethods($classes);

        system('rm -R ' . static::FILES_FROM_ARCHIVES_PATH);
    }

    public function getUrl(string $repository)
    {
        return "https://github.com/${repository}/archive/master.zip";
    }

    public function download(array $repositories): void
    {
        foreach ($repositories as $repository) {

            $path = $this->getPath($repository);

            if (!file_exists($dir = dirname($path))) {
                mkdir($dir);
            }

            $this->getClient()->request('get', $this->getUrl($repository), [
                'save_to' => $path,
                'progress' => [$this, 'onProgress']
            ]);

            $this->output->writeln('');
            $this->output->writeln("${repository} downloaded");
        }
    }

    public function getClient(): ClientInterface
    {
        return new Client;
    }

    public function getPath(string $filename): string
    {
        return static::DOWNLOADS_PATH . DIRECTORY_SEPARATOR .
            str_replace('/', '-', $filename) . '.zip';
    }

    public function onProgress(int $total, int $downloaded): void
    {
        if ($total <= 0) {
            return;
        }
        if (!$this->progressBar) {
            $this->progressBar = $this->createProgressBar(100);
        }
        $this->progressBar->setProgress(100 / $total * $downloaded);
    }

    public function createProgressBar(int $max): ProgressBar
    {
        $bar = new ProgressBar($this->output, $max);

        $bar->setBarCharacter('<fg=blue>·</>');
        $bar->setEmptyBarCharacter('<fg=red>·</>');
        $bar->setProgressCharacter('<fg=green>ᗧ</>');
        $bar->setFormat("%current:8s%/%max:-8s% %bar% %percent:5s%% %elapsed:7s%/%estimated:-7s% %memory%");

        return $bar;
    }

    public function getFilesFromArchive(array $repositories): void
    {
        foreach ($repositories as $repository) {
            $zip = new ZipArchive;
            $zip->open($this->getPath($repository));
            $zip->extractTo(static::FILES_FROM_ARCHIVES_PATH);
            $zip->close();

            unlink($this->getPath($repository));
            rmdir(static::DOWNLOADS_PATH);
        }
    }

    public function findClasses(): Finder
    {
        $finder = new Finder;
        return $finder->files()->in(static::FILES_FROM_ARCHIVES_PATH)
            ->contains(static::PUBLIC_METHODS_PATTERN)
            ->name('*.php');
    }

    public function getPublicMethods(Finder $finder)
    {
        foreach ($finder as $fileInfo) {

            $path = $fileInfo->getRelativePathname();
            $content = $fileInfo->getContents();
            $methods = $this->parseMethodsName($content);

            $this->output->writeln("<info>$path</info>");

            $this->getMethodsNameList($methods);
        }
    }

    public function parseMethodsName(string $content): array
    {
        preg_match_all(static::PUBLIC_METHODS_PATTERN, $content, $matches);

        return $matches['methodName'];
    }

    public function getMethodsNameList(array $methods)
    {
        foreach ($methods as $method) {
            $this->output->writeln("\t ${method}");
        }
    }
}