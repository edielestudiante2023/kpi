<?php
/**
 * Migración: Rol 'contador' (solo lectura) + accesos al módulo Conciliaciones
 *            + usuario Nohora Elizabeth Reyes Riaño (contadora externa).
 *
 * Uso:  php migrations/2026_05_12_rol_contador_y_usuario_nohora.php [local|production]
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

echo "=== Migración rol contador + usuario Nohora — entorno: {$env} ===\n\n";

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

$ROL_ID = 5;
$CORREO = 'contador.reyesr@gmail.com';
$NOMBRE = 'NOHORA ELIZABETH REYES RIAÑO';
$PASSWORD_PLANO = 'Colombia2026+'; // El usuario la cambiará al primer login

$allOk = true;

// ─── Paso 1: Crear rol 'contador' ───
echo "\n--- Paso 1: Crear rol 'contador' (id={$ROL_ID}) ---\n";
$stmt = $pdo->prepare("SELECT COUNT(*) FROM roles WHERE id_roles = ?");
$stmt->execute([$ROL_ID]);
if ((int) $stmt->fetchColumn() > 0) {
    echo "[SKIP] Rol con id={$ROL_ID} ya existe.\n";
} else {
    try {
        $pdo->prepare("INSERT INTO roles (id_roles, nombre_rol) VALUES (?, ?)")
            ->execute([$ROL_ID, 'contador']);
        echo "[OK] Rol 'contador' creado.\n";
    } catch (PDOException $e) {
        echo "[ERROR] " . $e->getMessage() . "\n";
        $allOk = false;
    }
}

// ─── Paso 2: Accesos a Conciliaciones para rol 5 ───
$accesos = [
    ['Conciliaciones - Dashboard Financiero', '/conciliaciones/dashboard'],
    ['Conciliaciones - Dashboard Portafolio', '/conciliaciones/dashboard-portafolio'],
    ['Conciliaciones - Estado de la Empresa', '/conciliaciones/balance'],
    ['Conciliaciones - Asesoría IA',          '/conciliaciones/asesoria-ia'],
    ['Conciliaciones - Presupuestos',         '/conciliaciones/presupuestos'],
    ['Conciliaciones - Facturación',          '/conciliaciones/facturacion'],
    ['Conciliaciones - Bancaria',             '/conciliaciones/bancaria'],
    ['Conciliaciones - Deudas',               '/conciliaciones/deudas'],
    ['Conciliaciones - Cuentas de Cobro',     '/conciliaciones/cuentas-cobro'],
    ['Conciliaciones - Clasificación Costos', '/conciliaciones/clasificacion'],
    ['Conciliaciones - Portafolios',          '/conciliaciones/portafolios'],
    ['Conciliaciones - Centros de Costo',     '/conciliaciones/centros-costo'],
    ['Conciliaciones - Cuentas de Banco',     '/conciliaciones/cuentas-banco'],
];

echo "\n--- Paso 2: Insertar accesos a Conciliaciones para rol {$ROL_ID} ---\n";
foreach ($accesos as [$detalle, $enlace]) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM accesos_rol WHERE id_roles = ? AND enlace = ?");
    $stmt->execute([$ROL_ID, $enlace]);
    if ((int) $stmt->fetchColumn() > 0) {
        echo "[SKIP] Acceso '{$detalle}' ya existe.\n";
        continue;
    }
    try {
        $pdo->prepare("INSERT INTO accesos_rol (id_roles, detalle, enlace, estado) VALUES (?, ?, ?, 'activo')")
            ->execute([$ROL_ID, $detalle, $enlace]);
        echo "[OK] Acceso '{$detalle}' insertado.\n";
    } catch (PDOException $e) {
        echo "[ERROR] " . $e->getMessage() . "\n";
        $allOk = false;
        break 1;
    }
}

// ─── Paso 3: Crear usuario Nohora ───
echo "\n--- Paso 3: Crear usuario contadora externa ---\n";
$stmt = $pdo->prepare("SELECT id_users, id_roles, activo FROM users WHERE correo = ?");
$stmt->execute([$CORREO]);
$exists = $stmt->fetch(PDO::FETCH_ASSOC);

if ($exists) {
    // Si existe pero con otro rol, lo actualizo al rol contador
    if ((int) $exists['id_roles'] !== $ROL_ID || (int) $exists['activo'] !== 1) {
        try {
            $pdo->prepare("UPDATE users SET id_roles = ?, activo = 1, nombre_completo = ? WHERE id_users = ?")
                ->execute([$ROL_ID, $NOMBRE, $exists['id_users']]);
            echo "[OK] Usuario actualizado a rol contador (id={$exists['id_users']}).\n";
        } catch (PDOException $e) {
            echo "[ERROR] " . $e->getMessage() . "\n";
            $allOk = false;
        }
    } else {
        echo "[SKIP] Usuario {$CORREO} ya existe con rol contador activo.\n";
    }
} else {
    $hash = password_hash($PASSWORD_PLANO, PASSWORD_DEFAULT);
    try {
        $pdo->prepare("INSERT INTO users
            (nombre_completo, documento_identidad, correo, cargo, password, id_roles, activo, primer_login, jornada, admin_bitacora)
            VALUES (?, ?, ?, ?, ?, ?, 1, 1, 'completa', 0)
        ")->execute([$NOMBRE, 'EXT-NOHORA', $CORREO, 'Contadora externa', $hash, $ROL_ID]);
        $newId = $pdo->lastInsertId();
        echo "[OK] Usuario Nohora creado (id={$newId}, correo={$CORREO}).\n";
        echo "      Password inicial: {$PASSWORD_PLANO} (debe cambiarla al primer login)\n";
    } catch (PDOException $e) {
        echo "[ERROR] " . $e->getMessage() . "\n";
        $allOk = false;
    }
}

echo "\n" . ($allOk ? "=== Migración completada con éxito ===" : "=== Migración falló ===") . "\n";
exit($allOk ? 0 : 1);
