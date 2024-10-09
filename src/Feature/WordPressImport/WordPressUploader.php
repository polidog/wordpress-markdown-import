<?php

namespace App\Feature\WordPressImport;

use GuzzleHttp\Exception\GuzzleException;

final readonly class WordPressUploader
{
    public function __construct(private WpClient $wordpressClient, private string $hugoImageDirectory)
    {
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function upload(Content $content): void
    {
        $content->upload($this);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function uploadImage(string $path): int
    {
        $filePath = $this->hugoImageDirectory.$path;
        $filename = basename($filePath);
        $fileType = mime_content_type($filePath);
        $body = file_get_contents($filePath);

        $response = $this->wordpressClient->request('post', 'media', [
            'headers' => [
                'Content-Disposition' => 'attachment; filename='.$filename,
                'Content-Type' => $fileType,
            ],
            'body' => $body,
        ]);

        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return $json['id'];
    }

    public function createAndGetTermIds(Taxonomy $taxonomy, array $terms): array
    {
        return array_map(/**
         * @throws GuzzleException
         * @throws \JsonException
         */ function ($term) use ($taxonomy) {
            $response = $this->wordpressClient->request('get', sprintf('%s', $taxonomy->getSlug()), [
                'query' => [
                    'search' => $term,
                ],
            ]);

            $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
            if (empty($json)) {
                $response = $this->wordpressClient->request('post', sprintf('%s', $taxonomy->getSlug()), [
                    'json' => [
                        'name' => $term,
                    ],
                ]);

                $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

                return $json['id'];
            }
            if (!isset($json[0]['id'])) {
                throw new \RuntimeException('term not found');
            }

            return $json[0]['id'];
        }, $terms);
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function createPost(string $title, string $content, ?int $image, array $categories, array $tags): array
    {
        $response = $this->wordpressClient->request('post', 'posts', [
            'json' => [
                'title' => $title,
                'content' => $content,
                'status' => 'publish',
                'categories' => $categories,
                'tags' => $tags,
                'featured_media' => $image,
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }
}
