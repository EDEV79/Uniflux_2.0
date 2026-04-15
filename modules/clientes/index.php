<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_login();

$pageTitle = 'Clientes | ' . app_name();
$pageHeading = 'Clientes';
$pageDescription = '';
$currentModule = 'clientes';

include APP_ROOT . '/includes/header.php';
?>
<section class="surface-card">
    <div class="empty-state py-5">
        <i class="fa-solid fa-users"></i>
        <h2 class="surface-card__title">Boundary de clientes listo</h2>
        <p>Este modulo queda separado para migrar la logica a una capa de servicios o a Flask sin acoplarla al layout.</p>
    </div>
</section>
<?php include APP_ROOT . '/includes/footer.php'; ?>