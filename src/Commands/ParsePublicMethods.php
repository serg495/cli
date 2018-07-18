<?php namespace App\Commands;

use App\Client;
use App\Services\Downloader;
use App\Services\PublicMethodsParser;
use App\Services\Zipper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class ParsePublicMethods extends Command
{
    const DOWNLOADS_PATH = 'uploads';
    const FILES_FROM_ARCHIVES_PATH = 'content';

    /** @var ProgressBar */
    protected $progressBar;

    /** @var OutputInterface */
    protected $output;

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

        $client = new Client($output);
        $repositories = $input->getArgument('repositories');

        $downloader = new Downloader($client, $repositories);
        $downloader->download( static::DOWNLOADS_PATH);

        $zipper = new Zipper($repositories);
        $zipper->unZipFiles(static::DOWNLOADS_PATH, static::FILES_FROM_ARCHIVES_PATH);

        $parser = new PublicMethodsParser;
        $classes = $parser->findClasses(static::FILES_FROM_ARCHIVES_PATH);
        $methods = $parser->getPublicMethods($classes);

        $this->printMethodsList($methods);

        $fileSystem = new Filesystem();
        $fileSystem->remove([static::DOWNLOADS_PATH, static::FILES_FROM_ARCHIVES_PATH]);
    }

    public function printMethodsList(array $methods): void
    {
        foreach ($methods as $path => $methodsName) {
            $this->output->writeln("<info>{$path}</info>");
            $this->output->writeln(implode("\n", $methodsName));
        }
    }
}