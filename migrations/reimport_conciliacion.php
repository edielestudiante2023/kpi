<?php
/**
 * Script CLI para reimportar conciliación bancaria
 * Uso: php migrations/reimport_conciliacion.php
 */
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

// ── Conexión ──
$host = $argv[1] ?? '127.0.0.1';
$port = (int)($argv[2] ?? 3306);
$dbname = $argv[3] ?? 'kpicycloid';
$user = $argv[4] ?? 'root';
$pass = $argv[5] ?? '';

$db = new mysqli($host, $user, $pass, $dbname, $port);
if ($db->connect_error) die("Error conexión: {$db->connect_error}\n");
$db->set_charset('utf8mb4');
echo "Conectado a {$host}:{$port}/{$dbname}\n";

// ── Mapear centros de costo ──
$r = $db->query('SELECT id_centro_costo, centro_costo FROM tbl_centros_costo');
$mapCentro = [];
while ($row = $r->fetch_assoc()) $mapCentro[mb_strtoupper(trim($row['centro_costo']))] = (int)$row['id_centro_costo'];

function toDateSmart($val) {
    if ($val === null || $val === '') return null;
    if ($val instanceof \DateTimeInterface) return $val->format('Y-m-d');
    if (is_numeric($val) && (float)$val > 1000) {
        try { return ExcelDate::excelToDateTimeObject((float)$val)->format('Y-m-d'); }
        catch (\Exception $e) { return null; }
    }
    $str = trim((string)$val);
    // Serial de Excel como texto con coma (ej: "45,643")
    $cleaned = str_replace(',', '', $str);
    if (is_numeric($cleaned) && (float)$cleaned > 1000 && (float)$cleaned < 100000) {
        try { return ExcelDate::excelToDateTimeObject((float)$cleaned)->format('Y-m-d'); }
        catch (\Exception $e) { /* continuar */ }
    }
    // Formato A/B/YYYY
    if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $str, $m)) {
        $a = (int)$m[1]; $b = (int)$m[2]; $y = (int)$m[3];
        if ($b > 12)      return sprintf('%04d-%02d-%02d', $y, $a, $b); // MM/DD/YYYY
        elseif ($a > 12)  return sprintf('%04d-%02d-%02d', $y, $b, $a); // DD/MM/YYYY
        else              return sprintf('%04d-%02d-%02d', $y, $a, $b); // Asumir MM/DD/YYYY
    }
    // Formato A-B-YYYY
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

// ── Truncar ──
$db->query("DELETE FROM tbl_conciliacion_bancaria");
echo "Truncado: {$db->affected_rows} registros eliminados\n";

// ── Importar ──
$imports = [
    ['file' => __DIR__ . '/../imports/samples/CONCILIACION BANCARIA SST.xlsx', 'cuenta' => 1],
    ['file' => __DIR__ . '/../imports/samples/CONCILIACION BANCARIA RPS.xlsx', 'cuenta' => 2],
];

$sql_cols = 'id_cuenta_banco,id_centro_costo,llave_item,deb_cred,fv,item_cliente,anio,mes,semana,valor,fecha_sistema,documento,descripcion_motivo,transaccion,oficina_recaudo,nit_originador,valor_cheque,valor_total,referencia_1,referencia_2,mes_real';

foreach ($imports as $imp) {
    $fname = basename($imp['file']);
    echo "\n=== {$fname} (cuenta={$imp['cuenta']}) ===\n";
    $sp = IOFactory::load($imp['file']);
    $rows = $sp->getActiveSheet()->toArray(null, true, true, true);
    array_shift($rows);

    $insertados = 0;
    $errores = [];
    $lote = [];
    $ultimaFecha = null;

    foreach ($rows as $numFila => $row) {
        $ccNombre = mb_strtoupper(trim($row['C'] ?? ''));
        if ($ccNombre === 'SS') $ccNombre = 'SST';
        if ($ccNombre === 'ST') $ccNombre = 'SST';
        if ($ccNombre === 'ASADO') $ccNombre = 'BIENESTAR';
        if ($ccNombre === 'PORTATOLIO') continue; // header duplicado
        $ccNombre = str_replace(
            ['CRÉDITO','DÉBITO','RECONSIGNACIÓN','DEVOLUCIÓN'],
            ['CREDITO','DEBITO','RECONSIGNACION','DEVOLUCION'],
            $ccNombre
        );

        $idCentro = $mapCentro[$ccNombre] ?? null;
        if (!$idCentro) { $errores[] = "Fila {$numFila}: CC '{$ccNombre}' no encontrado"; continue; }

        $fechaSistema = toDateSmart($row['L']);
        if ($fechaSistema && $fechaSistema !== '0000-00-00') {
            $ultimaFecha = $fechaSistema;
        } elseif ($ultimaFecha) {
            $fechaSistema = $ultimaFecha;
        }
        $anio = is_numeric($row['H']) ? (int)$row['H'] : null;
        $mes  = is_numeric($row['I']) ? (int)$row['I'] : null;
        $llaveItem = trim($row['D'] ?? '');

        if (empty($llaveItem) || empty($anio)) { $errores[] = "Fila {$numFila}: llave/anio vacío"; continue; }

        $lote[] = [
            $imp['cuenta'], $idCentro, $llaveItem,
            mb_strtoupper(trim($row['E'] ?? '')),
            trim($row['F'] ?? '') ?: null,
            trim($row['G'] ?? '') ?: null,
            $anio, $mes,
            is_numeric($row['J']) ? (int)$row['J'] : null,
            toDecimal($row['K']),
            $fechaSistema,
            is_numeric($row['M']) ? (int)$row['M'] : null,
            trim($row['N'] ?? '') ?: null,
            trim($row['O'] ?? '') ?: null,
            trim($row['P'] ?? '') ?: null,
            is_numeric($row['Q']) ? (int)$row['Q'] : null,
            toDecimal($row['R']),
            toDecimal($row['S']),
            trim((string)($row['T'] ?? '')) ?: null,
            trim((string)($row['U'] ?? '')) ?: null,
            $mes, // mes_real
        ];

        if (count($lote) >= 200) {
            $ph = '(' . implode(',', array_fill(0, 21, '?')) . ')';
            $sql = "INSERT INTO tbl_conciliacion_bancaria ({$sql_cols}) VALUES " . implode(',', array_fill(0, count($lote), $ph));
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
        $ph = '(' . implode(',', array_fill(0, 21, '?')) . ')';
        $sql = "INSERT INTO tbl_conciliacion_bancaria ({$sql_cols}) VALUES " . implode(',', array_fill(0, count($lote), $ph));
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
}

// ── Verificación ──
echo "\n=== VERIFICACIÓN ===\n";
$r = $db->query("SELECT cu.nombre_cuenta, COUNT(*) as c, MIN(fecha_sistema) as min_f, MAX(fecha_sistema) as max_f FROM tbl_conciliacion_bancaria cb JOIN tbl_cuentas_banco cu ON cu.id_cuenta_banco = cb.id_cuenta_banco GROUP BY cu.nombre_cuenta");
while ($row = $r->fetch_assoc()) echo "{$row['nombre_cuenta']}: {$row['c']} registros | {$row['min_f']} a {$row['max_f']}\n";

$r = $db->query("SELECT COUNT(*) as c FROM tbl_conciliacion_bancaria WHERE fecha_sistema = '2026-03-31'");
echo "2026-03-31: {$r->fetch_assoc()['c']} registros\n";

$r = $db->query("SELECT COUNT(*) as c FROM tbl_conciliacion_bancaria WHERE fecha_sistema >= '2026-03-13'");
echo ">= 2026-03-13: {$r->fetch_assoc()['c']} registros\n";

$r = $db->query("SELECT COUNT(*) as c FROM tbl_conciliacion_bancaria WHERE fecha_sistema IS NULL OR fecha_sistema = '0000-00-00'");
echo "Fechas NULL/0000: {$r->fetch_assoc()['c']} registros\n";

$db->close();
echo "\nListo.\n";
