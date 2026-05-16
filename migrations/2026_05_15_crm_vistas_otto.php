<?php

/**
 * Migración: Vistas SQL agregadoras para que OTTO (modo comercial) consulte
 * el estado del CRM con queries rápidos y enriquecidos.
 *
 *  - vw_crm_oportunidad_360:   ficha enriquecida de cada oportunidad (joins
 *    con empresa, contacto, etapa, responsable, motivo perdida, días sin
 *    actividad, días hasta cierre estimado, valor ponderado).
 *  - vw_crm_pipeline_resumen:  agregados por etapa abierta (count, valor,
 *    valor ponderado por probabilidad).
 *  - vw_crm_actividad_reciente: última interacción real por oportunidad
 *    (tipo, asunto, fecha) para que el agente IA detecte estancamiento con
 *    contexto, no solo la columna ultima_actividad_at.
 *
 * Uso:  php migrations/2026_05_15_crm_vistas_otto.php
 */

function leerEnv(string $path): array {
    $vars = [];
    if (!file_exists($path)) return $vars;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$key, $val] = array_map('trim', explode('=', $line, 2));
        $val = trim($val, "\"' ");
        $vars[$key] = $val;
    }
    return $vars;
}

$dotenv = leerEnv(__DIR__ . '/../.env');
$config = [
    'host'     => $dotenv['database.default.hostname'] ?? '127.0.0.1',
    'port'     => $dotenv['database.default.port']     ?? 3306,
    'username' => $dotenv['database.default.username'] ?? 'root',
    'password' => $dotenv['database.default.password'] ?? '',
    'database' => $dotenv['database.default.database'] ?? 'kpicycloid',
];
$dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
$opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
if ($config['host'] !== '127.0.0.1' && $config['host'] !== 'localhost') {
    $opts[PDO::MYSQL_ATTR_SSL_CA] = true;
    $opts[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
}
$pdo = new PDO($dsn, $config['username'], $config['password'], $opts);
echo "Conectado a: {$config['host']}\n\n";

// ─── 1. vw_crm_oportunidad_360 ────────────────────────────
echo "1. Vista vw_crm_oportunidad_360... ";
$pdo->exec("DROP VIEW IF EXISTS vw_crm_oportunidad_360");
$pdo->exec("
    CREATE VIEW vw_crm_oportunidad_360 AS
    SELECT
        o.id_oportunidad,
        o.codigo,
        o.titulo,
        o.descripcion,
        o.valor,
        o.moneda,
        o.probabilidad,
        ROUND(o.valor * o.probabilidad / 100, 2)            AS valor_ponderado,
        o.fecha_cierre_estimada,
        o.fecha_cierre_real,
        o.ultima_actividad_at,
        o.notificado_estancada,
        o.created_at,
        o.updated_at,
        DATEDIFF(NOW(), o.created_at)                       AS dias_desde_creacion,
        CASE
            WHEN o.ultima_actividad_at IS NULL THEN DATEDIFF(NOW(), o.created_at)
            ELSE DATEDIFF(NOW(), o.ultima_actividad_at)
        END                                                 AS dias_sin_actividad,
        CASE
            WHEN o.fecha_cierre_estimada IS NULL THEN NULL
            ELSE DATEDIFF(o.fecha_cierre_estimada, CURDATE())
        END                                                 AS dias_para_cierre_estimado,

        -- Empresa
        e.id_empresa,
        e.razon_social                                      AS empresa_nombre,
        e.nit                                               AS empresa_nit,
        e.sector                                            AS empresa_sector,
        e.tamano                                            AS empresa_tamano,
        e.ciudad                                            AS empresa_ciudad,

        -- Contacto principal
        c.id_contacto                                       AS contacto_id,
        c.nombre                                            AS contacto_nombre,
        c.cargo                                             AS contacto_cargo,
        c.email                                             AS contacto_email,
        c.telefono                                          AS contacto_telefono,

        -- Etapa
        et.id_etapa,
        et.nombre                                           AS etapa_nombre,
        et.color                                            AS etapa_color,
        et.tipo                                             AS etapa_tipo,
        et.orden                                            AS etapa_orden,
        et.probabilidad_default                             AS etapa_probabilidad_default,

        -- Responsable
        u.id_users                                          AS responsable_id,
        u.nombre_completo                                   AS responsable_nombre,
        u.correo                                            AS responsable_correo,

        -- Motivo de perdida (cuando aplique)
        mp.id_motivo_perdida,
        mp.nombre                                           AS motivo_perdida_nombre,

        -- Fuente del lead (desde empresa)
        f.nombre                                            AS empresa_fuente_nombre
    FROM tbl_crm_oportunidad o
    LEFT JOIN tbl_crm_empresa        e  ON e.id_empresa            = o.id_empresa
    LEFT JOIN tbl_crm_contacto       c  ON c.id_contacto           = o.id_contacto_principal
    LEFT JOIN tbl_crm_etapa          et ON et.id_etapa             = o.id_etapa
    LEFT JOIN users                  u  ON u.id_users              = o.id_responsable
    LEFT JOIN tbl_crm_motivo_perdida mp ON mp.id_motivo_perdida    = o.id_motivo_perdida
    LEFT JOIN tbl_crm_fuente         f  ON f.id_fuente             = e.id_fuente
");
echo "[CREADA]\n";

// ─── 2. vw_crm_pipeline_resumen ───────────────────────────
echo "2. Vista vw_crm_pipeline_resumen... ";
$pdo->exec("DROP VIEW IF EXISTS vw_crm_pipeline_resumen");
$pdo->exec("
    CREATE VIEW vw_crm_pipeline_resumen AS
    SELECT
        et.id_etapa,
        et.nombre                                           AS etapa_nombre,
        et.color                                            AS etapa_color,
        et.tipo                                             AS etapa_tipo,
        et.orden                                            AS etapa_orden,
        et.probabilidad_default,
        COUNT(o.id_oportunidad)                             AS cantidad,
        COALESCE(SUM(o.valor), 0)                           AS valor_total,
        COALESCE(AVG(o.valor), 0)                           AS valor_promedio,
        COALESCE(SUM(o.valor * o.probabilidad / 100), 0)    AS valor_ponderado
    FROM tbl_crm_etapa et
    LEFT JOIN tbl_crm_oportunidad o ON o.id_etapa = et.id_etapa
    WHERE et.activa = 1
    GROUP BY et.id_etapa, et.nombre, et.color, et.tipo, et.orden, et.probabilidad_default
");
echo "[CREADA]\n";

// ─── 3. vw_crm_actividad_reciente ─────────────────────────
echo "3. Vista vw_crm_actividad_reciente... ";
$pdo->exec("DROP VIEW IF EXISTS vw_crm_actividad_reciente");
$pdo->exec("
    CREATE VIEW vw_crm_actividad_reciente AS
    SELECT
        o.id_oportunidad,
        o.codigo                                            AS oportunidad_codigo,
        o.titulo                                            AS oportunidad_titulo,
        et.tipo                                             AS etapa_tipo,
        ult.id_interaccion                                  AS ultima_id_interaccion,
        ult.tipo                                            AS ultima_tipo,
        ult.asunto                                          AS ultima_asunto,
        ult.fecha_completada                                AS ultima_fecha_completada,
        ult.created_at                                      AS ultima_created_at,
        DATEDIFF(NOW(), COALESCE(ult.fecha_completada, ult.created_at, o.created_at)) AS dias_sin_actividad
    FROM tbl_crm_oportunidad o
    LEFT JOIN tbl_crm_etapa et ON et.id_etapa = o.id_etapa
    LEFT JOIN tbl_crm_interaccion ult
        ON ult.id_interaccion = (
            SELECT i2.id_interaccion
            FROM tbl_crm_interaccion i2
            WHERE i2.id_oportunidad = o.id_oportunidad
              AND i2.estado = 'completada'
            ORDER BY COALESCE(i2.fecha_completada, i2.created_at) DESC
            LIMIT 1
        )
");
echo "[CREADA]\n";

echo "\n=== VISTAS CREADAS ===\n";
echo "Probando que devuelvan resultado...\n";
$counts = [
    'vw_crm_oportunidad_360'   => $pdo->query("SELECT COUNT(*) FROM vw_crm_oportunidad_360")->fetchColumn(),
    'vw_crm_pipeline_resumen'  => $pdo->query("SELECT COUNT(*) FROM vw_crm_pipeline_resumen")->fetchColumn(),
    'vw_crm_actividad_reciente'=> $pdo->query("SELECT COUNT(*) FROM vw_crm_actividad_reciente")->fetchColumn(),
];
foreach ($counts as $vista => $n) {
    echo "  - {$vista}: {$n} filas\n";
}
echo "\n=== MIGRACION COMPLETADA ===\n";
