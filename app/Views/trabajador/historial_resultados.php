<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Historial de Resultados – Kpi Cycloid</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

  <style>
    html,
    body {
      height: 100%;
      margin: 0;
      padding: 0;
    }

    .container-fluid {
      display: flex;
      flex-direction: column;
      height: 100%;
    }

    .dataTables_wrapper .dt-buttons {
      margin-bottom: 1rem;
    }

    table.dataTable {
      width: 100% !important;
      table-layout: fixed;
    }

    /* ancho aprox 20 caracteres */
    table.dataTable th.col-formula,
    table.dataTable td.col-formula {
      width: 20ch;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* permitir wrap en primera columna */
    table.dataTable th:first-child,
    table.dataTable td:first-child {
      white-space: normal !important;
      word-wrap: break-word;
      overflow-wrap: anywhere;
      max-width: 20ch;
    }

    tfoot select {
      width: 100%;
      box-sizing: border-box;
    }
  </style>
</head>

<body>
  <?= $this->include('partials/nav') ?>

  <div class="container-fluid py-4 flex-grow-1">
    <a href="<?= base_url('trabajador/trabajadordashboard') ?>"
      class="btn btn-secondary mb-3">&larr; Volver al Dashboard</a>
    <h1 class="h3 mb-4">Historial de Resultados de Indicadores</h1>




    <form method="get" class="row g-3 mb-4" action="<?= base_url('trabajador/historialResultados') ?>">
      <div class="col-auto">
        <label for="fecha_desde" class="form-label">Desde:</label>
        <input type="text" id="fecha_desde" name="fecha_desde"
          class="datepicker form-control"
          value="<?= esc($fecha_desde) ?>">
      </div>
      <div class="col-auto">
        <label for="fecha_hasta" class="form-label">Hasta:</label>
        <input type="text" id="fecha_hasta" name="fecha_hasta"
          class="datepicker form-control"
          value="<?= esc($fecha_hasta) ?>">
      </div>
      <div class="col-auto align-self-end">
        <button type="submit" class="btn btn-primary">Filtrar</button>
      </div>
    </form>


    <?php if (empty($historial)): ?>
      <div class="alert alert-warning">No hay historial disponible.</div>
    <?php else: ?>
      <div class="table-responsive flex-grow-1">
        <table id="historialTable"
          class="table table-bordered table-striped align-middle dataTable">
          <thead class="table-dark">
            <tr>
              <th>Indicador</th>
              <th>Meta Valor</th>
              <th>Meta Descripción</th>
              <th>Tipo Meta</th>
              <th class="col-formula">Fórmula</th>
              <th>Unidad</th>
              <th>Objetivo Proceso</th>
              <th>Objetivo Calidad</th>
              <th>Tipo Aplicación</th>
              <th>Creado en</th>
              <th>Periodicidad</th>
              <th>Ponderación (%)</th>
              <th>Resultado</th>
              <th>Periodo de Corte</th>
              <th>Cumple</th>
              <th>Comentario</th>
              <th>Fecha de Registro</th>
            </tr>
          </thead>
          <tfoot>
            <tr>
              <?php for ($i = 0; $i < 17; $i++): ?>
                <th></th>
              <?php endfor; ?>
            </tr>
          </tfoot>
          <tbody>
            <?php foreach ($historial as $r): ?>
              <tr>
                <td data-bs-toggle="tooltip" data-bs-placement="top"
                  title="<?= esc($r['nombre_indicador']) ?>">
                  <?= esc($r['nombre_indicador']) ?>
                </td>
                <td><?= esc($r['meta_valor']) ?></td>
                <td data-bs-toggle="tooltip" data-bs-placement="top"
                  title="<?= esc($r['meta_texto']) ?>">
                  <?= esc($r['meta_texto']) ?>
                </td>
                <td><?= esc($r['tipo_meta']) ?></td>
                <td class="col-formula">
                  <div class="mb-1"
                    data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    title="<?= esc(implode('', array_column($formulasHist[$r['id_indicador']] ?? [], 'valor'))) ?>">
                    <small class="text-muted">Original:</small><br>
                    <?php
                    $orig = $formulasHist[$r['id_indicador']] ?? [];
                    if (!empty($orig)):
                      echo '<code>' . esc(implode('', array_column($orig, 'valor'))) . '</code>';
                    else:
                      echo '<code>' . esc($r['metodo_calculo']) . '</code>';
                    endif;
                    ?>
                  </div>
                  <div>
                    <small class="text-muted">Operac.:</small><br>
                    <?php
                    $json   = json_decode($r['valores_json'], true);
                    $parts  = $formulasHist[$r['id_indicador']] ?? [];
                    if (isset($json['formula_partes']) && $parts):
                      foreach ($parts as $p):
                        if ($p['tipo_parte'] === 'dato'):
                          echo '<span class="text-primary">' . esc($json['formula_partes'][$p['valor']] ?? '') . '</span>';
                        else:
                          echo '<span>' . esc($p['valor']) . '</span>';
                        endif;
                      endforeach;
                    else:
                      echo '<em class="text-muted">Dato ingresado directamente</em>';
                    endif;
                    ?>
                  </div>
                </td>
                <td><?= esc($r['unidad']) ?></td>
                <td><?= esc($r['objetivo_proceso']) ?></td>
                <td><?= esc($r['objetivo_calidad']) ?></td>
                <td><?= esc($r['tipo_aplicacion']) ?></td>
                <td><?= esc($r['creado_en']) ?></td>
                <td><?= esc($r['periodicidad']) ?></td>
                <td><?= esc($r['ponderacion']) ?>%</td>
                <td><?= esc($r['resultado_real']) ?></td>
                <td><?= esc($r['periodo']) ?></td>
                <td>
                  <?php if ($r['cumple'] === '1'): ?>
                    <span class="badge bg-success">Sí</span>
                  <?php elseif ($r['cumple'] === '0'): ?>
                    <span class="badge bg-danger">No</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">—</span>
                  <?php endif; ?>
                </td>
                <td><?= esc($r['comentario']) ?: '—' ?></td>
                <td data-bs-toggle="tooltip" data-bs-placement="top"
                  title="<?= esc($r['fecha_registro']) ?>">
                  <?= esc($r['fecha_registro']) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <?= $this->include('partials/logout') ?>

  <!-- JS: jQuery, Bootstrap, DataTables, Buttons, JSZip -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>


  <script>
    document.addEventListener('DOMContentLoaded', function() {
      flatpickr('.datepicker', {
        locale: 'es', // español
        dateFormat: 'Y-m-d', // formato interno ISO (para el value)
        altInput: true, // muestra otro input
        altFormat: 'd/m/Y', // DD/MM/YYYY
        allowInput: true, // dejar que el usuario escriba
        monthSelectorType: 'dropdown' // selector de mes desplegable
      });
    });
  </script>

  <script>
    // Inicializa tooltips de Bootstrap
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
      new bootstrap.Tooltip(el);
    });

    // DataTables
    $('#historialTable').DataTable({
      scrollX: true,
      dom: 'Bfrtip',
      buttons: [{
        extend: 'excelHtml5',
        title: 'Historial_Resultados'
      }],
      order: [
        [16, 'desc']
      ], // índice 16 = Fecha de Registro
      columnDefs: [{
        targets: [6, 7, 8, 9, 10],
        visible: false
      }]
    });
  </script>
</body>

</html>