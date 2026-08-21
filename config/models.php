<?php

return [
    'chatgpt-sol' => [
        'label' => 'ChatGPT Sol',
        'model' => 'gpt-4.1',
        'max_chars' => 50000,
        'max_tokens' => 20000,
        'encoding' => 'cl100k_base',
        'provider' => 'openai',
    ],

    'chatgpt-terra' => [
        'label' => 'ChatGPT Terra',
        'model' => 'gpt-4.1-mini',
        'max_chars' => 100000,
        'max_tokens' => 50000,
        'encoding' => 'cl100k_base',
        'provider' => 'openai',
    ],

    'chatgpt-luna' => [
        'label' => 'ChatGPT Luna',
        'model' => 'gpt-5',
        'max_chars' => 200000,
        'max_tokens' => 100000,
        'encoding' => 'o200k_base',
        'provider' => 'openai',
    ],

    'qwen-3.5-9b' => [
        'label' => 'Qwen 3.5 9B',
        'model' => 'qwen/qwen3.5-9b',
        'max_chars' => 32000,
        'max_tokens' => 8000,
        'encoding' => 'cl100k_base',
        'provider' => 'lmstudio',
    ],

    'deepseek-r1-qwen3-8b' => [
        'label' => 'DeepSeek R1 Qwen3 8B',
        'model' => 'deepseek/deepseek-r1-0528-qwen3-8',
        'max_chars' => 32000,
        'max_tokens' => 8000,
        'encoding' => 'cl100k_base',
        'provider' => 'lmstudio',
    ],

    'gemini-3.6-flash' => [
        'label' => 'Gemini 3.6 Flash',
        'model' => 'gemini-3.6-flash',
        'max_chars' => 200000,
        'max_tokens' => 8192,
        'encoding' => 'cl100k_base',
        'provider' => 'google',
    ],
];
