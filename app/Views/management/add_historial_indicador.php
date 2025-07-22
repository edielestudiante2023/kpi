<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nuevo Registro Historial – Kpi Cycloid</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?= $this->include('partials/nav') ?>

<div class="container py-4">
  <h1 class="h3 mb-4">Nuevo Registro Historial</h1>

  <?php if(session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger">
      <?php foreach(session()->getFlashdata('errors') as $e): ?><p><?= esc($e) ?></p><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form action="<?= base_url('historial_indicador/add') ?>" method="post">
    <?= csrf_field() ?>

    <!-- Asignación Indicador x Perfil -->
    <div class="mb-3">
      <label class="form-label">Asignación Indicador x Perfil</label>
      <select name="id_indicador_perfil" class="form-select" required>
        <option value="">-- Seleccione --</option>
        <?php foreach($asignaciones as $a): ?>
          <option value="<?= esc($a['id_indicador_perfil']) ?>" <?= old('id_indicador_perfil') == $a['id_indicador_perfil'] ? 'selected' : '' ?>>
            <?= esc($a['nombre_indicador']) ?> |
            Periodicidad: <?= esc($a['periodicidad']) ?> |
            Ponderación: <?= esc($a['ponderacion']) ?>% |
            Meta Valor: <?= esc($a['meta_valor']) ?> |
            Meta Desc: <?= esc($a['meta_descripcion']) ?> |
            Tipo Meta: <?= esc($a['tipo_meta']) ?> |
            Método: <?= esc($a['metodo_calculo']) ?> |
            Unidad: <?= esc($a['unidad']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Usuario -->
    <div class="mb-3">
      <label class="form-label">Usuario</label>
      <select name="id_usuario" class="form-select" required>
        <option value="">-- Seleccione --</option>
        <?php foreach($users as $u): ?>
          <option value="<?= esc($u['id_users']) ?>" <?= old('id_usuario') == $u['id_users'] ? 'selected' : '' ?>>
            <?= esc($u['nombre_completo']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Periodo -->
    <div class="mb-3">
      <label class="form-label">Periodo (YYYY-MM)</label>
      <input type="month" name="periodo" class="form-control" value="<?= old('periodo') ?>" required>
    </div>

    <!-- Valores JSON -->
    <div class="mb-3">
      <label class="form-label">Valores JSON</label>
      <textarea name="valores_json" class="form-control" rows="2" required><?= old('valores_json') ?></textarea>
    </div>

    <!-- Resultado Real -->
    <div class="mb-3">
      <label class="form-label">Resultado Real</label>
      <input type="number" step="any" name="resultado_real" class="form-control" value="<?= old('resultado_real') ?>" required>
    </div>

    <!-- Comentario -->
    <div class="mb-3">
      <label class="form-label">Comentario</label>
      <textarea name="comentario" class="form-control" rows="2"><?= old('comentario') ?></textarea>
    </div>

    <div class="alert alert-secondary">
      <strong>Datos informativos:</strong>
      <ul class="mb-0">
        <li>Tipo Aplicación</li>
        <li>Objetivo del Proceso</li>
        <li>Objetivo de Calidad</li>
        <li>Creado en (fecha indicador)</li>
      </ul>
      Estos campos se cargarán automáticamente en el historial.
    </div>

    <button type="submit" class="btn btn-success">Guardar Registro</button>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
