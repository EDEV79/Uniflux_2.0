<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_capability('servicios.view');

$pageTitle = 'Servicios | ' . app_name();
$pageHeading = 'Servicios';
$pageDescription = 'Boundary modular para futuros catalogos y operaciones API-driven.';
$currentModule = 'servicios';

include APP_ROOT . '/includes/header.php';
?>
<section class="surface-card">
    <div class="empty-state py-5">
        <i class="fa-solid fa-briefcase"></i>
        <h2 class="surface-card__title">Modulo desacoplado</h2>
        <p>Este punto de entrada mantiene la navegacion consistente mientras se trasladan reglas de negocio fuera de las vistas heredadas.</p>
    </div>
</section>
<?php include APP_ROOT . '/includes/footer.php'; ?>
