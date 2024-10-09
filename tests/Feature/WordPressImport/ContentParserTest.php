<?php

namespace App\Tests\Feature\WordPressImport;

use App\Feature\WordPressImport\ContentParser;
use PHPUnit\Framework\TestCase;

class ContentParserTest extends TestCase
{
    public function testParse(): void
    {
        $content = <<<CONTENT
---
title: Test
date: 2024-03-07T23:25:53+09:00
categories: 
    - Test
    - Another
tags:
    - Tag1
    - Tag2
image: /path/to/image.jpg
---
This is a test
CONTENT;

        $parser = new ContentParser();
        [$meta, $content] = $parser->parse($content);

        $time = (new \DateTimeImmutable('2024-03-07T23:25:53+09:00'))->getTimestamp();
        $this->assertSame([
            'title' => 'Test',
            'date' => $time,
            'categories' => ['Test', 'Another'],
            'tags' => ['Tag1', 'Tag2'],
            'image' => '/path/to/image.jpg',
        ], $meta);
        $this->assertSame('This is a test', $content);
    }
}
