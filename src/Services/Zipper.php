<?php namespace App\Services;

use ZipArchive;

class Zipper
{
    /** @var array */
    protected $files;

    public function __construct(array $files)
    {
        $this->files = $files;
    }

    public function unZipFiles(string $fromPath, string $toPath): void
    {
        foreach ($this->files as $file) {
            $zip = new ZipArchive;
            $zip->open(Downloader::getPath($file, $fromPath));
            $zip->extractTo($toPath);
            $zip->close();
        }
    }
}