<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sesiones Activas - KPI Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%);
            min-height: 100vh;
        }
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body>
    <?= $this->include('partials/nav') ?>

    <div class="container-fluid py-4">
        <!-- Usuario en sesion -->
        <div class="text-end mb-2">
            <span class="badge bg-primary fs-6">
                <i class="bi bi-person-circle me-1"></i>
                <?= esc(session()->get('nombre_completo')) ?>
            </span>
        </div>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-2">
                <?= view('components/back_to_dashboard') ?>
                <h1 class="h3 mb-0">
                    <i class="bi bi-broadcast text-success pulse me-2"></i>Sesiones Activas
                </h1>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('sesiones/dashboard') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Volver al Dashboard
                </a>
                <button onclick="location.reload()" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-clockwise me-1"></i> Actualizar
                </button>
            </div>
        </div>

        <!-- Contador -->
        <div class="alert alert-success d-flex align-items-center mb-4">
            <i class="bi bi-people-fill fs-4 me-3"></i>
            <div>
                <strong><?= count($sesiones) ?></strong> usuario(s) conectado(s) en este momento
            </div>
        </div>

        <!-- Tabla de sesiones activas -->
        <div class="card shadow-sm">
            <div class="card-body">
                <?php if (empty($sesiones)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-wifi-off fs-1 d-block mb-2"></i>
                        <h5>No hay usuarios conectados</h5>
                        <p>Las sesiones inactivas por mas de 10 minutos se cierran automaticamente</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Usuario</th>
                                    <th>Correo</th>
                                    <th class="text-center">Inicio de Sesion</th>
                                    <th class="text-center">Ultimo Latido</th>
                                    <th class="text-center">Tiempo Conectado</th>
                                    <th>IP</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sesiones as $s): ?>
                                <tr>
                                    <td>
                                        <i class="bi bi-circle-fill text-success me-2 small pulse"></i>
                                        <strong><?= esc($s['nombre_completo']) ?></strong>
                                    </td>
                                    <td class="text-muted"><?= esc($s['correo']) ?></td>
                                    <td class="text-center">
                                        <?= date('d/m/Y H:i:s', strtotime($s['fecha_inicio'])) ?>
                                    </td>
                                    <td class="text-center">
                                        <?= date('H:i:s', strtotime($s['fecha_ultimo_latido'])) ?>
                                        <br>
                                        <small class="text-muted">
                                            hace <?= tiempoTranscurrido($s['fecha_ultimo_latido']) ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary fs-6">
                                            <?= formatearTiempo($s['duracion_segundos']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <code><?= esc($s['ip_address'] ?? '-') ?></code>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger"
                                                onclick="cerrarSesion(<?= $s['id_sesion'] ?>)"
                                                title="Forzar cierre de sesion">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function cerrarSesion(idSesion) {
            if (!confirm('¿Deseas forzar el cierre de esta sesion?')) {
                return;
            }

            fetch('<?= base_url('sesiones/cerrar/') ?>' + idSesion, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: '<?= csrf_token() ?>=<?= csrf_hash() ?>'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'No se pudo cerrar la sesion'));
                }
            })
            .catch(error => {
                alert('Error de conexion');
            });
        }

        // Auto-refresh cada 30 segundos
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>

<?php
function formatearTiempo($segundos) {
    if ($segundos < 60) {
        return $segundos . 's';
    }
    if ($segundos < 3600) {
        return round($segundos / 60) . ' min';
    }
    $horas = floor($segundos / 3600);
    $minutos = round(($segundos % 3600) / 60);
    return $horas . 'h ' . $minutos . 'min';
}

function tiempoTranscurrido($fecha) {
    $diff = time() - strtotime($fecha);
    if ($diff < 60) return $diff . 's';
    if ($diff < 3600) return round($diff / 60) . ' min';
    return round($diff / 3600, 1) . 'h';
}
?>
