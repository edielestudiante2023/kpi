<?php
/**
 * Widget flotante OTTO — Asesor Financiero IA
 * Se incluye desde partials/nav.php (visible solo a roles 1/2/3).
 */
$rolId = (int) (session()->get('rol_id') ?? 0);
if (! in_array($rolId, [1, 2, 3], true)) return;
?>
<style>
:root {
    --otto-bg: #1d2638;
    --otto-bg-soft: #2a3450;
    --otto-text: #ffffff;
}

#otto-launcher {
    position: fixed;
    bottom: 24px;
    right: 24px;
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: var(--otto-bg);
    box-shadow: 0 6px 20px rgba(29, 38, 56, 0.35);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1060;
    transition: transform 0.18s ease, box-shadow 0.18s ease;
    border: none;
}
#otto-launcher:hover { transform: scale(1.06); box-shadow: 0 8px 26px rgba(29, 38, 56, 0.45); }
#otto-launcher img { width: 42px; height: 42px; }
#otto-launcher .otto-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #dc3545;
    color: #fff;
    border-radius: 999px;
    font-size: 0.65rem;
    padding: 2px 6px;
    font-weight: 600;
    display: none;
}

#otto-panel {
    position: fixed;
    bottom: 100px;
    right: 24px;
    width: 380px;
    max-width: calc(100vw - 32px);
    height: 600px;
    max-height: calc(100vh - 120px);
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 12px 32px rgba(0,0,0,0.18);
    display: none;
    flex-direction: column;
    z-index: 1060;
    overflow: hidden;
    font-family: -apple-system, "Segoe UI", Roboto, sans-serif;
}
#otto-panel.open { display: flex; animation: ottoSlideUp 0.22s ease; }
@keyframes ottoSlideUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}

.otto-header {
    background: var(--otto-bg);
    color: var(--otto-text);
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.otto-header img { width: 40px; height: 40px; border-radius: 50%; background: var(--otto-bg-soft); padding: 4px; }
.otto-header .otto-name { font-weight: 700; font-size: 1rem; line-height: 1; }
.otto-header .otto-subtitle { font-size: 0.72rem; opacity: 0.8; margin-top: 2px; }
.otto-header .otto-actions { margin-left: auto; display: flex; gap: 8px; }
.otto-header button {
    background: transparent; border: none; color: #fff; cursor: pointer; padding: 4px;
    border-radius: 4px; opacity: 0.7; transition: opacity 0.15s;
}
.otto-header button:hover { opacity: 1; background: rgba(255,255,255,0.1); }

.otto-body {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    background: #f6f8fa;
}

.otto-msg { display: flex; margin-bottom: 10px; align-items: flex-end; gap: 8px; }
.otto-msg.user { flex-direction: row-reverse; }
.otto-msg .avatar {
    width: 28px; height: 28px; border-radius: 50%; background: var(--otto-bg);
    flex-shrink: 0; display: flex; align-items: center; justify-content: center;
}
.otto-msg .avatar img { width: 20px; height: 20px; }
.otto-msg.user .avatar { background: #e9ecef; }
.otto-msg.user .avatar i { color: #6c757d; }

.otto-bubble {
    max-width: 78%;
    padding: 9px 12px;
    border-radius: 14px;
    font-size: 0.88rem;
    line-height: 1.4;
    word-wrap: break-word;
}
.otto-msg.assistant .otto-bubble {
    background: #fff;
    border: 1px solid #e9ecef;
    border-bottom-left-radius: 4px;
    color: #212529;
}
.otto-msg.user .otto-bubble {
    background: var(--otto-bg);
    color: #fff;
    border-bottom-right-radius: 4px;
}
.otto-msg .otto-bubble h1,
.otto-msg .otto-bubble h2,
.otto-msg .otto-bubble h3 { font-size: 0.95rem; margin: 0.4rem 0 0.3rem; font-weight: 700; }
.otto-msg .otto-bubble p { margin: 0.3rem 0; }
.otto-msg .otto-bubble ul,
.otto-msg .otto-bubble ol { margin: 0.3rem 0; padding-left: 1.2rem; }
.otto-msg .otto-bubble li { margin: 0.15rem 0; }
.otto-msg .otto-bubble strong { color: #1d2638; }
.otto-msg .otto-bubble table { width: 100%; border-collapse: collapse; font-size: 0.78rem; margin: 0.4rem 0; }
.otto-msg .otto-bubble th,
.otto-msg .otto-bubble td { border: 1px solid #dee2e6; padding: 4px 6px; }
.otto-msg .otto-bubble th { background: #f1f3f5; }
.otto-msg .otto-bubble code {
    background: #f1f3f5; padding: 1px 5px; border-radius: 3px;
    font-size: 0.82rem;
}

.otto-typing { display: flex; align-items: center; gap: 3px; padding: 9px 12px; }
.otto-typing span {
    width: 6px; height: 6px; border-radius: 50%; background: #adb5bd;
    animation: ottoBlink 1.2s infinite;
}
.otto-typing span:nth-child(2) { animation-delay: 0.15s; }
.otto-typing span:nth-child(3) { animation-delay: 0.3s; }
@keyframes ottoBlink {
    0%,80%,100% { opacity: 0.3; transform: scale(0.85); }
    40%         { opacity: 1;   transform: scale(1); }
}

.otto-chips { display: flex; flex-wrap: wrap; gap: 6px; margin: 8px 0 4px; padding-left: 36px; }
.otto-chip {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 999px;
    padding: 5px 11px;
    font-size: 0.75rem;
    cursor: pointer;
    transition: background 0.12s, border-color 0.12s;
    color: #212529;
}
.otto-chip:hover { background: var(--otto-bg); color: #fff; border-color: var(--otto-bg); }

.otto-footer {
    border-top: 1px solid #e9ecef;
    padding: 10px 12px;
    background: #fff;
}
.otto-input-row { display: flex; gap: 8px; align-items: center; }
.otto-input {
    flex: 1;
    border: 1px solid #ced4da;
    border-radius: 999px;
    padding: 8px 14px;
    font-size: 0.85rem;
    outline: none;
}
.otto-input:focus { border-color: var(--otto-bg); }
.otto-input:disabled { background: #f8f9fa; cursor: wait; }
.otto-send {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: var(--otto-bg);
    color: #fff;
    border: none;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: opacity 0.15s;
}
.otto-send:disabled { opacity: 0.5; cursor: not-allowed; }
.otto-cost {
    text-align: center;
    font-size: 0.65rem;
    color: #adb5bd;
    margin-top: 6px;
}
.otto-cost .over { color: #dc3545; font-weight: 600; }
</style>

<!-- Botón flotante -->
<button id="otto-launcher" title="Hablar con OTTO" type="button">
    <img src="<?= base_url('img/otto-avatar.png') ?>" alt="OTTO">
    <span class="otto-badge" id="otto-badge">!</span>
</button>

<!-- Panel chat -->
<div id="otto-panel">
    <div class="otto-header">
        <img src="<?= base_url('img/otto-avatar.png') ?>" alt="OTTO">
        <div>
            <div class="otto-name">OTTO</div>
            <div class="otto-subtitle">Asesor financiero IA · Cycloid Talent</div>
        </div>
        <div class="otto-actions">
            <button id="otto-new" title="Nueva conversación" type="button">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
            <button id="otto-close" title="Cerrar" type="button">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

    <div class="otto-body" id="otto-body"></div>

    <div class="otto-footer">
        <div class="otto-input-row">
            <input type="text" class="otto-input" id="otto-input"
                   placeholder="Pregúntale algo a OTTO..." autocomplete="off">
            <button class="otto-send" id="otto-send" type="button" title="Enviar">
                <i class="bi bi-send-fill"></i>
            </button>
        </div>
        <div class="otto-cost" id="otto-cost"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/marked@11.1.1/marked.min.js"></script>
<script>
(function () {
    const URL_INICIAR = "<?= base_url('conciliaciones/asesoria-ia/widget/iniciar') ?>";
    const URL_ENVIAR  = "<?= base_url('conciliaciones/asesoria-ia/widget/enviar') ?>";
    const URL_MSGS    = "<?= base_url('conciliaciones/asesoria-ia/widget/mensajes/') ?>";

    const PRESETS = [
        { key: 'diagnostico', label: '🩺 Diagnóstico financiero' },
        { key: 'cartera',     label: '💰 Priorización cobro' },
        { key: 'estrategia',  label: '🎯 Recomendaciones estratégicas' },
    ];

    const STORAGE_KEY = 'otto_conv_id';

    const $launcher = document.getElementById('otto-launcher');
    const $panel    = document.getElementById('otto-panel');
    const $close    = document.getElementById('otto-close');
    const $newBtn   = document.getElementById('otto-new');
    const $body     = document.getElementById('otto-body');
    const $input    = document.getElementById('otto-input');
    const $send     = document.getElementById('otto-send');
    const $cost     = document.getElementById('otto-cost');

    let convId = localStorage.getItem(STORAGE_KEY) || null;
    let cargando = false;

    function abrir() {
        $panel.classList.add('open');
        if (!convId) {
            renderBienvenida();
        } else {
            cargarHistorial(convId);
        }
        setTimeout(() => $input.focus(), 100);
    }
    function cerrar() { $panel.classList.remove('open'); }

    $launcher.addEventListener('click', () => {
        $panel.classList.contains('open') ? cerrar() : abrir();
    });
    $close.addEventListener('click', cerrar);
    $newBtn.addEventListener('click', () => {
        if (confirm('¿Empezar una conversación nueva con OTTO? Se perderá el contexto actual.')) {
            convId = null;
            localStorage.removeItem(STORAGE_KEY);
            renderBienvenida();
        }
    });

    function renderBienvenida() {
        $body.innerHTML = '';
        appendMessage('assistant', 'Hola, soy **OTTO**. Soy el asesor financiero IA de Cycloid Talent y tengo acceso a tus datos en tiempo real: cartera, recaudo, deudas, presupuestos y balance.\n\n¿En qué te ayudo hoy?');
        // Chips
        const chipsDiv = document.createElement('div');
        chipsDiv.className = 'otto-chips';
        PRESETS.forEach(p => {
            const btn = document.createElement('button');
            btn.className = 'otto-chip';
            btn.textContent = p.label;
            btn.type = 'button';
            btn.addEventListener('click', () => iniciarConPreset(p.key, p.label));
            chipsDiv.appendChild(btn);
        });
        $body.appendChild(chipsDiv);
        scrollBottom();
    }

    function appendMessage(rol, contenido, retornarEl = false) {
        const msg = document.createElement('div');
        msg.className = 'otto-msg ' + rol;
        const avatar = document.createElement('div');
        avatar.className = 'avatar';
        if (rol === 'assistant') {
            const img = document.createElement('img');
            img.src = "<?= base_url('img/otto-avatar.png') ?>";
            avatar.appendChild(img);
        } else {
            avatar.innerHTML = '<i class="bi bi-person-fill"></i>';
        }
        const bubble = document.createElement('div');
        bubble.className = 'otto-bubble';
        if (rol === 'assistant') {
            bubble.innerHTML = marked.parse(contenido || '');
        } else {
            bubble.textContent = contenido;
        }
        msg.appendChild(avatar);
        msg.appendChild(bubble);
        $body.appendChild(msg);
        scrollBottom();
        return retornarEl ? bubble : null;
    }

    function appendTyping() {
        const msg = document.createElement('div');
        msg.id = 'otto-typing';
        msg.className = 'otto-msg assistant';
        msg.innerHTML = `
            <div class="avatar"><img src="<?= base_url('img/otto-avatar.png') ?>"></div>
            <div class="otto-bubble">
                <div class="otto-typing"><span></span><span></span><span></span></div>
            </div>`;
        $body.appendChild(msg);
        scrollBottom();
    }
    function removeTyping() {
        const t = document.getElementById('otto-typing');
        if (t) t.remove();
    }

    function scrollBottom() {
        requestAnimationFrame(() => { $body.scrollTop = $body.scrollHeight; });
    }

    function setCargando(b) {
        cargando = b;
        $input.disabled = b;
        $send.disabled  = b;
    }

    async function iniciarConPreset(presetKey, label) {
        if (cargando) return;
        setCargando(true);
        // Quitar chips
        $body.querySelectorAll('.otto-chips').forEach(c => c.remove());
        appendMessage('user', label);
        appendTyping();
        try {
            const fd = new FormData();
            fd.append('preset', presetKey);
            const r = await fetch(URL_INICIAR, { method: 'POST', body: fd });
            const j = await r.json();
            removeTyping();
            if (!j.ok) {
                appendMessage('assistant', '⚠️ ' + (j.error || 'Error desconocido'));
                setCargando(false);
                return;
            }
            convId = j.id_conversacion;
            localStorage.setItem(STORAGE_KEY, convId);
            appendMessage('assistant', j.respuesta);
            actualizarCosto(j.costo_mes, j.budget_mes);
        } catch (e) {
            removeTyping();
            appendMessage('assistant', '⚠️ Error de red: ' + e.message);
        }
        setCargando(false);
    }

    async function enviarMensaje() {
        const texto = $input.value.trim();
        if (! texto || cargando) return;
        $input.value = '';
        appendMessage('user', texto);
        appendTyping();
        setCargando(true);
        try {
            const fd = new FormData();
            fd.append('mensaje', texto);
            if (convId) fd.append('id_conversacion', convId);
            const r = await fetch(URL_ENVIAR, { method: 'POST', body: fd });
            const j = await r.json();
            removeTyping();
            if (!j.ok) {
                appendMessage('assistant', '⚠️ ' + (j.error || 'Error desconocido'));
                setCargando(false);
                return;
            }
            if (!convId) {
                convId = j.id_conversacion;
                localStorage.setItem(STORAGE_KEY, convId);
            }
            appendMessage('assistant', j.respuesta);
            actualizarCosto(j.costo_mes, j.budget_mes);
        } catch (e) {
            removeTyping();
            appendMessage('assistant', '⚠️ Error de red: ' + e.message);
        }
        setCargando(false);
    }

    $send.addEventListener('click', enviarMensaje);
    $input.addEventListener('keypress', e => { if (e.key === 'Enter') enviarMensaje(); });

    async function cargarHistorial(id) {
        $body.innerHTML = '';
        try {
            const r = await fetch(URL_MSGS + id);
            const j = await r.json();
            if (!j.ok) {
                convId = null;
                localStorage.removeItem(STORAGE_KEY);
                renderBienvenida();
                return;
            }
            j.mensajes.forEach(m => appendMessage(m.rol, m.contenido));
            actualizarCosto(j.costo_mes, j.budget_mes);
        } catch (e) {
            renderBienvenida();
        }
    }

    function actualizarCosto(actual, budget) {
        if (actual == null || budget == null) return;
        const pct = budget > 0 ? Math.min(100, (actual / budget) * 100) : 0;
        const cls = pct > 80 ? 'over' : '';
        $cost.innerHTML = `Consumo del mes: <span class="${cls}">$${parseFloat(actual).toFixed(3)} / $${parseFloat(budget).toFixed(2)}</span>`;
    }

})();
</script>
