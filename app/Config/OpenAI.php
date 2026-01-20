<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class OpenAI extends BaseConfig
{
    /**
     * API Key de OpenAI
     * Se puede configurar en .env como OPENAI_API_KEY
     */
    public string $apiKey = '';

    /**
     * Modelo a usar (gpt-3.5-turbo es el más económico)
     */
    public string $model = 'gpt-3.5-turbo';

    /**
     * URL de la API
     */
    public string $apiUrl = 'https://api.openai.com/v1/chat/completions';

    /**
     * Timeout en segundos
     */
    public int $timeout = 30;

    /**
     * Máximo de tokens por respuesta
     */
    public int $maxTokens = 1500;

    /**
     * Temperatura (creatividad) 0-1
     */
    public float $temperature = 0.7;

    public function __construct()
    {
        parent::__construct();

        // Cargar API key desde .env
        $this->apiKey = env('OPENAI_API_KEY', $this->apiKey);
    }
}
