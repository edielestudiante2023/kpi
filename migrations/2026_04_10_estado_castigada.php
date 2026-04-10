<?php
/**
 * Migración: agregar 'castigada' al ENUM estado_pago + marcar FV-2-13
 * Uso: php migrations/2026_04_10_estado_castigada.php [host] [port] [db] [user] [pass]
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

// ═══ PASO 1: Agregar 'castigada' al ENUM ═══
echo "=== PASO 1: Verificar ENUM ===\n";
$r = $db->query("SHOW COLUMNS FROM tbl_facturacion LIKE 'estado_pago'");
$col = $r->fetch_assoc();
echo "Tipo actual: {$col['Type']}\n";

if (strpos($col['Type'], 'castigada') !== false) {
    echo "El valor 'castigada' ya existe. Omitiendo ALTER.\n";
} else {
    $db->query("ALTER TABLE tbl_facturacion MODIFY COLUMN estado_pago ENUM('pendiente','pagado','brecha','anticipo','castigada') NOT NULL DEFAULT 'pendiente'");
    if ($db->error) {
        echo "ERROR: {$db->error}\n";
    } else {
        echo "ENUM actualizado: incluye 'castigada'.\n";
    }
}

// ═══ PASO 2: Marcar FV-2-13 como castigada + pagado=1 ═══
echo "\n=== PASO 2: Marcar FV-2-13 como castigada ===\n";
$r = $db->query("SELECT comprobante, nombre_tercero, base_gravada, pagado, estado_pago FROM tbl_facturacion WHERE comprobante = 'FV-2-13'");
$row = $r->fetch_assoc();
if ($row) {
    echo "Antes: {$row['comprobante']} | {$row['nombre_tercero']} | base=\$" . number_format((float)$row['base_gravada'], 0, ',', '.') . " | pagado={$row['pagado']} | estado={$row['estado_pago']}\n";
    $db->query("UPDATE tbl_facturacion SET pagado = 1, estado_pago = 'castigada' WHERE comprobante = 'FV-2-13'");
    echo "Actualizada: {$db->affected_rows}\n";
} else {
    echo "FV-2-13 no encontrada.\n";
}

// ═══ VERIFICACIÓN ═══
echo "\n=== VERIFICACIÓN ===\n";
$r = $db->query("SELECT estado_pago, COUNT(*) as c, SUM(base_gravada) as total FROM tbl_facturacion GROUP BY estado_pago ORDER BY estado_pago");
while ($row = $r->fetch_assoc()) {
    echo "  {$row['estado_pago']}: {$row['c']} facturas | \$" . number_format((float)$row['total'], 0, ',', '.') . "\n";
}

$r = $db->query("SELECT COUNT(*) as c, SUM(base_gravada) as total FROM tbl_facturacion WHERE pagado = 0");
$row = $r->fetch_assoc();
echo "\nCARTERA (pagado=0): {$row['c']} facturas | \$" . number_format((float)$row['total'], 0, ',', '.') . "\n";

$db->close();
echo "\nListo.\n";
