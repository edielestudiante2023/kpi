<?php

/**
 * Migración: vistas SQL agregadoras para que OTTO modo marketing consulte
 * el estado del embudo con queries rápidos y enriquecidos.
 *
 *  - vw_marketing_lead_360:      ficha enriquecida de cada lead (joins con
 *    fuente, responsable, empresa convertida, oportunidad convertida; campos
 *    calculados días_sin_actualizar, días_desde_creacion, fue_convertido).
 *  - vw_marketing_embudo_resumen: agregados por estado (cantidad y % vs total).
 *  - vw_marketing_accion_resumen: agregados por tipo de acción con cantidad,
 *    costo total y leads atribuidos. Filtrable por rango de fecha en queries.
 *
 * Uso:  php migrations/2026_05_17_marketing_vistas_otto.php
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

// ─── 1. vw_marketing_lead_360 ─────────────────────────────
echo "1. Vista vw_marketing_lead_360... ";
$pdo->exec("DROP VIEW IF EXISTS vw_marketing_lead_360");
$pdo->exec("
    CREATE VIEW vw_marketing_lead_360 AS
    SELECT
        l.id_lead,
        l.nombre,
        l.empresa_text,
        l.cargo,
        l.email,
        l.telefono,
        l.estado,
        l.notas,
        l.fecha_calificacion,
        l.fecha_descartado,
        l.motivo_descarte,
        l.created_at,
        l.updated_at,
        DATEDIFF(NOW(), l.created_at)                       AS dias_desde_creacion,
        DATEDIFF(NOW(), l.updated_at)                       AS dias_sin_actualizar,
        CASE WHEN l.id_oportunidad_convertida IS NOT NULL THEN 1 ELSE 0 END AS fue_convertido,
        l.id_empresa_convertida,
        l.id_oportunidad_convertida,

        f.id_fuente,
        f.nombre                                            AS fuente_nombre,

        u.id_users                                          AS responsable_id,
        u.nombre_completo                                   AS responsable_nombre,

        e.razon_social                                      AS empresa_convertida_nombre,
        o.codigo                                            AS oportunidad_convertida_codigo,
        o.valor                                             AS oportunidad_convertida_valor
    FROM tbl_marketing_lead l
    LEFT JOIN tbl_crm_fuente       f ON f.id_fuente = l.id_fuente
    LEFT JOIN users                u ON u.id_users  = l.id_responsable
    LEFT JOIN tbl_crm_empresa      e ON e.id_empresa = l.id_empresa_convertida
    LEFT JOIN tbl_crm_oportunidad  o ON o.id_oportunidad = l.id_oportunidad_convertida
");
echo "[CREADA]\n";

// ─── 2. vw_marketing_embudo_resumen ───────────────────────
echo "2. Vista vw_marketing_embudo_resumen... ";
$pdo->exec("DROP VIEW IF EXISTS vw_marketing_embudo_resumen");
$pdo->exec("
    CREATE VIEW vw_marketing_embudo_resumen AS
    SELECT
        estado,
        COUNT(*)                                            AS cantidad,
        ROUND(COUNT(*) / (SELECT COUNT(*) FROM tbl_marketing_lead) * 100, 1) AS porcentaje
    FROM tbl_marketing_lead
    GROUP BY estado
");
echo "[CREADA]\n";

// ─── 3. vw_marketing_accion_resumen ───────────────────────
echo "3. Vista vw_marketing_accion_resumen... ";
$pdo->exec("DROP VIEW IF EXISTS vw_marketing_accion_resumen");
$pdo->exec("
    CREATE VIEW vw_marketing_accion_resumen AS
    SELECT
        t.id_tipo_accion,
        t.nombre                                            AS tipo_nombre,
        t.color                                             AS tipo_color,
        a.fecha,
        COUNT(a.id_accion)                                  AS cantidad,
        COALESCE(SUM(a.costo), 0)                           AS costo_total,
        COALESCE(SUM(a.leads_generados), 0)                 AS leads_atribuidos
    FROM tbl_marketing_tipo_accion t
    LEFT JOIN tbl_marketing_accion a ON a.id_tipo_accion = t.id_tipo_accion
    WHERE t.activa = 1
    GROUP BY t.id_tipo_accion, t.nombre, t.color, a.fecha
");
echo "[CREADA]\n";

echo "\n=== VISTAS CREADAS ===\n";
echo "Filas detectadas:\n";
$counts = [
    'vw_marketing_lead_360'        => $pdo->query("SELECT COUNT(*) FROM vw_marketing_lead_360")->fetchColumn(),
    'vw_marketing_embudo_resumen'  => $pdo->query("SELECT COUNT(*) FROM vw_marketing_embudo_resumen")->fetchColumn(),
    'vw_marketing_accion_resumen'  => $pdo->query("SELECT COUNT(*) FROM vw_marketing_accion_resumen")->fetchColumn(),
];
foreach ($counts as $v => $n) echo "  - {$v}: {$n} filas\n";
echo "\n=== MIGRACION COMPLETADA ===\n";
