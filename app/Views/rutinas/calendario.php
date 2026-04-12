<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario Rutinas – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .cal-table { border-collapse: separate; border-spacing: 0; width: 100%; }
        .cal-table th, .cal-table td {
            text-align: center; padding: 6px 4px; font-size: 13px;
            border: 1px solid #dee2e6; white-space: nowrap;
        }
        .cal-table thead th { background: #1c2437; color: #fff; position: sticky; top: 0; z-index: 2; }
        .cal-table .act-name {
            text-align: left; min-width: 180px; max-width: 250px;
            position: sticky; left: 0; background: #fff; z-index: 1;
            font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .cal-table thead .act-name { z-index: 3; }
        .cal-table .dia-letra { font-size: 11px; color: #bd9751; font-weight: 700; }
        .cal-table .dia-num { font-weight: 700; }
        .check-done { color: #28a745; font-size: 16px; }
        .check-miss { color: #dc3545; font-size: 16px; }
        .check-future { color: #dee2e6; font-size: 16px; }
        .puntaje-row td { font-weight: 700; font-size: 12px; }
        .puntaje-100 { background: #d4edda !important; }
        .puntaje-ok  { background: #fff3cd !important; }
        .puntaje-bad { background: #f8d7da !important; }
        .sem-header { background: #f0f2f5; font-weight: 700; font-size: 11px; color: #6c757d; }
        .acum-badge {
            display: inline-block; padding: 4px 12px; border-radius: 20px;
            font-weight: 700; font-size: 18px;
        }
        .scroll-wrapper { overflow-x: auto; max-width: 100%; }
    </style>
</head>
<body>
<?= $this->include('partials/nav') ?>

<?php
    $meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    $mesAnterior = $mes - 1;
    $anioAnterior = $anio;
    $mesSiguiente = $mes + 1;
    $anioSiguiente = $anio;
    if ($mesAnterior < 1)  { $mesAnterior = 12; $anioAnterior--; }
    if ($mesSiguiente > 12) { $mesSiguiente = 1; $anioSiguiente++; }

    // Agrupar días por semana ISO
    $semanas = [];
    foreach ($diasHabiles as $dh) {
        $sem = (int) date('W', strtotime($dh['fecha']));
        $semanas[$sem][] = $dh;
    }
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <?= view('components/back_to_dashboard') ?>
            <h1 class="h3 mb-0"><i class="bi bi-calendar-week me-2"></i>Rutinas — <?= $meses[$mes] ?> <?= $anio ?></h1>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <!-- Selector usuario -->
            <form method="get" class="d-flex gap-2 align-items-center">
                <input type="hidden" name="mes" value="<?= $mes ?>">
                <input type="hidden" name="anio" value="<?= $anio ?>">
                <select name="usuario" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width:200px">
                    <?php foreach ($usuariosConRutinas as $u): ?>
                        <option value="<?= $u['id_users'] ?>" <?= $u['id_users'] == $idUser ? 'selected' : '' ?>>
                            <?= esc($u['nombre_completo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <!-- Navegación mes -->
            <a href="?mes=<?= $mesAnterior ?>&anio=<?= $anioAnterior ?>&usuario=<?= $idUser ?>"
               class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-left"></i></a>
            <a href="?mes=<?= date('n') ?>&anio=<?= date('Y') ?>&usuario=<?= $idUser ?>"
               class="btn btn-outline-primary btn-sm">Hoy</a>
            <a href="?mes=<?= $mesSiguiente ?>&anio=<?= $anioSiguiente ?>&usuario=<?= $idUser ?>"
               class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-right"></i></a>
        </div>
    </div>

    <!-- Acumulado mensual -->
    <div class="text-center mb-3">
        <?php
            $acumClass = $acumuladoMensual >= 90 ? 'bg-success text-white'
                       : ($acumuladoMensual >= 60 ? 'bg-warning text-dark' : 'bg-danger text-white');
        ?>
        <span class="acum-badge <?= $acumClass ?>">
            <?= $meses[$mes] ?>: <?= $acumuladoMensual ?>%
        </span>
    </div>

    <?php if (empty($actividades)): ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle me-1"></i>
            <?= empty($usuariosConRutinas) ? 'No hay usuarios con rutinas asignadas.' : 'Este usuario no tiene actividades asignadas.' ?>
        </div>
    <?php else: ?>

    <!-- Tabla calendario -->
    <div class="card shadow-sm">
        <div class="card-body p-2">
            <div class="scroll-wrapper">
                <table class="cal-table">
                    <thead>
                        <tr>
                            <th class="act-name">Actividad</th>
                            <?php foreach ($diasHabiles as $dh): ?>
                                <th>
                                    <div class="dia-num"><?= $dh['dia'] ?></div>
                                    <div class="dia-letra"><?= $dh['dia_letra'] ?></div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($actividades as $act): ?>
                            <tr>
                                <td class="act-name" title="<?= esc($act['nombre']) ?>"><?= esc($act['nombre']) ?></td>
                                <?php foreach ($diasHabiles as $dh): ?>
                                    <?php
                                        $key = $act['id_actividad'] . '_' . $dh['fecha'];
                                        $esFuturo = $dh['fecha'] > $hoy;
                                        $completado = isset($registros[$key]);
                                    ?>
                                    <td>
                                        <?php if ($completado): ?>
                                            <i class="bi bi-check-square-fill check-done"></i>
                                        <?php elseif ($esFuturo): ?>
                                            <i class="bi bi-dash check-future"></i>
                                        <?php else: ?>
                                            <i class="bi bi-x-square check-miss"></i>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>

                        <!-- Fila de puntaje diario -->
                        <tr class="puntaje-row">
                            <td class="act-name">Puntaje</td>
                            <?php foreach ($diasHabiles as $dh): ?>
                                <?php
                                    $p = $puntajeDiario[$dh['fecha']] ?? 0;
                                    $esFuturo = $dh['fecha'] > $hoy;
                                    $tieneDatos = $p > 0;
                                    $mostrar = !$esFuturo || $tieneDatos;
                                    $cls = $mostrar ? ($p >= 100 ? 'puntaje-100' : ($p >= 60 ? 'puntaje-ok' : 'puntaje-bad')) : '';
                                ?>
                                <td class="<?= $cls ?>">
                                    <?= $mostrar ? $p . '%' : '' ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>

                        <!-- Fila acumulado semanal -->
                        <tr class="puntaje-row">
                            <td class="act-name">Semana</td>
                            <?php
                                $semActual = null;
                                foreach ($diasHabiles as $i => $dh):
                                    $sem = (int) date('W', strtotime($dh['fecha']));
                                    $diasEnSemana = count($semanas[$sem] ?? []);
                                    if ($sem !== $semActual):
                                        $semActual = $sem;
                                        $val = $semanales[$sem] ?? 0;
                                        $cls = $val >= 90 ? 'puntaje-100' : ($val >= 60 ? 'puntaje-ok' : 'puntaje-bad');
                                        // Solo mostrar si ya pasó al menos un día de la semana
                                        $primerDia = $semanas[$sem][0]['fecha'];
                                        $mostrar = $primerDia <= $hoy;
                            ?>
                                <td colspan="<?= $diasEnSemana ?>" class="sem-header <?= $mostrar ? $cls : '' ?>">
                                    <?= $mostrar ? "S{$sem}: {$val}%" : '' ?>
                                </td>
                            <?php
                                    endif;
                                endforeach;
                            ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
