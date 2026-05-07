<?php
/**
 * Migración: agregar columna categoria a rutinas_actividades
 * Uso: php migrations/2026_04_10_add_categoria_rutinas_actividades.php [host] [port] [db] [user] [pass]
 */
$host = $argv[1] ?? '127.0.0.1';
$port = (int)($argv[2] ?? 3306);
$dbname = $argv[3] ?? 'kpicycloid';
$user = $argv[4] ?? 'root';
$pass = $argv[5] ?? '';

$db = new mysqli($host, $user, $pass, $dbname, $port);
if ($db->connect_error) die("Error conexión: {$db->connect_error}\n");
$db->set_charset('utf8mb4');
echo "Conectado a {$host}:{$port}/{$dbname}\n\n";

// Verificar si ya existe
$r = $db->query("SHOW COLUMNS FROM rutinas_actividades LIKE 'categoria'");
if ($r->num_rows > 0) {
    echo "Columna categoria ya existe, omitiendo ALTER.\n";
} else {
    $db->query("ALTER TABLE rutinas_actividades ADD COLUMN categoria VARCHAR(100) NULL DEFAULT 'General' AFTER nombre");
    if ($db->error) {
        echo "ERROR al agregar columna: {$db->error}\n";
    } else {
        echo "Columna categoria agregada (VARCHAR 100, default 'General').\n";
    }
    $db->query("ALTER TABLE rutinas_actividades ADD INDEX idx_categoria (categoria)");
    echo "Índice idx_categoria creado.\n";

    // Poblar default para los registros existentes
    $db->query("UPDATE rutinas_actividades SET categoria = 'General' WHERE categoria IS NULL OR categoria = ''");
    echo "Registros existentes marcados como 'General': {$db->affected_rows}\n";
}

// Verificación
echo "\n=== VERIFICACIÓN ===\n";
$r = $db->query("DESCRIBE rutinas_actividades");
while ($col = $r->fetch_assoc()) {
    if ($col['Field'] === 'categoria') {
        echo "Columna categoria: {$col['Type']} | Null={$col['Null']} | Default={$col['Default']}\n";
    }
}

$r = $db->query("SELECT COALESCE(categoria, 'NULL') as cat, COUNT(*) as c FROM rutinas_actividades GROUP BY categoria");
echo "\nDistribución por categoría:\n";
while ($row = $r->fetch_assoc()) {
    echo "  {$row['cat']}: {$row['c']}\n";
}

$db->close();
echo "\nListo.\n";
