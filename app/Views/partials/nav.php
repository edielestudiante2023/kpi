<!-- app/Views/partials/nav.php -->
<nav style="padding: .5rem 0; background-color: #f8f9fa; border-bottom: 1px solid #dee2e6;">
    <div class="container">
        <div class="d-flex justify-content-center align-items-center flex-nowrap gap-3">

            <!-- Logo 1 -->
            <div class="d-flex justify-content-center">
                <a href="#">
                    <img src="<?= base_url('img/LOGO_AFIANCOL.png') ?>"
                        alt="Logo Afiancol"
                        style="max-height: 100px; width: auto;">
                </a>
            </div>

            <!-- Logo 2 -->
            <div class="d-flex justify-content-center">
                <a href="#">
                    <img src="<?= base_url('img/logogestionhumana2.png') ?>"
                        alt="Logo Gestión Humana 2"
                        style="max-height: 100px; width: auto;">
                </a>
            </div>

            <!-- Logo 3 -->
            <div class="d-flex justify-content-center">
                <a href="#">
                    <img src="<?= base_url('img/logogestionhumanapersonas.png') ?>"
                        alt="Logo Gestión Humana Personas"
                        style="max-height: 100px; width: auto;">
                </a>
            </div>

            <!-- Logo 4 -->
            <div class="d-flex justify-content-center">
                <a href="#">
                    <img src="<?= base_url('img/logoafilogro.png') ?>"
                        alt="Logo Afilogro"
                        style="max-height: 100px; width: auto;">
                </a>
            </div>

            <!-- Logout -->
            <div class="d-flex justify-content-center">
                <?= $this->include('partials/logout') ?>
            </div>

        </div>
    </div>
</nav>