<?php
/**
 * Migración: agregar 'semanal' a ENUM frecuencia y columna meta_semanal
 * Uso: php migrations/2026_05_07_rutinas_frecuencia_semanal.php [host] [port] [db] [user] [pass]
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

// Modificar ENUM frecuencia para incluir 'semanal'
$db->query("ALTER TABLE rutinas_actividades MODIFY COLUMN frecuencia ENUM('L-V','diaria','semanal') NOT NULL DEFAULT 'L-V' COMMENT 'L-V = lunes a viernes, diaria = todos los dias, semanal = N veces a la semana'");
if ($db->error) {
    echo "ERROR ENUM frecuencia: {$db->error}\n";
} else {
    echo "ENUM frecuencia actualizado: ahora acepta 'semanal'\n";
}

// Agregar columna meta_semanal
$r = $db->query("SHOW COLUMNS FROM rutinas_actividades LIKE 'meta_semanal'");
if ($r->num_rows > 0) {
    echo "Columna meta_semanal ya existe.\n";
} else {
    $db->query("ALTER TABLE rutinas_actividades ADD COLUMN meta_semanal INT NULL DEFAULT NULL COMMENT 'Veces por semana requeridas (solo para frecuencia=semanal)' AFTER frecuencia");
    if ($db->error) {
        echo "ERROR meta_semanal: {$db->error}\n";
    } else {
        echo "Columna meta_semanal agregada.\n";
    }
}

// Verificacion
echo "\n=== VERIFICACION ===\n";
$r = $db->query("DESCRIBE rutinas_actividades");
while ($col = $r->fetch_assoc()) {
    if (in_array($col['Field'], ['frecuencia','meta_semanal'])) {
        echo "  {$col['Field']}: {$col['Type']} | Null={$col['Null']} | Default=" . ($col['Default'] ?? 'NULL') . "\n";
    }
}

$db->close();
echo "\nListo.\n";
