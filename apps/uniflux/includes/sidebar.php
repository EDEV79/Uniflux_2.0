<?php
$currentModule = isset($currentModule) ? $currentModule : 'dashboard';
$navigationItems = array(
    array('key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fa-solid fa-chart-line', 'href' => 'inicio.php', 'capability' => 'dashboard.view'),
    array('key' => 'comprobantes', 'label' => 'Comprobantes', 'icon' => 'fa-solid fa-file-invoice-dollar', 'href' => 'comprobantes.php', 'capability' => 'comprobantes.manage'),
    array('key' => 'gastos', 'label' => 'Gastos', 'icon' => 'fa-solid fa-wallet', 'href' => 'gastos.php', 'capability' => 'gastos.manage'),
    array('key' => 'solicitudes', 'label' => 'Solicitudes', 'icon' => 'fa-solid fa-list-check', 'href' => 'solicitudes.php', 'capability' => 'solicitudes.manage'),
    array('key' => 'clientes', 'label' => 'Clientes Admin', 'icon' => 'fa-solid fa-users-gear', 'href' => 'clientes.php', 'capability' => 'clientes.manage'),
    array('key' => 'servicios', 'label' => 'Servicios', 'icon' => 'fa-solid fa-briefcase', 'href' => 'servicios.php', 'capability' => 'servicios.view'),
);
?>
<aside class="app-sidebar" id="appSidebar" data-sidebar-collapse-toggle>
    <div>
        <div class="sidebar-brand">
            <div class="sidebar-brand__mark">UF</div>
            <div>
                <h2><?php echo e(app_name()); ?></h2>
            </div>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section__label">Menu</div>
            <nav class="nav flex-column sidebar-nav">
                <?php foreach ($navigationItems as $item) : ?>
                    <?php
                    $requiredCapability = isset($item['capability']) ? (string) $item['capability'] : '';
                    if ($requiredCapability !== '' && !current_user_can($requiredCapability)) {
                        continue;
                    }
                    ?>
                    <a class="sidebar-link <?php echo $currentModule === $item['key'] ? 'is-active' : ''; ?>" href="<?php echo e(app_url($item['href'])); ?>">
                        <i class="<?php echo e($item['icon']); ?>"></i>
                        <span><?php echo e($item['label']); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>

    <footer class="sidebar-footer">
        <small>UniFlux &copy; 2026</small>
    </footer>
</aside>
