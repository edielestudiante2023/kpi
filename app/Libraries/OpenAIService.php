<?php

namespace App\Libraries;

use Config\OpenAI;

/**
 * Servicio para interactuar con la API de OpenAI
 * Usa GPT-3.5-turbo para minimizar costos
 */
class OpenAIService
{
    private string $apiKey;
    private string $model;
    private string $apiUrl;
    private int $timeout;
    private int $maxTokens;
    private float $temperature;

    public function __construct()
    {
        $config = config('OpenAI');

        $this->apiKey = $config->apiKey;
        $this->model = $config->model;
        $this->apiUrl = $config->apiUrl;
        $this->timeout = $config->timeout;
        $this->maxTokens = $config->maxTokens;
        $this->temperature = $config->temperature;
    }

    /**
     * Verifica si la API key está configurada
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Genera un indicador basado en la descripción del usuario
     * @param string $descripcion Descripción inicial del indicador
     * @param string|null $ajuste Instrucción de ajuste (opcional)
     * @param array|null $contextoPrevio Resultado previo para refinar (opcional)
     */
    public function generarIndicador(string $descripcion, ?string $ajuste = null, ?array $contextoPrevio = null): array
    {
        $contextoTexto = '';
        if ($ajuste && $contextoPrevio) {
            $contextoTexto = "

RESULTADO ANTERIOR (que el usuario quiere ajustar):
" . json_encode($contextoPrevio, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "

AJUSTE SOLICITADO POR EL USUARIO: \"$ajuste\"

Modifica el resultado anterior según lo que pide el usuario, manteniendo la misma estructura JSON.";
        }

        $prompt = <<<PROMPT
Eres un experto en KPIs e indicadores de gestión empresarial.
El usuario te describirá lo que quiere medir y debes generar un indicador completo.

Descripción del usuario: "$descripcion"
$contextoTexto

Responde SOLO con un JSON válido (sin markdown, sin explicaciones) con esta estructura exacta:
{
    "nombre": "Nombre corto del indicador (máx 100 caracteres)",
    "descripcion": "Descripción detallada de qué mide y para qué sirve (máx 500 caracteres)",
    "meta": número entero o decimal que representa la meta ideal (ej: 95, 100, 80.5),
    "unidad_medida": "% | unidades | horas | días | pesos | otro",
    "tipo_calculo": "porcentaje | promedio | suma | conteo | ratio",
    "formula_legible": "Fórmula en texto legible (ej: (ventas_realizadas / meta_ventas) * 100)",
    "partes_formula": [
        {"tipo": "dato|constante|operador|parentesis_apertura|parentesis_cierre", "valor": "nombre_variable o símbolo", "orden": 1},
        {"tipo": "...", "valor": "...", "orden": 2}
    ],
    "frecuencia_sugerida": "diario | semanal | mensual | trimestral | anual",
    "variables_necesarias": ["lista", "de", "variables", "que", "el", "usuario", "debe", "ingresar"]
}

Reglas para partes_formula:
- tipo "dato": variables que el usuario ingresará (usar nombres en snake_case sin espacios ni tildes)
- tipo "constante": números fijos como 100, 0.5, etc.
- tipo "operador": +, -, *, /
- tipo "parentesis_apertura": (
- tipo "parentesis_cierre": )
- El orden debe ser secuencial empezando en 1

Ejemplo de partes_formula para "(ventas / meta) * 100":
[
    {"tipo": "parentesis_apertura", "valor": "(", "orden": 1},
    {"tipo": "dato", "valor": "ventas", "orden": 2},
    {"tipo": "operador", "valor": "/", "orden": 3},
    {"tipo": "dato", "valor": "meta", "orden": 4},
    {"tipo": "parentesis_cierre", "valor": ")", "orden": 5},
    {"tipo": "operador", "valor": "*", "orden": 6},
    {"tipo": "constante", "valor": "100", "orden": 7}
]
PROMPT;

        return $this->makeRequest($prompt);
    }

    /**
     * Genera una actividad basada en la descripción del usuario
     * @param string $descripcion Descripción inicial de la actividad
     * @param string|null $ajuste Instrucción de ajuste (opcional)
     * @param array|null $contextoPrevio Resultado previo para refinar (opcional)
     */
    public function generarActividad(string $descripcion, ?string $ajuste = null, ?array $contextoPrevio = null): array
    {
        $contextoTexto = '';
        if ($ajuste && $contextoPrevio) {
            $contextoTexto = "

RESULTADO ANTERIOR (que el usuario quiere ajustar):
" . json_encode($contextoPrevio, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "

AJUSTE SOLICITADO POR EL USUARIO: \"$ajuste\"

Modifica el resultado anterior según lo que pide el usuario, manteniendo la misma estructura JSON.";
        }

        $prompt = <<<PROMPT
Eres un experto en gestión de proyectos y planificación de actividades.
El usuario te describirá lo que quiere hacer y debes generar una actividad estructurada.

Descripción del usuario: "$descripcion"
$contextoTexto

Responde SOLO con un JSON válido (sin markdown, sin explicaciones) con esta estructura exacta:
{
    "titulo": "Título corto y descriptivo de la actividad (máx 150 caracteres)",
    "descripcion": "Descripción detallada de la actividad, qué se debe hacer y el resultado esperado (máx 1000 caracteres)",
    "prioridad": "alta | media | baja",
    "categoria_sugerida": "General | Urgente | Proyecto | Mejora",
    "pasos_sugeridos": [
        "Paso 1: descripción del primer paso",
        "Paso 2: descripción del segundo paso",
        "Paso 3: etc..."
    ],
    "duracion_estimada_dias": número entero estimado de días para completar,
    "recursos_necesarios": ["lista", "de", "recursos", "o", "herramientas", "necesarias"],
    "criterios_exito": ["criterio 1 para considerar la actividad exitosa", "criterio 2", "etc"]
}

Genera entre 3 y 6 pasos sugeridos.
La prioridad debe basarse en la urgencia implícita en la descripción.
PROMPT;

        return $this->makeRequest($prompt);
    }

    /**
     * Realiza la petición a la API de OpenAI
     */
    private function makeRequest(string $prompt): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'API key de OpenAI no configurada'
            ];
        }

        $data = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Eres un asistente que responde SOLO con JSON válido, sin markdown ni explicaciones adicionales.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => $this->timeout
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            log_message('error', 'OpenAI cURL Error: ' . $error);
            return [
                'success' => false,
                'error' => 'Error de conexión: ' . $error
            ];
        }

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['error']['message'] ?? 'Error desconocido';
            log_message('error', 'OpenAI API Error: ' . $response);
            return [
                'success' => false,
                'error' => 'Error de API: ' . $errorMessage
            ];
        }

        $result = json_decode($response, true);

        if (!isset($result['choices'][0]['message']['content'])) {
            return [
                'success' => false,
                'error' => 'Respuesta inválida de OpenAI'
            ];
        }

        $content = $result['choices'][0]['message']['content'];

        // Limpiar posible markdown
        $content = preg_replace('/```json\s*/', '', $content);
        $content = preg_replace('/```\s*/', '', $content);
        $content = trim($content);

        $parsed = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            log_message('error', 'OpenAI JSON Parse Error: ' . $content);
            return [
                'success' => false,
                'error' => 'Error al procesar respuesta de IA',
                'raw' => $content
            ];
        }

        return [
            'success' => true,
            'data' => $parsed,
            'tokens_used' => $result['usage']['total_tokens'] ?? 0
        ];
    }
}
