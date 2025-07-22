<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Historial de Indicadores del Equipo – Afilogro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">

    <style>
        .tag-si {
            background-color: #198754;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        .tag-no {
            background-color: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        .small-cell {
            font-size: 0.85rem;
        }
    </style>
</head>

<body>
    <?= $this->include('partials/nav') ?>

    <div class="container-fluid py-4">
        <h1 class="h4 mb-4">📊 Historial de Indicadores del Equipo</h1>

        <!-- Filtros -->
        <form method="get" class="row g-3 mb-3">
            <div class="col-md-3">
                <label for="fecha_desde" class="form-label">Desde</label>
                <input type="date" class="form-control" name="fecha_desde" value="<?= esc($fecha_desde) ?>">
            </div>
            <div class="col-md-3">
                <label for="fecha_hasta" class="form-label">Hasta</label>
                <input type="date" class="form-control" name="fecha_hasta" value="<?= esc($fecha_hasta) ?>">
            </div>
            <div class="col-md-3 align-self-end">
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </div>
        </form>

        <!-- Tabla -->
        <div class="table-responsive">
            <table id="tablaHistorial" class="table table-striped table-bordered table-sm" style="width:100%">
                <thead>
                    <tr class="table-secondary text-center">
                        <th>Periodo</th>
                        <th>Trabajador</th>
                        <th>Indicador</th>
                        <th>Resultado</th>
                        <th>Meta</th>
                        <th>Unidad</th>
                        <th>Cumple</th>
                        <th>Comentario</th>
                        <th>Fórmula</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipo as $item): ?>
                        <tr class="small-cell">
                            <td><?= esc($item['periodo']) ?></td>
                            <td><?= esc($item['nombre_completo']) ?></td>
                            <td><?= esc($item['nombre_indicador']) ?></td>
                            <td class="text-end"><?= esc($item['resultado_real']) ?></td>
                            <td class="text-end">
                                <?= esc($item['meta_valor']) ?>
                                <?= isset($item['meta_texto']) ? esc($item['meta_texto']) : '–' ?>
                            </td>
                            <td><?= esc($item['unidad']) ?></td>
                            <td class="text-center">
                                <?= $item['cumple'] == 1 ? '<span class="tag-si">Sí</span>' : '<span class="tag-no">No</span>' ?>
                            </td>
                            <td><?= esc($item['comentario']) ?></td>
                            <td>
                                <?php
                                $fid = $item['id_indicador'];
                                if (isset($formulasHist[$fid])) {
                                    echo '<span class="text-primary" title="Fórmula: ';
                                    foreach ($formulasHist[$fid] as $p) {
                                        echo $p['valor'] . ' ';
                                    }
                                    echo '">Ver fórmula</span>';
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#tablaHistorial').DataTable({
                pageLength: 25,
                ordering: true,
                responsive: true,
                dom: 'Bfrtip',
                buttons: [{
                    extend: 'excelHtml5',
                    text: '📥 Descargar Excel',
                    className: 'btn btn-success btn-sm'
                }],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                }
            });
        });
    </script>
</body>

</html>