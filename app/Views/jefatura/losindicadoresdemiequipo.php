<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Indicadores del Equipo – Edición</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- DataTables CSS -->
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <!-- Datepicker CSS -->
  
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <style>
    .bg-lavanda {
      background-color: #EDE7F6 !important;
    }

    .bg-lavanda .form-control {
      background-color: #EDE7F6 !important;
      border-color: #D1C4E9 !important;
    }

    html,
    body {
      height: 100%;
    }

    .dataTables_scrollBody {
      max-height: 70vh !important;
    }

    .periodo-cell {
      cursor: pointer;
    }
  </style>
</head>

<body>
  <?= $this->include('partials/nav') ?>

  <div class="container-fluid py-4">
    <h1 class="h3 mb-4">Editar Indicadores – Equipo</h1>



    <form method="get" class="row g-3 mb-4" action="<?= base_url('jefatura/losindicadoresdemiequipo') ?>">
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


    <!-- Tabla edición -->
    <form method="post" action="<?= base_url('jefatura/guardarIndicadoresDeEquipo') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="fecha_desde" value="<?= esc($fecha_desde) ?>">
      <input type="hidden" name="fecha_hasta" value="<?= esc($fecha_hasta) ?>">

      <div class="table-responsive">
        <table id="edicionTable" class="table table-striped" style="width:100%">
          <thead class="table-dark">
            <tr>
              <th>Periodo</th>
              <th>Trabajador</th>
              <th>Indicador</th>
              <th>Meta Valor</th>
              <th>Tipo Meta</th>
              <th>Fórmula</th>
              <th>Unidad</th>
              <th>Resultado</th>
              <th>Cumple</th>
              <th>Comentario</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tfoot>
            <tr>
              <th></th>
              <th><select class="form-select form-select-sm">
                  <option value="">Todos</option>
                </select></th>
              <th><select class="form-select form-select-sm">
                  <option value="">Todos</option>
                </select></th>
              <th></th>
              <th><select class="form-select form-select-sm">
                  <option value="">Todos</option>
                </select></th>
              <th></th>
              <th><select class="form-select form-select-sm">
                  <option value="">Todos</option>
                </select></th>
              <th></th>
              <th>
                <select class="form-select form-select-sm">
                  <option value="">Todos</option>
                  <option value="1">Sí</option>
                  <option value="0">No</option>
                </select>
              </th>
              <th></th>
              <th></th>
            </tr>
          </tfoot>
          <tbody>
            <?php foreach ($equipo as $item): ?>
              <tr>
                <td class="periodo-cell bg-lavanda" data-id="<?= $item['id_historial'] ?>">
                  <?= esc($item['periodo']) ?>
                </td>
                <td><?= esc($item['nombre_completo']) ?></td>
                <td><?= esc($item['nombre_indicador']) ?></td>
                <td><?= esc($item['meta_valor']) ?></td>
                <td><?= esc($item['tipo_meta']) ?></td>
                <td class="col-formula">
                  <?php if (isset($formulas[$item['id_indicador']])): ?>
                    <?php foreach ($formulas[$item['id_indicador']] as $parte): ?>
                      <?php if ($parte['tipo_parte'] === 'dato'): ?>
                        <span class="text-primary"><?= esc($parte['valor']) ?></span>
                      <?php else: ?>
                        <span><?= esc($parte['valor']) ?></span>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <code><?= esc($item['metodo_calculo']) ?></code>
                  <?php endif; ?>
                </td>
                <td><?= esc($item['unidad']) ?></td>
                <td class="bg-lavanda">
                  <input
                    type="text"
                    name="cambios[<?= $item['id_historial'] ?>][resultado_real]"
                    class="form-control form-control-sm"
                    value="<?= esc($item['resultado_real']) ?>">
                </td>
                <td class="td-cumple" data-id="<?= $item['id_historial'] ?>">
                  <select class="form-select form-select-sm select-cumple">
                    <option value="" disabled <?= ! isset($item['cumple']) || $item['cumple'] === '' ? 'selected' : '' ?>>
                      Elija cumplimiento
                    </option>
                    <option value="1" <?= (isset($item['cumple']) && (string)$item['cumple'] === '1') ? 'selected' : '' ?>>
                      Sí
                    </option>
                    <option value="0" <?= (isset($item['cumple']) && (string)$item['cumple'] === '0') ? 'selected' : '' ?>>
                      No
                    </option>
                  </select>
                </td>

                <td>
                  <input
                    type="text"
                    name="cambios[<?= $item['id_historial'] ?>][comentario]"
                    class="form-control form-control-sm"
                    value="<?= esc($item['comentario']) ?>">
                </td>
                <td>
                  <button
                    type="submit"
                    formaction="<?= base_url('jefatura/guardarIndicadoresDeEquipo') ?>"
                    formmethod="post"
                    class="btn btn-sm btn-success"
                    name="enviar[<?= $item['id_historial'] ?>]">
                    Enviar
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </form>

    <div class="mt-4">
      <a href="<?= base_url('jefatura/jefaturadashboard') ?>" class="btn btn-primary">
        <i class="bi bi-house-door me-1"></i>Dashboard
      </a>
      <a href="<?= base_url('jefatura/jefaturadashboard') ?>" class="btn btn-secondary ms-2">&larr; Volver</a>
      <a href="<?= base_url('jefatura/historiallosindicadoresdemiequipo') ?>" class="btn btn-warning ms-2">Historial</a>
    </div>
  </div>

  <?= $this->include('partials/logout') ?>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
  
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
  <script>
    // Tokens CSRF
    const csrfName = '<?= csrf_token() ?>';
    const csrfHash = '<?= csrf_hash() ?>';

    $(document).ready(function() {
      
    

      // DataTable
      $('#edicionTable').DataTable({
        scrollX: true,
        autoWidth: false,
        order: [
          [0, 'desc']
        ],
        columnDefs: [{
            targets: 0,
            type: 'date'
          }, // Periodo
          {
            targets: 8,
            orderable: false
          } // Cumple no ordenable
        ],
        initComplete: function() {
          this.api().columns().every(function(index) {
            const column = this;
            const footerSelect = $('select', column.footer());
            if (footerSelect.length) {
              column.data().unique().sort().each(function(d) {
                footerSelect.append('<option value="' + d + '">' + d + '</option>');
              });
              footerSelect.on('change', function() {
                const val = $.fn.dataTable.util.escapeRegex($(this).val());
                column.search(val ? '^' + val + '$' : '', true, false).draw();
              });
            }
          });
        }
      });

      // Edición inline de “periodo”
      $(document).on('click', '.periodo-cell', function() {
        const cell = $(this);
        const id = cell.data('id');
        const original = cell.text().trim();
        if (cell.find('input').length) return;

        cell.html('<input type="text" class="form-control form-control-sm periodo-input" value="' + original + '">');
        const input = cell.find('input')[0];

        
      });

      // Edición inline de “cumple”
      $(document).on('change', '.select-cumple', function() {
        const select = $(this);
        const td = select.closest('.td-cumple');
        const id = td.data('id');
        const cumple = select.val();
        const data = {
          id_historial: id,
          cumple: cumple
        };
        data[csrfName] = csrfHash;

        $.ajax({
          url: '<?= base_url('jefatura/editarCumpleEquipo') ?>',
          method: 'POST',
          data: data,
          dataType: 'json',
          success: response => {
            if (!response.success) alert('No se pudo actualizar: ' + response.message);
          },
          error: () => alert('Error al comunicar con el servidor.')
        });
      });
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