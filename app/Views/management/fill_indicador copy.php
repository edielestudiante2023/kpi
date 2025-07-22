<!-- app/Views/management/fill_indicador.php -->
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Diligenciar Indicador – <?= esc($indicador['nombre']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?= $this->include('partials/nav') ?>

<div class="container py-4">
  <h1 class="h3 mb-4">Diligenciar: <?= esc($indicador['nombre']) ?></h1>
  <p class="text-muted">Aplica a: <?= esc($indicador['tipo_aplicacion']) ?> — Unidad: <?= esc($indicador['unidad']) ?></p>

  <form action="<?= base_url('indicadores/fill/'.$indicador['id_indicador']) ?>" method="post">
    <?= csrf_field() ?>

    <div class="mb-3">
      <strong>Fórmula:</strong>
      <div class="border rounded p-3">
        <?php foreach($partes as $parte): ?>
          <?php if ($parte['tipo_parte'] === 'dato'): ?>
            <input 
              type="number" 
              step="any"
              name="dato[<?= esc($parte['valor']) ?>]" 
              class="form-control d-inline-block mx-1" 
              style="width: 100px;" 
              placeholder="<?= esc($parte['valor']) ?>"
              required>
          <?php else: ?>
            <span class="mx-1"><?= esc($parte['valor']) ?></span>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center">
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-calculator me-1"></i> Calcular
      </button>
      <a href="<?= base_url('indicadores') ?>" class="btn btn-link">&larr; Volver</a>
    </div>
  </form>
</div>

<?= $this->include('partials/logout') ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
