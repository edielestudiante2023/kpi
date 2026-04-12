<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rutina Diaria – Cycloid Talent</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f2f5; min-height: 100vh; }

        .header {
            background: #1c2437; color: #fff; padding: 20px; text-align: center;
            position: sticky; top: 0; z-index: 10;
        }
        .header h1 { font-size: 20px; color: #bd9751; margin-bottom: 4px; }
        .header .sub { font-size: 13px; color: #adb5bd; }

        .stats {
            background: #fff; padding: 12px 20px; display: flex; justify-content: center; gap: 30px;
            border-bottom: 1px solid #dee2e6; font-size: 14px;
        }
        .stats span { font-weight: 700; }
        .stats .pendiente { color: #dc3545; }
        .stats .cerrado { color: #28a745; }

        .container { max-width: 700px; margin: 0 auto; padding: 20px; }

        .card {
            background: #fff; border-radius: 10px; padding: 16px 20px; margin-bottom: 12px;
            border-left: 4px solid #ffc107; transition: all 0.3s;
            display: flex; align-items: center; gap: 14px;
        }
        .card.cumple { border-left-color: #28a745; background: #f0fff4; }

        .card input[type=checkbox] {
            width: 22px; height: 22px; cursor: pointer; accent-color: #28a745; flex-shrink: 0;
        }

        .card-info { flex: 1; }
        .card-info .nombre { font-weight: 700; font-size: 15px; color: #1c2437; }
        .card-info .desc { font-size: 13px; color: #6c757d; margin-top: 2px; }

        .badge-done {
            background: #28a745; color: #fff; font-size: 11px; padding: 3px 10px;
            border-radius: 12px; font-weight: 600;
        }
        .badge-pending {
            background: #ffc107; color: #333; font-size: 11px; padding: 3px 10px;
            border-radius: 12px; font-weight: 600;
        }

        .empty-state {
            text-align: center; padding: 60px 20px; color: #28a745;
        }
        .empty-state i { font-size: 60px; margin-bottom: 10px; }
        .empty-state h2 { font-size: 22px; }
        .empty-state p { color: #6c757d; }

        #toast {
            display: none; position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
            background: #28a745; color: #fff; padding: 12px 24px; border-radius: 8px;
            font-weight: 600; font-size: 14px; z-index: 100; box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>

<?php
    $fechaFmt = date('d/m/Y', strtotime($fecha));
    $diasSemana = ['Domingo','Lunes','Martes','Miercoles','Jueves','Viernes','Sabado'];
    $diaSemana = $diasSemana[(int)date('w', strtotime($fecha))];
    $pendientes = 0;
    $cerrados = 0;
    foreach ($actividades as $act) {
        if (isset($completados[$act['id_actividad']])) {
            $cerrados++;
        } else {
            $pendientes++;
        }
    }
?>

<div class="header">
    <h1><i class="fa-solid fa-clipboard-check"></i> Rutina Diaria</h1>
    <div class="sub"><?= esc($usuario['nombre_completo']) ?> &middot; <?= $diaSemana ?> <?= $fechaFmt ?></div>
</div>

<div class="stats">
    <div>Pendientes: <span class="pendiente" id="cntPendiente"><?= $pendientes ?></span></div>
    <div>Completadas: <span class="cerrado" id="cntCerrado"><?= $cerrados ?></span></div>
</div>

<div class="container">
    <?php if (empty($actividades)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-circle-check"></i>
            <h2>Sin actividades</h2>
            <p>No tienes actividades asignadas para este dia.</p>
        </div>
    <?php elseif ($pendientes === 0): ?>
        <div class="empty-state">
            <i class="fa-solid fa-circle-check"></i>
            <h2>Todo al dia!</h2>
            <p>Completaste todas tus actividades.</p>
        </div>
    <?php endif; ?>

    <?php foreach ($actividades as $act):
        $done = isset($completados[$act['id_actividad']]);
    ?>
        <div class="card <?= $done ? 'cumple' : '' ?>" id="card-<?= $act['id_actividad'] ?>">
            <input type="checkbox" data-id="<?= $act['id_actividad'] ?>"
                   <?= $done ? 'checked disabled' : '' ?>>
            <div class="card-info">
                <div class="nombre"><?= esc($act['nombre']) ?></div>
                <?php if ($act['descripcion']): ?>
                    <div class="desc"><?= esc($act['descripcion']) ?></div>
                <?php endif; ?>
            </div>
            <div id="estado-<?= $act['id_actividad'] ?>">
                <?= $done
                    ? '<span class="badge-done"><i class="fa-solid fa-check"></i> Hecha</span>'
                    : '<span class="badge-pending">Pendiente</span>'
                ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div id="toast"></div>

<script>
var pendienteCount = <?= $pendientes ?>;
var cerradoCount   = <?= $cerrados ?>;
var userId  = <?= (int) $usuario['id_users'] ?>;
var fecha   = '<?= esc($fecha) ?>';
var token   = '<?= esc($token) ?>';
var baseUrl = '<?= rtrim(base_url(), "/") ?>';

function showToast(msg) {
    var t = document.getElementById('toast');
    t.textContent = msg;
    t.style.display = 'block';
    setTimeout(function() { t.style.display = 'none'; }, 2000);
}

document.querySelectorAll('input[type=checkbox]').forEach(function(cb) {
    cb.addEventListener('change', function() {
        if (!this.checked) return;
        var id   = this.dataset.id;
        var self = this;
        self.disabled = true;

        var fd = new FormData();
        fd.append('user_id', userId);
        fd.append('fecha', fecha);
        fd.append('token', token);
        fd.append('id_actividad', id);

        fetch(baseUrl + '/rutinas/checklist/update', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                var card   = document.getElementById('card-' + id);
                var estado = document.getElementById('estado-' + id);
                card.classList.add('cumple');
                estado.innerHTML = '<span class="badge-done"><i class="fa-solid fa-check"></i> Hecha</span>';
                pendienteCount--;
                cerradoCount++;
                document.getElementById('cntPendiente').textContent = pendienteCount;
                document.getElementById('cntCerrado').textContent   = cerradoCount;
                showToast('Marcada como completada');
            } else {
                self.checked  = false;
                self.disabled = false;
                alert('Error: ' + (data.message || 'Intenta de nuevo.'));
            }
        })
        .catch(function() {
            self.checked  = false;
            self.disabled = false;
            alert('Error de conexion.');
        });
    });
});
</script>
</body>
</html>
