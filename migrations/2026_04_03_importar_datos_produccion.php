<?php
/**
 * Importar datos de LOCAL a PRODUCCIÓN
 * - tbl_facturacion (1,333 registros)
 * - tbl_conciliacion_bancaria (3,314 registros)
 *
 * Uso:  php migrations/2026_04_03_importar_datos_produccion.php
 */

echo "=== Importación LOCAL → PRODUCCIÓN ===\n\n";

// Conexión LOCAL
try {
    $local = new PDO('mysql:host=127.0.0.1;port=3306;dbname=kpicycloid;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "[OK] Conexión LOCAL\n";
} catch (PDOException $e) {
    echo "[ERROR] LOCAL: " . $e->getMessage() . "\n";
    exit(1);
}

// Conexión PRODUCCIÓN
$dotenv = @parse_ini_file(__DIR__ . '/../.env.production');
$prodConfig = [
    'host' => $dotenv['DB_HOST'] ?? getenv('DB_HOST'),
    'port' => $dotenv['DB_PORT'] ?? getenv('DB_PORT') ?: 25060,
    'user' => $dotenv['DB_USER'] ?? getenv('DB_USER'),
    'pass' => $dotenv['DB_PASS'] ?? getenv('DB_PASS'),
    'db'   => $dotenv['DB_NAME'] ?? getenv('DB_NAME') ?: 'kpicycloid',
];
try {
    $prod = new PDO(
        "mysql:host={$prodConfig['host']};port={$prodConfig['port']};dbname={$prodConfig['db']};charset=utf8mb4",
        $prodConfig['user'],
        $prodConfig['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ]
    );
    echo "[OK] Conexión PRODUCCIÓN\n";
} catch (PDOException $e) {
    echo "[ERROR] PRODUCCIÓN: " . $e->getMessage() . "\n";
    exit(1);
}

// ── tbl_facturacion ──
echo "\n--- tbl_facturacion ---\n";

$countProd = (int) $prod->query('SELECT COUNT(*) FROM tbl_facturacion')->fetchColumn();
if ($countProd > 0) {
    echo "[SKIP] Ya tiene {$countProd} registros en producción.\n";
} else {
    $rows = $local->query('SELECT * FROM tbl_facturacion ORDER BY id_facturacion')->fetchAll(PDO::FETCH_ASSOC);
    echo "Registros en local: " . count($rows) . "\n";

    if (count($rows) > 0) {
        $cols = array_keys($rows[0]);
        // Excluir id_facturacion y created_at para que se generen en producción
        $cols = array_filter($cols, fn($c) => !in_array($c, ['id_facturacion', 'created_at']));
        $cols = array_values($cols);
        $placeholders = ':' . implode(', :', $cols);
        $colNames = implode(', ', $cols);
        $stmt = $prod->prepare("INSERT INTO tbl_facturacion ({$colNames}) VALUES ({$placeholders})");

        $allDateCols = ['fecha_pago', 'fecha_anticipo', 'fecha_elaboracion', 'fecha_vence'];
        $prod->beginTransaction();
        $insertados = 0;
        $omitidos = 0;
        foreach ($rows as $row) {
            $data = [];
            $skip = false;
            foreach ($cols as $c) {
                $val = $row[$c];
                if (in_array($c, $allDateCols) && ($val === '0000-00-00' || $val === '')) {
                    $val = null;
                }
                // Si fecha_elaboracion es null, omitir registro (NOT NULL en BD)
                if ($c === 'fecha_elaboracion' && $val === null) {
                    $skip = true;
                    break;
                }
                $data[$c] = $val;
            }
            if ($skip) { $omitidos++; continue; }
            $stmt->execute($data);
            $insertados++;
        }
        $prod->commit();
        if ($omitidos > 0) echo "[WARN] Omitidos {$omitidos} registros sin fecha_elaboracion\n";
        echo "[OK] Insertados: {$insertados}\n";
    }
}

// ── tbl_conciliacion_bancaria ──
echo "\n--- tbl_conciliacion_bancaria ---\n";

$countProd = (int) $prod->query('SELECT COUNT(*) FROM tbl_conciliacion_bancaria')->fetchColumn();
if ($countProd > 0) {
    echo "[SKIP] Ya tiene {$countProd} registros en producción.\n";
} else {
    $rows = $local->query('SELECT * FROM tbl_conciliacion_bancaria ORDER BY id_conciliacion')->fetchAll(PDO::FETCH_ASSOC);
    echo "Registros en local: " . count($rows) . "\n";

    if (count($rows) > 0) {
        $cols = array_keys($rows[0]);
        $cols = array_filter($cols, fn($c) => !in_array($c, ['id_conciliacion', 'created_at']));
        $cols = array_values($cols);
        $placeholders = ':' . implode(', :', $cols);
        $colNames = implode(', ', $cols);
        $stmt = $prod->prepare("INSERT INTO tbl_conciliacion_bancaria ({$colNames}) VALUES ({$placeholders})");

        $prod->beginTransaction();
        $insertados = 0;
        foreach ($rows as $row) {
            $data = [];
            foreach ($cols as $c) {
                $data[$c] = $row[$c];
            }
            $stmt->execute($data);
            $insertados++;
        }
        $prod->commit();
        echo "[OK] Insertados: {$insertados}\n";
    }
}

// Verificar
echo "\n=== VERIFICACIÓN PRODUCCIÓN ===\n";
$f = $prod->query('SELECT COUNT(*) FROM tbl_facturacion')->fetchColumn();
$c = $prod->query('SELECT COUNT(*) FROM tbl_conciliacion_bancaria')->fetchColumn();
echo "tbl_facturacion: {$f}\n";
echo "tbl_conciliacion_bancaria: {$c}\n";
echo "\n=== Completado ===\n";
