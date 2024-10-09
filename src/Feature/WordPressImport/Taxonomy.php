<?php

namespace App\Feature\WordPressImport;

enum Taxonomy
{
    case CATEGORY;
    case TAG;

    public function getSlug(): string
    {
        return match ($this) {
            self::CATEGORY => 'categories',
            self::TAG => 'tags',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::CATEGORY => 'カテゴリー',
            self::TAG => 'タグ',
        };
    }
}
