<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once __DIR__ . '/repository.php';

require_admin();

$pdo = app_pdo();
if (!app_table_exists($pdo, 'usuario')) {
    http_response_code(500);
    exit('La tabla usuario no existe en la base de datos actual.');
}

clientes_ensure_schema($pdo);

$search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
$perPageOptions = array(10, 25, 50, 100);
$perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 10;
if (!in_array($perPage, $perPageOptions, true)) {
    $perPage = 10;
}

$sortMap = array(
    'id' => 'id',
    'nempleado' => 'nempleado',
    'nombre' => 'nombre',
    'email' => 'email',
    'permiso' => 'permiso',
    'status' => 'status',
    'suscripcion_hasta' => 'suscripcion_hasta',
);

$sortBy = isset($_GET['sort']) ? strtolower(trim((string) $_GET['sort'])) : 'id';
if (!isset($sortMap[$sortBy])) {
    $sortBy = 'id';
}

$sortDirection = isset($_GET['dir']) ? strtolower(trim((string) $_GET['dir'])) : 'desc';
if (!in_array($sortDirection, array('asc', 'desc'), true)) {
    $sortDirection = 'desc';
}

$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$errors = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token(isset($_POST['_token']) ? $_POST['_token'] : '')) {
        $errors['general'] = 'La sesion expiro. Recarga la pagina e intenta nuevamente.';
    } else {
        $payload = clientes_access_request_data($_POST);
        $errors = clientes_validate_access_data($pdo, $payload);

        if ($payload['id'] === current_user_id() && $payload['status'] === 0) {
            $errors['general'] = 'No puedes darte de baja a ti mismo.';
        }

        if ($payload['id'] === current_user_id() && $payload['permiso'] < 2) {
            $errors['general'] = 'No puedes quitarte tu rol de administrador.';
        }

        if ($payload['id'] === current_user_id() && in_array('clientes.manage', $payload['blocked_capabilities'], true)) {
            $errors['general'] = 'No puedes bloquearte a ti mismo el acceso al panel de clientes admin.';
        }

        if (empty($errors)) {
            clientes_update_access($pdo, $payload);
            flash('success', 'Acceso del usuario actualizado correctamente.');
            header('Location: ' . app_url('modules/clientes/index.php'));
            exit;
        }
    }
}

$totalItems = clientes_count($pdo, $search);
$pagination = build_pagination($totalItems, $perPage, $page);
if ($pagination['current_page'] > $pagination['total_pages']) {
    $pagination = build_pagination($totalItems, $perPage, $pagination['total_pages']);
}

$records = clientes_paginated(
    $pdo,
    $search,
    $pagination['per_page'],
    $pagination['offset'],
    $sortMap[$sortBy],
    $sortDirection
);

$pageTitle = 'Clientes Admin | ' . app_name();
$pageHeading = 'Clientes Admin';
$pageDescription = '';
$currentModule = 'clientes';
$pageActions = array(
    array(
        'label' => 'Recargar panel',
        'href' => 'modules/clientes/index.php',
        'icon' => 'fa-solid fa-rotate-right',
        'class' => 'btn-outline-secondary',
    ),
);

$sortableColumns = array(
    'id' => 'ID',
    'nempleado' => 'Empleado',
    'nombre' => 'Nombre',
    'email' => 'Email',
    'permiso' => 'Rol',
    'status' => 'Estado',
    'subscription_status' => 'Cobro',
    'suscripcion_hasta' => 'Suscripcion',
);

$roleOptions = clientes_role_options();
$subscriptionStatusOptions = clientes_subscription_status_options();
$blockedCapabilitiesOptions = clientes_blocked_capabilities_options();

include APP_ROOT . '/includes/header.php';
?>

<article class="surface-card h-100 crm-animate-in block-workspace mb-4" id="blockWorkspaceClientes">
    <div class="surface-card__header">
        <div>
            <h2 class="surface-card__title">RBAC y suscripciones</h2>
            <p class="surface-card__subtitle">Administra rol, estado, vigencia y paginas permitidas para cada cliente del sistema.</p>
        </div>
    </div>

    <?php if (!empty($errors['general'])) : ?>
        <div class="alert alert-danger"><?php echo e($errors['general']); ?></div>
    <?php endif; ?>

    <form class="row g-3 align-items-end" method="get" data-loading-form>
        <input type="hidden" name="sort" value="<?php echo e($sortBy); ?>">
        <input type="hidden" name="dir" value="<?php echo e($sortDirection); ?>">

        <div class="col-12 col-md-7 col-lg-5">
            <label class="form-label" for="tableQuickFilterClientes">Buscar en la pagina</label>
            <input id="tableQuickFilterClientes" type="text" class="form-control" data-table-filter="#clientesAdminTable" placeholder="Filtra en el listado visible">
        </div>

        <div class="col-12 col-md-5 col-lg-4 ms-lg-auto">
            <label class="form-label" for="searchServerClientes">Buscar en base de datos</label>
            <input id="searchServerClientes" type="text" class="form-control" name="search" value="<?php echo e($search); ?>" placeholder="Nombre, email, celular, empleado">
        </div>

        <div class="col-12 col-md-4 col-lg-3">
            <label class="form-label">Registros por pagina</label>
            <select class="form-select" name="per_page">
                <?php foreach ($perPageOptions as $size) : ?>
                    <option value="<?php echo e($size); ?>" <?php echo $perPage === $size ? 'selected' : ''; ?>><?php echo e($size); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-outline-primary" data-loading-button>
                <i class="fa-solid fa-magnifying-glass me-2"></i>Aplicar filtros
            </button>
        </div>
    </form>
</article>

<section class="surface-card crm-animate-in block-table" id="blockListadoClientesAdmin">
    <div class="surface-card__header">
        <div>
            <h2 class="surface-card__title">Panel administrador de clientes</h2>
            <p class="surface-card__subtitle">Da de baja usuarios sin pago y controla acceso por modulo o pagina.</p>
        </div>
        <span class="table-count"><?php echo e(number_format($totalItems)); ?> registros</span>
    </div>

    <div class="table-responsive">
        <table class="table modern-table align-middle" id="clientesAdminTable">
            <thead>
                <tr>
                    <?php foreach ($sortableColumns as $columnKey => $columnLabel) : ?>
                        <?php
                        $isActive = $sortBy === $columnKey;
                        $nextDirection = $isActive && $sortDirection === 'asc' ? 'desc' : 'asc';
                        $sortIcon = $isActive ? ($sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort';
                        ?>
                        <th>
                            <a class="table-sort <?php echo $isActive ? 'is-active' : ''; ?>" href="<?php echo e(app_url('modules/clientes/index.php')); ?>?<?php echo e(query_string_with(array('sort' => $columnKey, 'dir' => $nextDirection, 'page' => 1))); ?>">
                                <span><?php echo e($columnLabel); ?></span>
                                <i class="fa-solid <?php echo e($sortIcon); ?>"></i>
                            </a>
                        </th>
                    <?php endforeach; ?>
                    <th class="text-end">Accion</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)) : ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-state py-4">
                                <i class="fa-regular fa-folder-open"></i>
                                <p>No se encontraron usuarios con los filtros actuales.</p>
                            </div>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($records as $record) : ?>
                        <?php $statusBadge = clientes_status_badge($record); ?>
                        <?php
                        $recordBlockedCapabilities = array();
                        if (!empty($record['blocked_capabilities'])) {
                            $decodedBlockedCapabilities = json_decode($record['blocked_capabilities'], true);
                            if (is_array($decodedBlockedCapabilities)) {
                                foreach ($decodedBlockedCapabilities as $blockedCapability) {
                                    if (is_string($blockedCapability)) {
                                        $recordBlockedCapabilities[] = $blockedCapability;
                                    }
                                }
                            }
                        }
                        ?>
                        <tr data-filter-row>
                            <td data-label="ID"><strong>#<?php echo e($record['id']); ?></strong></td>
                            <td data-label="Empleado"><?php echo e($record['nempleado']); ?></td>
                            <td data-label="Nombre">
                                <div class="table-primary-text"><?php echo e(trim($record['nombre'] . ' ' . $record['apellido'])); ?></div>
                                <small class="text-muted"><?php echo e($record['celular']); ?></small>
                            </td>
                            <td data-label="Email"><?php echo e($record['email']); ?></td>
                            <td data-label="Rol"><span class="badge rounded-pill badge-soft-info"><?php echo e(clientes_role_label($record['permiso'])); ?></span></td>
                            <td data-label="Estado"><span class="badge rounded-pill <?php echo e($statusBadge['class']); ?>"><?php echo e($statusBadge['label']); ?></span></td>
                            <td data-label="Cobro"><?php echo e(!empty($record['subscription_status']) ? $subscriptionStatusOptions[$record['subscription_status']] : 'N/D'); ?></td>
                            <td data-label="Suscripcion"><?php echo e(!empty($record['suscripcion_hasta']) ? $record['suscripcion_hasta'] : 'Sin fecha'); ?></td>
                            <td data-label="Accion">
                                <form method="post" class="d-flex flex-column gap-2 justify-content-end" data-loading-form>
                                    <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                                    <input type="hidden" name="id" value="<?php echo e($record['id']); ?>">

                                    <div class="d-flex flex-column flex-xl-row gap-2 justify-content-end">
                                        <select name="permiso" class="form-select form-select-sm" style="min-width: 140px;">
                                            <?php foreach ($roleOptions as $roleValue => $roleLabel) : ?>
                                                <option value="<?php echo e($roleValue); ?>" <?php echo (int) $record['permiso'] === (int) $roleValue ? 'selected' : ''; ?>><?php echo e($roleLabel); ?></option>
                                            <?php endforeach; ?>
                                        </select>

                                        <select name="status" class="form-select form-select-sm" style="min-width: 120px;">
                                            <option value="1" <?php echo (int) $record['status'] === 1 ? 'selected' : ''; ?>>Activo</option>
                                            <option value="0" <?php echo (int) $record['status'] === 0 ? 'selected' : ''; ?>>Inactivo</option>
                                        </select>

                                        <select name="subscription_status" class="form-select form-select-sm" style="min-width: 160px;">
                                            <?php foreach ($subscriptionStatusOptions as $subscriptionValue => $subscriptionLabel) : ?>
                                                <option value="<?php echo e($subscriptionValue); ?>" <?php echo (string) $record['subscription_status'] === (string) $subscriptionValue ? 'selected' : ''; ?>><?php echo e($subscriptionLabel); ?></option>
                                            <?php endforeach; ?>
                                        </select>

                                        <input type="date" name="suscripcion_hasta" class="form-control form-control-sm" value="<?php echo e(!empty($record['suscripcion_hasta']) ? $record['suscripcion_hasta'] : ''); ?>" style="min-width: 155px;">

                                        <input type="text" name="bloqueado_motivo" class="form-control form-control-sm" value="<?php echo e(!empty($record['bloqueado_motivo']) ? $record['bloqueado_motivo'] : ''); ?>" placeholder="Motivo de bloqueo" style="min-width: 180px;">
                                    </div>

                                    <div class="capability-blocker">
                                        <div class="capability-blocker__title">Bloquear paginas</div>
                                        <div class="capability-blocker__list">
                                            <?php foreach ($blockedCapabilitiesOptions as $capabilityKey => $capabilityLabel) : ?>
                                                <label class="form-check form-check-inline m-0 capability-blocker__item">
                                                    <input class="form-check-input" type="checkbox" name="blocked_capabilities[]" value="<?php echo e($capabilityKey); ?>" <?php echo in_array($capabilityKey, $recordBlockedCapabilities, true) ? 'checked' : ''; ?>>
                                                    <span class="form-check-label small"><?php echo e($capabilityLabel); ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <input type="hidden" name="form_action" value="update_access">
                                    <div class="d-flex justify-content-end">
                                        <button type="submit" class="btn btn-sm btn-primary" data-loading-button>
                                            <i class="fa-solid fa-floppy-disk me-1"></i>Guardar
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 pt-3">
        <div class="text-muted small">Pagina <?php echo e($pagination['current_page']); ?> de <?php echo e($pagination['total_pages']); ?></div>
        <nav aria-label="Paginacion de clientes admin">
            <ul class="pagination mb-0">
                <li class="page-item <?php echo $pagination['current_page'] <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo e(app_url('modules/clientes/index.php')); ?>?<?php echo e(query_string_with(array('page' => max(1, $pagination['current_page'] - 1)))); ?>">Anterior</a>
                </li>
                <?php for ($pageNumber = 1; $pageNumber <= $pagination['total_pages']; $pageNumber++) : ?>
                    <li class="page-item <?php echo $pageNumber === $pagination['current_page'] ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo e(app_url('modules/clientes/index.php')); ?>?<?php echo e(query_string_with(array('page' => $pageNumber))); ?>"><?php echo e($pageNumber); ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $pagination['current_page'] >= $pagination['total_pages'] ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo e(app_url('modules/clientes/index.php')); ?>?<?php echo e(query_string_with(array('page' => min($pagination['total_pages'], $pagination['current_page'] + 1)))); ?>">Siguiente</a>
                </li>
            </ul>
        </nav>
    </div>
</section>

<?php include APP_ROOT . '/includes/footer.php'; ?>