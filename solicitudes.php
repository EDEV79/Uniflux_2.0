<?php

require_once __DIR__ . '/includes/bootstrap.php';

require_capability('solicitudes.manage');

$pdo = app_pdo();

if (!app_table_exists($pdo, 'solicitudes')) {
    http_response_code(500);
    exit('La tabla solicitudes no existe en la base de datos actual.');
}

$hasInventario = app_table_exists($pdo, 'inventariouber');
$search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
$perPageOptions = array(10, 25, 50, 100);
$perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 10;
if (!in_array($perPage, $perPageOptions, true)) {
    $perPage = 10;
}

$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$errors = array();
$formData = array(
    'placa' => '',
    'fecha' => date('Y-m-d'),
    'detalle' => '',
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token(isset($_POST['_token']) ? $_POST['_token'] : '')) {
        $errors['general'] = 'La sesion expiro. Recarga la pagina e intenta nuevamente.';
    } else {
        $formAction = isset($_POST['form_action']) ? (string) $_POST['form_action'] : 'create';

        if ($formAction === 'delete') {
            $deleteId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            $deleteStatement = $pdo->prepare('DELETE FROM solicitudes WHERE id = :id');
            $deleteStatement->execute(array('id' => $deleteId));

            flash('success', 'Solicitud eliminada correctamente.');
            header('Location: ' . app_url('solicitudes.php'));
            exit;
        }

        $formData = array(
            'placa' => isset($_POST['placa']) ? trim((string) $_POST['placa']) : '',
            'fecha' => isset($_POST['fecha']) ? trim((string) $_POST['fecha']) : '',
            'detalle' => isset($_POST['detalle']) ? trim((string) $_POST['detalle']) : '',
        );

        if ($formData['placa'] === '') {
            $errors['placa'] = 'Selecciona una placa.';
        }

        if ($formData['fecha'] === '' || ExplodeFecha($formData['fecha']) === '0000-00-00') {
            $errors['fecha'] = 'Ingresa una fecha valida.';
        }

        if ($formData['detalle'] === '') {
            $errors['detalle'] = 'Describe la solicitud.';
        }

        if (empty($errors)) {
            $insertStatement = $pdo->prepare(
                'INSERT INTO solicitudes (placa, nombre, detalle, fecha, usuario)
                 VALUES (:placa, :nombre, :detalle, :fecha, :usuario)'
            );
            $insertStatement->execute(array(
                'placa' => $formData['placa'],
                'nombre' => current_user_name(),
                'detalle' => $formData['detalle'],
                'fecha' => ExplodeFecha($formData['fecha']),
                'usuario' => current_user_name(),
            ));

            flash('success', 'Solicitud registrada correctamente.');
            header('Location: ' . app_url('solicitudes.php'));
            exit;
        }
    }
}

$placaOptions = array();
if ($hasInventario) {
    $placaStatement = $pdo->query('SELECT placa FROM inventariouber ORDER BY placa ASC');
    $placaOptions = $placaStatement->fetchAll();
}

$countSql = 'SELECT COUNT(*) FROM solicitudes';
$conditions = array();
$parameters = array();

if ($search !== '') {
    $conditions[] = '(placa LIKE :search OR nombre LIKE :search OR detalle LIKE :search OR usuario LIKE :search)';
    $parameters['search'] = '%' . $search . '%';
}

if (!empty($conditions)) {
    $countSql .= ' WHERE ' . implode(' AND ', $conditions);
}

$countStatement = $pdo->prepare($countSql);
$countStatement->execute($parameters);
$totalItems = (int) $countStatement->fetchColumn();

$pagination = build_pagination($totalItems, $perPage, $page);
if ($pagination['current_page'] > $pagination['total_pages']) {
    $pagination = build_pagination($totalItems, $perPage, $pagination['total_pages']);
}

$listSql = 'SELECT id, placa, nombre, detalle, fecha, usuario, created_at FROM solicitudes';
if (!empty($conditions)) {
    $listSql .= ' WHERE ' . implode(' AND ', $conditions);
}
$listSql .= ' ORDER BY id DESC LIMIT :limit OFFSET :offset';

$listStatement = $pdo->prepare($listSql);
if (isset($parameters['search'])) {
    $listStatement->bindValue(':search', $parameters['search'], PDO::PARAM_STR);
}
$listStatement->bindValue(':limit', (int) $pagination['per_page'], PDO::PARAM_INT);
$listStatement->bindValue(':offset', (int) $pagination['offset'], PDO::PARAM_INT);
$listStatement->execute();
$records = $listStatement->fetchAll();

$pageTitle = 'Solicitudes | ' . app_name();
$pageHeading = 'Solicitudes';
$pageDescription = '';
$currentModule = 'solicitudes';
$pageActions = array(
    array(
        'label' => 'Recargar panel',
        'href' => 'solicitudes.php',
        'icon' => 'fa-solid fa-rotate-right',
        'class' => 'btn-outline-secondary',
    ),
);

include APP_ROOT . '/includes/header.php';
?>

<section class="row g-4 mb-4">
    <div class="col-12 col-xl-4">
        <article class="surface-card h-100 crm-animate-in">
            <div class="surface-card__header">
                <div>
                    <h2 class="surface-card__title">Nueva solicitud</h2>
                    <p class="surface-card__subtitle">Registra requerimientos operativos por placa y deja trazabilidad en el sistema.</p>
                </div>
            </div>

            <?php if (!empty($errors['general'])) : ?>
                <div class="alert alert-danger"><?php echo e($errors['general']); ?></div>
            <?php endif; ?>

            <?php if (!$hasInventario) : ?>
                <div class="alert alert-warning mb-0">No hay inventario de autos disponible para seleccionar placas.</div>
            <?php else : ?>
                <form method="post" class="row g-3" data-loading-form>
                    <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                    <input type="hidden" name="form_action" value="create">

                    <div class="col-12">
                        <label class="form-label" for="placaSolicitud">Placa</label>
                        <select id="placaSolicitud" name="placa" class="form-select <?php echo isset($errors['placa']) ? 'is-invalid' : ''; ?>" required>
                            <option value="">Selecciona una placa</option>
                            <?php foreach ($placaOptions as $option) : ?>
                                <option value="<?php echo e($option['placa']); ?>" <?php echo $formData['placa'] === $option['placa'] ? 'selected' : ''; ?>><?php echo e($option['placa']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['placa'])) : ?>
                            <div class="invalid-feedback"><?php echo e($errors['placa']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Solicitante</label>
                        <input type="text" class="form-control" value="<?php echo e(current_user_name()); ?>" readonly>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="fechaSolicitud">Fecha</label>
                        <input id="fechaSolicitud" type="text" name="fecha" data-datepicker class="form-control <?php echo isset($errors['fecha']) ? 'is-invalid' : ''; ?>" value="<?php echo e($formData['fecha']); ?>" placeholder="AAAA-MM-DD" required>
                        <?php if (isset($errors['fecha'])) : ?>
                            <div class="invalid-feedback"><?php echo e($errors['fecha']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="detalleSolicitud">Detalle</label>
                        <textarea id="detalleSolicitud" name="detalle" rows="6" class="form-control <?php echo isset($errors['detalle']) ? 'is-invalid' : ''; ?>" placeholder="Describe mantenimiento, documentacion o incidencia" required><?php echo e($formData['detalle']); ?></textarea>
                        <?php if (isset($errors['detalle'])) : ?>
                            <div class="invalid-feedback"><?php echo e($errors['detalle']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-12 d-grid">
                        <button type="submit" class="btn btn-primary" data-loading-button>
                            <i class="fa-solid fa-paper-plane me-2"></i>Registrar solicitud
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </article>
    </div>

    <div class="col-12 col-xl-8">
        <article class="surface-card h-100 crm-animate-in block-workspace">
            <div class="surface-card__header">
                <div>
                    <h2 class="surface-card__title">Workspace</h2>
                    <p class="surface-card__subtitle">Filtra y navega el historial de solicitudes registradas.</p>
                </div>
            </div>

            <form class="row g-3 align-items-end" method="get" data-loading-form>
                <div class="col-12 col-md-6">
                    <label for="tableQuickFilterSolicitudes" class="form-label">Buscar en la pagina</label>
                    <input id="tableQuickFilterSolicitudes" type="text" class="form-control" data-table-filter="#solicitudesTable" placeholder="Filtra el listado visible">
                </div>

                <div class="col-12 col-md-6 col-xl-4">
                    <label for="searchSolicitudes" class="form-label">Buscar en base de datos</label>
                    <input id="searchSolicitudes" type="text" class="form-control" name="search" value="<?php echo e($search); ?>" placeholder="Placa, nombre o detalle">
                </div>

                <div class="col-12 col-md-4 col-xl-2">
                    <label class="form-label">Por pagina</label>
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
    </div>
</section>

<section class="surface-card crm-animate-in block-table">
    <div class="surface-card__header">
        <div>
            <h2 class="surface-card__title">Historial de solicitudes</h2>
            <p class="surface-card__subtitle">Consulta el seguimiento reciente de requerimientos por vehiculo.</p>
        </div>
        <span class="table-count"><?php echo e(number_format($totalItems)); ?> registros</span>
    </div>

    <div class="table-responsive">
        <table class="table modern-table align-middle" id="solicitudesTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Placa</th>
                    <th>Solicitante</th>
                    <th>Fecha</th>
                    <th>Detalle</th>
                    <th>Creado</th>
                    <th class="text-end">Accion</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)) : ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state py-4">
                                <i class="fa-regular fa-folder-open"></i>
                                <p>No se encontraron solicitudes con los filtros actuales.</p>
                            </div>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($records as $record) : ?>
                        <tr data-filter-row>
                            <td data-label="ID"><strong>#<?php echo e($record['id']); ?></strong></td>
                            <td data-label="Placa"><span class="badge rounded-pill badge-soft-info"><?php echo e($record['placa']); ?></span></td>
                            <td data-label="Solicitante">
                                <div class="table-primary-text"><?php echo e($record['nombre']); ?></div>
                                <small class="text-muted"><?php echo e($record['usuario']); ?></small>
                            </td>
                            <td data-label="Fecha"><?php echo e(!empty($record['fecha']) ? TraeFechaExplode($record['fecha']) : 'Sin fecha'); ?></td>
                            <td data-label="Detalle"><?php echo e($record['detalle']); ?></td>
                            <td data-label="Creado"><?php echo e(!empty($record['created_at']) ? $record['created_at'] : 'N/D'); ?></td>
                            <td data-label="Accion" class="text-end">
                                <form method="post" class="d-inline-flex" data-loading-form onsubmit="return confirm('¿Eliminar esta solicitud?');">
                                    <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                                    <input type="hidden" name="form_action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo e($record['id']); ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" data-loading-button>
                                        <i class="fa-solid fa-trash-can me-1"></i>Eliminar
                                    </button>
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
        <nav aria-label="Paginacion de solicitudes">
            <ul class="pagination mb-0">
                <li class="page-item <?php echo $pagination['current_page'] <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo e(app_url('solicitudes.php')); ?>?<?php echo e(query_string_with(array('page' => max(1, $pagination['current_page'] - 1)))); ?>">Anterior</a>
                </li>
                <?php for ($pageNumber = 1; $pageNumber <= $pagination['total_pages']; $pageNumber++) : ?>
                    <li class="page-item <?php echo $pageNumber === $pagination['current_page'] ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo e(app_url('solicitudes.php')); ?>?<?php echo e(query_string_with(array('page' => $pageNumber))); ?>"><?php echo e($pageNumber); ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $pagination['current_page'] >= $pagination['total_pages'] ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo e(app_url('solicitudes.php')); ?>?<?php echo e(query_string_with(array('page' => min($pagination['total_pages'], $pagination['current_page'] + 1)))); ?>">Siguiente</a>
                </li>
            </ul>
        </nav>
    </div>
</section>

<?php include APP_ROOT . '/includes/footer.php'; ?>
