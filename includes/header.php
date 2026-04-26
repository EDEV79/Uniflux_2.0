<?php
$pageTitle = isset($pageTitle) ? $pageTitle : app_name();
$pageHeading = isset($pageHeading) ? $pageHeading : 'Dashboard';
$pageDescription = isset($pageDescription) ? $pageDescription : 'Vista general del sistema';
$pageActions = isset($pageActions) ? $pageActions : array();
$currentModule = isset($currentModule) ? $currentModule : 'dashboard';
$flashMessages = consume_flashes();
$saasAdminCssPath = APP_ROOT . '/css/saas-admin.css';
$saasAdminCssVersion = file_exists($saasAdminCssPath) ? (string) filemtime($saasAdminCssPath) : '1';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo e($pageTitle); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css" onerror="this.onerror=null;this.href='https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css';">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.3/themes/base/jquery-ui.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.css">
    <link rel="stylesheet" href="<?php echo e(asset_url('css/saas-admin.css')); ?>?v=<?php echo e($saasAdminCssVersion); ?>">
</head>

<body class="admin-body" data-module="<?php echo e($currentModule); ?>">
    <div class="app-shell" id="appShell">
        <?php include APP_ROOT . '/includes/sidebar.php'; ?>
        <button type="button" class="sidebar-overlay" id="sidebarOverlay" aria-label="Cerrar menu lateral"></button>
        <main class="content-panel">
            <?php include APP_ROOT . '/includes/topbar.php'; ?>

            <section class="app-content container-fluid">
                <section class="page-toolbar d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                    <div>
                        <div class="page-toolbar__label">Workspace</div>
                        <div class="breadcrumb-lite">Inicio / <?php echo e($pageHeading); ?></div>
                    </div>
                    <?php if (!empty($pageActions)) : ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($pageActions as $action) : ?>
                                <a class="btn <?php echo e(isset($action['class']) ? $action['class'] : 'btn-primary'); ?>" href="<?php echo e(app_url($action['href'])); ?>">
                                    <?php if (!empty($action['icon'])) : ?>
                                        <i class="<?php echo e($action['icon']); ?> me-2"></i>
                                    <?php endif; ?>
                                    <?php echo e($action['label']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <?php if (!empty($flashMessages)) : ?>
                    <section class="mb-4">
                        <?php foreach ($flashMessages as $flashMessage) : ?>
                            <div class="alert alert-<?php echo e($flashMessage['type']); ?> alert-dismissible fade show shadow-sm" role="alert">
                                <?php echo e($flashMessage['message']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endforeach; ?>
                    </section>
                <?php endif; ?>
