<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once __DIR__ . '/repository.php';

require_login();

$pdo = app_pdo();
$action = isset($_GET['action']) ? $_GET['action'] : 'index';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$perPageOptions = array(10, 25, 50, 100);
$perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 10;
if (!in_array($perPage, $perPageOptions, true)) {
    $perPage = 10;
}

$sortMap = array(
    'id' => 'id',
    'nombre' => 'nombre',
    'email' => 'email',
    'fecha' => 'fecha',
    'precio_show' => 'precio_show',
);
$sortBy = isset($_GET['sort']) ? strtolower(trim($_GET['sort'])) : 'id';
if (!isset($sortMap[$sortBy])) {
    $sortBy = 'id';
}

$sortDirection = isset($_GET['dir']) ? strtolower(trim($_GET['dir'])) : 'desc';
if (!in_array($sortDirection, array('asc', 'desc'), true)) {
    $sortDirection = 'desc';
}

$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

$errors = array();
$editingId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$formData = comprobantes_default_form_data();

if ($editingId) {
    $existingRecord = comprobantes_find($pdo, $editingId);
    if ($existingRecord) {
        $formData = array_merge($formData, $existingRecord);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedAction = isset($_POST['form_action']) ? $_POST['form_action'] : 'create';
    $formData = array_merge($formData, comprobantes_request_data($_POST));

    if (!verify_csrf_token(isset($_POST['_token']) ? $_POST['_token'] : '')) {
        $errors['general'] = 'La sesion expiro. Recarga la pagina e intenta nuevamente.';
    } elseif ($postedAction === 'delete') {
        $deleteId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        comprobantes_delete($pdo, $deleteId);
        flash('success', 'Comprobante eliminado correctamente.');
        header('Location: comprobanteinvest.php');
        exit;
    } else {
        $errors = comprobantes_validate($formData);

        if (empty($errors)) {
            if ($postedAction === 'update') {
                $updateId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
                comprobantes_update($pdo, $updateId, $formData);
                flash('success', 'Comprobante actualizado correctamente.');
                header('Location: comprobanteinvest.php?action=edit&id=' . $updateId);
                exit;
            }

            comprobantes_create($pdo, $formData, current_user_name());
            flash('success', 'Comprobante creado correctamente.');
            header('Location: comprobanteinvest.php');
            exit;
        }
    }
}

$totalItems = comprobantes_count($pdo, $search);
$pagination = build_pagination($totalItems, $perPage, $page);
if ($pagination['current_page'] > $pagination['total_pages']) {
    $pagination = build_pagination($totalItems, $perPage, $pagination['total_pages']);
}

$records = comprobantes_paginated(
    $pdo,
    $search,
    $pagination['per_page'],
    $pagination['offset'],
    $sortMap[$sortBy],
    $sortDirection
);

$pageTitle = 'Comprobantes | ' . app_name();
$pageHeading = 'Comprobantes';
$pageDescription = '';
$currentModule = 'comprobantes';
$pageActions = array(
    array(
        'label' => 'Nuevo comprobante',
        'href' => 'comprobanteinvest.php?action=create',
        'icon' => 'fa-solid fa-plus',
        'class' => 'btn-primary',
    ),
    array(
        'label' => 'Limpiar filtros',
        'href' => 'comprobanteinvest.php',
        'icon' => 'fa-solid fa-rotate-left',
        'class' => 'btn-outline-secondary',
    ),
);

$formMode = $editingId ? 'edit' : 'create';

include APP_ROOT . '/includes/header.php';
?>

<?php include APP_ROOT . '/components/form-comprobante.php'; ?>

<?php include APP_ROOT . '/components/workspace.php'; ?>

<?php include APP_ROOT . '/components/table-comprobantes.php'; ?>

<?php include APP_ROOT . '/includes/footer.php'; ?>