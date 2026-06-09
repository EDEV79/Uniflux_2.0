<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once __DIR__ . '/repository.php';

require_capability('gastos.manage');

$pdo = app_pdo();
gastos_ensure_schema($pdo);

if (!app_table_exists($pdo, 'gastos')) {
    http_response_code(500);
    exit('La tabla gastos no existe en la base de datos actual.');
}

$action = isset($_GET['action']) ? $_GET['action'] : 'index';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$perPageOptions = array(10, 25, 50, 100);
$perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 10;
if (!in_array($perPage, $perPageOptions, true)) {
    $perPage = 10;
}

$sortMap = array(
    'idgastos' => 'idgastos',
    'proveedor' => 'proveedor',
    'nfactura' => 'nfactura',
    'fecha' => 'fecha',
    'total' => 'total',
);

$sortBy = isset($_GET['sort']) ? strtolower(trim($_GET['sort'])) : 'idgastos';
if (!isset($sortMap[$sortBy])) {
    $sortBy = 'idgastos';
}

$sortDirection = isset($_GET['dir']) ? strtolower(trim($_GET['dir'])) : 'desc';
if (!in_array($sortDirection, array('asc', 'desc'), true)) {
    $sortDirection = 'desc';
}

$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$errors = array();
$editingId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$formData = gastos_default_form_data();

if ($editingId) {
    $existingRecord = gastos_find($pdo, $editingId);
    if ($existingRecord) {
        $formData = array_merge($formData, $existingRecord);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedAction = isset($_POST['form_action']) ? $_POST['form_action'] : 'create';
    $formData = array_merge($formData, gastos_request_data($_POST));

    if (!verify_csrf_token(isset($_POST['_token']) ? $_POST['_token'] : '')) {
        $errors['general'] = 'La sesion expiro. Recarga la pagina e intenta nuevamente.';
    } elseif ($postedAction === 'delete') {
        $deleteId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        gastos_delete($pdo, $deleteId);
        flash('success', 'Gasto eliminado correctamente.');
        header('Location: ' . app_url('gastos.php'));
        exit;
    } else {
        $errors = gastos_validate($formData);

        if (empty($errors)) {
            if ($postedAction === 'update') {
                $updateId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
                gastos_update($pdo, $updateId, $formData);
                flash('success', 'Gasto actualizado correctamente.');
                header('Location: ' . app_url('gastos.php'));
                exit;
            }

            gastos_create($pdo, $formData);
            flash('success', 'Gasto creado correctamente.');
            header('Location: ' . app_url('gastos.php'));
            exit;
        }
    }
}

$totalItems = gastos_count($pdo, $search);
$pagination = build_pagination($totalItems, $perPage, $page);
if ($pagination['current_page'] > $pagination['total_pages']) {
    $pagination = build_pagination($totalItems, $perPage, $pagination['total_pages']);
}

$records = gastos_paginated(
    $pdo,
    $search,
    $pagination['per_page'],
    $pagination['offset'],
    $sortMap[$sortBy],
    $sortDirection
);

$pageTitle = 'Gastos | ' . app_name();
$pageHeading = 'Gastos';
$pageDescription = '';
$currentModule = 'gastos';
$pageActions = array(
    array(
        'label' => 'Nuevo gasto',
        'href' => 'gastos.php?action=create',
        'icon' => 'fa-solid fa-plus',
        'class' => 'btn-primary',
    ),
    array(
        'label' => 'Limpiar filtros',
        'href' => 'gastos.php',
        'icon' => 'fa-solid fa-rotate-left',
        'class' => 'btn-outline-secondary',
    ),
);

$formMode = $editingId ? 'edit' : 'create';

include APP_ROOT . '/includes/header.php';
?>

<?php include APP_ROOT . '/components/form-gasto.php'; ?>

<?php include APP_ROOT . '/components/workspace-gastos.php'; ?>

<?php include APP_ROOT . '/components/table-gastos.php'; ?>

<?php include APP_ROOT . '/includes/footer.php'; ?>
