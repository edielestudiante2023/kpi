<?= $this->extend('bitacora/layout') ?>

<?= $this->section('content') ?>

<?php
$meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
          'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

$mesAnterior   = $mes == 1 ? 12 : $mes - 1;
$anioAnterior  = $mes == 1 ? $anio - 1 : $anio;
$mesSiguiente  = $mes == 12 ? 1 : $mes + 1;
$anioSiguiente = $mes == 12 ? $anio + 1 : $anio;
$esMesActual   = ($anio == date('Y') && $mes == date('n'));

$baseUrl      = 'bitacora/analisis';
$usuarioParam = ($esAdmin && $filtroUsuario) ? '?usuario=' . $filtroUsuario : '';
?>

<h6 class="text-muted mb-3">
    <i class="bi bi-bar-chart-line me-1"></i> Análisis de Actividades
</h6>

<!-- Selector de mes -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="d-flex align-items-center justify-content-between">
            <a href="<?= base_url("{$baseUrl}/{$anioAnterior}/{$mesAnterior}{$usuarioParam}") ?>"
               class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-chevron-left"></i>
            </a>
            <span class="fw-bold"><?= $meses[$mes] ?> <?= $anio ?></span>
            <?php if (!$esMesActual): ?>
                <a href="<?= base_url("{$baseUrl}/{$anioSiguiente}/{$mesSiguiente}{$usuarioParam}") ?>"
                   class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-chevron-right"></i>
                </a>
            <?php else: ?>
                <span class="btn btn-sm btn-outline-secondary disabled">
                    <i class="bi bi-chevron-right"></i>
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Selector de usuario (solo admin/superadmin) -->
<?php if ($esAdmin): ?>
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" action="<?= base_url("{$baseUrl}/{$anio}/{$mes}") ?>">
            <div class="d-flex gap-2 align-items-center">
                <label class="text-muted small mb-0 text-nowrap">
                    <i class="bi bi-people me-1"></i>Usuario:
                </label>
                <select name="usuario" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Todos los usuarios</option>
                    <?php foreach ($usuariosLista as $u): ?>
                        <option value="<?= $u['id_users'] ?>"
                            <?= $filtroUsuario == $u['id_users'] ? 'selected' : '' ?>>
                            <?= esc($u['nombre_completo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ===== BARRA DE SEGMENTADORES ===== -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="row g-2">
            <div class="col-4">
                <label class="text-muted d-block" style="font-size:0.68rem; margin-bottom:2px;">
                    <i class="bi bi-calendar-week me-1"></i>Semana
                </label>
                <select id="filtroSemana" class="form-select form-select-sm">
                    <option value="">Todas</option>
                </select>
            </div>
            <div class="col-4">
                <label class="text-muted d-block" style="font-size:0.68rem; margin-bottom:2px;">
                    <i class="bi bi-building me-1"></i>C. Costo
                </label>
                <select id="filtroCC" class="form-select form-select-sm">
                    <option value="">Todos</option>
                </select>
            </div>
            <div class="col-4">
                <label class="text-muted d-block" style="font-size:0.68rem; margin-bottom:2px;">
                    <i class="bi bi-tag me-1"></i>Actividad
                </label>
                <select id="filtroDesc" class="form-select form-select-sm">
                    <option value="">Todas</option>
                </select>
            </div>
        </div>
        <div class="text-end mt-2">
            <button id="btnLimpiarFiltros" class="btn btn-sm btn-outline-secondary d-none"
                    style="font-size:0.7rem;">
                <i class="bi bi-x-circle me-1"></i>Limpiar filtros
            </button>
        </div>
    </div>
</div>

<!-- Stat cards (actualizadas por JS) -->
<div class="row g-2 mb-3">
    <div class="col-4">
        <div class="total-horas py-2" style="font-size:0.85rem;">
            <i class="bi bi-clock"></i><br>
            <strong id="statHoras"><?= formatMinutosHoras($totalMinutos) ?></strong>
            <div class="small" style="opacity:0.7;">Total</div>
        </div>
    </div>
    <div class="col-4">
        <div class="total-horas py-2" style="font-size:0.85rem;">
            <i class="bi bi-calendar-check"></i><br>
            <strong id="statDias"><?= $totalDias ?></strong>
            <div class="small" style="opacity:0.7;">Días</div>
        </div>
    </div>
    <div class="col-4">
        <div class="total-horas py-2" style="font-size:0.85rem;">
            <i class="bi bi-list-check"></i><br>
            <strong id="statActividades"><?= $totalActividades ?></strong>
            <div class="small" style="opacity:0.7;">Actividades</div>
        </div>
    </div>
</div>

<?php if ($totalMinutos > 0): ?>

<!-- Chart 1: Horas por día -->
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <h6 class="card-title small text-muted mb-2">
            <i class="bi bi-bar-chart me-1"></i>
            <span id="titleDias">Horas por día</span>
        </h6>
        <div style="position:relative; height:180px;">
            <canvas id="chartDias"></canvas>
        </div>
    </div>
</div>

<!-- Chart 2: Tiempo por Centro de Costo (barras clickeables) -->
<div class="card shadow-sm mb-3" id="cardCC">
    <div class="card-body">
        <h6 class="card-title small text-muted mb-2">
            <i class="bi bi-building me-1"></i> Tiempo por Centro de Costo
            <span class="text-muted fw-normal" style="font-size:0.65rem; margin-left:4px;">
                <i class="bi bi-hand-index me-1"></i>Toca una barra para filtrar
            </span>
        </h6>
        <div style="position:relative; height:200px; cursor:pointer;">
            <canvas id="chartCC"></canvas>
        </div>
    </div>
</div>

<!-- Chart 3: Horas por semana -->
<div class="card shadow-sm mb-3" id="cardSemanal">
    <div class="card-body">
        <h6 class="card-title small text-muted mb-2">
            <i class="bi bi-calendar-week me-1"></i> Horas por semana
        </h6>
        <div style="position:relative; height:180px;">
            <canvas id="chartSemanal"></canvas>
        </div>
    </div>
</div>

<!-- Chart 4: Top actividades -->
<div class="card shadow-sm mb-3" id="cardTop">
    <div class="card-body">
        <h6 class="card-title small text-muted mb-2">
            <i class="bi bi-list-ol me-1"></i>
            <span id="titleTop">Top actividades por tiempo</span>
        </h6>
        <div id="wrapperTop" style="position:relative; height:280px;">
            <canvas id="chartTop"></canvas>
        </div>
    </div>
</div>

<!-- Empty state por filtro -->
<div id="emptyFiltro" class="text-center text-muted py-3 d-none">
    <i class="bi bi-funnel fs-2 d-block mb-1"></i>
    Sin datos para los filtros seleccionados
</div>

<?php else: ?>
<div class="text-center text-muted py-4">
    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
    Sin registros en <?= $meses[$mes] ?> <?= $anio ?>
</div>
<?php endif; ?>

<?= $this->endSection() ?>


<?= $this->section('scripts') ?>
<?php if ($totalMinutos > 0): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    /* ── Datos del servidor ───────────────────────────── */
    var RAW      = <?= $chartRawData ?>;
    var SEMANAS  = <?= $semanasOpciones ?>;
    var CCS      = <?= $ccOpciones ?>;
    var DESC     = <?= $descOpciones ?>;
    var DIAS_MES = <?= $diasDelMes ?>;

    var COLORS = ['#0d6efd','#198754','#ffc107','#dc3545',
                  '#0dcaf0','#6f42c1','#fd7e14','#20c997','#6c757d'];

    Chart.defaults.font.size = 11;
    Chart.defaults.plugins.legend.labels.boxWidth = 12;

    /* ── Poblar dropdowns ─────────────────────────────── */
    var elSemana = document.getElementById('filtroSemana');
    var elCC     = document.getElementById('filtroCC');
    var elDesc   = document.getElementById('filtroDesc');

    SEMANAS.forEach(function(s) {
        elSemana.add(new Option(s.label, s.week_num));
    });
    CCS.forEach(function(c) { elCC.add(new Option(c, c)); });
    DESC.forEach(function(d) {
        var label = d.length > 30 ? d.substring(0, 30) + '…' : d;
        elDesc.add(new Option(label, d));
    });

    /* ── Helpers de agregación ────────────────────────── */
    function agruparPor(datos, campo) {
        var map = {};
        datos.forEach(function(r) {
            var k = r[campo];
            if (!map[k]) map[k] = 0;
            map[k] += parseFloat(r.total_minutos);
        });
        return map;
    }

    function topN(map, n) {
        var entries = Object.keys(map).map(function(k) {
            return { key: k, val: map[k] };
        });
        entries.sort(function(a, b) { return b.val - a.val; });
        return entries.slice(0, n);
    }

    function minToH(min) { return Math.round(min / 60 * 100) / 100; }

    function fechasUnicas(datos) {
        var set = {};
        datos.forEach(function(r) { set[r.fecha] = true; });
        return Object.keys(set).length;
    }

    function formatHoras(horas) {
        var h = Math.floor(horas);
        var m = Math.round((horas - h) * 60);
        if (h === 0) return m + 'min';
        return m > 0 ? h + 'h ' + m + 'min' : h + 'h';
    }

    /* ── Crear instancias Chart.js ────────────────────── */
    var charts = {};

    // Chart 1: barras diarias
    charts.dias = new Chart(document.getElementById('chartDias'), {
        type: 'bar',
        data: { labels: [], datasets: [{ label: 'Horas', data: [],
            backgroundColor: 'rgba(13,110,253,0.7)', borderColor: '#0d6efd',
            borderWidth: 1, borderRadius: 3 }] },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false },
                tooltip: { callbacks: { label: function(c) { return c.parsed.y.toFixed(1)+'h'; } } } },
            scales: {
                x: { ticks: { maxTicksLimit: 12, font: { size: 10 } }, grid: { display: false } },
                y: { beginAtZero: true, ticks: { callback: function(v){ return v+'h'; }, font: { size: 10 } } }
            }
        }
    });

    // Chart 2: horizontal bar CC — barras clickeables como filtro
    charts.cc = new Chart(document.getElementById('chartCC'), {
        type: 'bar',
        data: { labels: [], datasets: [{ label: 'Horas', data: [],
            backgroundColor: [], borderColor: [], borderWidth: 1, borderRadius: 3 }] },
        options: {
            indexAxis: 'y',
            responsive: true, maintainAspectRatio: false,
            onClick: function(evt, elements) {
                if (!elements.length) return;
                var label = charts.cc.data.labels[elements[0].index];
                if (label === 'Otros') return; // "Otros" no es filtrable
                var actual = $('#filtroCC').val();
                if (actual === label) {
                    // segundo click → deseleccionar
                    $('#filtroCC').val(null).trigger('change');
                } else {
                    $('#filtroCC').val(label).trigger('change');
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: function(c) {
                    var total = c.dataset.data.reduce(function(a,b){ return a+b; }, 0);
                    var pct = total > 0 ? ((c.parsed.x / total) * 100).toFixed(1) : 0;
                    return c.parsed.x.toFixed(1) + 'h (' + pct + '%)';
                }}}
            },
            scales: {
                x: { beginAtZero: true, ticks: { callback: function(v){ return v+'h'; }, font: { size: 10 } } },
                y: { ticks: { font: { size: 10 } } }
            }
        }
    });

    // Chart 3: barras semanales
    charts.sem = new Chart(document.getElementById('chartSemanal'), {
        type: 'bar',
        data: { labels: [], datasets: [{ label: 'Horas', data: [],
            backgroundColor: 'rgba(25,135,84,0.7)', borderColor: '#198754',
            borderWidth: 1, borderRadius: 4 }] },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { font: { size: 9 } }, grid: { display: false } },
                y: { beginAtZero: true, ticks: { callback: function(v){ return v+'h'; }, font: { size: 10 } } }
            }
        }
    });

    // Chart 4: horizontal bar top actividades
    charts.top = new Chart(document.getElementById('chartTop'), {
        type: 'bar',
        data: { labels: [], datasets: [{ label: 'Horas', data: [],
            backgroundColor: [], borderColor: [], borderWidth: 1, borderRadius: 3 }] },
        options: {
            indexAxis: 'y',
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { callback: function(v){ return v+'h'; }, font: { size: 10 } } },
                y: { ticks: { font: { size: 10 } } }
            }
        }
    });

    /* ── Función principal de filtrado ───────────────── */
    function filtrar() {
        var semana = elSemana.value;
        var cc     = elCC.value;
        var desc   = elDesc.value;

        var datos = RAW.filter(function(r) {
            if (semana !== '' && String(r.week_num) !== semana) return false;
            if (cc     !== '' && r.centro_costo_nombre !== cc)  return false;
            if (desc   !== '' && r.descripcion !== desc)        return false;
            return true;
        });

        var hayFiltro = semana !== '' || cc !== '' || desc !== '';
        var hayDatos  = datos.length > 0;

        // Stat cards
        var totalMin  = datos.reduce(function(s,r){ return s + parseFloat(r.total_minutos); }, 0);
        var totalActs = datos.reduce(function(s,r){ return s + parseInt(r.num_actividades); }, 0);
        document.getElementById('statHoras').textContent       = formatHoras(totalMin / 60);
        document.getElementById('statDias').textContent        = fechasUnicas(datos);
        document.getElementById('statActividades').textContent = totalActs;

        // Mostrar/ocultar empty state y charts
        document.getElementById('emptyFiltro').classList.toggle('d-none', !hayFiltro || hayDatos);
        ['cardCC','cardSemanal','cardTop'].forEach(function(id) {
            document.getElementById(id).style.display = hayDatos ? '' : 'none';
        });

        // Botón limpiar
        document.getElementById('btnLimpiarFiltros').classList.toggle('d-none', !hayFiltro);

        if (!hayDatos) {
            actualizarChartDias([], semana);
            return;
        }

        actualizarChartDias(datos, semana);
        actualizarChartCC(datos);
        actualizarChartSemanal(datos);
        actualizarChartTop(datos, desc);
    }

    /* ── Actualizar chart barras diarias ─────────────── */
    function actualizarChartDias(datos, semanaFiltro) {
        var labels, valores;

        if (semanaFiltro !== '') {
            // Zoom a la semana: solo los días con datos
            var map = agruparPor(datos, 'dia_num');
            var dias = Object.keys(map).map(Number).sort(function(a,b){ return a-b; });
            labels  = dias.map(function(d){ return d; });
            valores = dias.map(function(d){ return minToH(map[d] || 0); });
            document.getElementById('titleDias').textContent = 'Horas por día (semana filtrada)';
        } else {
            // Mes completo con ceros
            var mapDia = agruparPor(datos, 'dia_num');
            labels  = [];
            valores = [];
            for (var d = 1; d <= DIAS_MES; d++) {
                labels.push(d);
                valores.push(minToH(mapDia[d] || 0));
            }
            document.getElementById('titleDias').textContent = 'Horas por día';
        }

        charts.dias.data.labels = labels;
        charts.dias.data.datasets[0].data = valores;
        charts.dias.update('active');
    }

    /* ── Actualizar barras horizontales CC ──────────── */
    function actualizarChartCC(datos) {
        var map  = agruparPor(datos, 'centro_costo_nombre');
        var top8 = topN(map, 8);
        var resto = 0;
        Object.keys(map).forEach(function(k) {
            if (!top8.find(function(e){ return e.key === k; })) resto += map[k];
        });

        var labels = top8.map(function(e){ return e.key; });
        var values = top8.map(function(e){ return minToH(e.val); });
        if (resto > 0) { labels.push('Otros'); values.push(minToH(resto)); }

        var colors = labels.map(function(_, i){ return COLORS[i % COLORS.length]; });
        var ccActivo = $('#filtroCC').val();

        // Resaltar barra activa; opacar las demás si hay filtro
        var bgColors = labels.map(function(lbl, i) {
            if (!ccActivo) return colors[i] + 'BF';
            return lbl === ccActivo ? colors[i] : colors[i] + '40';
        });
        var bdColors = labels.map(function(lbl, i) {
            return lbl === ccActivo ? colors[i] : colors[i] + '80';
        });

        // Ajustar altura según cantidad de barras
        var h = Math.max(100, labels.length * 30 + 20);
        document.getElementById('chartCC').parentElement.style.height = h + 'px';

        charts.cc.data.labels = labels;
        charts.cc.data.datasets[0].data = values;
        charts.cc.data.datasets[0].backgroundColor = bgColors;
        charts.cc.data.datasets[0].borderColor = bdColors;
        charts.cc.update('active');
    }

    /* ── Actualizar barras semanales ─────────────────── */
    function actualizarChartSemanal(datos) {
        var map = agruparPor(datos, 'week_num');
        var semOrdenadas = SEMANAS.filter(function(s){
            return map.hasOwnProperty(s.week_num);
        });

        charts.sem.data.labels = semOrdenadas.map(function(s){ return s.label; });
        charts.sem.data.datasets[0].data = semOrdenadas.map(function(s){
            return minToH(map[s.week_num] || 0);
        });
        charts.sem.update('active');
    }

    /* ── Actualizar top actividades ──────────────────── */
    function actualizarChartTop(datos, descFiltro) {
        var labels, values, colors;

        if (descFiltro !== '') {
            // Modo: distribución de UNA actividad por CC
            var map = agruparPor(datos, 'centro_costo_nombre');
            var entries = topN(map, 10);
            labels = entries.map(function(e){
                return e.key.length > 28 ? e.key.substring(0,28)+'…' : e.key;
            });
            values = entries.map(function(e){ return minToH(e.val); });
            colors = labels.map(function(_, i){ return COLORS[i % COLORS.length]; });
            document.getElementById('titleTop').textContent = 'Centros de costo — ' +
                (descFiltro.length > 25 ? descFiltro.substring(0,25)+'…' : descFiltro);
        } else {
            // Modo: top 10 actividades
            var mapD = agruparPor(datos, 'descripcion');
            var topEntries = topN(mapD, 10);
            labels = topEntries.map(function(e){
                return e.key.length > 28 ? e.key.substring(0,28)+'…' : e.key;
            });
            values = topEntries.map(function(e){ return minToH(e.val); });
            colors = labels.map(function(_, i){ return COLORS[i % COLORS.length]; });
            document.getElementById('titleTop').textContent = 'Top actividades por tiempo';
        }

        // Ajustar altura dinámica
        var h = Math.max(120, labels.length * 30 + 20);
        document.getElementById('wrapperTop').style.height = h + 'px';

        charts.top.data.labels = labels;
        charts.top.data.datasets[0].data   = values;
        charts.top.data.datasets[0].backgroundColor = colors.map(function(c){ return c + 'BF'; });
        charts.top.data.datasets[0].borderColor     = colors;
        charts.top.update('active');
    }

    /* ── Select2 ─────────────────────────────────────── */
    var s2opts = {
        theme: 'bootstrap-5',
        width: '100%',
        allowClear: true,
        language: {
            noResults: function() { return 'Sin resultados'; },
            searching:  function() { return 'Buscando…'; }
        }
    };
    $('#filtroSemana').select2(Object.assign({}, s2opts, { placeholder: 'Todas' }));
    $('#filtroCC').select2(Object.assign({}, s2opts,     { placeholder: 'Todos' }));
    $('#filtroDesc').select2(Object.assign({}, s2opts,   { placeholder: 'Todas' }));

    /* ── Listeners (jQuery — requerido por Select2) ──── */
    $('#filtroSemana, #filtroCC, #filtroDesc').on('change', filtrar);

    document.getElementById('btnLimpiarFiltros').addEventListener('click', function() {
        $('#filtroSemana').val(null).trigger('change');
        $('#filtroCC').val(null).trigger('change');
        $('#filtroDesc').val(null).trigger('change');
    });

    /* ── Render inicial (sin filtros) ────────────────── */
    filtrar();

})();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
