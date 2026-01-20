<?php
/**
 * Componente de logout con confirmación modal.
 *
 * Uso: <?= $this->include('partials/logout') ?>
 */
?>

<div class="text-center py-3">
    <button type="button" class="btn btn-outline-danger"
            data-bs-toggle="modal" data-bs-target="#logoutConfirmModal">
        <i class="bi bi-box-arrow-right me-1"></i> Cerrar sesion
    </button>
</div>

<?= view('components/confirm_modal', [
    'id' => 'logoutConfirmModal',
    'title' => 'Cerrar sesion',
    'message' => 'Estas seguro de que deseas cerrar sesion?',
    'confirmText' => 'Si, cerrar sesion',
    'cancelText' => 'Cancelar',
    'confirmClass' => 'btn-danger',
    'icon' => 'bi-box-arrow-right',
    'method' => 'get'
]) ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var logoutModal = document.getElementById('logoutConfirmModal');
    if (logoutModal) {
        logoutModal.addEventListener('show.bs.modal', function() {
            var link = document.getElementById('logoutConfirmModalLink');
            if (link) {
                link.href = '<?= base_url('/logout') ?>';
            }
        });
    }
});
</script>

<?= $this->include('partials/heartbeat') ?>
