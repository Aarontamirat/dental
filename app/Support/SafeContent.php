<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

final class SafeContent
{
    public static function html(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        return (new HtmlSanitizer((new HtmlSanitizerConfig)->allowSafeElements()->allowLinkSchemes(['https', 'http', 'mailto', 'tel'])->allowRelativeLinks()->forceAttribute('a', 'rel', 'noopener noreferrer')->dropElement('img')->dropElement('iframe')->dropElement('style')->dropElement('script')))->sanitize($html);
    }

    public static function sanitize(array $data): array
    {
        if (array_key_exists('content', $data)) {
            $data['content'] = self::html($data['content']);
        }
        foreach ($data['translations'] ?? [] as $locale => $translation) {
            if (array_key_exists('content', $translation)) {
                $data['translations'][$locale]['content'] = self::html($translation['content']);
            }
        }

        return $data;
    }
}
