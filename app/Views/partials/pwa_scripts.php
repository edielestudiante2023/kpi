<!-- PWA Service Worker Registration -->
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then((registration) => {
                console.log('PWA: Service Worker registrado con exito', registration.scope);
            })
            .catch((error) => {
                console.log('PWA: Error al registrar Service Worker', error);
            });
    });
}

// Detectar si se puede instalar la PWA
let deferredPrompt;
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;

    // Mostrar boton de instalacion si existe
    const installBtn = document.getElementById('pwa-install-btn');
    if (installBtn) {
        installBtn.style.display = 'block';
        installBtn.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                console.log('PWA: Usuario respondio:', outcome);
                deferredPrompt = null;
                installBtn.style.display = 'none';
            }
        });
    }
});

// Detectar cuando se instala la PWA
window.addEventListener('appinstalled', () => {
    console.log('PWA: App instalada exitosamente');
    deferredPrompt = null;
});
</script>
