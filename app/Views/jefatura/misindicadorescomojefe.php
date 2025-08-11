<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Mis Indicadores como Jefatura – Kpi Cycloid</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <!-- Bootstrap 5 CSS -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet">
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

    .table-responsive {
      flex-grow: 1;
    }

    /* Restringir ancho columna Fórmula */
    .col-formula {
      max-width: 20ch;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
  </style>
</head>

<body>
  <?= $this->include('partials/nav') ?>

  <div class="container-fluid py-4">
    <h1 class="h3 mb-4">
      Mis Indicadores como Jefatura – Periodo <?= esc($periodo) ?>
    </h1>

    <?php if (session()->getFlashdata('success')): ?>
      <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php elseif (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('jefatura/saveIndicadoresComoJefe') ?>">
      <?= csrf_field() ?>

      <div class="row mb-3">
        <div class="col-md-4">
          <label for="periodo" class="form-label">Fecha de corte:</label>
          <input
            type="date"
            name="periodo"
            id="periodo"
            class="form-control"
            value="<?= esc($periodo) ?>"
            required>
        </div>
        <div class="col-md-8 d-flex align-items-end">
          <h3 style="color: #6f42c1;">
            📅 Selecciona la fecha real de corte a la que corresponde el resultado que vas a registrar.
          </h3>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered align-middle w-100">
          <thead class="table-dark">
            <tr>
              <th>Indicador</th>
              <th>Meta Valor</th>
              <th>Meta Descripción</th>
              <th>Tipo Meta</th>
              <th class="col-formula">Fórmula</th>
              <th>Calcular</th>
              <th>Unidad</th>
              <th>Objetivo Proceso</th>
              <th>Objetivo Calidad</th>
              <th>Tipo Aplicación</th>
              <th>Periodicidad</th>

              <th>Ponderación (%)</th>
              <th>Resultado</th>
              <th>Comentario</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $i):
              $ip = $i['id_indicador_perfil'];
            ?>
              <tr>
                <td><?= esc($i['nombre_indicador']) ?></td>
                <td><?= esc($i['meta_valor']) ?></td>
                <td><?= esc($i['meta_descripcion']) ?></td>
                <td><?= esc($i['tipo_meta']) ?></td>

                <td class="col-formula">
                  <?php if (isset($formulas[$i['id_indicador']])): ?>
                    <?php foreach ($formulas[$i['id_indicador']] as $parte): ?>
                      <?php if ($parte['tipo_parte'] === 'dato'): ?>
                        <span class="text-primary"><?= esc($parte['valor']) ?></span>
                      <?php else: ?>
                        <span><?= esc($parte['valor']) ?></span>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <code><?= esc($i['metodo_calculo']) ?></code>
                  <?php endif; ?>
                </td>

                <td class="text-center">
                  <a href="<?= base_url('jefatura/formula/' . $i['id_indicador']) ?>"
                    class="btn btn-outline-secondary btn-sm">
                    Diligenciar
                  </a>
                </td>

                <td><?= esc($i['unidad']) ?></td>
                <td class="small"><?= esc($i['objetivo_proceso']) ?></td>
                <td class="small"><?= esc($i['objetivo_calidad']) ?></td>
                <td><?= esc($i['tipo_aplicacion']) ?></td>
                <td><?= esc($i['periodicidad']) ?></td>

                <td><?= esc($i['ponderacion']) ?>%</td>

                <td>
                  <input
                    type="text"
                    name="resultado_real[<?= $ip ?>]"
                    class="form-control resultado-input"
                    data-ip="<?= $ip ?>"
                    placeholder="Ingresa resultado">
                </td>
                <td>
                  <textarea
                    name="comentario[<?= $ip ?>]"
                    class="form-control comentario-input"
                    rows="1"
                    placeholder="Comentario (opcional)"></textarea>
                </td>
                <td class="text-center">
                  <button
                    type="submit"
                    name="single"
                    value="<?= $ip ?>"
                    class="btn btn-success btn-sm save-btn"
                    style="display:none;"
                    data-ip="<?= $ip ?>">
                    Guardar
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="mt-4">
        <a href="<?= base_url('jefatura/jefaturadashboard') ?>" class="btn btn-primary">
          <i class="bi bi-house-door me-1"></i>Dashboard
        </a>
      </div>
    </form>
  </div>

  <?= $this->include('partials/logout') ?>

  <!-- JS -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    $(function() {
      $('.resultado-input').on('input', function() {
        const ip = $(this).data('ip');
        const val = $(this).val().trim();
        const btn = $('.save-btn[data-ip="' + ip + '"]');
        if (val !== '' && val !== '0') {
          btn.show();
        } else {
          btn.hide();
        }
      });
    });
  </script>
</body>

</html>