<?php
/**
 * Migración: agregar 'anticipo' al ENUM estado_pago en tbl_facturacion
 * Uso: php migrations/2026_04_09_estado_pago_anticipo.php [host] [port] [db] [user] [pass]
 */
$host   = $argv[1] ?? '127.0.0.1';
$port   = (int)($argv[2] ?? 3306);
$dbname = $argv[3] ?? 'kpicycloid';
$user   = $argv[4] ?? 'root';
$pass   = $argv[5] ?? '';

$db = new mysqli($host, $user, $pass, $dbname, $port);
if ($db->connect_error) die("Error conexión: {$db->connect_error}\n");
$db->set_charset('utf8mb4');
echo "Conectado a {$host}:{$port}/{$dbname}\n\n";

// ═══ PASO 1: Verificar estado actual del ENUM ═══
echo "=== PASO 1: Verificar ENUM actual ===\n";
$r = $db->query("SHOW COLUMNS FROM tbl_facturacion LIKE 'estado_pago'");
$col = $r->fetch_assoc();
echo "Tipo actual: {$col['Type']}\n";

if (strpos($col['Type'], 'anticipo') !== false) {
    echo "El valor 'anticipo' ya existe en el ENUM. Omitiendo ALTER.\n";
} else {
    $db->query("ALTER TABLE tbl_facturacion MODIFY COLUMN estado_pago ENUM('pendiente','pagado','brecha','anticipo') NOT NULL DEFAULT 'pendiente'");
    if ($db->error) {
        echo "ERROR al modificar ENUM: {$db->error}\n";
    } else {
        echo "ENUM actualizado: ahora incluye 'anticipo'.\n";
    }
}

// ═══ PASO 2: Marcar facturas con anticipo > 0 y no pagadas como 'anticipo' ═══
echo "\n=== PASO 2: Actualizar estado_pago para facturas con anticipo ===\n";
$db->query("
    UPDATE tbl_facturacion
    SET estado_pago = 'anticipo'
    WHERE anticipo > 0
      AND pagado = 0
      AND estado_pago IN ('pendiente','brecha')
");
echo "Facturas marcadas como 'anticipo': {$db->affected_rows}\n";

// ═══ VERIFICACIÓN ═══
echo "\n=== VERIFICACIÓN ===\n";
$r = $db->query("SELECT estado_pago, COUNT(*) as c, SUM(base_gravada) as total, SUM(anticipo) as total_anticipo FROM tbl_facturacion GROUP BY estado_pago ORDER BY estado_pago");
while ($row = $r->fetch_assoc()) {
    echo "  {$row['estado_pago']}: {$row['c']} facturas | base=\$" . number_format((float)$row['total'], 0, ',', '.')
        . " | anticipos=\$" . number_format((float)$row['total_anticipo'], 0, ',', '.') . "\n";
}

$db->close();
echo "\nListo.\n";
