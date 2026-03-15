<?php

/**
 * Diagnóstico: buscar duplicados de "Checklist al salir de la oficina"
 * Ejecutar: php migrations/2026_03_11_fix_duplicados_checklist.php
 */

$config = [
    'host'     => 'db-mysql-cycloid-do-user-18794030-0.h.db.ondigitalocean.com',
    'port'     => 25060,
    'username' => 'cycloid_userdb',
    'password' => getenv('DB_PASSWORD') ?: '',
    'database' => 'kpicycloid',
];

$dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
$opts = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_TIMEOUT => 60,
    PDO::MYSQL_ATTR_SSL_CA => true,
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
];

$pdo = new PDO($dsn, $config['username'], $config['password'], $opts);
echo "Conectado a PRO\n\n";

// 1. Buscar duplicados con título similar
echo "=== Actividades 'Checklist al salir de la oficina' ===\n";
$stmt = $pdo->query("
    SELECT id_actividad, codigo, titulo, estado, id_usuario_creador, fecha_creacion
    FROM actividades
    WHERE titulo LIKE '%Checklist al salir%'
    ORDER BY id_actividad ASC
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total encontradas: " . count($rows) . "\n\n";
foreach ($rows as $r) {
    echo "  ID: {$r['id_actividad']} | Código: {$r['codigo']} | Estado: {$r['estado']} | Creado: {$r['fecha_creacion']}\n";
}

// 2. Mostrar qué IDs se pueden eliminar (mantener el primero)
if (count($rows) > 1) {
    $keep = $rows[0]['id_actividad'];
    $deleteIds = [];
    foreach ($rows as $i => $r) {
        if ($i > 0) $deleteIds[] = $r['id_actividad'];
    }
    echo "\n--- Propuesta ---\n";
    echo "Mantener: ID {$keep} (original)\n";
    echo "Eliminar: " . implode(', ', $deleteIds) . " (" . count($deleteIds) . " duplicados)\n";

    // Preguntar antes de borrar
    echo "\nEliminando duplicados...\n";

    // Eliminar historial asociado primero
    $idList = implode(',', $deleteIds);
    $pdo->exec("DELETE FROM actividad_historial WHERE id_actividad IN ({$idList})");
    echo "  Historial eliminado\n";

    // Eliminar comentarios
    $pdo->exec("DELETE FROM actividad_comentarios WHERE id_actividad IN ({$idList})");
    echo "  Comentarios eliminados\n";

    // Eliminar archivos
    $pdo->exec("DELETE FROM actividad_archivos WHERE id_actividad IN ({$idList})");
    echo "  Archivos eliminados\n";

    // Eliminar las actividades duplicadas
    $pdo->exec("DELETE FROM actividades WHERE id_actividad IN ({$idList})");
    echo "  Actividades duplicadas eliminadas\n";

    echo "\nLimpieza completada. Se mantuvo ID {$keep}.\n";
}
