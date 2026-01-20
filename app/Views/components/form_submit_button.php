<?php
/**
 * Componente reutilizable de boton submit con spinner de carga.
 *
 * Uso:
 * <?= view('components/form_submit_button', [
 *     'text' => 'Guardar',           // Texto del boton
 *     'loadingText' => 'Guardando',  // Texto mientras carga (opcional)
 *     'icon' => 'bi-save',           // Icono Bootstrap Icons (opcional)
 *     'class' => 'btn-primary',      // Clase del boton (opcional, default: btn-primary)
 *     'id' => 'btn-submit',          // ID del boton (opcional)
 *     'formId' => 'my-form',         // ID del formulario a vincular (requerido para spinner)
 *     'name' => 'accion',            // Nombre del campo (opcional)
 *     'value' => 'guardar',          // Valor del campo (opcional)
 * ]) ?>
 */

$text = $text ?? 'Guardar';
$loadingText = $loadingText ?? 'Procesando';
$icon = $icon ?? 'bi-check-lg';
$class = $class ?? 'btn-primary';
$id = $id ?? 'btn-submit-' . uniqid();
$formId = $formId ?? '';
$name = $name ?? '';
$value = $value ?? '';
?>

<button type="submit"
        id="<?= esc($id) ?>"
        class="btn <?= esc($class) ?> btn-with-spinner"
        <?= $name ? 'name="' . esc($name) . '"' : '' ?>
        <?= $value ? 'value="' . esc($value) . '"' : '' ?>
        data-loading-text="<?= esc($loadingText) ?>"
        data-original-text="<?= esc($text) ?>">
    <span class="btn-text">
        <i class="bi <?= esc($icon) ?> me-1"></i><?= esc($text) ?>
    </span>
    <span class="btn-spinner d-none">
        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
        <?= esc($loadingText) ?>...
    </span>
</button>

<?php if ($formId): ?>
<script>
(function() {
    var form = document.getElementById('<?= esc($formId) ?>');
    var btn = document.getElementById('<?= esc($id) ?>');

    if (form && btn) {
        form.addEventListener('submit', function(e) {
            // Solo activar spinner si el formulario es valido
            if (form.checkValidity()) {
                var btnText = btn.querySelector('.btn-text');
                var btnSpinner = btn.querySelector('.btn-spinner');

                if (btnText && btnSpinner) {
                    btnText.classList.add('d-none');
                    btnSpinner.classList.remove('d-none');
                    btn.disabled = true;
                }
            }
        });
    }
})();
</script>
<?php endif; ?>
