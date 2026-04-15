<?php
$currentModule = isset($currentModule) ? $currentModule : 'dashboard';
$navigationItems = array(
    array('key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fa-solid fa-chart-line', 'href' => 'inicio.php'),
    array('key' => 'comprobantes', 'label' => 'Comprobantes', 'icon' => 'fa-solid fa-file-invoice-dollar', 'href' => 'comprobanteinvest.php'),
    array('key' => 'gastos', 'label' => 'Gastos', 'icon' => 'fa-solid fa-wallet', 'href' => 'modules/gastos/index.php'),
    array('key' => 'clientes', 'label' => 'Clientes', 'icon' => 'fa-solid fa-users', 'href' => 'modules/clientes/index.php'),
    array('key' => 'servicios', 'label' => 'Servicios', 'icon' => 'fa-solid fa-briefcase', 'href' => 'modules/servicios/index.php'),
);
?>
<aside class="app-sidebar" id="appSidebar" data-sidebar-collapse-toggle>
    <div>
        <div class="sidebar-brand">
            <div class="sidebar-brand__mark">AH</div>
            <div>
                <h2><?php echo e(app_name()); ?></h2>
            </div>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section__label">Menu</div>
            <nav class="nav flex-column sidebar-nav">
                <?php foreach ($navigationItems as $item) : ?>
                    <a class="sidebar-link <?php echo $currentModule === $item['key'] ? 'is-active' : ''; ?>" href="<?php echo e(app_url($item['href'])); ?>">
                        <i class="<?php echo e($item['icon']); ?>"></i>
                        <span><?php echo e($item['label']); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>

    <footer class="sidebar-footer">
        <small>AHE &copy; 2026</small>
    </footer>
</aside>