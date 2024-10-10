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
     * @return array{id: int}
     *
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function uploadImage(string $path): array
    {
        $filePath = $this->hugoImageDirectory.$path;
        if (!file_exists($filePath)) {
            return [];
        }

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

        return $json;
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

    public function uploadPostImage(string $content): string
    {
        if (preg_match_all('/<img.*?src=["\'](.*?)["\'].*?>/i', $content, $matches)) {
            foreach ($matches[1] as $key => $imagePath) {
                $uploadedImage = $this->uploadImage($imagePath);
                if (isset($uploadedImage['source_url'])) {
                    $content = str_replace($imagePath, $uploadedImage['source_url'], $content);
                } elseif (isset($matches[0][$key])) {
                    // 存在しない場合は画像を消す
                    $content = str_replace($matches[0][$key], '', $content);
                }
            }
        }

        return $content;
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
