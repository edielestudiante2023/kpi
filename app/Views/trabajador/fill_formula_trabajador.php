<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Diligenciar Fórmula – Kpi Cycloid</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS -->
    <link 
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" 
      rel="stylesheet"
    >
    <style>
      /* Inputs con ancho triplicado y altura duplicada */
      .formula-input {
        width: 300px;
        height: 60px; /* Duplicar altura estándar (~30px) */
        font-size: 1.1rem;
        padding: 10px 12px;
      }
      /* Contenedor flex para inputs y operadores */
      .formula-container {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.75rem;
        margin-top: 10px;
      }
      .formula-container span {
        line-height: 60px; /* Alinear con la altura de los inputs */
        font-size: 1.2rem;
        font-weight: 500;
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
