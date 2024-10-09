<?php

namespace App\Feature\WordPressImport;

class ContentLoader
{
    public function load(string $fileDirectory): iterable
    {
        if (!is_dir($fileDirectory)) {
            throw new \InvalidArgumentException('Invalid directory: '.$fileDirectory);
        }

        $dir = scandir($fileDirectory);
        if (!$dir) {
            return;
        }

        foreach ($dir as $path) {
            if ('.' === $path || '..' === $path) {
                continue;
            }
            $filePath = $fileDirectory.'/'.$path;
            if (is_dir($filePath)) {
                yield from $this->load($filePath);
            } else {
                yield $path => file_get_contents($filePath);
            }
        }
    }
}
