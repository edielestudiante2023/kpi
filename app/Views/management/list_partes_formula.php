<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Partes de Fórmula por Indicador</title>

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- DataTables CSS -->
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <!-- DataTables Buttons CSS -->
  <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
</head>
<body>
  <div class="container mt-4">
    <h3 class="mb-4">🧩 Partes de Fórmula por Indicador</h3>

    <div class="mb-3">
      <a href="<?= site_url('partesformula/add') ?>" class="btn btn-primary">
        ➕ Agregar Parte de Fórmula
      </a>
    </div>

    <div class="table-responsive">
      <table id="partesTable" class="table table-striped table-hover table-bordered nowrap w-100">
        <thead class="table-dark">
          <tr>
            <th>ID</th>
            <th>Indicador</th>
            <th>Tipo de Parte</th>
            <th>Valor</th>
            <th>Orden</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tfoot>
          <tr>
            <th><input type="text" placeholder="Buscar ID" class="form-control form-control-sm" /></th>
            <th><input type="text" placeholder="Buscar Indicador" class="form-control form-control-sm" /></th>
            <th><input type="text" placeholder="Buscar Tipo" class="form-control form-control-sm" /></th>
            <th><input type="text" placeholder="Buscar Valor" class="form-control form-control-sm" /></th>
            <th><input type="text" placeholder="Buscar Orden" class="form-control form-control-sm" /></th>
            <th></th>
          </tr>
        </tfoot>
        <tbody>
          <?php if (! empty($partes)): ?>
            <?php foreach ($partes as $parte): ?>
              <tr>
                <td><?= esc($parte['id_parte_formula']) ?></td>
                <td><?= esc($parte['nombre_indicador']) ?></td>
                <td><?= esc($parte['tipo_parte']) ?></td>
                <td><?= esc($parte['valor']) ?></td>
                <td><?= esc($parte['orden']) ?></td>
                <td>
                  <a href="<?= site_url('partesformula/edit/' . $parte['id_parte_formula']) ?>" class="btn btn-sm btn-warning" target="_blank">✏️ Editar</a>
                  <a href="<?= site_url('partesformula/delete/' . $parte['id_parte_formula']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de eliminar esta parte?')">🗑️ Eliminar</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="text-center">No se encontraron registros.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- DataTables JS -->
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <!-- DataTables Buttons JS -->
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

  <script>
    $(document).ready(function() {
      var table = $('#partesTable').DataTable({
        responsive: true,
        dom: 'Bfrtip',
        buttons: [
          { extend: 'copy',  className: 'btn btn-outline-secondary btn-sm' },
          { extend: 'excel', className: 'btn btn-outline-success btn-sm' },
          { extend: 'pdf',   className: 'btn btn-outline-danger btn-sm' },
          { extend: 'print', className: 'btn btn-outline-info btn-sm' }
        ],
        language: {
          url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        order: [[0, 'desc']], // Orden por defecto por ID descendente
        columnDefs: [
          { orderable: false, targets: -1 }  // Deshabilita orden en columna de acciones
        ],
        initComplete: function () {
          // Aplicar filtros en tfoot
          this.api().columns().every(function () {
            var column = this;
            $('input', column.footer()).on('keyup change clear', function () {
              if (column.search() !== this.value) {
                column
                  .search(this.value)
                  .draw();
              }
            });
          });
        }
      });
    });
  </script>
</body>
</html>
