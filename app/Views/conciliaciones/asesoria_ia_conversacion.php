<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($conversacion['titulo']) ?> – Asesoría IA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .msg-user {
            background: #e7f1ff; padding:14px 16px; border-radius:14px;
            margin-bottom:16px;
        }
        .msg-assistant {
            background: #fff; padding:18px 20px; border-radius:14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom:16px;
            border-left: 4px solid #6f42c1;
        }
        .msg-assistant h2, .msg-assistant h3 { font-size:1.1rem; margin-top:1rem; }
        .msg-assistant h1 { font-size:1.25rem; margin-top:1rem; }
        .msg-assistant ul, .msg-assistant ol { padding-left:1.4rem; }
        .msg-assistant table { font-size:0.9rem; margin:0.8rem 0; }
        .msg-assistant code { background:#f5f5f5; padding:2px 6px; border-radius:4px; }
        .meta-block { font-size:0.75rem; color:#6c757d; margin-top:10px; }
        .tool-call {
            background: #f8f9fa; border-left:3px solid #20c997;
            padding:8px 12px; margin:6px 0; font-family: monospace; font-size:0.8rem;
            border-radius:4px;
        }
    </style>
</head>
<body class="bg-light">
<?= $this->include('partials/nav') ?>

<div class="container py-4" style="max-width:900px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h5 mb-0"><?= esc($conversacion['titulo']) ?></h1>
            <small class="text-muted">
                <i class="bi bi-person me-1"></i><?= esc($conversacion['creado_por']) ?>
                · <?= date('d/m/Y H:i', strtotime($conversacion['created_at'])) ?>
                · <span class="badge bg-secondary"><?= esc($conversacion['tipo']) ?></span>
            </small>
        </div>
        <a href="<?= base_url('conciliaciones/asesoria-ia') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>

    <?php foreach ($mensajes as $m): ?>
        <?php if ($m['rol'] === 'user'): ?>
            <div class="msg-user">
                <i class="bi bi-person-fill text-primary me-1"></i><strong>Solicitud</strong>
                <p class="mb-0 mt-1"><?= esc($m['contenido']) ?></p>
            </div>
        <?php elseif ($m['rol'] === 'assistant'): ?>
            <div class="msg-assistant">
                <i class="bi bi-robot text-primary me-1"></i><strong>Análisis</strong>
                <div id="md-<?= $m['id_mensaje'] ?>" class="markdown-content mt-2"></div>
                <textarea id="raw-<?= $m['id_mensaje'] ?>" hidden><?= esc($m['contenido']) ?></textarea>

                <?php
                $toolCalls = json_decode($m['tool_calls'] ?? '[]', true) ?: [];
                if (!empty($toolCalls)):
                ?>
                <details class="mt-3">
                    <summary class="text-muted small" style="cursor:pointer;">
                        <i class="bi bi-tools me-1"></i>Herramientas consultadas (<?= count($toolCalls) ?>)
                    </summary>
                    <?php foreach ($toolCalls as $tc): ?>
                        <div class="tool-call">
                            <strong><?= esc($tc['name']) ?></strong>(<?= esc(json_encode($tc['input'], JSON_UNESCAPED_UNICODE)) ?>)
                        </div>
                    <?php endforeach; ?>
                </details>
                <?php endif; ?>

                <div class="meta-block">
                    <i class="bi bi-cpu me-1"></i><strong>Modelo:</strong> <?= esc($m['modelo']) ?>
                    · <strong>Tokens:</strong> <?= number_format($m['tokens_input']) ?> in / <?= number_format($m['tokens_output']) ?> out
                    <?php if ($m['tokens_cache_read'] > 0): ?>
                        · <strong>Caché hit:</strong> <?= number_format($m['tokens_cache_read']) ?>
                    <?php endif; ?>
                    · <strong>Costo:</strong> $<?= number_format($m['costo_usd'], 4) ?> USD
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <div class="alert alert-light border mt-3" style="font-size:0.85rem;">
        <i class="bi bi-info-circle me-1"></i>
        Este análisis fue generado por IA usando los datos reales del sistema al momento de la consulta. Las recomendaciones son sugerencias para apoyar la toma de decisiones, no reemplazan el juicio profesional.
        <span class="float-end text-muted">
            Consumo del mes: $<?= number_format($costoMes, 3) ?> / $<?= number_format($budgetMes, 2) ?>
        </span>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/marked@11.1.1/marked.min.js"></script>
<script>
document.querySelectorAll('.markdown-content').forEach(div => {
    const raw = document.getElementById('raw-' + div.id.replace('md-', ''));
    if (raw) {
        div.innerHTML = marked.parse(raw.value);
        // Bootstrap tables
        div.querySelectorAll('table').forEach(t => t.classList.add('table', 'table-sm', 'table-bordered'));
    }
});
</script>
</body>
</html>
