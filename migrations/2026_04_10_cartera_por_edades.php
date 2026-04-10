<?php
/**
 * Migración: Sincronizar cartera con la hoja "CARTERA POR EDADES".
 * Solo estas 39 facturas deben tener pagado=0. El resto pasa a pagado=1.
 * Uso: php migrations/2026_04_10_cartera_por_edades.php [host] [port] [db] [user] [pass]
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

// Las 39 facturas que son cartera real (pagado=0)
$cartera = [
    'FV-2-2155','FV-2-2159','FV-2-2114','FV-2-2154','FV-2-2144',
    'FV-2-2160','FV-2-2161','FV-2-2116','FV-2-1974','FV-2-1924',
    'FV-2-1877','FV-2-1831','FV-2-2162','FV-2-2118','FV-2-2151',
    'FV-2-2088','FV-2-2164','FV-2-2182','FV-2-2165','FV-2-2175',
    'FV-2-2150','FV-2-2166','FV-2-2122','FV-2-1931','FV-2-2167',
    'FV-2-2178','FV-2-2181','FV-2-2177','FV-2-2183','FV-2-13',
    'FV-2-2126','FV-2-2145','FV-2-2104','FV-2-2146','FV-2-2180',
    'FV-2-2066','FV-2-2171','FV-2-2149','FV-2-2147',
];

$carteraList = "'" . implode("','", $cartera) . "'";

// ═══ DIAGNÓSTICO PREVIO ═══
echo "=== DIAGNÓSTICO PREVIO ===\n";
$r = $db->query("SELECT pagado, estado_pago, COUNT(*) as c FROM tbl_facturacion GROUP BY pagado, estado_pago ORDER BY pagado, estado_pago");
while ($row = $r->fetch_assoc()) {
    echo "  pagado={$row['pagado']} estado={$row['estado_pago']}: {$row['c']}\n";
}

// Verificar que las 39 facturas existan
$r = $db->query("SELECT comprobante FROM tbl_facturacion WHERE comprobante IN ({$carteraList})");
echo "\nFacturas de cartera encontradas: {$r->num_rows} de " . count($cartera) . "\n";
if ($r->num_rows < count($cartera)) {
    // Mostrar cuáles faltan
    $encontradas = [];
    while ($row = $r->fetch_assoc()) $encontradas[] = $row['comprobante'];
    $faltantes = array_diff($cartera, $encontradas);
    echo "FALTAN: " . implode(', ', $faltantes) . "\n";
}

// ═══ PASO 1: Marcar como pagadas las que tienen pagado=0 pero NO están en la lista ═══
echo "\n=== PASO 1: Facturas fuera de cartera que estaban con pagado=0 → pagado=1 ===\n";
$r = $db->query("SELECT comprobante, nombre_tercero, estado_pago FROM tbl_facturacion WHERE pagado = 0 AND comprobante NOT IN ({$carteraList})");
echo "Facturas a marcar como pagadas: {$r->num_rows}\n";
while ($row = $r->fetch_assoc()) {
    echo "  {$row['comprobante']} | {$row['nombre_tercero']} | era: {$row['estado_pago']}\n";
}

$db->query("UPDATE tbl_facturacion SET pagado = 1, estado_pago = 'pagado' WHERE pagado = 0 AND comprobante NOT IN ({$carteraList})");
echo "Actualizadas: {$db->affected_rows}\n";

// ═══ PASO 2: Marcar como NO pagadas las que están en la lista pero tienen pagado=1 ═══
echo "\n=== PASO 2: Facturas de cartera que estaban con pagado=1 → pagado=0 ===\n";
$r = $db->query("SELECT comprobante, nombre_tercero, estado_pago FROM tbl_facturacion WHERE pagado = 1 AND comprobante IN ({$carteraList})");
echo "Facturas a marcar como cartera: {$r->num_rows}\n";
while ($row = $r->fetch_assoc()) {
    echo "  {$row['comprobante']} | {$row['nombre_tercero']} | era: {$row['estado_pago']}\n";
}

$db->query("UPDATE tbl_facturacion SET pagado = 0, estado_pago = 'pendiente' WHERE pagado = 1 AND comprobante IN ({$carteraList})");
echo "Actualizadas: {$db->affected_rows}\n";

// ═══ PASO 3: Asegurar que las brechas que ahora son pagadas tengan estado correcto ═══
echo "\n=== PASO 3: Limpiar estados incoherentes ===\n";
$db->query("UPDATE tbl_facturacion SET estado_pago = 'pagado' WHERE pagado = 1 AND estado_pago != 'pagado'");
echo "Pagadas con estado corregido: {$db->affected_rows}\n";

$db->query("UPDATE tbl_facturacion SET estado_pago = 'pendiente' WHERE pagado = 0 AND estado_pago NOT IN ('pendiente','brecha','anticipo')");
echo "Pendientes con estado corregido: {$db->affected_rows}\n";

// ═══ VERIFICACIÓN ═══
echo "\n=== VERIFICACIÓN FINAL ===\n";
$r = $db->query("SELECT pagado, estado_pago, COUNT(*) as c, SUM(base_gravada) as total FROM tbl_facturacion GROUP BY pagado, estado_pago ORDER BY pagado, estado_pago");
while ($row = $r->fetch_assoc()) {
    echo "  pagado={$row['pagado']} estado={$row['estado_pago']}: {$row['c']} facturas | \$" . number_format((float)$row['total'], 0, ',', '.') . "\n";
}

$r = $db->query("SELECT COUNT(*) as c, SUM(base_gravada) as total FROM tbl_facturacion WHERE pagado = 0");
$row = $r->fetch_assoc();
echo "\nTOTAL CARTERA: {$row['c']} facturas | \$" . number_format((float)$row['total'], 0, ',', '.') . "\n";

$db->close();
echo "\nListo.\n";
