<!-- app/Views/management/edit_historial_indicador.php -->
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Editar Historial Indicador – Kpi Cycloid</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <?= $this->include('partials/nav') ?>

  <div class="container py-4">
    <h1 class="h3 mb-4">Editar Registro Historial</h1>

    <?php if (session()->getFlashdata('errors')): ?>
      <div class="alert alert-danger">
        <?php foreach (session()->getFlashdata('errors') as $e): ?>
          <p><?= esc($e) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form action="<?= base_url('historial_indicador/edit/' . $record['id_historial']) ?>" method="post">
      <?= csrf_field() ?>

      <!-- Editable: Periodo primero -->
      <div class="mb-3">
        <label for="periodo" class="form-label">Periodo (YYYY-MM-DD)</label>
        <input
          type="date"
          id="periodo"
          name="periodo"
          class="form-control"
          value="<?= old('periodo', esc($record['periodo'])) ?>"
          required>
      </div>

          <div class="mb-3">
        <label for="cumple" class="form-label">Cumple</label>
        <select id="cumple" name="cumple" class="form-select">
          <option value="1" <?= old('cumple', $record['cumple']) == 1 ? 'selected' : '' ?>>Sí</option>
          <option value="0" <?= old('cumple', $record['cumple']) == 0 ? 'selected' : '' ?>>No</option>
        </select>
      </div>

      <!-- Asignación Indicador x Perfil -->
      <div class="mb-3">
        <label for="id_indicador_perfil" class="form-label">Asignación Indicador x Perfil</label>
        <select
          id="id_indicador_perfil"
          name="id_indicador_perfil"
          class="form-select"
          required>
          <?php foreach ($asignaciones as $a): ?>
            <option
              value="<?= esc($a['id_indicador_perfil']) ?>"
              <?= $a['id_indicador_perfil'] == $record['id_indicador_perfil'] ? 'selected' : '' ?>>
              <?= esc($a['nombre_indicador']) ?> |
              Periodicidad: <?= esc($a['periodicidad']) ?> |
              Ponderación: <?= esc($a['ponderacion']) ?>% |
              Meta Valor: <?= esc($a['meta_valor']) ?> |
              Meta Desc: <?= esc($a['meta_descripcion']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Usuario -->
      <div class="mb-3">
        <label for="id_usuario" class="form-label">Usuario</label>
        <select
          id="id_usuario"
          name="id_usuario"
          class="form-select"
          required>
          <?php foreach ($users as $u): ?>
            <option
              value="<?= esc($u['id_users']) ?>"
              <?= set_select('id_usuario', $u['id_users'], $record['id_usuario'] == $u['id_users']) ?>>
              <?= esc($u['nombre_completo']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Datos informativos del Indicador -->
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <label class="form-label">Periodicidad</label>
          <input type="text" class="form-control" value="<?= esc($record['periodicidad']) ?>" readonly>
        </div>
        <div class="col-md-6">
          <label class="form-label">Ponderación</label>
          <input type="text" class="form-control" value="<?= esc($record['ponderacion']) ?>%" readonly>
        </div>
        <div class="col-md-6">
          <label class="form-label">Meta Valor</label>
          <input type="text" class="form-control" value="<?= esc($record['meta_valor']) ?>" readonly>
        </div>
        <div class="col-md-6">
          <label class="form-label">Meta Descripción</label>
          <input type="text" class="form-control" value="<?= esc($record['meta_descripcion']) ?>" readonly>
        </div>
        <div class="col-md-6">
          <label class="form-label">Tipo Meta</label>
          <input type="text" class="form-control" value="<?= esc($record['tipo_meta']) ?>" readonly>
        </div>
        <div class="col-md-6">
          <label class="form-label">Método de Cálculo</label>
          <input type="text" class="form-control" value="<?= esc($record['metodo_calculo']) ?>" readonly>
        </div>
        <div class="col-md-6">
          <label class="form-label">Unidad</label>
          <input type="text" class="form-control" value="<?= esc($record['unidad']) ?>" readonly>
        </div>
        <div class="col-md-6">
          <label class="form-label">Objetivo Proceso</label>
          <input type="text" class="form-control" value="<?= esc($record['objetivo_proceso']) ?>" readonly>
        </div>
        <div class="col-md-6">
          <label class="form-label">Objetivo Calidad</label>
          <input type="text" class="form-control" value="<?= esc($record['objetivo_calidad']) ?>" readonly>
        </div>
        <div class="col-md-6">
          <label class="form-label">Tipo Aplicación</label>
          <input type="text" class="form-control" value="<?= esc($record['tipo_aplicacion']) ?>" readonly>
        </div>
        <div class="col-md-6">
          <label class="form-label">Creado en</label>
          <input type="text" class="form-control" value="<?= esc($record['created_at']) ?>" readonly>
        </div>
      </div>

      <!-- Editable: Valores JSON, Resultado, Comentario -->
      <div class="mb-3">
        <label for="valores_json" class="form-label">Valores JSON</label>
        <textarea
          id="valores_json"
          name="valores_json"
          class="form-control"
          rows="2"
          required><?= old('valores_json', esc($record['valores_json'])) ?></textarea>
      </div>

      <div class="mb-3">
        <label for="resultado_real" class="form-label">Resultado Real</label>
        <input
          type="number"
          step="any"
          id="resultado_real"
          name="resultado_real"
          class="form-control"
          value="<?= old('resultado_real', esc($record['resultado_real'])) ?>"
          required>
      </div>

      <div class="mb-3">
        <label for="comentario" class="form-label">Comentario</label>
        <textarea
          id="comentario"
          name="comentario"
          class="form-control"
          rows="2"><?= old('comentario', esc($record['comentario'])) ?></textarea>
      </div>

      <button type="submit" class="btn btn-primary">Actualizar Registro</button>
    </form>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
