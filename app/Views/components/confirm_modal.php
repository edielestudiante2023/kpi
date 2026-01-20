<?php
/**
 * Componente de modal de confirmación reutilizable.
 *
 * Uso:
 * <?= view('components/confirm_modal', [
 *     'id' => 'deleteUserModal',
 *     'title' => 'Eliminar Usuario',
 *     'message' => '¿Seguro que deseas eliminar este usuario?',
 *     'confirmText' => 'Eliminar',
 *     'confirmClass' => 'btn-danger',
 *     'icon' => 'bi-trash'
 * ]) ?>
 *
 * Luego usar data attributes en el botón:
 * <button data-bs-toggle="modal" data-bs-target="#deleteUserModal"
 *         data-action-url="/users/delete/123"
 *         data-item-name="Juan Perez">
 */

$id = $id ?? 'confirmModal';
$title = $title ?? 'Confirmar accion';
$message = $message ?? '¿Estas seguro de realizar esta accion?';
$confirmText = $confirmText ?? 'Confirmar';
$cancelText = $cancelText ?? 'Cancelar';
$confirmClass = $confirmClass ?? 'btn-danger';
$icon = $icon ?? 'bi-exclamation-triangle';
$method = $method ?? 'get'; // 'get' o 'post'
?>

<div class="modal fade" id="<?= esc($id) ?>" tabindex="-1" aria-labelledby="<?= esc($id) ?>Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="<?= esc($id) ?>Label">
                    <i class="bi <?= esc($icon) ?> me-2 text-warning"></i><?= esc($title) ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="<?= esc($id) ?>Message"><?= esc($message) ?></p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <?= esc($cancelText) ?>
                </button>
                <?php if ($method === 'post'): ?>
                    <form id="<?= esc($id) ?>Form" method="post" class="d-inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn <?= esc($confirmClass) ?>">
                            <i class="bi <?= esc($icon) ?> me-1"></i><?= esc($confirmText) ?>
                        </button>
                    </form>
                <?php else: ?>
                    <a id="<?= esc($id) ?>Link" href="#" class="btn <?= esc($confirmClass) ?>">
                        <i class="bi <?= esc($icon) ?> me-1"></i><?= esc($confirmText) ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('<?= esc($id) ?>');
    if (modal) {
        modal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var actionUrl = button.getAttribute('data-action-url');
            var itemName = button.getAttribute('data-item-name');

            if (actionUrl) {
                <?php if ($method === 'post'): ?>
                var form = document.getElementById('<?= esc($id) ?>Form');
                if (form) form.action = actionUrl;
                <?php else: ?>
                var link = document.getElementById('<?= esc($id) ?>Link');
                if (link) link.href = actionUrl;
                <?php endif; ?>
            }

            if (itemName) {
                var messageEl = document.getElementById('<?= esc($id) ?>Message');
                if (messageEl) {
                    messageEl.innerHTML = '<?= esc($message) ?>'.replace('{item}', '<strong>' + itemName + '</strong>');
                }
            }
        });
    }
});
</script>
