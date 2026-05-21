<?php
/**
 * Migración: fecha_nacimiento y cumple_silenciado_hasta en users + poblar fechas
 * Uso: php migrations/2026_05_21_cumpleanos_usuarios.php [host] [port] [db] [user] [pass]
 */
$host = $argv[1] ?? '127.0.0.1';
$port = (int)($argv[2] ?? 3306);
$dbname = $argv[3] ?? 'kpicycloid';
$user = $argv[4] ?? 'root';
$pass = $argv[5] ?? '';

$db = new mysqli($host, $user, $pass, $dbname, $port);
if ($db->connect_error) die("Error conexion: {$db->connect_error}\n");
$db->set_charset('utf8mb4');
echo "Conectado a {$host}:{$port}/{$dbname}\n\n";

// Columna fecha_nacimiento
$r = $db->query("SHOW COLUMNS FROM users LIKE 'fecha_nacimiento'");
if ($r->num_rows > 0) {
    echo "Columna fecha_nacimiento ya existe.\n";
} else {
    $db->query("ALTER TABLE users ADD COLUMN fecha_nacimiento DATE NULL DEFAULT NULL COMMENT 'Fecha de nacimiento para recordatorio de cumpleanos'");
    echo $db->error ? "ERROR fecha_nacimiento: {$db->error}\n" : "Columna fecha_nacimiento agregada.\n";
}

// Columna cumple_silenciado_hasta
$r = $db->query("SHOW COLUMNS FROM users LIKE 'cumple_silenciado_hasta'");
if ($r->num_rows > 0) {
    echo "Columna cumple_silenciado_hasta ya existe.\n";
} else {
    $db->query("ALTER TABLE users ADD COLUMN cumple_silenciado_hasta DATE NULL DEFAULT NULL COMMENT 'Si hoy <= esta fecha, no se envia recordatorio de su cumpleanos'");
    echo $db->error ? "ERROR cumple_silenciado_hasta: {$db->error}\n" : "Columna cumple_silenciado_hasta agregada.\n";
}

// Poblar fechas de nacimiento POR CORREO (consistente entre local y produccion)
echo "\n=== Poblando fechas de nacimiento ===\n";
$fechas = [
    'diana.cuestas@cycloidtalent.com'           => '1978-04-20', // Diana
    'edison.cuervo@cycloidtalent.com'           => '1981-06-13', // Edison
    'head.consultant.cycloidtalent@gmail.com'   => '1981-06-13', // Edison (2da cuenta, solo local)
    'eleyson.segura@cycloidtalent.com'          => '1997-05-30', // Eleyson
    'natalia.jimenez@cycloidtalent.com'         => '1991-11-13', // Natalia
    'solangel.cuervo@cycloidtalent.com'         => '2007-10-18', // Solangel
];

foreach ($fechas as $correo => $fecha) {
    $stmt = $db->prepare("SELECT id_users, nombre_completo FROM users WHERE correo = ?");
    $stmt->bind_param('s', $correo);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        echo "  {$correo}: NO existe en esta BD, omitido.\n";
        continue;
    }
    $row = $res->fetch_assoc();
    $upd = $db->prepare("UPDATE users SET fecha_nacimiento = ? WHERE correo = ?");
    $upd->bind_param('ss', $fecha, $correo);
    $upd->execute();
    echo "  {$row['nombre_completo']} ({$correo}): fecha_nacimiento = {$fecha}\n";
}

// Verificación
echo "\n=== VERIFICACION ===\n";
$r = $db->query("SELECT id_users, nombre_completo, fecha_nacimiento FROM users WHERE fecha_nacimiento IS NOT NULL ORDER BY MONTH(fecha_nacimiento), DAY(fecha_nacimiento)");
while ($row = $r->fetch_assoc()) {
    echo "  {$row['nombre_completo']}: {$row['fecha_nacimiento']}\n";
}

$db->close();
echo "\nListo.\n";
