<?php

namespace App\Feature\WordPressImport;

use GuzzleHttp\Exception\GuzzleException;

final readonly class Content
{
    public function __construct(
        public string $title,
        public \DateTimeImmutable $date,
        public array $categories,
        public array $tags,
        public ?string $image,
        public string $content,
    ) {
    }

    public static function newInstance(ContentParser $parser, string $content): Content
    {
        [$meta, $content] = $parser->parse($content);

        $time = (new \DateTimeImmutable())->setTimestamp($meta['date']);

        return new self(
            $meta['title'],
            $time,
            $meta['categories'] ?? [],
            $meta['tags'] ?? [],
            $meta['image'] ?? null,
            $content,
        );
    }

    /**
     * @throws \JsonException
     * @throws GuzzleException
     */
    public function upload(WordPressUploader $uploader): void
    {
        // アイキャッチ画像のアップロード
        $image = null !== $this->image ? $uploader->uploadImage($this->image)['id'] : null;
        $categories = $uploader->createAndGetTermIds(Taxonomy::CATEGORY, $this->categories);
        $tags = $uploader->createAndGetTermIds(Taxonomy::TAG, $this->tags);

        $parsedContent = (new \Parsedown())->parse($this->content);
        $parsedContent = $uploader->uploadPostImage($parsedContent);
        $uploader->createPost($this->title, $parsedContent, $image, $categories, $tags);
    }
}
