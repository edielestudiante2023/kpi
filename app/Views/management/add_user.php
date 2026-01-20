<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Crear Nuevo Usuario – Kpi Cycloid</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
  <style>
    .select2-container .select2-selection--single {
      height: calc(1.5em + .75rem + 2px);
      padding: .375rem .75rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
      height: calc(1.5em + .75rem + 2px);
    }
    .select2-container.is-invalid .select2-selection {
      border-color: #dc3545 !important;
    }
  </style>
</head>
<body>
  <?= $this->include('partials/nav') ?>

  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3">Crear Nuevo Usuario</h1>
      <a href="<?= base_url('users') ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i> Volver al listado
      </a>
    </div>

    <?php if (session()->getFlashdata('errors')): ?>
      <?= view('components/alert', [
          'type' => 'danger',
          'message' => '<ul class="mb-0">' . implode('', array_map(fn($e) => '<li>' . esc($e) . '</li>', session()->getFlashdata('errors'))) . '</ul>',
          'dismissible' => true,
          'icon' => 'bi-exclamation-triangle'
      ]) ?>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
      <?= view('components/alert', ['type' => 'success', 'message' => session()->getFlashdata('success')]) ?>
    <?php endif; ?>

    <div class="card shadow-sm">
      <div class="card-body">
        <form id="form-add-user" action="<?= base_url('users/add') ?>" method="post" novalidate>
          <?= csrf_field() ?>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="nombre_completo" class="form-label">Nombre completo</label>
              <input type="text" id="nombre_completo" name="nombre_completo"
                     class="form-control" value="<?= old('nombre_completo') ?>" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="documento_identidad" class="form-label">Documento de identidad</label>
              <input type="text" id="documento_identidad" name="documento_identidad"
                     class="form-control" value="<?= old('documento_identidad') ?>" required>
            </div>
          </div>

          <div class="mb-3">
            <label for="correo" class="form-label">Correo electrónico</label>
            <input type="email" id="correo" name="correo"
                   class="form-control" value="<?= old('correo') ?>" required>
          </div>

          <div class="mb-3">
            <label for="cargo" class="form-label">Cargo <small class="text-muted">(se asignará automáticamente al seleccionar perfil)</small></label>
            <input type="text" id="cargo" name="cargo"
                   class="form-control bg-light" value="<?= old('cargo') ?>"
                   placeholder="Se completará en el siguiente paso" readonly>
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">Contraseña</label>
            <div class="input-group">
              <input type="password" id="password" name="password"
                     class="form-control" placeholder="••••••••" required>
              <button type="button" class="btn btn-outline-secondary toggle-password">
                <i class="bi bi-eye-fill"></i>
              </button>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="id_roles" class="form-label">Rol</label>
              <select id="id_roles" name="id_roles"
                      class="form-select select2"
                      data-placeholder="Seleccione un rol"
                      data-required="true">
                <option value=""></option>
                <?php foreach ($roles as $role): ?>
                  <option value="<?= esc($role['id_roles']) ?>"
                    <?= old('id_roles') == $role['id_roles'] ? 'selected' : '' ?>>
                    <?= esc($role['nombre_rol']) ?>
                  </option>
                <?php endforeach ?>
              </select>
            </div>

            <div class="col-md-4 mb-3">
              <label for="id_areas" class="form-label">Área</label>
              <select id="id_areas" name="id_areas"
                      class="form-select select2"
                      data-placeholder="Seleccione un área"
                      data-required="true">
                <option value=""></option>
                <?php foreach ($areas as $area): ?>
                  <option value="<?= esc($area['id_areas']) ?>"
                    <?= old('id_areas') == $area['id_areas'] ? 'selected' : '' ?>>
                    <?= esc($area['nombre_area']) ?>
                  </option>
                <?php endforeach ?>
              </select>
            </div>

            <div class="col-md-4 mb-3">
              <label for="activo" class="form-label">Estado</label>
              <select id="activo" name="activo"
                      class="form-select select2"
                      data-placeholder="Seleccione estado"
                      data-required="true">
                <option value=""></option>
                <option value="1" <?= old('activo') === '1' ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= old('activo') === '0' ? 'selected' : '' ?>>Inactivo</option>
              </select>
            </div>
          </div>

          <div class="d-flex justify-content-end mt-4">
            <?= view('components/form_submit_button', [
                'text' => 'Guardar y continuar',
                'loadingText' => 'Guardando',
                'icon' => 'bi-arrow-right-circle',
                'class' => 'btn-primary',
                'formId' => 'form-add-user'
            ]) ?>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script>
    $(document).ready(function() {
      $('.toggle-password').on('click', function() {
        const $input = $(this).closest('.input-group').find('input');
        const $icon = $(this).find('i');
        if ($input.attr('type') === 'password') {
          $input.attr('type', 'text');
          $icon.removeClass('bi-eye-fill').addClass('bi-eye-slash-fill');
        } else {
          $input.attr('type', 'password');
          $icon.removeClass('bi-eye-slash-fill').addClass('bi-eye-fill');
        }
      });

      $('.select2').each(function() {
        const $select = $(this);
        const placeholder = $select.data('placeholder') || 'Seleccione una opción';
        $select.select2({
          theme: 'bootstrap-5',
          placeholder: placeholder,
          allowClear: true,
          width: '100%'
        });
      });

      $('#form-add-user').on('submit', function(e) {
        let valid = true;
        $('.select2').each(function() {
          if ($(this).data('required') && (!$(this).val() || $(this).val().length === 0)) {
            valid = false;
            $(this).next('.select2-container').addClass('is-invalid');
          } else {
            $(this).next('.select2-container').removeClass('is-invalid');
          }
        });
        if (!valid) {
          e.preventDefault();
          alert('Por favor completa todos los campos obligatorios.');
        }
      });
    });
  </script>
</body>
</html>
