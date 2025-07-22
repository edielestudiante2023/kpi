<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Historial de Indicadores de Mi Equipo – Afilogro</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <!-- Bootstrap & DataTables CSS -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet">
  <link
    href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css"
    rel="stylesheet">
  <link
    href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css"
    rel="stylesheet">
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
    }

    tfoot select {
      width: 100%;
      box-sizing: border-box;
    }

    /* ancho fijo para la columna fórmula */
    .col-formula {
      width: 20ch;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    td .dropdown-toggle {
      max-width: 200px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    td .dropdown-menu {
      max-width: 400px;
      white-space: normal;
    }

    /* todas las celdas con ancho máximo de 30 caracteres */
    #historialTable td {
      max-width: 30ch;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* enlaces dropdown sin azul ni subrayado */
    .dropdown-toggle {
      color: inherit;
      text-decoration: none;
    }

    .dropdown-toggle:hover {
      color: inherit;
      text-decoration: none;
    }
  </style>
</head>

<body>
  <?= $this->include('partials/nav') ?>

  <div class="container-fluid py-4 flex-grow-1">



    <form method="get" class="row g-3 mb-4" action="<?= base_url('jefatura/historiallosindicadoresdemiequipo') ?>">
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

    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <?php if (empty($equipo)): ?>
      <div class="alert alert-warning">
        No hay indicadores reportados por tu equipo en este rango de fechas.
      </div>
    <?php else: ?>

      <!-- Botones para contraer y expandir -->
      <div class="mb-3">
        <button id="contraerCols" class="btn btn-sm"
          style="background-color: purple; color: gold; margin-right: .5rem;">
          Contraer Columnas
        </button>
        <button id="expandirCols" class="btn btn-sm"
          style="background-color: purple; color: gold;">
          Expandir Columnas
        </button>
      </div>

      <div class="table-responsive">
        <table id="historialTable" class="table table-bordered table-striped align-middle nowrap w-100">
          <thead class="table-dark">
            <tr>
              <th>Periodo</th>
              <th>Colaborador</th>
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
              <th>Resultado Real</th>
              <th>Cumple</th>
              <th>Comentario</th>
              <th>Fecha de Registro</th>
            </tr>
          </thead>
          <tfoot>
            <tr>
              <?php for ($i = 0; $i < 17; $i++): ?><th></th><?php endfor; ?>
            </tr>
          </tfoot>
          <tbody>
            <?php foreach ($equipo as $r): ?>
              <tr>
                <td data-bs-toggle="tooltip" title="<?= esc($r['periodo']) ?>">
                  <?= esc($r['periodo']) ?></td>
                <td data-bs-toggle="tooltip" title="<?= esc($r['nombre_completo']) ?>">
                  <?= esc($r['nombre_completo']) ?></td>
                <td data-bs-toggle="tooltip" title="<?= esc($r['nombre_indicador']) ?>">
                  <?= esc($r['nombre_indicador']) ?></td>
                <td data-bs-toggle="tooltip" title="<?= esc($r['meta_valor']) ?>">
                  <?= esc($r['meta_valor']) ?></td>
                <td data-bs-toggle="tooltip" title="<?= esc($r['meta_texto']) ?>">
                  <?= esc($r['meta_texto']) ?></td>
                <td data-bs-toggle="tooltip" title="<?= esc($r['tipo_meta']) ?>">
                  <?= esc($r['tipo_meta']) ?></td>

                <!-- Columna Fórmula -->
                <td class="col-formula" data-bs-toggle="tooltip"
                  title="<?php
                          $parts = $formulasHist[$r['id_indicador']] ?? [];
                          if (!empty($parts) && isset(json_decode($r['valores_json'], true)['formula_partes'])) {
                            $txt = '';
                            foreach ($parts as $p) {
                              $txt .= $p['tipo_parte'] === 'dato'
                                ? (json_decode($r['valores_json'], true)['formula_partes'][$p['valor']] ?? '')
                                : $p['valor'];
                            }
                            echo esc($txt);
                          } else {
                            echo esc($r['metodo_calculo']);
                          }
                          ?>">
                  <?php
                  $orig = $formulasHist[$r['id_indicador']] ?? [];
                  if (! empty($orig)):
                    echo '<code>' . esc(implode('', array_column($orig, 'valor'))) . '</code>';
                  else:
                    echo '<code>' . esc($r['metodo_calculo']) . '</code>';
                  endif;
                  ?>
                  <br>
                  <small class="text-muted">Operac.:</small>
                  <?php
                  $json  = json_decode($r['valores_json'], true);
                  if (isset($json['formula_partes'])):
                    foreach ($formulasHist[$r['id_indicador']] as $p):
                      if ($p['tipo_parte'] === 'dato'):
                        echo '<span class="text-primary">'
                          . esc($json['formula_partes'][$p['valor']] ?? '')
                          . '</span>';
                      else:
                        echo '<span>' . esc($p['valor']) . '</span>';
                      endif;
                    endforeach;
                  else:
                    echo '<em class="text-muted">Dato ingresado directamente</em>';
                  endif;
                  ?>
                </td>

                <td data-bs-toggle="tooltip" title="<?= esc($r['unidad']) ?>">
                  <?= esc($r['unidad']) ?></td>
                <td>
                  <div class="dropdown">
                    <a class="dropdown-toggle" data-bs-toggle="tooltip" title="<?= esc($r['objetivo_proceso']) ?>">
                      <?= esc($r['objetivo_proceso']) ?></a>
                    <div class="dropdown-menu p-3">
                      <?= nl2br(esc($r['objetivo_proceso'])) ?>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="dropdown">
                    <a class="dropdown-toggle" data-bs-toggle="tooltip" title="<?= esc($r['objetivo_calidad']) ?>">
                      <?= esc($r['objetivo_calidad']) ?></a>
                    <div class="dropdown-menu p-3">
                      <?= nl2br(esc($r['objetivo_calidad'])) ?>
                    </div>
                  </div>
                </td>
                <td data-bs-toggle="tooltip" title="<?= esc($r['tipo_aplicacion']) ?>">
                  <?= esc($r['tipo_aplicacion']) ?></td>
                <td data-bs-toggle="tooltip" title="<?= esc($r['creado_en']) ?>">
                  <?= esc($r['creado_en']) ?></td>
                <td data-bs-toggle="tooltip" title="<?= esc($r['periodicidad']) ?>">
                  <?= esc($r['periodicidad']) ?></td>
                <td data-bs-toggle="tooltip" title="<?= esc($r['ponderacion']) ?>%">
                  <?= esc($r['ponderacion']) ?>%</td>
                <td data-bs-toggle="tooltip" title="<?= esc($r['resultado_real']) ?>">
                  <?= esc($r['resultado_real']) ?></td>
                <td>
                  <?php if ($r['cumple']): ?>
                    <span class="badge bg-success" data-bs-toggle="tooltip" title="Sí">Sí</span>
                  <?php else: ?>
                    <span class="badge bg-danger" data-bs-toggle="tooltip" title="No">No</span>
                  <?php endif; ?>
                </td>
                <td data-bs-toggle="tooltip" title="<?= esc($r['comentario'] ?: '—') ?>">
                  <?= esc($r['comentario']) ?: '—' ?></td>
                <td data-bs-toggle="tooltip" title="<?= esc($r['fecha_registro']) ?>">
                  <?= esc($r['fecha_registro']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    <?php endif; ?>

    <div class="mt-4">
      <a href="<?= base_url('jefatura/jefaturadashboard') ?>" class="btn btn-secondary">
        &larr; Volver al Dashboard
      </a>
    </div>
  </div>

  <?= $this->include('partials/logout') ?>

  <!-- JS: jQuery, Bootstrap Bundle (incluye Popper) y DataTables + Buttons -->
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
    $(document).ready(function() {
      var hiddenCols = [3, 4, 5, 6, 7, 8, 9, 10, 11, 12];

      function initTooltips() {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
          new bootstrap.Tooltip(el);
        });
      }

      var table = $('#historialTable').DataTable({
        dom: 'lBfrtip',
        pageLength: 5,
        lengthMenu: [
          [5, 10, 20, 50, 100],
          [5, 10, 20, 50, 100]
        ],
        scrollX: true,
        buttons: [{
          extend: 'excelHtml5',
          title: 'Historial_Equipo'
        }],
        order: [
          [0, 'desc']
        ],
        columnDefs: [{
          targets: hiddenCols,
          visible: false
        }],
        initComplete: initTooltips,
        drawCallback: initTooltips
      });

      $('#contraerCols').on('click', () => table.columns(hiddenCols).visible(false));
      $('#expandirCols').on('click', () => table.columns(hiddenCols).visible(true));
    });
  </script>
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
</body>

</html>