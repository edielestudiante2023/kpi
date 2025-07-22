<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kpi Cycloid</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Estilos personalizados -->
    <style>
        body {
            background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            border-radius: 15px;
            border: none;
            /* Duplicamos el ancho máximo */
            max-width: 840px;
            width: 100%;
        }
        .input-group-text {
            background-color: #f8f9fa;
        }
        .toggle-password {
            cursor: pointer;
        }
        .toggle-password:hover {
            background-color: #f1f3f5;
        }
        .forgot-link {
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-12">
          <div class="card shadow-lg my-5 mx-auto">
            <div class="card-body p-4 p-sm-5">
              <div class="text-center mb-4">
                  <img src="<?= base_url('img/logoenterprisesstblancoslogan.png') ?>" alt="Logo Kpi Cycloid" class="img-fluid" style="max-height: 80px;">
                  <h2 class="mt-3 mb-1">Iniciar Sesión</h2>
                  <p class="text-muted mb-4">Ingresa tus credenciales para continuar</p>
              </div>

              <!-- Mensajes Flash -->
              <?php if (session()->getFlashdata('success')): ?>
                  <div class="alert alert-success alert-dismissible fade show" role="alert">
                      <?= session()->getFlashdata('success') ?>
                      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>
              <?php endif; ?>
              <?php if (session()->getFlashdata('error')): ?>
                  <div class="alert alert-danger alert-dismissible fade show" role="alert">
                      <?= session()->getFlashdata('error') ?>
                      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>
              <?php endif; ?>

              <form method="post" action="<?= site_url('login') ?>">
                  <?= csrf_field() ?>

                  <!-- Correo -->
                  <div class="mb-3">
                      <label for="correo" class="form-label">Correo electrónico</label>
                      <div class="input-group input-group-lg">
                          <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                          <input type="email" name="correo" id="correo"
                                 class="form-control" placeholder="usuario@ejemplo.com"
                                 required autofocus>
                      </div>
                  </div>

                  <!-- Contraseña -->
                  <div class="mb-3">
                      <label for="password" class="form-label">Contraseña</label>
                      <div class="input-group input-group-lg">
                          <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                          <input type="password" name="password" id="password"
                                 class="form-control" placeholder="••••••••" required>
                          <button type="button" class="btn btn-outline-secondary toggle-password">
                              <i class="bi bi-eye-fill"></i>
                          </button>
                      </div>
                  </div>

                  <!-- Recordar sesión -->
                  <div class="mb-3 form-check">
                      <input type="checkbox" class="form-check-input" id="remember">
                      <label class="form-check-label" for="remember">Recordar sesión</label>
                  </div>

                  <!-- Botón Entrar -->
                  <div class="d-grid mb-2">
                      <button type="submit" class="btn btn-primary btn-lg">
                          <i class="bi bi-box-arrow-in-right me-2"></i> Entrar
                      </button>
                  </div>

                  <!-- Enlace Olvidaste contraseña -->
                  <div class="text-center">
                      <a href="<?= base_url('recuperar') ?>" class="forgot-link text-decoration-none">
                          ¿Olvidaste tu contraseña?
                      </a>
                  </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bootstrap JS Bundle con Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
    <!-- Toggle contraseña -->
    <script>
    document.querySelector('.toggle-password').addEventListener('click', function() {
        const input = document.getElementById('password');
        const icon = this.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('bi-eye-fill', 'bi-eye-slash-fill');
        } else {
            input.type = 'password';
            icon.classList.replace('bi-eye-slash-fill', 'bi-eye-fill');
        }
    });
    </script>
</body>
</html>
