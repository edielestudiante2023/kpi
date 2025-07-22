<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Diligenciar Indicador</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
  <h3><?= esc($indicador['nombre']) ?></h3>
  <form action="<?= base_url("indicadores/fill/{$indicador['id_indicador']}") ?>" method="post">
    <?= csrf_field() ?>

    <p><strong>Fórmula:</strong>
    <?php foreach ($partes as $p): ?>
      <?php if ($p['tipo_parte'] === 'dato'): ?>
        <input type="number"
               step="any"
               name="dato[<?= esc($p['valor']) ?>]"
               placeholder="<?= esc($p['valor']) ?>"
               class="form-control d-inline-block mx-1"
               style="width:100px" required>
      <?php else: ?>
        <span class="mx-1"><?= esc($p['valor']) ?></span>
      <?php endif; ?>
    <?php endforeach; ?>
    </p>

    <button type="submit" class="btn btn-primary">Calcular</button>
    <a href="<?= base_url('indicadores') ?>" class="btn btn-secondary">Volver</a>
  </form>
</body>
</html>
