<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Confirmar Resultado – Afilogro</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 CSS -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet">
</head>

<body class="p-4">
  <?= $this->include('partials/nav') ?>

  <div class="container">
    <h2 class="mb-4">Confirmar Fórmula: <?= esc($indicador['nombre']) ?></h2>

    <p><strong>Fórmula completa:</strong> <code><?= esc($formula) ?></code></p>
    <p><strong>Resultado calculado:</strong> <span class="h4"><?= esc($resultado) ?></span></p>

    <form
      action="<?= base_url('jefatura/formula/guardar/' . $indicador['id_indicador']) ?>"
      method="post">
      <?= csrf_field() ?>

      <!-- Pasamos el resultado -->
      <input
        type="hidden"
        name="resultado"
        value="<?= esc($resultado) ?>">
      <!-- Identificador del indicador -->
      <input
        type="hidden"
        name="id_indicador"
        value="<?= esc($indicador['id_indicador']) ?>">

      <!-- Pasamos cada valor de parte de fórmula -->
      <?php foreach ($formula_partes as $clave => $valor): ?>
        <input
          type="hidden"
          name="formula_partes[<?= esc($clave) ?>]"
          value="<?= esc($valor) ?>">
      <?php endforeach; ?>

      <div class="row mb-3">
        <div class="col-md-4">
          <label for="periodo" class="form-label">Fecha de corte:</label>
          <input
            type="date"
            name="periodo"
            id="periodo"
            class="form-control"
            value="<?= esc(date('Y-m-d')) ?>"
            required>
        </div>
        <div class="col-md-8 d-flex align-items-end">
          <h3 style="color: #6f42c1;">
            📅 Selecciona la fecha real de corte a la que corresponde el resultado que vas a registrar.
          </h3>
        </div>
      </div>
      <button type="submit" class="btn btn-success">Usar este resultado</button>
      <a href="<?= base_url('jefatura/misIndicadoresComoJefe') ?>" class="btn btn-secondary ms-2">
        Cancelar
      </a>
    </form>
  </div>

  <?= $this->include('partials/logout') ?>

  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>