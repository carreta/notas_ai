<?php

namespace App\Support;

use Yethee\Tiktoken\EncoderProvider;

class TokenCounter
{
    private EncoderProvider $provider;

    public function __construct()
    {
        $cachePath = storage_path('app/private/tiktoken');

        if (! is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }

        $this->provider = new EncoderProvider();
        $this->provider->setVocabCache($cachePath);
    }

    public function count(string $text, string $encoding): int {
        if ($text === '') {
            return 0;
        }

        $encoder = $this->provider->get($encoding);

        return count($encoder->encode($text));
    }
}
