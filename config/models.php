<?php

return [
    'chatgpt-sol' => [
        'label' => 'ChatGPT Sol',
        'model' => 'gpt-4.1',
        'max_chars' => 50000,
        'max_tokens' => 20000,
        'encoding' => 'cl100k_base',
    ],

    'chatgpt-terra' => [
        'label' => 'ChatGPT Terra',
        'model' => 'gpt-4.1-mini',
        'max_chars' => 100000,
        'max_tokens' => 50000,
        'encoding' => 'cl100k_base',
    ],

    'chatgpt-luna' => [
        'label' => 'ChatGPT Luna',
        'model' => 'gpt-5',
        'max_chars' => 200000,
        'max_tokens' => 100000,
        'encoding' => 'o200k_base',
    ],
];