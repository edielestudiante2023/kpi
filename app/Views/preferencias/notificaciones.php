<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preferencias de Notificación – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>

<body>
    <?= $this->include('partials/nav') ?>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex align-items-center gap-2">
                        <?= view('components/back_to_dashboard') ?>
                        <h1 class="h3 mb-0">
                            <i class="bi bi-bell me-2"></i>Preferencias de Notificacion
                        </h1>
                    </div>
                </div>

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle me-2"></i>
                        <?= session()->getFlashdata('success') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-gear me-2"></i>Configurar Notificaciones por Email</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">
                            Selecciona qué notificaciones deseas recibir por correo electrónico relacionadas con el módulo de actividades.
                        </p>

                        <form action="<?= base_url('preferencias/notificaciones/guardar') ?>" method="post">
                            <?= csrf_field() ?>

                            <div class="mb-4">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        id="notif_asignacion" name="notif_asignacion" value="1"
                                        <?= $preferencias['notif_asignacion'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="notif_asignacion">
                                        <strong>Asignación de actividades</strong>
                                        <br><small class="text-muted">Recibir email cuando me asignen una nueva actividad</small>
                                    </label>
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        id="notif_cambio_estado" name="notif_cambio_estado" value="1"
                                        <?= $preferencias['notif_cambio_estado'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="notif_cambio_estado">
                                        <strong>Cambios de estado</strong>
                                        <br><small class="text-muted">Recibir email cuando cambie el estado de una actividad en la que participo</small>
                                    </label>
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        id="notif_comentarios" name="notif_comentarios" value="1"
                                        <?= $preferencias['notif_comentarios'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="notif_comentarios">
                                        <strong>Nuevos comentarios</strong>
                                        <br><small class="text-muted">Recibir email cuando agreguen comentarios a mis actividades</small>
                                    </label>
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        id="notif_vencimiento" name="notif_vencimiento" value="1"
                                        <?= $preferencias['notif_vencimiento'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="notif_vencimiento">
                                        <strong>Recordatorios de vencimiento</strong>
                                        <br><small class="text-muted">Recibir email cuando una actividad esté próxima a vencer o vencida</small>
                                    </label>
                                </div>

                                <hr>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        id="resumen_diario" name="resumen_diario" value="1"
                                        <?= $preferencias['resumen_diario'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="resumen_diario">
                                        <strong>Resumen diario</strong>
                                        <br><small class="text-muted">Recibir un resumen diario con el estado de todas mis actividades pendientes</small>
                                    </label>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-1"></i> Guardar Preferencias
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mt-4 border-info">
                    <div class="card-body">
                        <h6 class="card-title text-info"><i class="bi bi-info-circle me-2"></i>Información</h6>
                        <p class="card-text small mb-0">
                            Las notificaciones se envían al correo registrado en tu perfil:
                            <strong><?= esc(session()->get('correo') ?? 'No disponible') ?></strong>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
