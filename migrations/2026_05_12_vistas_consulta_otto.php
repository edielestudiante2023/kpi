<?php
/**
 * Migración: VIEWS de consulta para que OTTO pueda responder preguntas
 * cruzadas (cliente 360, factura con contexto, recaudo con metadatos).
 *
 * Idempotente: usa CREATE OR REPLACE VIEW.
 *
 * Uso:  php migrations/2026_05_12_vistas_consulta_otto.php [local|production]
 */

$env = $argv[1] ?? 'local';

if ($env === 'production') {
    $dotenv = @parse_ini_file(__DIR__ . '/../.env.production');
    $config = [
        'host'     => $dotenv['DB_HOST'] ?? getenv('DB_HOST'),
        'port'     => $dotenv['DB_PORT'] ?? getenv('DB_PORT') ?: 25060,
        'username' => $dotenv['DB_USER'] ?? getenv('DB_USER'),
        'password' => $dotenv['DB_PASS'] ?? getenv('DB_PASS'),
        'database' => $dotenv['DB_NAME'] ?? getenv('DB_NAME') ?: 'kpicycloid',
    ];
} else {
    $config = [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'username' => 'root',
        'password' => '',
        'database' => 'kpicycloid',
    ];
}

echo "=== Migración VIEWS consulta OTTO — entorno: {$env} ===\n\n";

$dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ]);
    echo "[OK] Conexión exitosa a {$env}\n";
} catch (PDOException $e) {
    echo "[ERROR] No se pudo conectar: " . $e->getMessage() . "\n";
    exit(1);
}

$views = [];

// ─── vw_cliente_360 ───────────────────────────────────────────────
$views[] = [
    'name' => 'vw_cliente_360',
    'sql' => "CREATE OR REPLACE VIEW vw_cliente_360 AS
        SELECT
            f.identificacion AS nit,
            MAX(f.nombre_tercero) AS nombre_cliente,
            p.portafolio,
            f.id_portafolio,
            COUNT(*) AS total_facturas,
            SUM(CASE WHEN f.pagado = 1 THEN 1 ELSE 0 END) AS facturas_pagadas,
            SUM(CASE WHEN f.pagado = 0 THEN 1 ELSE 0 END) AS facturas_pendientes,
            ROUND(SUM(f.base_gravada), 2) AS total_facturado_bruto,
            ROUND(SUM(COALESCE(f.valor_pagado, 0)), 2) AS total_recaudado,
            ROUND(SUM(CASE WHEN f.pagado = 0
                THEN GREATEST(0, f.base_gravada - COALESCE(f.valor_pagado,0) - COALESCE(f.anticipo,0))
                ELSE 0 END), 2) AS saldo_pendiente,
            MIN(f.fecha_elaboracion) AS fecha_primera_factura,
            MAX(f.fecha_elaboracion) AS fecha_ultima_factura,
            MAX(f.fecha_pago) AS fecha_ultimo_pago
        FROM tbl_facturacion f
        LEFT JOIN tbl_portafolios p ON p.id_portafolio = f.id_portafolio
        WHERE f.identificacion IS NOT NULL
        GROUP BY f.identificacion, f.id_portafolio, p.portafolio",
];

// ─── vw_factura_detalle ───────────────────────────────────────────
$views[] = [
    'name' => 'vw_factura_detalle',
    'sql' => "CREATE OR REPLACE VIEW vw_factura_detalle AS
        SELECT
            f.id_facturacion,
            f.comprobante,
            f.numero_factura,
            f.fecha_elaboracion,
            f.fecha_vence,
            f.identificacion AS nit_cliente,
            f.nombre_tercero AS cliente,
            f.sucursal,
            f.id_portafolio,
            p.portafolio,
            f.portafolio_detallado,
            f.vendedor,
            f.anio,
            f.mes,
            f.semana,
            f.base_gravada,
            f.iva,
            f.retefuente_4,
            f.total,
            f.pagado,
            f.fecha_pago,
            f.valor_pagado,
            f.fecha_anticipo,
            f.anticipo,
            f.estado_pago,
            CASE
                WHEN f.estado_pago = 'castigada' THEN 'CASTIGADA'
                WHEN f.estado_pago = 'brecha'    THEN 'BRECHA'
                WHEN f.estado_pago = 'anticipo'  THEN 'ANTICIPO'
                WHEN f.pagado = 1                THEN 'PAGADA'
                WHEN f.fecha_elaboracion IS NOT NULL
                     AND DATEDIFF(CURDATE(), f.fecha_elaboracion) > 30
                     AND f.pagado = 0 THEN 'VENCIDA'
                ELSE 'PENDIENTE'
            END AS estado_calculado,
            CASE
                WHEN f.pagado = 1 OR f.fecha_elaboracion IS NULL THEN NULL
                ELSE GREATEST(0, DATEDIFF(CURDATE(), f.fecha_elaboracion) - 30)
            END AS dias_mora,
            ROUND(GREATEST(0, f.base_gravada - COALESCE(f.valor_pagado,0) - COALESCE(f.anticipo,0)), 2) AS saldo_actual,
            CASE
                WHEN f.fecha_pago IS NOT NULL AND f.fecha_elaboracion IS NOT NULL
                THEN DATEDIFF(f.fecha_pago, f.fecha_elaboracion)
                ELSE NULL
            END AS dias_para_cobrar
        FROM tbl_facturacion f
        LEFT JOIN tbl_portafolios p ON p.id_portafolio = f.id_portafolio",
];

// ─── vw_recaudo_detalle ───────────────────────────────────────────
$views[] = [
    'name' => 'vw_recaudo_detalle',
    'sql' => "CREATE OR REPLACE VIEW vw_recaudo_detalle AS
        SELECT
            cb.id_conciliacion,
            cb.id_cuenta_banco,
            cu.nombre_cuenta,
            cb.id_centro_costo,
            cc.centro_costo,
            cb.llave_item,
            cb.deb_cred,
            cb.fv,
            cb.item_cliente,
            cb.anio,
            cb.mes,
            cb.mes_real,
            cb.semana,
            cb.valor,
            cb.fecha_sistema,
            cb.documento,
            cb.descripcion_motivo,
            cb.transaccion,
            cb.oficina_recaudo,
            cb.nit_originador,
            cb.referencia_1,
            cb.referencia_2,
            cl.categoria,
            cl.tipo AS tipo_categoria
        FROM tbl_conciliacion_bancaria cb
        LEFT JOIN tbl_cuentas_banco cu  ON cu.id_cuenta_banco = cb.id_cuenta_banco
        LEFT JOIN tbl_centros_costo cc  ON cc.id_centro_costo = cb.id_centro_costo
        LEFT JOIN tbl_clasificacion_costos cl ON cl.llave_item = cb.llave_item",
];

$allOk = true;
foreach ($views as $i => $v) {
    $num = $i + 1;
    echo "\n--- Paso {$num}: CREATE OR REPLACE VIEW {$v['name']} ---\n";
    try {
        $pdo->exec($v['sql']);
        $count = (int) $pdo->query("SELECT COUNT(*) FROM {$v['name']}")->fetchColumn();
        echo "[OK] View '{$v['name']}' creada/actualizada. Filas accesibles: {$count}\n";
    } catch (PDOException $e) {
        echo "[ERROR] " . $e->getMessage() . "\n";
        $allOk = false;
        break;
    }
}

echo "\n" . ($allOk ? "=== Migración completada con éxito ===" : "=== Migración falló ===") . "\n";
exit($allOk ? 0 : 1);
