<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">


<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>


<script>
  document.addEventListener('DOMContentLoaded', function(){
    flatpickr('.datepicker', {
      locale: 'es',                 // español
      dateFormat: 'Y-m-d',          // formato interno ISO (para el value)
      altInput: true,               // muestra otro input
      altFormat: 'd/m/Y',           // DD/MM/YYYY
      allowInput: true,             // dejar que el usuario escriba
      monthSelectorType: 'dropdown' // selector de mes desplegable
    });
  });
</script>


<form method="get" class="row g-3 mb-4" action="<?= base_url('jefatura/historialmisindicadoresfeje') ?>">
  <div class="col-auto">
    <label for="fecha_desde" class="form-label">Desde:</label>
    <input type="text" id="fecha_desde" name="fecha_desde"
      class="datepicker form-control"
      value="<?= esc($fecha_desde) ?>">
  </div>
  <div class="col-auto">
    <label for="fecha_hasta" class="form-label">Hasta:</label>
    <input type="text" id="fecha_hasta" name="fecha_hasta"
      class="datepicker form-control"
      value="<?= esc($fecha_hasta) ?>">
  </div>
  <div class="col-auto align-self-end">
    <button type="submit" class="btn btn-primary">Filtrar</button>
  </div>
</form>

flatpickr
