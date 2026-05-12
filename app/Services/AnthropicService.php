<?php

namespace App\Services;

/**
 * Cliente HTTP para la API de Anthropic con soporte de:
 * - Tool use (function calling)
 * - Prompt caching (system + tools cacheados, descuento 90% input)
 * - Cálculo automático de costo en USD
 *
 * Documentación: https://docs.anthropic.com/en/api/messages
 */
class AnthropicService
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION = '2023-06-01';

    // Pricing por millón de tokens (claude-sonnet-4-6, mayo 2026)
    private const PRECIOS = [
        'claude-sonnet-4-6' => [
            'input'        => 3.00,   // USD por 1M tokens input estándar
            'cache_write'  => 3.75,   // primera vez que se cachea (25% extra)
            'cache_read'   => 0.30,   // cachehit (90% descuento)
            'output'       => 15.00,  // USD por 1M tokens output
        ],
    ];

    private string $apiKey;
    private string $modelo;

    public function __construct(?string $apiKey = null, ?string $modelo = null)
    {
        $this->apiKey = $apiKey ?? env('ANTHROPIC_API_KEY', '');
        $this->modelo = $modelo ?? env('ANTHROPIC_MODEL', 'claude-sonnet-4-6');

        if (empty($this->apiKey)) {
            throw new \RuntimeException('ANTHROPIC_API_KEY no configurada en .env');
        }
    }

    /**
     * Ejecuta un análisis con tool use loop.
     *
     * @param string $systemPrompt Prompt de sistema (se cachea)
     * @param array  $tools        Definición de tools (se cachea)
     * @param array  $messages     Historial de mensajes (user/assistant alternados)
     * @param callable $toolExecutor function(string $toolName, array $input): string
     * @param int    $maxIterations Tope para evitar loops infinitos
     *
     * @return array {
     *   'final_text': string,
     *   'messages_acumulados': array,
     *   'tokens_input_total': int,
     *   'tokens_output_total': int,
     *   'tokens_cache_read_total': int,
     *   'tokens_cache_write_total': int,
     *   'costo_total_usd': float,
     *   'iteraciones': int
     * }
     */
    public function analizar(
        string $systemPrompt,
        array $tools,
        array $messages,
        callable $toolExecutor,
        int $maxIterations = 6
    ): array {
        $totales = [
            'tokens_input_total'       => 0,
            'tokens_output_total'      => 0,
            'tokens_cache_read_total'  => 0,
            'tokens_cache_write_total' => 0,
            'costo_total_usd'          => 0,
            'iteraciones'              => 0,
        ];

        $finalText = '';
        $iter = 0;

        while ($iter < $maxIterations) {
            $iter++;
            $totales['iteraciones'] = $iter;

            $response = $this->request($systemPrompt, $tools, $messages);

            // Sumar tokens y costo
            $usage = $response['usage'] ?? [];
            $tIn   = (int) ($usage['input_tokens'] ?? 0);
            $tOut  = (int) ($usage['output_tokens'] ?? 0);
            $tCacheR = (int) ($usage['cache_read_input_tokens'] ?? 0);
            $tCacheW = (int) ($usage['cache_creation_input_tokens'] ?? 0);

            $totales['tokens_input_total']       += $tIn;
            $totales['tokens_output_total']      += $tOut;
            $totales['tokens_cache_read_total']  += $tCacheR;
            $totales['tokens_cache_write_total'] += $tCacheW;
            $totales['costo_total_usd']          += $this->calcularCosto($tIn, $tOut, $tCacheR, $tCacheW);

            $content = $response['content'] ?? [];

            // Agregar respuesta del assistant al historial
            $messages[] = ['role' => 'assistant', 'content' => $content];

            // Extraer texto y tool_use de la respuesta
            $toolUses = [];
            foreach ($content as $block) {
                if (($block['type'] ?? '') === 'text') {
                    $finalText = $block['text']; // último text gana
                } elseif (($block['type'] ?? '') === 'tool_use') {
                    $toolUses[] = $block;
                }
            }

            // Si no hay tool calls, terminamos
            if (empty($toolUses) || ($response['stop_reason'] ?? '') !== 'tool_use') {
                break;
            }

            // Ejecutar tools y mandar resultados
            $toolResults = [];
            foreach ($toolUses as $tu) {
                $toolName  = $tu['name'] ?? '';
                $toolInput = $tu['input'] ?? [];
                try {
                    $resultado = $toolExecutor($toolName, $toolInput);
                } catch (\Throwable $e) {
                    $resultado = json_encode([
                        'error' => 'tool failed: ' . $e->getMessage()
                    ], JSON_UNESCAPED_UNICODE);
                }
                $toolResults[] = [
                    'type' => 'tool_result',
                    'tool_use_id' => $tu['id'] ?? '',
                    'content' => is_string($resultado) ? $resultado : json_encode($resultado, JSON_UNESCAPED_UNICODE),
                ];
            }

            $messages[] = ['role' => 'user', 'content' => $toolResults];
        }

        return array_merge($totales, [
            'final_text'           => $finalText,
            'messages_acumulados'  => $messages,
        ]);
    }

    /**
     * Hace una sola request HTTP a Anthropic. Bajo nivel.
     */
    private function request(string $systemPrompt, array $tools, array $messages): array
    {
        // System con cache_control para aprovechar prompt caching
        $systemArr = [
            ['type' => 'text', 'text' => $systemPrompt, 'cache_control' => ['type' => 'ephemeral']],
        ];

        // Normalizar tools: properties vacíos deben ser object, no array
        foreach ($tools as $i => $t) {
            if (isset($t['input_schema']['properties']) && empty($t['input_schema']['properties'])) {
                $tools[$i]['input_schema']['properties'] = new \stdClass();
            }
        }

        // Tools con cache_control en el último para marcar el breakpoint
        if (!empty($tools)) {
            $lastIdx = count($tools) - 1;
            $tools[$lastIdx]['cache_control'] = ['type' => 'ephemeral'];
        }

        // Normalizar messages: tool_use.input debe ser object aunque esté vacío
        foreach ($messages as $mi => $msg) {
            if (!isset($msg['content']) || !is_array($msg['content'])) continue;
            foreach ($msg['content'] as $bi => $block) {
                if (is_array($block) && ($block['type'] ?? '') === 'tool_use' && empty($block['input'] ?? null)) {
                    $messages[$mi]['content'][$bi]['input'] = new \stdClass();
                }
            }
        }

        $body = [
            'model'      => $this->modelo,
            'max_tokens' => 4096,
            'system'     => $systemArr,
            'messages'   => $messages,
        ];
        if (!empty($tools)) {
            $body['tools'] = $tools;
        }

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: ' . self::API_VERSION,
            ],
            CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT        => 90,
        ]);

        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new \RuntimeException('cURL error: ' . $err);
        }

        $data = json_decode($raw, true);
        if ($code !== 200) {
            $msg = $data['error']['message'] ?? $raw;
            throw new \RuntimeException("Anthropic API error {$code}: {$msg}");
        }
        return $data;
    }

    /**
     * Costo en USD según pricing del modelo configurado.
     */
    private function calcularCosto(int $tIn, int $tOut, int $tCacheRead, int $tCacheWrite): float
    {
        $modelKey = $this->modelo;
        $p = self::PRECIOS[$modelKey] ?? self::PRECIOS['claude-sonnet-4-6'];

        return (
            ($tIn        * $p['input'])       +
            ($tOut       * $p['output'])      +
            ($tCacheRead * $p['cache_read'])  +
            ($tCacheWrite* $p['cache_write'])
        ) / 1_000_000;
    }

    public function modelo(): string
    {
        return $this->modelo;
    }
}
