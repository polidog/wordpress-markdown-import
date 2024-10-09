<?php

namespace App\Feature\WordPressImport;

use Symfony\Component\Yaml\Yaml;

class ContentParser
{
    public function parse(string $content): array
    {
        if (preg_match('/^---\n(.*?)\n---/s', $content, $matches)) {
            $yaml = Yaml::parse($matches[1]);
            $content = trim(preg_replace('/^---\n(.*?)\n---/s', '', $content));

            return [$yaml, $content];
        }

        return [null, $content];
    }
}
