<!-- app/Views/management/list_historial_indicador.php -->
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Historial Indicadores – Kpi Cycloid</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
</head>
<body>
  <?= $this->include('partials/nav') ?>

  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3">Historial Indicadores</h1>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
      <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>

    <div class="table-responsive">
      <table id="histTable" class="table table-striped table-bordered nowrap w-100">
        <thead class="table-dark">
          <tr>
            <th class="text-center">Acciones</th>
            <th>Periodo</th>
            <th>Indicador</th>
            <th>Perfil</th>
            <th>Usuario</th>
            <th>Periodicidad</th>
            <th>Ponderación</th>
            <th>Meta Valor</th>
            <th>Meta Descripción</th>
            <th>Tipo Meta</th>
            <th>Método Cálculo</th>
            <th>Unidad</th>
            <th>Resultado</th>
            <th>Comentario</th>
            <th>Cumple</th>
            <th>Fecha Registro</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($records as $r): ?>
          <tr>
            <td class="text-center">
              <a href="<?= base_url('historial_indicador/edit/'.$r['id_historial']) ?>" class="btn btn-sm btn-warning me-1">Editar</a>
              <a href="<?= base_url('historial_indicador/delete/'.$r['id_historial']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar registro?')">Eliminar</a>
            </td>
            <td><?= esc($r['periodo']) ?></td>
            <td><?= esc($r['indicador']) ?></td>
            <td><?= esc($r['perfil']) ?></td>
            <td><?= esc($r['usuario']) ?></td>
            <td><?= esc($r['periodicidad']) ?></td>
            <td>
              <?= esc($r['ponderacion']) ?><?= esc($r['unidad']) === '%' ? '%' : '' ?>
            </td>
            <td><?= esc($r['meta_valor']) ?></td>
            <td><?= esc($r['meta_descripcion']) ?></td>
            <td><?= esc($r['tipo_meta']) ?></td>
            <td><?= esc($r['metodo_calculo']) ?></td>
            <td><?= esc($r['unidad']) ?></td>
            <td><?= esc($r['resultado_real']) ?></td>
            <td><?= esc($r['comentario']) ?></td>
            <td><?= $r['cumple'] ? 'Sí' : 'No' ?></td>
            <td><?= esc($r['fecha_registro']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?= $this->include('partials/logout') ?>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
  <script>
    $(document).ready(function() {
      $('#histTable').DataTable({
        responsive: true,
        autoWidth: false,
        dom: 'lBfrtip',              // Mostrar selector de registros y botones
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
        buttons: [
          {
            extend: 'excelHtml5',
            text: 'Descargar Excel',
            titleAttr: 'Exportar a Excel'
          }
        ],
        order: [[15, 'desc']],
        language: {
          url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        }
      });
    });
  </script>
</body>
</html>
