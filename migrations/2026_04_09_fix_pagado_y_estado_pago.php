<?php
/**
 * Migración: marcar 14 facturas como pagadas + agregar columna estado_pago
 * Uso: php migrations/2026_04_09_fix_pagado_y_estado_pago.php [host] [port] [db] [user] [pass]
 */
$host = $argv[1] ?? '127.0.0.1';
$port = (int)($argv[2] ?? 3306);
$dbname = $argv[3] ?? 'kpicycloid';
$user = $argv[4] ?? 'root';
$pass = $argv[5] ?? '';

$db = new mysqli($host, $user, $pass, $dbname, $port);
if ($db->connect_error) die("Error conexión: {$db->connect_error}\n");
$db->set_charset('utf8mb4');
echo "Conectado a {$host}:{$port}/{$dbname}\n\n";

// ═══ PASO 1: Marcar 14 facturas como pagado = 1 ═══
// 13 que coinciden (diff < $1) + Villa Veronica (FV-2-2184, decisión del usuario)
$ids_pagadas = [
    2568,  // FV-2-2086
    2584,  // FV-2-2102
    2594,  // FV-2-2112
    2606,  // FV-2-2124
    2624,  // FV-2-2142
    2630,  // FV-2-2148
    2634,  // FV-2-2152
    2635,  // FV-2-2153
    2639,  // FV-2-2157
    2640,  // FV-2-2158
    2645,  // FV-2-2163
    2651,  // FV-2-2169
    2655,  // FV-2-2173
    2666,  // FV-2-2184 (Villa Veronica - decisión usuario)
];

// Verificar que existan antes de actualizar
$idList = implode(',', $ids_pagadas);
$r = $db->query("SELECT id_facturacion, comprobante, pagado FROM tbl_facturacion WHERE id_facturacion IN ({$idList})");
echo "=== PASO 1: Marcar facturas como pagadas ===\n";
echo "Encontradas: {$r->num_rows} de " . count($ids_pagadas) . "\n";

// Si los IDs no coinciden (producción puede tener IDs diferentes), buscar por comprobante
if ($r->num_rows < count($ids_pagadas)) {
    echo "IDs no coinciden, buscando por comprobante...\n";
    $comprobantes = [
        'FV-2-2086','FV-2-2102','FV-2-2112','FV-2-2124','FV-2-2142',
        'FV-2-2148','FV-2-2152','FV-2-2153','FV-2-2157','FV-2-2158',
        'FV-2-2163','FV-2-2169','FV-2-2173','FV-2-2184',
    ];
    $compList = "'" . implode("','", $comprobantes) . "'";
    $db->query("UPDATE tbl_facturacion SET pagado = 1 WHERE comprobante IN ({$compList}) AND pagado = 0 AND valor_pagado > 0");
    echo "Actualizadas por comprobante: {$db->affected_rows}\n";
} else {
    $db->query("UPDATE tbl_facturacion SET pagado = 1 WHERE id_facturacion IN ({$idList}) AND pagado = 0");
    echo "Actualizadas por ID: {$db->affected_rows}\n";
}

// ═══ PASO 2: Agregar columna estado_pago ═══
echo "\n=== PASO 2: Agregar columna estado_pago ===\n";

// Verificar si ya existe
$r = $db->query("SHOW COLUMNS FROM tbl_facturacion LIKE 'estado_pago'");
if ($r->num_rows > 0) {
    echo "Columna estado_pago ya existe, omitiendo ALTER.\n";
} else {
    $db->query("ALTER TABLE tbl_facturacion ADD COLUMN estado_pago ENUM('pendiente','pagado','brecha') NOT NULL DEFAULT 'pendiente' AFTER pagado");
    if ($db->error) {
        echo "ERROR al agregar columna: {$db->error}\n";
    } else {
        echo "Columna estado_pago agregada.\n";
    }
    $db->query("ALTER TABLE tbl_facturacion ADD INDEX idx_estado_pago (estado_pago)");
    echo "Índice idx_estado_pago creado.\n";
}

// ═══ PASO 3: Poblar estado_pago según datos existentes ═══
echo "\n=== PASO 3: Poblar estado_pago ===\n";

// Todas las pagadas → 'pagado'
$db->query("UPDATE tbl_facturacion SET estado_pago = 'pagado' WHERE pagado = 1");
echo "Marcadas como 'pagado': {$db->affected_rows}\n";

// No pagadas con valor_pagado > 0 y diferencia >= 2000 → 'brecha'
$db->query("
    UPDATE tbl_facturacion
    SET estado_pago = 'brecha'
    WHERE pagado = 0
      AND valor_pagado > 0
      AND fecha_pago IS NOT NULL
      AND ABS((base_gravada + iva - ABS(retefuente_4)) - valor_pagado) >= 2000
");
echo "Marcadas como 'brecha': {$db->affected_rows}\n";

// No pagadas sin pago → 'pendiente' (ya es el default)
$db->query("UPDATE tbl_facturacion SET estado_pago = 'pendiente' WHERE pagado = 0 AND estado_pago != 'brecha'");
echo "Marcadas como 'pendiente': {$db->affected_rows}\n";

// ═══ VERIFICACIÓN ═══
echo "\n=== VERIFICACIÓN ===\n";
$r = $db->query("SELECT estado_pago, COUNT(*) as c, SUM(base_gravada) as total FROM tbl_facturacion GROUP BY estado_pago");
while ($row = $r->fetch_assoc()) {
    echo "  {$row['estado_pago']}: {$row['c']} facturas | \$" . number_format((float)$row['total'], 0, ',', '.') . "\n";
}

$r = $db->query("SELECT pagado, COUNT(*) as c FROM tbl_facturacion GROUP BY pagado");
echo "\nPor pagado:\n";
while ($row = $r->fetch_assoc()) {
    echo "  pagado={$row['pagado']}: {$row['c']}\n";
}

// Facturas con brecha - detalle
$r = $db->query("
    SELECT comprobante, nombre_tercero, identificacion, base_gravada, iva, ABS(retefuente_4) as ret4,
           (base_gravada + iva - ABS(retefuente_4)) as liquido, valor_pagado,
           ROUND((base_gravada + iva - ABS(retefuente_4)) - valor_pagado, 2) as diferencia
    FROM tbl_facturacion WHERE estado_pago = 'brecha'
");
if ($r->num_rows > 0) {
    echo "\nFacturas con BRECHA:\n";
    while ($row = $r->fetch_assoc()) {
        echo "  {$row['comprobante']} | NIT {$row['identificacion']} | {$row['nombre_tercero']} | líquido="
            . number_format((float)$row['liquido'], 0, ',', '.')
            . " pagado=" . number_format((float)$row['valor_pagado'], 0, ',', '.')
            . " diff=" . number_format((float)$row['diferencia'], 0, ',', '.') . "\n";
    }
}

$db->close();
echo "\nListo.\n";
