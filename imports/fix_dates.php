<?php
/**
 * Fix: corregir fechas invertidas (MM/DD swapped) en conciliacion y facturacion
 * Ejecutar: php imports/fix_dates.php [local|production|ambas]
 */

$env = $argv[1] ?? 'ambas';

function getLocal() {
    return new PDO('mysql:host=127.0.0.1;port=3306;dbname=kpicycloid;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
}

function getProd() {
    $dotenv = @parse_ini_file(__DIR__ . '/../.env.production');
    return new PDO(
        "mysql:host={$dotenv['DB_HOST']};port={$dotenv['DB_PORT']};dbname={$dotenv['DB_NAME']};charset=utf8mb4",
        $dotenv['DB_USER'], $dotenv['DB_PASS'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false]
    );
}

function fixDates($pdo, $nombre) {
    echo "\n=== {$nombre} ===\n";

    // Conciliacion bancaria
    $sql = "UPDATE tbl_conciliacion_bancaria
            SET fecha_sistema = DATE(CONCAT(YEAR(fecha_sistema), '-', LPAD(DAY(fecha_sistema), 2, '0'), '-', LPAD(MONTH(fecha_sistema), 2, '0')))
            WHERE DAY(fecha_sistema) = mes
            AND MONTH(fecha_sistema) != mes
            AND DAY(fecha_sistema) <= 12";
    $count = $pdo->exec($sql);
    echo "Conciliacion fechas corregidas: {$count}\n";

    // Facturacion - fecha_elaboracion
    $sql2 = "UPDATE tbl_facturacion
             SET fecha_elaboracion = DATE(CONCAT(YEAR(fecha_elaboracion), '-', LPAD(DAY(fecha_elaboracion), 2, '0'), '-', LPAD(MONTH(fecha_elaboracion), 2, '0')))
             WHERE DAY(fecha_elaboracion) = mes
             AND MONTH(fecha_elaboracion) != mes
             AND DAY(fecha_elaboracion) <= 12";
    $count2 = $pdo->exec($sql2);
    echo "Facturacion fecha_elaboracion corregidas: {$count2}\n";

    // Facturacion - fecha_pago
    $sql3 = "UPDATE tbl_facturacion
             SET fecha_pago = DATE(CONCAT(YEAR(fecha_pago), '-', LPAD(DAY(fecha_pago), 2, '0'), '-', LPAD(MONTH(fecha_pago), 2, '0')))
             WHERE fecha_pago IS NOT NULL
             AND mes_pago IS NOT NULL
             AND DAY(fecha_pago) = mes_pago
             AND MONTH(fecha_pago) != mes_pago
             AND DAY(fecha_pago) <= 12";
    $count3 = $pdo->exec($sql3);
    echo "Facturacion fecha_pago corregidas: {$count3}\n";
}

if ($env === 'local' || $env === 'ambas') {
    fixDates(getLocal(), 'LOCAL');
}
if ($env === 'production' || $env === 'ambas') {
    fixDates(getProd(), 'PRODUCCIÓN');
}

echo "\n=== Completado ===\n";
