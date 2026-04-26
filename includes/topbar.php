<?php $accessBadge = current_user_access_badge(); ?>
<header class="crm-navbar">
    <div class="crm-navbar__main">
        <button class="btn btn-outline-light topbar__toggle" type="button" data-sidebar-toggle aria-label="Abrir menu" aria-expanded="false" aria-controls="appSidebar">
            <i class="bi bi-list" data-sidebar-toggle-icon></i>
        </button>
        <button class="btn btn-outline-light topbar__collapse" type="button" data-sidebar-collapse aria-label="Contraer barra lateral" aria-expanded="true" title="Contraer barra lateral">
            <i class="bi bi-chevron-left"></i>
        </button>
        <div class="crm-navbar__title">
            <h1><?php echo e($pageHeading); ?></h1>
        </div>
    </div>
    <div class="crm-navbar-actions">
        <div class="user-dropdown" data-user-dropdown>
            <button class="user-dropdown__trigger" type="button" aria-haspopup="true" aria-expanded="false" data-user-dropdown-trigger>
                <i class="bi bi-person-circle user-dropdown__avatar"></i>
                <div class="user-dropdown__info">
                    <strong><?php echo e(current_user_name()); ?></strong>
                    <div class="mt-1">
                        <span class="badge rounded-pill <?php echo e($accessBadge['class']); ?>"><?php echo e($accessBadge['label']); ?></span>
                    </div>
                    <small><?php echo e(current_user_role_label()); ?> · <?php echo e(current_user_username()); ?></small>
                </div>
                <i class="bi bi-chevron-down user-dropdown__caret"></i>
            </button>
            <div class="user-dropdown__menu" role="menu">
                <a class="user-dropdown__item" href="<?php echo e(app_url('cambiopass.php')); ?>" role="menuitem">
                    <i class="fa-solid fa-key"></i>
                    <span>Cambiar contrasena</span>
                </a>
                <a class="user-dropdown__item user-dropdown__item--danger" href="<?php echo e(app_url('logout.php')); ?>" role="menuitem">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    <span>Cerrar sesion</span>
                </a>
            </div>
        </div>
    </div>
</header>
