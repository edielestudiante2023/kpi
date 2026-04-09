<?php
/**
 * Migración: tbl_clasificacion_costos
 * Mapea cada llave_item de conciliación bancaria a una categoría y tipo (fijo/variable/ingreso/neutro)
 *
 * Uso:  php migrations/2026_04_03_tbl_clasificacion_costos.php [local|production]
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

echo "=== Migración tbl_clasificacion_costos — entorno: {$env} ===\n\n";

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

$steps = [];

$steps[] = [
    'name' => 'Crear tabla tbl_clasificacion_costos',
    'check' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$config['database']}' AND TABLE_NAME = 'tbl_clasificacion_costos'",
    'sql' => "CREATE TABLE tbl_clasificacion_costos (
        id_clasificacion INT AUTO_INCREMENT PRIMARY KEY,
        llave_item VARCHAR(255) NOT NULL,
        categoria VARCHAR(100) NOT NULL COMMENT 'Agrupación: NOMINA, HONORARIOS, IMPUESTOS, etc.',
        tipo ENUM('fijo', 'variable', 'ingreso', 'neutro') NOT NULL DEFAULT 'fijo',
        UNIQUE KEY uk_llave (llave_item),
        KEY idx_categoria (categoria),
        KEY idx_tipo (tipo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
];

$steps[] = [
    'name' => 'Insertar clasificación inicial',
    'check' => "SELECT COUNT(*) FROM tbl_clasificacion_costos",
    'sql' => "INSERT INTO tbl_clasificacion_costos (llave_item, categoria, tipo) VALUES
        -- INGRESOS
        ('PAGO CLIENTE', 'INGRESOS CLIENTES', 'ingreso'),
        ('PAGO DE CLIENTE', 'INGRESOS CLIENTES', 'ingreso'),
        ('RENDIMIENTO FINANCIERO', 'RENDIMIENTOS', 'ingreso'),
        ('RENDIMIENTOS', 'RENDIMIENTOS', 'ingreso'),
        ('RENDIMIENTOS FINANCIEROS', 'RENDIMIENTOS', 'ingreso'),
        ('INTERESES BANCARIOS', 'RENDIMIENTOS', 'ingreso'),
        ('APORTE CAPITAL SOCIAL', 'CAPITAL', 'neutro'),
        ('DEVOLUCION', 'DEVOLUCIONES', 'neutro'),
        ('REINTEGRO', 'DEVOLUCIONES', 'neutro'),
        ('RETORNO DE TRANSFERENCIA', 'DEVOLUCIONES', 'neutro'),
        ('RECHAZO TRANSACCION', 'DEVOLUCIONES', 'neutro'),
        ('RECHAZO FINANCIERO', 'DEVOLUCIONES', 'neutro'),
        ('TRANSACCION RECHADA', 'DEVOLUCIONES', 'neutro'),
        ('AVANCE TC', 'CAPITAL', 'neutro'),

        -- NOMINA (fijo)
        ('NOMINA', 'NOMINA', 'fijo'),
        ('PAGO NOMINA', 'NOMINA', 'fijo'),
        ('NOMINA 30 ENE', 'NOMINA', 'fijo'),
        ('NOMINA FEB 2023', 'NOMINA', 'fijo'),
        ('NOMINA FEB 2025', 'NOMINA', 'fijo'),
        ('NOMINA MARZO 2023', 'NOMINA', 'fijo'),
        ('NOMINA MARZO 2024', 'NOMINA', 'fijo'),
        ('NOMINA MARZO 2025', 'NOMINA', 'fijo'),
        ('NOMINA ABRIL 2023', 'NOMINA', 'fijo'),
        ('NOMINA ABRIL 2024', 'NOMINA', 'fijo'),
        ('NOMINA ABRIL 2025', 'NOMINA', 'fijo'),
        ('NOMINA MAYO 2023', 'NOMINA', 'fijo'),
        ('NOMINA MAYO 2024', 'NOMINA', 'fijo'),
        ('NOMINA MAYO 2025', 'NOMINA', 'fijo'),
        ('NOMINA JUNIO 2023', 'NOMINA', 'fijo'),
        ('NOMINA JUNIO 2024', 'NOMINA', 'fijo'),
        ('NOMINA JULIO 2023', 'NOMINA', 'fijo'),
        ('NOMINA JULIO 2023 - LIQUIDACIÓN', 'NOMINA', 'fijo'),
        ('NOMINA JULIO 2024', 'NOMINA', 'fijo'),
        ('NOMINA AGOSTO 2023', 'NOMINA', 'fijo'),
        ('NOMINA AGOSTO 2024', 'NOMINA', 'fijo'),
        ('NOMINA SEPTIEMBRE 2023', 'NOMINA', 'fijo'),
        ('NOMINA SEPTIEMBRE 2024', 'NOMINA', 'fijo'),
        ('NOMINA OCTUBRE 2023', 'NOMINA', 'fijo'),
        ('NOMINA OCTUBRE 2024', 'NOMINA', 'fijo'),
        ('NOMINA NOVIEMBRE 2023', 'NOMINA', 'fijo'),
        ('NOMINA NOVIEMBRE 2024', 'NOMINA', 'fijo'),
        ('NOMINA DICIEMBRE 2023', 'NOMINA', 'fijo'),
        ('NOMINA DICIEMBRE 2024', 'NOMINA', 'fijo'),
        ('NOMINA ENERO 2024', 'NOMINA', 'fijo'),
        ('NOMINA ENERO 2025', 'NOMINA', 'fijo'),
        ('NOMINA FEBRERO 2024', 'NOMINA', 'fijo'),
        ('PAGO NOMINA 2025', 'NOMINA', 'fijo'),
        ('PAGO NOMINA ENERO 2025', 'NOMINA', 'fijo'),
        ('PAGO NOMINA MARZO', 'NOMINA', 'fijo'),
        ('PAGO NOMINA MARZO 2025', 'NOMINA', 'fijo'),
        ('PAGO NOMINA MARZO - AUX TRANS', 'NOMINA', 'fijo'),
        ('PAGO NOMINA MARZO . AUX TRANS', 'NOMINA', 'fijo'),
        ('PAGO NOMINA ABRIL 2025', 'NOMINA', 'fijo'),
        ('PAGO NOMINA MAYO 2025', 'NOMINA', 'fijo'),
        ('PAGO NOMINA JUNIO 2025', 'NOMINA', 'fijo'),
        ('PAGO NOMINA JULIO', 'NOMINA', 'fijo'),
        ('PAGO NOMINA JULIO 2025', 'NOMINA', 'fijo'),
        ('PAGO NOMINA AGOSTO 2025', 'NOMINA', 'fijo'),
        ('PAGO NOMINA SEPTIEMBRE', 'NOMINA', 'fijo'),
        ('PAGO NOMINA OCTUBRE 2025', 'NOMINA', 'fijo'),
        ('PAGO NOMINA DIC', 'NOMINA', 'fijo'),
        ('ADELANTO NOMINA ENERO', 'NOMINA', 'fijo'),
        ('PAGO ADELANTO NOMINA', 'NOMINA', 'fijo'),
        ('RECONSIGNACIÓN NOMINA FEB 23', 'NOMINA', 'fijo'),
        ('PAGO LIQUIDACIÓN', 'NOMINA', 'fijo'),
        ('LIQUIDACIÓN', 'NOMINA', 'fijo'),
        ('LIQUIDACIÓN ANGIE PESILLO', 'NOMINA', 'fijo'),

        -- AUX TRANSPORTE (fijo)
        ('AUX CELULAR', 'NOMINA', 'fijo'),
        ('AUX TRANSPORTE', 'NOMINA', 'fijo'),
        ('AUX TRANSPORTE EDISION CUERVO', 'NOMINA', 'fijo'),
        ('AUX. DE TRANSPORTE', 'NOMINA', 'fijo'),
        ('AUX. TRANSPORTE', 'NOMINA', 'fijo'),
        ('PAGO AUX TRANSPORTE', 'NOMINA', 'fijo'),
        ('PAGO AUX. TRANSPORTE', 'NOMINA', 'fijo'),
        ('PAGO BONIFICACION', 'NOMINA', 'fijo'),

        -- SEGURIDAD SOCIAL (fijo)
        ('PAGO PLANILLA', 'SEGURIDAD SOCIAL', 'fijo'),
        ('PAGO PLANILLA JULIO 2023', 'SEGURIDAD SOCIAL', 'fijo'),
        ('PAGO PLANILLA PILA', 'SEGURIDAD SOCIAL', 'fijo'),
        ('PLANILLA DIC', 'SEGURIDAD SOCIAL', 'fijo'),
        ('PLANILLA ENE', 'SEGURIDAD SOCIAL', 'fijo'),
        ('PLANILLA OCT 2023', 'SEGURIDAD SOCIAL', 'fijo'),
        ('PLANILLA TERCERIZADA AGO 2023', 'SEGURIDAD SOCIAL', 'fijo'),
        ('PLANILLA TERCERIZADA SEPT2023', 'SEGURIDAD SOCIAL', 'fijo'),
        ('SEGURIDAD SOACIAL', 'SEGURIDAD SOCIAL', 'fijo'),

        -- HONORARIOS (fijo)
        ('PAGO HONORARIOS', 'HONORARIOS', 'fijo'),
        ('PAGO HONORARIOS ABRIL 2025', 'HONORARIOS', 'fijo'),
        ('PAGO HONORARIOS AGOSTO 2025', 'HONORARIOS', 'fijo'),
        ('PAGO HONORARIOS DAVID RINCON', 'HONORARIOS', 'fijo'),
        ('PAGO HONORARIOS EDISON CUERVO', 'HONORARIOS', 'fijo'),
        ('PAGO HONORARIOS EDWIN LOPEZ', 'HONORARIOS', 'fijo'),
        ('PAGO HONORARIOS FRANCISCO NEUTA', 'HONORARIOS', 'fijo'),
        ('PAGO HONORARIOS JULIO 2025', 'HONORARIOS', 'fijo'),
        ('PAGO HONORARIOS LUISA CEBALLOS', 'HONORARIOS', 'fijo'),
        ('PAGO HONORARIOS MARZO 2025', 'HONORARIOS', 'fijo'),
        ('PAGO HONORARIOS MAYO 2025', 'HONORARIOS', 'fijo'),
        ('PAGO HONORARIOS SEP', 'HONORARIOS', 'fijo'),
        ('PAGO HONORARIOS SEPTIEMBRE', 'HONORARIOS', 'fijo'),
        ('PAGO HONRARIOS', 'HONORARIOS', 'fijo'),
        ('HONORARIOS MARZO 2025', 'HONORARIOS', 'fijo'),
        ('ABONO HONORARIOS', 'HONORARIOS', 'fijo'),

        -- CONTABILIDAD (fijo)
        ('CONTADOR ABRIL 2023', 'CONTABILIDAD', 'fijo'),
        ('CONTADOR AGOSTO 2023', 'CONTABILIDAD', 'fijo'),
        ('CONTADOR ENERO', 'CONTABILIDAD', 'fijo'),
        ('CONTADOR FEBRERO', 'CONTABILIDAD', 'fijo'),
        ('CONTADOR JULIO 2023', 'CONTABILIDAD', 'fijo'),
        ('CONTADOR JUNIO 2023', 'CONTABILIDAD', 'fijo'),
        ('CONTADOR MARZO 2023', 'CONTABILIDAD', 'fijo'),
        ('CONTADOR MAYO 2023', 'CONTABILIDAD', 'fijo'),
        ('CONTADOR NOVIEMBRE', 'CONTABILIDAD', 'fijo'),
        ('CONTADOR OCTUBRE 2023', 'CONTABILIDAD', 'fijo'),
        ('CONTADOR SEPTIEMBRE 2023', 'CONTABILIDAD', 'fijo'),
        ('ASESORIA SEG SOCIAL', 'CONTABILIDAD', 'fijo'),

        -- GASTOS FINANCIEROS (fijo)
        ('GASTO FINANCIERO', 'GASTOS FINANCIEROS', 'fijo'),
        ('GASTO FINANCIERA', 'GASTOS FINANCIEROS', 'fijo'),
        ('GASTOS FINANCIERO', 'GASTOS FINANCIEROS', 'fijo'),
        ('GASTOS FINANCIEROS', 'GASTOS FINANCIEROS', 'fijo'),
        ('COBRO BANCARIO', 'GASTOS FINANCIEROS', 'fijo'),

        -- HERRAMIENTAS DIGITALES (fijo)
        ('CHAMILO', 'HERRAMIENTAS DIGITALES', 'fijo'),
        ('PAGO CHAMILO ANUAL', 'HERRAMIENTAS DIGITALES', 'fijo'),
        ('CHAT GPT', 'HERRAMIENTAS DIGITALES', 'fijo'),
        ('CHAT GPT4', 'HERRAMIENTAS DIGITALES', 'fijo'),
        ('PAGO CHAT GPT', 'HERRAMIENTAS DIGITALES', 'fijo'),
        ('PAGO CHAT GPT 2 ENERO 2025', 'HERRAMIENTAS DIGITALES', 'fijo'),
        ('PAGO CHAT GPT 4 EDISON CUERVO', 'HERRAMIENTAS DIGITALES', 'fijo'),
        ('PAGO CHAT GPT 4 EDISON CUERVO FEB 2025', 'HERRAMIENTAS DIGITALES', 'fijo'),
        ('PAGO CHAT GPT4', 'HERRAMIENTAS DIGITALES', 'fijo'),
        ('HERRAMIENTAS DIGITALES', 'HERRAMIENTAS DIGITALES', 'fijo'),
        ('PAGO HERRAMIENTAS DIGITALES', 'HERRAMIENTAS DIGITALES', 'fijo'),
        ('PAGO HERRAMIETNAS DIGITALES', 'HERRAMIENTAS DIGITALES', 'fijo'),
        ('LICENCIAS Y HERRAMIENTAS', 'HERRAMIENTAS DIGITALES', 'fijo'),
        ('COMPRA CANVA', 'HERRAMIENTAS DIGITALES', 'fijo'),
        ('COMPRA DE CANVA PRO', 'HERRAMIENTAS DIGITALES', 'fijo'),
        ('COMPRA LICENCIA', 'HERRAMIENTAS DIGITALES', 'fijo'),
        ('LICENCIA DOMINIO Y HOSTING 2024', 'HERRAMIENTAS DIGITALES', 'fijo'),
        ('SERVIDOR Y WEBSITE', 'HERRAMIENTAS DIGITALES', 'fijo'),
        ('SIIGO', 'HERRAMIENTAS DIGITALES', 'fijo'),
        ('COMPUTRABAJO', 'HERRAMIENTAS DIGITALES', 'fijo'),
        ('COMPUTRABAJO SIIGO', 'HERRAMIENTAS DIGITALES', 'fijo'),
        ('CREDITO COMPUTRABAJO SIIGO', 'HERRAMIENTAS DIGITALES', 'fijo'),
        ('DESARROLLO', 'HERRAMIENTAS DIGITALES', 'fijo'),

        -- CAMARA DE COMERCIO (fijo)
        ('CAMARA DE COMERCIO', 'CAMARA DE COMERCIO', 'fijo'),
        ('CAMARA Y CIO', 'CAMARA DE COMERCIO', 'fijo'),
        ('CCB', 'CAMARA DE COMERCIO', 'fijo'),
        ('COMPRA CAMARA DE COMERCIO', 'CAMARA DE COMERCIO', 'fijo'),
        ('PAGO CAMARA COMERCIO', 'CAMARA DE COMERCIO', 'fijo'),
        ('PAGO CAMARA DE CIO', 'CAMARA DE COMERCIO', 'fijo'),
        ('RENOVACION CAMARA', 'CAMARA DE COMERCIO', 'fijo'),

        -- OFICINA (fijo)
        ('ADECUACION OFICINA', 'OFICINA', 'fijo'),
        ('COMPRA ADECUACION OFI', 'OFICINA', 'fijo'),
        ('ASEO OFICINA', 'OFICINA', 'fijo'),
        ('COMPRA MOBILIARIO', 'OFICINA', 'fijo'),
        ('PAGO MUEBLES', 'OFICINA', 'fijo'),
        ('CARNET OFICINA', 'OFICINA', 'fijo'),
        ('MANTENIMIENTO COMPUTADOR', 'OFICINA', 'fijo'),

        -- IMPUESTOS (variable)
        ('IMPUESTOS', 'IMPUESTOS', 'variable'),
        ('IVA', 'IMPUESTOS', 'variable'),
        ('PAGO DIAN', 'IMPUESTOS', 'variable'),
        ('PAGO DIAN IVA', 'IMPUESTOS', 'variable'),
        ('PAGO DIAN RETENCION', 'IMPUESTOS', 'variable'),
        ('PAGO IMPUESTO IVA', 'IMPUESTOS', 'variable'),
        ('PAGO IMPUESTOS', 'IMPUESTOS', 'variable'),
        ('PAGO IVA', 'IMPUESTOS', 'variable'),
        ('PAGO RETE FUENTE', 'IMPUESTOS', 'variable'),
        ('PAGO RETEFUENTE', 'IMPUESTOS', 'variable'),

        -- OPERATIVO VARIABLE
        ('PAGO ESTUDIOS DE CONFIABILIDAD', 'OPERATIVO', 'variable'),
        ('ESTUDIO CONFIABILIDAD', 'OPERATIVO', 'variable'),
        ('EST SEGURIDAD ARDURRA', 'OPERATIVO', 'variable'),
        ('EXAMED', 'OPERATIVO', 'variable'),
        ('EXAMENES MEDICOS', 'OPERATIVO', 'variable'),
        ('POLIGRAFIA', 'OPERATIVO', 'variable'),
        ('VISITA DOMICILIARIA', 'OPERATIVO', 'variable'),
        ('VISITAS DOMICILIARIAS', 'OPERATIVO', 'variable'),
        ('COMPRA CARTILLAS', 'OPERATIVO', 'variable'),
        ('COMPRA DE CARTILLAS', 'OPERATIVO', 'variable'),
        ('PAGO CARTILLAS', 'OPERATIVO', 'variable'),
        ('PAGO ENVIO CARTILLAS', 'OPERATIVO', 'variable'),
        ('COMPRA PARA CLIENTE', 'OPERATIVO', 'variable'),
        ('SST APOYO ELKIN', 'OPERATIVO', 'variable'),
        ('VISITA AFIANCOL', 'OPERATIVO', 'variable'),
        ('VISITA CLIENTE', 'OPERATIVO', 'variable'),
        ('PAGO COMISIONES PAOLA', 'OPERATIVO', 'variable'),
        ('COMISION POR REFERIDO', 'OPERATIVO', 'variable'),

        -- BIENESTAR (fijo)
        ('BIENESTAR', 'BIENESTAR', 'fijo'),
        ('GASTO BIENESTAR', 'BIENESTAR', 'fijo'),
        ('ACTIVIDAD 21 DIC 2024', 'BIENESTAR', 'fijo'),
        ('ACTIVIDAD FIN DE AÑO', 'BIENESTAR', 'fijo'),
        ('FIN DE AÑO', 'BIENESTAR', 'fijo'),
        ('COMPRA BONO CUMPLEAÑOS', 'BIENESTAR', 'fijo'),
        ('COMPRA DULCES', 'BIENESTAR', 'fijo'),
        ('COMPRA OBSEQUIOS', 'BIENESTAR', 'fijo'),
        ('PAGO TOTAL REGALOS', 'BIENESTAR', 'fijo'),
        ('ARROZ CON LECHE', 'BIENESTAR', 'fijo'),
        ('CAFE CON PAN', 'BIENESTAR', 'fijo'),
        ('PAGO CAFE', 'BIENESTAR', 'fijo'),
        ('ALMUERZO DE TRABAJO', 'BIENESTAR', 'fijo'),
        ('PAGO ALMUERZO', 'BIENESTAR', 'fijo'),
        ('PAGO ALMUERZO DE TRABAJO', 'BIENESTAR', 'fijo'),
        ('INVERSION EVENTO', 'BIENESTAR', 'fijo'),
        ('PAGO EVENTO', 'BIENESTAR', 'fijo'),
        ('PAGO CURSO 20 HORAS EDISION CUERVO', 'BIENESTAR', 'fijo'),
        ('PAGO POLITECNICO', 'BIENESTAR', 'fijo'),

        -- TRANSPORTE (variable)
        ('GASTO COMBUSTIBLE', 'TRANSPORTE', 'variable'),
        ('GASTOS DE TRANSPORTE', 'TRANSPORTE', 'variable'),
        ('PAGO GASOLINA', 'TRANSPORTE', 'variable'),
        ('PAGO GASOLINA EVENTO', 'TRANSPORTE', 'variable'),
        ('PAGO PARQUEADERO', 'TRANSPORTE', 'variable'),
        ('PICO Y PLACA', 'TRANSPORTE', 'variable'),
        ('TRANSPORTE', 'TRANSPORTE', 'variable'),

        -- COMPRAS VARIAS (variable)
        ('COMPRAS', 'COMPRAS VARIAS', 'variable'),
        ('PAGO IMPRESION', 'COMPRAS VARIAS', 'variable'),
        ('PAGO POLIZA', 'COMPRAS VARIAS', 'variable'),
        ('PAGO PROVEEDOR', 'COMPRAS VARIAS', 'variable'),
        ('PAGO PROVEEDORES', 'COMPRAS VARIAS', 'variable'),
        ('PROFESSIONAL AND SERVICES', 'COMPRAS VARIAS', 'variable'),
        ('PAGO P&S', 'COMPRAS VARIAS', 'variable'),
        ('LUZ DARY BARRETO', 'COMPRAS VARIAS', 'variable'),

        -- NEUTRO (movimientos entre cuentas)
        ('TRASLADO FONDOS', 'TRASLADOS', 'neutro'),
        ('TRANSLADO FONDOS', 'TRASLADOS', 'neutro'),
        ('TRANSFERENCIA FONDOS', 'TRASLADOS', 'neutro'),
        ('TRASFERENCIA FONDOS', 'TRASLADOS', 'neutro'),
        ('ABONO CYCLOID', 'TRASLADOS', 'neutro'),
        ('CYCLOID TALENT RPS', 'TRASLADOS', 'neutro'),
        ('APORTE A CAPITAL SOCIAL', 'CAPITAL', 'neutro'),
        ('SOCIO CAPITAL', 'CAPITAL', 'neutro'),
        ('PAGO CAPITAL', 'CAPITAL', 'neutro'),
        ('PAGO CAPITAL SOCIO', 'CAPITAL', 'neutro'),
        ('PAGO KAPITAL SOCIO', 'CAPITAL', 'neutro'),
        ('PAGO ABONO CAPITAL', 'CAPITAL', 'neutro'),
        ('PAGO RETORNO CAPITAL', 'CAPITAL', 'neutro'),
        ('INVERSIONES', 'CAPITAL', 'neutro'),
        ('RETIRO CAJERO', 'CAPITAL', 'neutro'),

        -- CREDITOS (neutro)
        ('PAGO DE CRÉDITOS', 'CREDITOS', 'neutro'),
        ('PAGO BEATRIZ PRESTAMO', 'CREDITOS', 'neutro'),

        -- DEVOLUCIONES EGRESO (neutro)
        ('DEVOLUCION ALVAREZ & ASOCIADOS', 'DEVOLUCIONES', 'neutro')",
];

// Ejecutar pasos
$allOk = true;
foreach ($steps as $i => $step) {
    $num = $i + 1;
    echo "\n--- Paso {$num}: {$step['name']} ---\n";

    $stmt = $pdo->query($step['check']);
    $exists = (int) $stmt->fetchColumn();

    if ($exists > 0) {
        echo "[SKIP] Ya existe, no se ejecuta.\n";
        continue;
    }

    try {
        $pdo->exec($step['sql']);
        echo "[OK] Ejecutado correctamente.\n";
    } catch (PDOException $e) {
        echo "[ERROR] " . $e->getMessage() . "\n";
        $allOk = false;
        break;
    }
}

// Verificar cobertura
if ($allOk && $env === 'local') {
    echo "\n--- Verificación de cobertura ---\n";
    $sinClasificar = $pdo->query("
        SELECT cb.llave_item, COUNT(*) as total
        FROM tbl_conciliacion_bancaria cb
        LEFT JOIN tbl_clasificacion_costos cc ON cc.llave_item = cb.llave_item
        WHERE cc.id_clasificacion IS NULL
        GROUP BY cb.llave_item
        ORDER BY total DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (empty($sinClasificar)) {
        echo "[OK] 100% de llave_items clasificados.\n";
    } else {
        echo "[WARN] " . count($sinClasificar) . " llave_items sin clasificar:\n";
        foreach ($sinClasificar as $s) {
            echo "  - {$s['llave_item']} ({$s['total']} registros)\n";
        }
    }
}

echo "\n" . ($allOk ? "=== Migración completada con éxito ===" : "=== Migración falló ===") . "\n";
exit($allOk ? 0 : 1);
