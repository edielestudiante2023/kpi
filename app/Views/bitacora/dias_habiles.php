<?= $this->extend('bitacora/layout') ?>

<?= $this->section('content') ?>

<h6 class="mb-3"><i class="bi bi-calendar-check me-1"></i> Dias Habiles <?= $anio ?></h6>

<!-- Navegacion por ano -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="<?= base_url('bitacora/dias-habiles/' . ($anio - 1)) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-chevron-left"></i> <?= $anio - 1 ?>
    </a>
    <span class="fw-bold"><?= $anio ?></span>
    <a href="<?= base_url('bitacora/dias-habiles/' . ($anio + 1)) ?>" class="btn btn-sm btn-outline-secondary">
        <?= $anio + 1 ?> <i class="bi bi-chevron-right"></i>
    </a>
</div>

<?php
$nombresMes = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$diasSemana = ['L','M','X','J','V','S','D'];
?>

<?php for ($mes = 1; $mes <= 12; $mes++):
    $diasEnMes = (int) date('t', mktime(0, 0, 0, $mes, 1, $anio));
    $diasConfig = $config[$mes] ?? [];
    $tieneConfig = !empty($diasConfig);

    // Calcular meta de cada quincena
    $q1 = array_filter($diasConfig, fn($d) => $d <= 15);
    $q2 = array_filter($diasConfig, fn($d) => $d > 15);
    $metaQ1 = count($q1) * 8 * 0.80;
    $metaQ2 = count($q2) * 8 * 0.80;
?>
<div class="card shadow-sm mb-3" id="mes-<?= $mes ?>">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0 fw-bold"><?= $nombresMes[$mes] ?></h6>
            <div class="d-flex gap-2 align-items-center">
                <?php if ($tieneConfig): ?>
                <span class="badge bg-success" style="font-size:0.65rem">Configurado</span>
                <?php else: ?>
                <span class="badge bg-secondary" style="font-size:0.65rem">Sin configurar</span>
                <?php endif; ?>
            </div>
        </div>

        <?php $primerDiaSemana = (int) date('N', mktime(0, 0, 0, $mes, 1, $anio)); ?>

        <!-- Grid calendario 7 columnas -->
        <div class="grid-calendario" data-mes="<?= $mes ?>">
            <!-- Header dias de la semana -->
            <?php for ($ds = 0; $ds < 7; $ds++): ?>
            <div class="grid-header"><?= $diasSemana[$ds] ?></div>
            <?php endfor; ?>

            <!-- Espacios vacios antes del dia 1 -->
            <?php for ($s = 1; $s < $primerDiaSemana; $s++): ?>
            <div></div>
            <?php endfor; ?>

            <?php for ($dia = 1; $dia <= $diasEnMes; $dia++):
                $diaSemana = (int) date('N', mktime(0, 0, 0, $mes, $dia, $anio));
                $esFinDeSemana = $diaSemana >= 6;
                $esHabil = in_array($dia, $diasConfig);
                $esFestivo = in_array(sprintf('%04d-%02d-%02d', $anio, $mes, $dia), $festivos);
                $esSeparador = $dia === 15; // Linea visual entre quincenas

                // Determinar clase CSS
                if ($esHabil) {
                    $clase = 'dia-circulo dia-habil';
                } elseif ($esFinDeSemana) {
                    $clase = 'dia-circulo dia-finsemana';
                } elseif ($esFestivo) {
                    $clase = 'dia-circulo dia-festivo';
                } else {
                    $clase = 'dia-circulo dia-nohabil';
                }
            ?>
            <div class="<?= $clase ?>"
                 data-dia="<?= $dia ?>"
                 data-mes="<?= $mes ?>"
                 title="<?= $dia ?>/<?= $mes ?>/<?= $anio ?><?= $esFestivo ? ' (Festivo)' : '' ?><?= $esFinDeSemana ? ' (Fin de semana)' : '' ?>">
                <?= $dia ?>
            </div>
            <?php endfor; ?>
        </div>

        <!-- Separador quincenas y resumen meta -->
        <div class="mt-2 d-flex justify-content-between" style="font-size:0.7rem;">
            <div>
                <span class="text-muted">Q1 (1-15):</span>
                <strong class="text-primary" id="q1-<?= $mes ?>"><?= count($q1) ?></strong> dias
                <span class="text-muted ms-1">=</span>
                <strong class="text-success" id="meta-q1-<?= $mes ?>"><?= number_format($metaQ1, 1) ?>h</strong>
            </div>
            <div>
                <span class="text-muted">Q2 (16-<?= $diasEnMes ?>):</span>
                <strong class="text-primary" id="q2-<?= $mes ?>"><?= count($q2) ?></strong> dias
                <span class="text-muted ms-1">=</span>
                <strong class="text-success" id="meta-q2-<?= $mes ?>"><?= number_format($metaQ2, 1) ?>h</strong>
            </div>
        </div>

        <!-- Boton guardar -->
        <button class="btn btn-sm btn-primary w-100 mt-2 btn-guardar-mes" data-mes="<?= $mes ?>" disabled>
            <i class="bi bi-check-lg me-1"></i> Guardar <?= $nombresMes[$mes] ?>
        </button>
    </div>
</div>
<?php endfor; ?>

<!-- Volver a liquidacion -->
<a href="<?= base_url('bitacora/liquidacion') ?>" class="btn btn-outline-secondary btn-sm w-100 mt-3 mb-3">
    <i class="bi bi-arrow-left me-1"></i> Volver a Liquidacion
</a>

<style>
.grid-calendario {
    display: grid;
    grid-template-columns: repeat(7, 36px);
    gap: 4px;
    justify-content: center;
}
.grid-header {
    text-align: center;
    font-size: 0.6rem;
    color: #999;
    font-weight: 700;
    height: 18px;
    line-height: 18px;
}
.dia-circulo {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.72rem;
    font-weight: 600;
    cursor: pointer;
    user-select: none;
    transition: all 0.15s;
    border: 2px solid #dee2e6;
    background: #fff;
    color: #333;
}
.dia-circulo:hover {
    transform: scale(1.15);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.dia-circulo.dia-habil {
    background: #198754;
    color: #fff;
    border-color: #198754;
}
.dia-circulo.dia-finsemana {
    background: #f8f9fa;
    color: #adb5bd;
    border-color: #e9ecef;
}
.dia-circulo.dia-festivo {
    background: #fff3cd;
    color: #856404;
    border-color: #ffc107;
}
.dia-circulo.dia-nohabil {
    background: #fff;
    color: #6c757d;
    border-color: #dee2e6;
}
.dia-circulo.dia-modificado {
    box-shadow: 0 0 0 2px #0d6efd;
}
</style>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function() {
    var BASE = '<?= base_url() ?>';
    var CSRF_NAME = '<?= csrf_token() ?>';
    var CSRF_HASH = '<?= csrf_hash() ?>';
    var ANIO = <?= $anio ?>;

    // Estado: track de cambios por mes
    var cambios = {}; // { mes: true }

    // Click en circulo
    document.querySelectorAll('.dia-circulo').forEach(function(el) {
        el.addEventListener('click', function() {
            var dia = parseInt(el.getAttribute('data-dia'));
            var mes = parseInt(el.getAttribute('data-mes'));

            // Toggle habil/no-habil
            if (el.classList.contains('dia-habil')) {
                el.classList.remove('dia-habil');
                el.classList.add('dia-nohabil');
            } else {
                el.classList.remove('dia-nohabil', 'dia-finsemana', 'dia-festivo');
                el.classList.add('dia-habil');
            }
            el.classList.add('dia-modificado');

            // Marcar mes como modificado
            cambios[mes] = true;
            var btn = document.querySelector('.btn-guardar-mes[data-mes="' + mes + '"]');
            if (btn) {
                btn.disabled = false;
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-warning');
                btn.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i> Guardar cambios';
            }

            // Recalcular metas
            recalcularMetas(mes);
        });
    });

    function recalcularMetas(mes) {
        var container = document.querySelector('[data-mes="' + mes + '"]');
        if (!container) return;

        var habiles = container.querySelectorAll('.dia-habil');
        var q1 = 0, q2 = 0;
        habiles.forEach(function(el) {
            var d = parseInt(el.getAttribute('data-dia'));
            if (d <= 15) q1++;
            else q2++;
        });

        var elQ1 = document.getElementById('q1-' + mes);
        var elQ2 = document.getElementById('q2-' + mes);
        var elMetaQ1 = document.getElementById('meta-q1-' + mes);
        var elMetaQ2 = document.getElementById('meta-q2-' + mes);

        if (elQ1) elQ1.textContent = q1;
        if (elQ2) elQ2.textContent = q2;
        if (elMetaQ1) elMetaQ1.textContent = (q1 * 8 * 0.80).toFixed(1) + 'h';
        if (elMetaQ2) elMetaQ2.textContent = (q2 * 8 * 0.80).toFixed(1) + 'h';
    }

    // Guardar mes
    document.querySelectorAll('.btn-guardar-mes').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var mes = parseInt(btn.getAttribute('data-mes'));
            var container = document.querySelector('[data-mes="' + mes + '"]');
            if (!container) return;

            // Recopilar dias habiles
            var dias = [];
            container.querySelectorAll('.dia-habil').forEach(function(el) {
                dias.push(parseInt(el.getAttribute('data-dia')));
            });

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';

            var fd = new FormData();
            fd.append(CSRF_NAME, CSRF_HASH);
            fd.append('anio', ANIO);
            fd.append('mes', mes);
            fd.append('dias', JSON.stringify(dias));

            fetch(BASE + 'bitacora/dias-habiles/guardar', {
                method: 'POST',
                body: fd
            })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                if (resp.csrf_token) CSRF_HASH = resp.csrf_token;

                if (resp.ok) {
                    btn.classList.remove('btn-warning');
                    btn.classList.add('btn-success');
                    btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Guardado';
                    delete cambios[mes];

                    // Quitar indicador de modificado
                    container.querySelectorAll('.dia-modificado').forEach(function(el) {
                        el.classList.remove('dia-modificado');
                    });

                    // Actualizar badge
                    var card = document.getElementById('mes-' + mes);
                    if (card) {
                        var badge = card.querySelector('.badge');
                        if (badge) {
                            badge.className = 'badge bg-success';
                            badge.style.fontSize = '0.65rem';
                            badge.textContent = 'Configurado';
                        }
                    }

                    setTimeout(function() {
                        btn.classList.remove('btn-success');
                        btn.classList.add('btn-primary');
                        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Guardar ' + btn.closest('.card').querySelector('h6').textContent;
                        btn.disabled = true;
                    }, 2000);
                } else {
                    alert(resp.error || 'Error al guardar');
                    btn.disabled = false;
                    btn.classList.remove('btn-warning');
                    btn.classList.add('btn-primary');
                    btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Guardar';
                }
            })
            .catch(function() {
                alert('Error de conexion');
                btn.disabled = false;
            });
        });
    });

    // Advertir si hay cambios sin guardar al salir
    window.addEventListener('beforeunload', function(e) {
        if (Object.keys(cambios).length > 0) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
})();
</script>
<?= $this->endSection() ?>
