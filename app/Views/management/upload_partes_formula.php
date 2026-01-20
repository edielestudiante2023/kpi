<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cargar CSV de Formulas - KPI Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%);
            min-height: 100vh;
        }
        .upload-card {
            max-width: 900px;
            margin: 0 auto;
        }
        .example-table th {
            background-color: #052c65;
            color: white;
            font-size: 0.85rem;
        }
        .example-table td {
            font-family: monospace;
            font-size: 0.85rem;
        }
        .tipo-badge {
            font-size: 0.75rem;
            font-family: monospace;
        }
        .drop-zone {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }
        .drop-zone:hover, .drop-zone.dragover {
            border-color: #0d6efd;
            background-color: #f8f9ff;
        }
        .drop-zone.has-file {
            border-color: #198754;
            background-color: #f0fff4;
        }
    </style>
</head>
<body>
    <?= $this->include('partials/nav') ?>

    <div class="container py-4">
        <div class="upload-card">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1"><i class="bi bi-file-earmark-arrow-up me-2"></i>Cargar CSV de Partes de Formula</h4>
                    <p class="text-muted mb-0">Importacion masiva de componentes de formulas</p>
                </div>
                <a href="<?= site_url('partesformula/list') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Volver
                </a>
            </div>

            <!-- Alertas -->
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?= session()->getFlashdata('success') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <pre class="mb-0" style="white-space: pre-wrap; font-family: inherit;"><?= esc(session()->getFlashdata('error')) ?></pre>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Instrucciones -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Instrucciones</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold">Formato del archivo:</h6>
                            <ul class="small mb-3">
                                <li>Archivo <strong>.csv</strong> con separador <strong>punto y coma (;)</strong></li>
                                <li>La primera fila debe contener los encabezados</li>
                                <li>Codificacion UTF-8 recomendada</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold">Columnas requeridas (en orden):</h6>
                            <ol class="small mb-0">
                                <li><code>id_indicador</code> - ID numerico del indicador</li>
                                <li><code>tipo_parte</code> - Tipo de componente</li>
                                <li><code>valor</code> - Valor o simbolo</li>
                                <li><code>orden</code> - Posicion en la formula</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tipos permitidos -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-tag me-2"></i>Tipos de Parte Permitidos</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tipo (valor exacto)</th>
                                    <th>Descripcion</th>
                                    <th>Ejemplos de valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="badge bg-secondary tipo-badge">dato</span></td>
                                    <td>Variable que el usuario ingresa</td>
                                    <td><code>ventas_mes</code>, <code>total_horas</code>, <code>unidades_producidas</code></td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-info tipo-badge">constante</span></td>
                                    <td>Valor fijo numerico</td>
                                    <td><code>100</code>, <code>0.5</code>, <code>12</code></td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-warning text-dark tipo-badge">operador</span></td>
                                    <td>Operacion matematica</td>
                                    <td><code>+</code>, <code>-</code>, <code>*</code>, <code>/</code></td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-success tipo-badge">parentesis_apertura</span></td>
                                    <td>Abre agrupacion</td>
                                    <td><code>(</code></td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-success tipo-badge">parentesis_cierre</span></td>
                                    <td>Cierra agrupacion</td>
                                    <td><code>)</code></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-warning mt-3 mb-0 small">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>Importante:</strong> Los tipos deben escribirse exactamente como se muestran (minusculas, con guion bajo).
                        No se aceptan variantes como <code>DATO</code>, <code>Dato</code>, <code>variable</code>, etc.
                    </div>
                </div>
            </div>

            <!-- Ejemplo de CSV -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-table me-2"></i>Ejemplo de CSV</h6>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="descargarEjemplo()">
                        <i class="bi bi-download me-1"></i> Descargar Ejemplo
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm example-table mb-0">
                            <thead>
                                <tr>
                                    <th>id_indicador</th>
                                    <th>tipo_parte</th>
                                    <th>valor</th>
                                    <th>orden</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>5</td><td>parentesis_apertura</td><td>(</td><td>1</td></tr>
                                <tr><td>5</td><td>dato</td><td>ventas_realizadas</td><td>2</td></tr>
                                <tr><td>5</td><td>operador</td><td>/</td><td>3</td></tr>
                                <tr><td>5</td><td>dato</td><td>meta_ventas</td><td>4</td></tr>
                                <tr><td>5</td><td>parentesis_cierre</td><td>)</td><td>5</td></tr>
                                <tr><td>5</td><td>operador</td><td>*</td><td>6</td></tr>
                                <tr><td>5</td><td>constante</td><td>100</td><td>7</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="bg-light p-3 border-top">
                        <small class="text-muted">
                            <i class="bi bi-lightbulb me-1"></i>
                            <strong>Formula resultante:</strong>
                            <code>( ventas_realizadas / meta_ventas ) * 100</code>
                            <span class="ms-2">= Porcentaje de cumplimiento de ventas</span>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Formulario de carga -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-upload me-2"></i>Subir Archivo</h6>
                </div>
                <div class="card-body">
                    <form action="<?= site_url('partesformula/upload') ?>" method="post" enctype="multipart/form-data" id="uploadForm">
                        <div class="drop-zone mb-3" id="dropZone">
                            <i class="bi bi-cloud-arrow-up fs-1 text-muted d-block mb-2"></i>
                            <p class="mb-1">Arrastra tu archivo CSV aqui o haz clic para seleccionar</p>
                            <small class="text-muted">Solo archivos .csv con separador punto y coma (;)</small>
                            <input type="file" name="csv_file" id="csv_file" class="d-none" accept=".csv" required>
                        </div>

                        <div id="fileInfo" class="alert alert-info d-none mb-3">
                            <i class="bi bi-file-earmark-text me-2"></i>
                            <span id="fileName"></span>
                            <button type="button" class="btn-close float-end" onclick="limpiarArchivo()"></button>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success" id="btnSubir" disabled>
                                <i class="bi bi-check-lg me-1"></i> Subir y Procesar CSV
                            </button>
                            <a href="<?= site_url('partesformula/list') ?>" class="btn btn-outline-secondary">
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?= $this->include('partials/logout') ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('csv_file');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const btnSubir = document.getElementById('btnSubir');

        // Click en zona de drop
        dropZone.addEventListener('click', () => fileInput.click());

        // Drag and drop
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');

            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                mostrarArchivo(e.dataTransfer.files[0]);
            }
        });

        // Cambio en input file
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length) {
                mostrarArchivo(fileInput.files[0]);
            }
        });

        function mostrarArchivo(file) {
            if (file.name.endsWith('.csv')) {
                fileName.textContent = file.name + ' (' + formatBytes(file.size) + ')';
                fileInfo.classList.remove('d-none');
                dropZone.classList.add('has-file');
                btnSubir.disabled = false;
            } else {
                alert('Por favor selecciona un archivo .csv');
                limpiarArchivo();
            }
        }

        function limpiarArchivo() {
            fileInput.value = '';
            fileInfo.classList.add('d-none');
            dropZone.classList.remove('has-file');
            btnSubir.disabled = true;
        }

        function formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function descargarEjemplo() {
            const contenido = `id_indicador;tipo_parte;valor;orden
5;parentesis_apertura;(;1
5;dato;ventas_realizadas;2
5;operador;/;3
5;dato;meta_ventas;4
5;parentesis_cierre;);5
5;operador;*;6
5;constante;100;7
6;dato;horas_trabajadas;1
6;operador;/;2
6;dato;horas_programadas;3
6;operador;*;4
6;constante;100;5`;

            const blob = new Blob(['\ufeff' + contenido], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'ejemplo_partes_formula.csv';
            link.click();
            URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>
