<!-- app/Views/auth/form_resetear.php -->

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - Kpi Cycloid</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .card {
            border-radius: 15px;
            border: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6"> <!-- Ajuste de ancho para que se vea más amplia -->
                <div class="card shadow-lg my-5">
                    <div class="card-body p-4 p-sm-5">
                        <h3 class="text-center mb-4">Restablecer Contraseña</h3>

                        <!-- Mensajes Flash -->
                        <?php if (session()->getFlashdata('error')): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= session()->getFlashdata('error') ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        <?php if (session()->getFlashdata('success')): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?= session()->getFlashdata('success') ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Formulario de restablecimiento -->
                        <form method="post" action="<?= site_url('resetear/' . $token) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="token" value="<?= esc($token) ?>">

                            <div class="mb-3">
                                <label for="password" class="form-label">Nueva Contraseña</label>
                                <input type="password" class="form-control form-control-lg"
                                    id="password" name="password"
                                    placeholder="••••••••" required autofocus>
                            </div>

                            <div class="mb-3">
                                <label for="password_confirm" class="form-label">Confirmar Contraseña</label>
                                <input type="password" class="form-control form-control-lg"
                                    id="password_confirm" name="password_confirm"
                                    placeholder="••••••••" required>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    Cambiar Contraseña
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-3">
                            <a href="<?= base_url('login') ?>" class="text-decoration-none">
                                Volver al inicio de sesión
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
</body>

</html>