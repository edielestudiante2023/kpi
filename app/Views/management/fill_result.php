<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Resultado Indicador</title></head>
<body class="p-4">
  <h3><?= esc($indicador['nombre']) ?></h3>
  <p><strong>Fórmula evaluada:</strong> <code><?= esc($formula) ?></code></p>
  <p><strong>Resultado:</strong> <?= esc($resultado) ?></p>
  <a href="<?= base_url('indicadores') ?>">Volver al listado</a>
</body>
</html>
