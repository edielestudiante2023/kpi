<?php
// app/Views/management/list_indicadores.php
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Listado de Indicadores – Kpi Cycloid</title>

  <!-- Bootstrap & DataTables CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <!-- DataTables Buttons CSS -->
  <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
  <!-- Select2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>

  <style>
    #indicadorTable {
      width: 100% !important;
      table-layout: fixed;
      font-family: Arial, sans-serif;
      font-size: 0.875rem;
    }
    #indicadorTable tbody tr { height: 3rem; }
    .cell-content {
      display: block;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      height: 3rem;
      line-height: 3rem;
    }
    .action-cell {
      display: flex;
      flex-direction: column;
      justify-content: center;
      gap: 0.2rem;
      height: 100%;
    }
    .action-cell .btn {
      padding: 0.2rem 0.4rem;
      font-size: 0.75rem;
      line-height: 1rem;
    }
    tfoot input {
      width: 100%;
      box-sizing: border-box;
      padding: 0.2rem;
      font-size: 0.875rem;
    }
  </style>
</head>

<body class="p-0">
  <?= $this->include('partials/nav') ?>

  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3">Listado de Indicadores</h1>
      <div>
        <button id="resetFilters" class="btn btn-secondary me-2">
          <i class="bi bi-arrow-counterclockwise me-1"></i> Restablecer filtros
        </button>
        <a href="<?= base_url('indicadores/add') ?>" class="btn btn-primary me-2">
          <i class="bi bi-plus-lg me-1"></i> Nuevo Indicador
        </a>
      </div>
    </div>

    <!-- Filtro “Nombre Indicador” -->
    <div id="nombreFilter" class="mb-4 d-flex align-items-end">
      <div class="me-3">
        <label for="filterNameDropdown" class="form-label">Buscar Nombre Indicador (Lista)</label>
        <select id="filterNameDropdown" class="form-select">
          <option value="">Todos</option>
          <?php foreach (array_unique(array_column($indicadores, 'nombre')) as $nombre): ?>
            <option value="<?= esc($nombre) ?>"><?= esc($nombre) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label for="filterNameSelect2" class="form-label">Buscar Nombre Indicador (Texto)</label>
        <select id="filterNameSelect2" class="form-select"></select>
      </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
      <?= view('components/alert', ['type' => 'success', 'message' => session()->getFlashdata('success')]) ?>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
      <?= view('components/alert', ['type' => 'danger', 'message' => session()->getFlashdata('error')]) ?>
    <?php endif; ?>

    <?php if (empty($indicadores)): ?>
      <div class="card shadow-sm">
        <div class="card-body">
          <?= view('components/empty_state', [
              'icon' => 'bi-bar-chart',
              'title' => 'Sin indicadores',
              'message' => 'No hay indicadores creados en el sistema.',
              'actionUrl' => base_url('indicadores/add'),
              'actionText' => 'Crear Indicador',
              'actionIcon' => 'bi-plus-lg'
          ]) ?>
        </div>
      </div>
    <?php else: ?>

    <table id="indicadorTable" class="table table-striped table-bordered nowrap w-100">
      <thead class="table-dark align-middle">
        <tr>
          <th>ID</th>
          <th>Nombre Indicador</th>
          <th>Meta Valor</th>
          <th>Meta Descripción</th>
          <th>Tipo Meta</th>
          <th>Fórmula</th>
          <th>Unidad</th>
          <th>Objetivo Proceso</th>
          <th>Objetivo Calidad</th>
          <th>Tipo Aplicación</th>
          <th>Activo</th>
          <th>Periodicidad</th>
          <th>Ponderación (%)</th>
          <th class="text-center">Acciones</th>
        </tr>
      </thead>
      <tfoot class="table-dark align-middle">
        <tr>
          <th></th>
          <th></th>
          <th><input type="text" placeholder="Buscar Meta Valor"></th>
          <th><input type="text" placeholder="Buscar Descripción"></th>
          <th><input type="text" placeholder="Buscar Tipo Meta"></th>
          <th><input type="text" placeholder="Buscar Fórmula"></th>
          <th><input type="text" placeholder="Buscar Unidad"></th>
          <th><input type="text" placeholder="Buscar Obj. Proceso"></th>
          <th><input type="text" placeholder="Buscar Obj. Calidad"></th>
          <th><input type="text" placeholder="Buscar Tipo Aplicación"></th>
          <th><input type="text" placeholder="Buscar Activo"></th>
          <th><input type="text" placeholder="Buscar Periodicidad"></th>
          <th><input type="text" placeholder="Buscar % Ponderación"></th>
          <th><input type="text" placeholder="Buscar Acciones"></th>
        </tr>
      </tfoot>
      <tbody>
        <?php foreach ($indicadores as $i): ?>
          <tr>
            <td><?= esc($i['id_indicador']) ?></td>
            <td>
              <div class="cell-content" data-bs-toggle="tooltip" title="<?= esc($i['nombre']) ?>">
                <?= esc($i['nombre']) ?>
              </div>
            </td>
            <td><?= esc($i['meta_valor'] ?? '—') ?></td>
            <td>
              <div class="cell-content" data-bs-toggle="tooltip" title="<?= esc($i['meta_descripcion'] ?? '') ?>">
                <?= esc($i['meta_descripcion'] ?? '—') ?>
              </div>
            </td>
            <td><?= esc($i['tipo_meta'] ?? '—') ?></td>
            <td>
              <div class="cell-content" data-bs-toggle="tooltip" title="<?= esc($i['formula_renderizada']) ?>">
                <?= esc($i['formula_renderizada']) ?>
              </div>
            </td>
            <td><?= esc($i['unidad'] ?? '—') ?></td>
            <td>
              <div class="cell-content" data-bs-toggle="tooltip" title="<?= esc($i['objetivo_proceso'] ?? '') ?>">
                <?= esc($i['objetivo_proceso'] ?? '—') ?>
              </div>
            </td>
            <td class="small text-muted">
              <div class="cell-content" data-bs-toggle="tooltip" title="<?= esc($i['objetivo_calidad'] ?? '') ?>">
                <?= esc($i['objetivo_calidad'] ?? '—') ?>
              </div>
            </td>
            <td><?= esc($i['tipo_aplicacion'] ?? '—') ?></td>
            <td><?= isset($i['activo']) ? ($i['activo'] ? 'Sí' : 'No') : '—' ?></td>
            <td><?= esc($i['periodicidad'] ?? '—') ?></td>
            <td><?= esc($i['ponderacion'] ?? '0') ?>%</td>
            <td class="text-center">
              <div class="action-cell">
                <a href="<?= base_url('indicadores/edit/' . $i['id_indicador']) ?>" class="btn btn-warning">Editar</a>
                <a href="<?= base_url('indicadores/delete/' . $i['id_indicador']) ?>" class="btn btn-danger" onclick="return confirm('¿Eliminar este indicador?')">Eliminar</a>
                <a href="<?= base_url('indicadores/fill/' . $i['id_indicador']) ?>" class="btn btn-info">Diligenciar</a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
  <!-- DataTables Buttons JS -->
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
  <!-- Select2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <script>
    const nameStorageKey = 'indicadorNameFilter';

    $(document).ready(function() {
      // Inicializar DataTable con botón Excel y menú de longitud
      var table = $('#indicadorTable').DataTable({
        dom: 'Blfrtip',                   // B=Buttons, l=length, f=filter, r=processing, t=table, i=info, p=pagination
        pageLength: 25,                  // registros iniciales
        lengthMenu: [
          [10, 25, 50, 100, -1],
          [10, 25, 50, 100, "Todos"]
        ],
        buttons: [
          {
            extend: 'excelHtml5',
            text: '📥 Exportar a Excel',
            titleAttr: 'Exportar a Excel',
            className: 'btn btn-success btn-sm',
            exportOptions: {
              columns: ':not(:last-child)',  // omitir la columna "Acciones"
              format: {
                body: function(data, row, column, node) {
                  // Solo exportar el texto visible, ignorando atributos y HTML oculto
                  return $(node).text().trim();
                }
              }
            }
          }
        ],
        responsive: true,
        autoWidth: false,
        language: {
          url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json',
          lengthMenu: "Mostrar _MENU_ registros",
          info: "Mostrando _START_ a _END_ de _TOTAL_ indicadores",
          infoFiltered: "(filtrado de _MAX_ registros totales)",
          zeroRecords: "No se encontraron registros"
        },
        initComplete: function() {
          this.api().columns().every(function(idx) {
            if (idx === 0 || idx === 1) return;  // sin filtro en ID y Nombre (se filtra arriba)
            var column = this;
            $('input', column.footer()).on('keyup change clear', function() {
              if (column.search() !== this.value) {
                column.search(this.value).draw();
              }
            });
          });
        }
      });

      // Mover botones al contenedor izquierdo (junto al menú de longitud)
      table.buttons().container()
           .appendTo('#indicadorTable_wrapper .col-md-6:eq(0)');

      // Inicializar tooltips
      document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el);
      });

      // Select2 para búsqueda de Nombre Indicador
      const indicatorNames = <?= json_encode(array_values(array_unique(array_column($indicadores, 'nombre')))); ?>;
      $('#filterNameSelect2').select2({
        data: indicatorNames.map(n => ({ id: n, text: n })),
        placeholder: 'Buscar texto…',
        allowClear: true,
        width: '200px'
      });

      // Restaurar filtros de LocalStorage
      const saved = JSON.parse(localStorage.getItem(nameStorageKey) || '{}');
      if (saved.dropdown) {
        $('#filterNameDropdown').val(saved.dropdown);
        table.column(1).search(saved.dropdown).draw();
      }
      if (saved.select2) {
        $('#filterNameSelect2').val(saved.select2).trigger('change');
        table.column(1).search(saved.select2).draw();
      }

      // Cambios en los filtros de Nombre Indicador
      $('#filterNameDropdown').on('change', function() {
        $('#filterNameSelect2').val(null).trigger('change');
        table.column(1).search(this.value).draw();
        localStorage.setItem(nameStorageKey, JSON.stringify({ dropdown: this.value, select2: '' }));
      });
      $('#filterNameSelect2').on('change', function() {
        $('#filterNameDropdown').val('');
        table.column(1).search(this.value || '').draw();
        localStorage.setItem(nameStorageKey, JSON.stringify({ dropdown: '', select2: this.value }));
      });

      // Reset general de filtros
      $('#resetFilters').on('click', function() {
        localStorage.removeItem(nameStorageKey);
        $('#filterNameDropdown').val('');
        $('#filterNameSelect2').val(null).trigger('change');
        table.columns().every(function(idx) {
          this.search('');
          if (idx > 1) $(this.footer()).find('input').val('');
        });
        table.draw();
      });
    });
  </script>
</body>
</html>
