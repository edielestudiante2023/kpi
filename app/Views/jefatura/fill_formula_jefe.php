<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Diligenciar Fórmula – Kpi Cycloid Jefatura</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS -->
    <link 
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" 
      rel="stylesheet"
    >
    <style>
      .formula-container{ display:flex; flex-wrap:wrap; align-items:flex-start; gap:.75rem; }
      .formula-group{ display:grid; grid-template-columns: 1fr 120px; column-gap:.5rem; align-items:center; flex:1 1 520px; min-width:420px; max-width:680px; }
      .formula-label{ padding:8px 10px; border:1px solid #dee2e6; border-radius:.375rem; background:#f8f9fa; font-size:.95rem; white-space:normal; overflow:visible; }
      .formula-input{ height:56px; width:120px; font-size:1rem; overflow-x:auto; white-space:nowrap; }
      .formula-token{ line-height:56px; font-weight:500; }
    </style>
</head>
<body class="p-4">
    <?= $this->include('partials/nav') ?>

    <div class="container">
        <h2 class="mb-4">Diligenciar Fórmula: <?= esc($indicador['nombre']) ?></h2>
        <form 
          action="<?= base_url('jefatura/formula/evaluar/' . $indicador['id_indicador']) ?>" 
          method="post"
        >
            <?= csrf_field() ?>

            <div class="mb-3">
                <strong class="me-2">Fórmula:</strong>
                <div class="formula-container">
                  <?php foreach ($partes as $p): ?>
                    <?php if ($p['tipo_parte'] === 'dato'): ?>
                      <div class="formula-group">
                        <span class="formula-label" title="<?= esc($p['valor']) ?>"><?= esc($p['valor']) ?></span>
                        <input type="number" step="any" name="dato[<?= esc($p['valor']) ?>]" placeholder="0" class="form-control formula-input" required>
                      </div>
                    <?php else: ?>
                      <span class="formula-token"><?= esc($p['valor']) ?></span>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary me-2">Calcular</button>
            <a href="<?= base_url('trabajador/misIndicadores') ?>" class="btn btn-secondary">Volver</a>
        </form>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
