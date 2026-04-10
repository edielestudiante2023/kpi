<?php
/**
 * Script CLI para reimportar facturación con fechas corregidas
 * Uso: php migrations/reimport_facturacion.php [host] [port] [db] [user] [pass]
 */
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

$host = $argv[1] ?? '127.0.0.1';
$port = (int)($argv[2] ?? 3306);
$dbname = $argv[3] ?? 'kpicycloid';
$user = $argv[4] ?? 'root';
$pass = $argv[5] ?? '';

$db = new mysqli($host, $user, $pass, $dbname, $port);
if ($db->connect_error) die("Error conexión: {$db->connect_error}\n");
$db->set_charset('utf8mb4');
echo "Conectado a {$host}:{$port}/{$dbname}\n";

// Mapear portafolios
$r = $db->query('SELECT id_portafolio, portafolio FROM tbl_portafolios');
$mapPort = [];
while ($row = $r->fetch_assoc()) $mapPort[mb_strtoupper(trim($row['portafolio']))] = (int)$row['id_portafolio'];
echo "Portafolios: " . implode(', ', array_keys($mapPort)) . "\n";

function toDateSmart($val) {
    if ($val === null || $val === '') return null;
    if ($val instanceof \DateTimeInterface) return $val->format('Y-m-d');
    if (is_numeric($val) && (float)$val > 1000) {
        try { return ExcelDate::excelToDateTimeObject((float)$val)->format('Y-m-d'); }
        catch (\Exception $e) { return null; }
    }
    $str = trim((string)$val);
    // Serial como texto con coma
    $cleaned = str_replace(',', '', $str);
    if (is_numeric($cleaned) && (float)$cleaned > 1000 && (float)$cleaned < 100000) {
        try { return ExcelDate::excelToDateTimeObject((float)$cleaned)->format('Y-m-d'); }
        catch (\Exception $e) { /* continuar */ }
    }
    // A/B/YYYY
    if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $str, $m)) {
        $a = (int)$m[1]; $b = (int)$m[2]; $y = (int)$m[3];
        if ($b > 12)      return sprintf('%04d-%02d-%02d', $y, $a, $b);
        elseif ($a > 12)  return sprintf('%04d-%02d-%02d', $y, $b, $a);
        else              return sprintf('%04d-%02d-%02d', $y, $a, $b); // MM/DD/YYYY
    }
    // A-B-YYYY
    if (preg_match('#^(\d{1,2})-(\d{1,2})-(\d{2,4})$#', $str, $m)) {
        $y = (int)$m[3]; if ($y < 100) $y += 2000;
        $a = (int)$m[1]; $b = (int)$m[2];
        if ($b > 12)      return sprintf('%04d-%02d-%02d', $y, $a, $b);
        elseif ($a > 12)  return sprintf('%04d-%02d-%02d', $y, $b, $a);
        else              return sprintf('%04d-%02d-%02d', $y, $a, $b);
    }
    return null;
}

function toDecimal($val) {
    if ($val === null || $val === '') return null;
    $str = preg_replace('/[\$\s]/', '', (string)$val);
    if (strpos($str, ',') !== false && strpos($str, '.') === false) $str = str_replace(',', '', $str);
    elseif (preg_match('/^\-?[\d.]+,\d{1,2}$/', $str)) { $str = str_replace('.', '', $str); $str = str_replace(',', '.', $str); }
    return is_numeric($str) ? round((float)$str, 2) : null;
}

function toInt($val) {
    if ($val === null || $val === '') return null;
    $clean = str_replace([' ', ','], '', (string)$val);
    return is_numeric($clean) ? (int)$clean : null;
}

// Truncar
$db->query("DELETE FROM tbl_facturacion");
echo "Truncado: {$db->affected_rows} registros eliminados\n";

// Importar
$file = __DIR__ . '/../imports/samples/TABLA DE FACTURACION.xlsx';
echo "\n=== Importando " . basename($file) . " ===\n";
$sp = IOFactory::load($file);
$rows = $sp->getActiveSheet()->toArray(null, true, true, true);
array_shift($rows);

$sql_cols = 'id_portafolio,semana,fecha_pago,mes_pago,valor_pagado,dif_facturado_pagado,valor_esperado_recaudo_iva,retencion_renta_4,base_gravable_neta,pagado,anio,mes,extrae,fecha_anticipo,anticipo,comprobante,fecha_elaboracion,identificacion,sucursal,nombre_tercero,base_gravada,base_exenta,iva,retefuente_4,recompra,cargo_en_totales,descuento_en_totales,total,vendedor,base_comisiones,numero_factura,portafolio_detallado,fecha_vence';
$ncols = 33;

$insertados = 0;
$errores = [];
$lote = [];

foreach ($rows as $numFila => $row) {
    $portNombre = mb_strtoupper(trim($row['A'] ?? ''));
    if ($portNombre === 'ST') $portNombre = 'SST';
    if ($portNombre === 'PORTATOLIO' || $portNombre === '') continue;

    $idPortafolio = $mapPort[$portNombre] ?? null;
    if (!$idPortafolio) { $errores[] = "Fila {$numFila}: Portafolio '{$portNombre}' no encontrado"; continue; }

    $comprobante = trim($row['P'] ?? '');
    $anio = toInt($row['K']);
    if (empty($comprobante) || empty($anio)) { $errores[] = "Fila {$numFila}: comprobante/anio vacío"; continue; }

    $lote[] = [
        $idPortafolio,
        toInt($row['B']),
        toDateSmart($row['C']),
        toInt($row['D']),
        toDecimal($row['E']),
        toDecimal($row['F']),
        toDecimal($row['G']),
        toDecimal($row['H']),
        toDecimal($row['I']),
        (mb_strtoupper(trim($row['J'] ?? '')) === 'SI') ? 1 : 0,
        $anio,
        toInt($row['L']),
        trim($row['M'] ?? '') ?: null,
        toDateSmart($row['N']),
        toDecimal($row['O']),
        $comprobante,
        toDateSmart($row['Q']),
        toInt($row['R']),
        trim($row['S'] ?? '') ?: null,
        trim($row['T'] ?? ''),
        toDecimal($row['U']),
        toDecimal($row['V']),
        toDecimal($row['W']),
        toDecimal($row['X']),
        toInt($row['Y']) ? 1 : 0,
        toDecimal($row['Z']),
        toDecimal($row['AA']),
        toDecimal($row['AB']),
        trim($row['AC'] ?? '') ?: null,
        toDecimal($row['AD']),
        toInt($row['AE']),
        trim($row['AF'] ?? '') ?: null,
        toDateSmart($row['AG']),
    ];

    if (count($lote) >= 200) {
        $ph = '(' . implode(',', array_fill(0, $ncols, '?')) . ')';
        $sql = "INSERT INTO tbl_facturacion ({$sql_cols}) VALUES " . implode(',', array_fill(0, count($lote), $ph));
        $stmt = $db->prepare($sql);
        $types = ''; $vals = [];
        foreach ($lote as $r2) {
            foreach ($r2 as $v) {
                $types .= is_int($v) ? 'i' : (is_float($v) ? 'd' : 's');
                $vals[] = $v;
            }
        }
        $stmt->bind_param($types, ...$vals);
        $stmt->execute();
        if ($stmt->error) echo "ERROR: {$stmt->error}\n";
        $insertados += count($lote);
        $lote = [];
    }
}

if (!empty($lote)) {
    $ph = '(' . implode(',', array_fill(0, $ncols, '?')) . ')';
    $sql = "INSERT INTO tbl_facturacion ({$sql_cols}) VALUES " . implode(',', array_fill(0, count($lote), $ph));
    $stmt = $db->prepare($sql);
    $types = ''; $vals = [];
    foreach ($lote as $r2) {
        foreach ($r2 as $v) {
            $types .= is_int($v) ? 'i' : (is_float($v) ? 'd' : 's');
            $vals[] = $v;
        }
    }
    $stmt->bind_param($types, ...$vals);
    $stmt->execute();
    if ($stmt->error) echo "ERROR: {$stmt->error}\n";
    $insertados += count($lote);
}

echo "Insertados: {$insertados} | Errores: " . count($errores) . "\n";
foreach (array_slice($errores, 0, 5) as $e) echo "  {$e}\n";

// Verificación
echo "\n=== VERIFICACIÓN ===\n";
$r = $db->query("SELECT COUNT(*) as c FROM tbl_facturacion");
echo "Total registros: {$r->fetch_assoc()['c']}\n";

$r = $db->query("SELECT MIN(fecha_elaboracion) as min_f, MAX(fecha_elaboracion) as max_f FROM tbl_facturacion WHERE fecha_elaboracion IS NOT NULL");
$row = $r->fetch_assoc();
echo "Rango fecha_elaboracion: {$row['min_f']} a {$row['max_f']}\n";

$r = $db->query("SELECT MIN(fecha_pago) as min_f, MAX(fecha_pago) as max_f FROM tbl_facturacion WHERE fecha_pago IS NOT NULL");
$row = $r->fetch_assoc();
echo "Rango fecha_pago: {$row['min_f']} a {$row['max_f']}\n";

$r = $db->query("SELECT MIN(fecha_vence) as min_f, MAX(fecha_vence) as max_f FROM tbl_facturacion WHERE fecha_vence IS NOT NULL");
$row = $r->fetch_assoc();
echo "Rango fecha_vence: {$row['min_f']} a {$row['max_f']}\n";

// Verificar meses que no cuadran (fecha_elaboracion mes != mes columna)
$r = $db->query("SELECT COUNT(*) as c FROM tbl_facturacion WHERE MONTH(fecha_elaboracion) != mes AND fecha_elaboracion IS NOT NULL");
echo "Fecha elab. mes != columna mes: {$r->fetch_assoc()['c']}\n";

$r = $db->query("SELECT COUNT(*) as c FROM tbl_facturacion WHERE YEAR(fecha_elaboracion) != anio AND fecha_elaboracion IS NOT NULL");
echo "Fecha elab. año != columna anio: {$r->fetch_assoc()['c']}\n";

$db->close();
echo "\nListo.\n";
