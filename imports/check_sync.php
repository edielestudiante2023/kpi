<?php
$local = new PDO('mysql:host=127.0.0.1;port=3306;dbname=kpicycloid;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
$dotenv = @parse_ini_file(__DIR__ . '/../.env.production');
$prod = new PDO(
    "mysql:host={$dotenv['DB_HOST']};port={$dotenv['DB_PORT']};dbname={$dotenv['DB_NAME']};charset=utf8mb4",
    $dotenv['DB_USER'], $dotenv['DB_PASS'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false]
);

$tablas = [
    'tbl_portafolios', 'tbl_centros_costo', 'tbl_cuentas_banco',
    'tbl_facturacion', 'tbl_conciliacion_bancaria',
    'tbl_facturacion_cruda', 'tbl_movimiento_bancario_crudo',
    'tbl_deudas', 'tbl_deuda_abonos', 'tbl_clasificacion_costos',
];

echo str_pad('TABLA', 35) . str_pad('LOCAL', 10) . str_pad('PROD', 10) . 'ESTADO' . PHP_EOL;
echo str_repeat('-', 70) . PHP_EOL;

foreach ($tablas as $t) {
    $lEx = (int) $local->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'kpicycloid' AND TABLE_NAME = '{$t}'")->fetchColumn();
    $pEx = (int) $prod->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'kpicycloid' AND TABLE_NAME = '{$t}'")->fetchColumn();

    $lC = $lEx ? $local->query("SELECT COUNT(*) FROM {$t}")->fetchColumn() : '-';
    $pC = $pEx ? $prod->query("SELECT COUNT(*) FROM {$t}")->fetchColumn() : '-';

    if (!$pEx && $lEx)       $estado = 'FALTA EN PROD';
    elseif (!$lEx && $pEx)   $estado = 'FALTA EN LOCAL';
    elseif ($lC === $pC)     $estado = 'OK';
    else                     $estado = 'DIFERENTE';

    echo str_pad($t, 35) . str_pad($lC, 10) . str_pad($pC, 10) . $estado . PHP_EOL;
}

// Verificar columna saldo_inicial en producción
$col = $prod->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'kpicycloid' AND TABLE_NAME = 'tbl_cuentas_banco' AND COLUMN_NAME = 'saldo_inicial'")->fetchColumn();
echo PHP_EOL . "Columna saldo_inicial en prod: " . ($col ? 'SI' : 'NO') . PHP_EOL;
