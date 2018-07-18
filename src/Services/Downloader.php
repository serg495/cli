<?php namespace App\Services;

use GuzzleHttp\ClientInterface;

class Downloader
{
    /** @var ClientInterface  */
    protected $client;

    /** @var array */
    protected $repositories;

    public function __construct(ClientInterface $client, array $repositories)
    {
        $this->client = $client;
        $this->repositories = $repositories;
    }

    public function getUrl(string $repository) : string
    {
        return "https://github.com/{$repository}/archive/master.zip";
    }

    public static function getPath(string $filename, string $downloadPath): string
    {
        $filename = str_replace('/', '-', $filename) . '.zip';

        return $downloadPath . DIRECTORY_SEPARATOR . $filename;
    }

    public function download(string $downloadPath): void
    {
        foreach ($this->repositories as $repository) {

            $path = $this->getPath($repository, $downloadPath);

            if (!file_exists($dir = dirname($path))) {
                mkdir($dir);
            }

            $this->client->request('get', $this->getUrl($repository), [
                'save_to' => $path,
                'progress' => [$this->client, 'onProgress']
            ]);
        }
    }



}