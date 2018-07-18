<?php namespace App\Services;

use Symfony\Component\Finder\Finder as SymfonyFinder;

class PublicMethodsParser
{


    public function findClasses(string $directory): SymfonyFinder
    {
        return SymfonyFinder::create()
            ->files()
            ->in($directory)
            ->contains('/public\s(static\s)?function\s(?P<methodName>(\w*))/')
            ->name('*.php');
    }

    public function getPublicMethods(SymfonyFinder $finder) : array
    {
        foreach ($finder as $fileInfo) {

            $path = $fileInfo->getRelativePathname();
            $content = $fileInfo->getContents();
            $methods = $this->parseMethodsName($content);

            $publicMethods[$path] = $methods;
        }

        return $publicMethods;
    }

    public function parseMethodsName(string $content) : array
    {
        preg_match_all('/public\s(static\s)?function\s(?P<methodName>(\w*))/', $content, $matches);

        return $matches['methodName'];
    }
}