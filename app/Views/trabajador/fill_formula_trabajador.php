<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Diligenciar Fórmula – Afilogro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS -->
    <link 
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" 
      rel="stylesheet"
    >
    <style>
      /* Inputs con ancho triplicado */
      .formula-input {
        width: 300px;
      }
      /* Contenedor flex para inputs y operadores */
      .formula-container {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
      }
      .formula-container span {
        line-height: 1.6;
      }
    </style>
</head>
<body class="p-4">
    <?= $this->include('partials/nav') ?>

    <div class="container">
        <h2 class="mb-4">Diligenciar Fórmula: <?= esc($indicador['nombre']) ?></h2>
        <form 
          action="<?= base_url('trabajador/formula/evaluar/' . $indicador['id_indicador']) ?>" 
          method="post"
        >
            <?= csrf_field() ?>

            <div class="mb-3">
                <strong class="me-2">Fórmula:</strong>
                <div class="formula-container">
                  <?php foreach ($partes as $p): ?>
                      <?php if ($p['tipo_parte'] === 'dato'): ?>
                          <input 
                            type="number"
                            step="any"
                            name="dato[<?= esc($p['valor']) ?>]"
                            placeholder="<?= esc($p['valor']) ?>"
                            class="form-control formula-input"
                            required
                          >
                      <?php else: ?>
                          <span><?= esc($p['valor']) ?></span>
                      <?php endif; ?>
                  <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary me-2">Calcular</button>
            <a href="<?= base_url('trabajador/misIndicadores') ?>" class="btn btn-secondary">Volver</a>
        </form>
    </div>

    <?= $this->include('partials/logout') ?>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
