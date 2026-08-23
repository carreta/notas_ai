<?php

return [
    'chatgpt-sol' => [
        'label' => 'ChatGPT Sol',
        'model' => 'gpt-5.6-sol',
        'max_chars' => 50000,
        'max_tokens' => 20000,
        'provider' => 'openai',
    ],

    'chatgpt-terra' => [
        'label' => 'ChatGPT Terra',
        'model' => 'gpt-5.6-terra',
        'max_chars' => 100000,
        'max_tokens' => 50000,
        'provider' => 'openai',
    ],

    'chatgpt-luna' => [
        'label' => 'ChatGPT Luna',
        'model' => 'gpt-5.6-luna',
        'max_chars' => 200000,
        'max_tokens' => 100000,
        'provider' => 'openai',
    ],

    'qwen-3.5-9b' => [
        'label' => 'Local - Qwen 3.5 9B',
        'model' => 'qwen/qwen3.5-9b',
        'max_chars' => 32000,
        'max_tokens' => 8000,
        'encoding' => 'cl100k_base',
        'provider' => 'lmstudio',
    ],

    'Gemma 4 12B' => [
        'label' => 'Local - Gemma 4 12B',
        'model' => 'google/gemma-4-12b',
        'max_chars' => 32000,
        'max_tokens' => 8000,
        'encoding' => 'cl100k_base',
        'provider' => 'lmstudio',
    ],

    'gemini-3.7-flash' => [
        'label' => 'Gemini 3.7 Flash',
        'model' => 'gemini-3.7-flash',
        'max_chars' => 200000,
        'max_tokens' => 8192,
        'encoding' => 'cl100k_base',
        'provider' => 'google',
    ],
];
