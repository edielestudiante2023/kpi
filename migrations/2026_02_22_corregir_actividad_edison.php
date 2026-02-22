<?php

/**
 * Corrección: Actividad de Edison que quedó sin cerrar.
 * Llegó de Villas de Hato Chico a las 7:27 PM del 21 de febrero 2026.
 *
 * Uso:  php migrations/2026_02_22_corregir_actividad_edison.php
 *       (Lee credenciales del .env del proyecto)
 */

// ── Conexión (lee del .env del proyecto) ──────────────────
$dotenv = @parse_ini_file(__DIR__ . '/../.env');
$config = [
    'host'     => $dotenv['database.default.hostname'] ?? '127.0.0.1',
    'port'     => $dotenv['database.default.port']     ?? 3306,
    'username' => $dotenv['database.default.username'] ?? 'root',
    'password' => $dotenv['database.default.password'] ?? '',
    'database' => $dotenv['database.default.database'] ?? 'kpicycloid',
];

$dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
$opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
// SSL para conexiones remotas (DigitalOcean)
if ($config['host'] !== '127.0.0.1' && $config['host'] !== 'localhost') {
    $opts[PDO::MYSQL_ATTR_SSL_CA] = true;
    $opts[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
}
$pdo = new PDO($dsn, $config['username'], $config['password'], $opts);

echo "Conectado a: {$config['host']}\n\n";

// ── 1. Buscar el usuario Edison ───────────────────────────
$stmt = $pdo->prepare("SELECT id_users, nombre_completo FROM users WHERE nombre_completo LIKE '%Edison%' LIMIT 1");
$stmt->execute();
$edison = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$edison) {
    echo "[ERROR] No se encontró al usuario Edison.\n";
    exit(1);
}

echo "Usuario: {$edison['nombre_completo']} (ID: {$edison['id_users']})\n\n";

// ── 2. Buscar actividades del 21 feb con duración larga o en_progreso ──
$stmt = $pdo->prepare("
    SELECT ba.*, cc.nombre AS centro_costo
    FROM bitacora_actividades ba
    LEFT JOIN centros_costo cc ON ba.id_centro_costo = cc.id_centro_costo
    WHERE ba.id_usuario = :id
      AND ba.fecha = '2026-02-21'
    ORDER BY ba.numero_actividad ASC
");
$stmt->execute(['id' => $edison['id_users']]);
$actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($actividades)) {
    echo "[ERROR] No se encontraron actividades del 21 de febrero para Edison.\n";
    exit(1);
}

echo "Actividades del 21 de febrero 2026:\n";
echo str_repeat('-', 100) . "\n";
printf("%-5s %-30s %-20s %-20s %-20s %-10s %-12s\n",
    "#", "Descripción", "Centro Costo", "Inicio", "Fin", "Min", "Estado");
echo str_repeat('-', 100) . "\n";

$actividadCorregir = null;
foreach ($actividades as $a) {
    $desc = mb_substr($a['descripcion'], 0, 28);
    printf("%-5s %-30s %-20s %-20s %-20s %-10s %-12s\n",
        $a['numero_actividad'],
        $desc,
        mb_substr($a['centro_costo'] ?? 'N/A', 0, 18),
        $a['hora_inicio'],
        $a['hora_fin'] ?? 'EN PROGRESO',
        $a['duracion_minutos'] ?? '-',
        $a['estado']
    );
    // Buscar la actividad problemática: en_progreso o con duración > 500 min
    if ($a['estado'] === 'en_progreso' || ($a['duracion_minutos'] && $a['duracion_minutos'] > 500)) {
        $actividadCorregir = $a;
    }
}
echo str_repeat('-', 100) . "\n\n";

if (!$actividadCorregir) {
    echo "[INFO] No se encontró actividad en_progreso o con duración excesiva.\n";
    echo "Si necesitas corregir una actividad específica, indica el id_bitacora.\n";
    exit(0);
}

echo ">>> Actividad a corregir: #{$actividadCorregir['numero_actividad']} (id_bitacora: {$actividadCorregir['id_bitacora']})\n";
echo "    Descripción: {$actividadCorregir['descripcion']}\n";
echo "    Inicio: {$actividadCorregir['hora_inicio']}\n";
echo "    Fin actual: " . ($actividadCorregir['hora_fin'] ?? 'NULL (en progreso)') . "\n";
echo "    Duración actual: " . ($actividadCorregir['duracion_minutos'] ?? 'NULL') . " min\n\n";

// ── 3. Corregir: hora_fin = 2026-02-21 19:27:00 ──────────
$horaFinCorrecta = '2026-02-21 19:27:00';
$horaInicio = new DateTime($actividadCorregir['hora_inicio']);
$horaFin    = new DateTime($horaFinCorrecta);
$diffMinutos = round(($horaFin->getTimestamp() - $horaInicio->getTimestamp()) / 60, 2);

echo ">>> Corrección:\n";
echo "    hora_fin:          $horaFinCorrecta\n";
echo "    duracion_minutos:  $diffMinutos\n";
echo "    estado:            finalizada\n\n";

$stmt = $pdo->prepare("
    UPDATE bitacora_actividades
    SET hora_fin = :hora_fin,
        duracion_minutos = :duracion,
        estado = 'finalizada'
    WHERE id_bitacora = :id
");
$stmt->execute([
    'hora_fin' => $horaFinCorrecta,
    'duracion' => $diffMinutos,
    'id'       => $actividadCorregir['id_bitacora'],
]);

echo "[OK] Actividad #{$actividadCorregir['numero_actividad']} corregida exitosamente.\n";
echo "     Duración real: " . floor($diffMinutos / 60) . "h " . round($diffMinutos % 60) . "min\n";
