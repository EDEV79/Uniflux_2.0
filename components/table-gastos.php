<?php
$sortableColumns = array(
    'idgastos' => 'No.',
    'proveedor' => 'Proveedor',
    'nfactura' => 'Factura',
    'fecha' => 'Fecha',
    'total' => 'Total',
);
?>
<section class="surface-card crm-animate-in block-table" id="blockListadoGastos">
    <div class="surface-card__header">
        <div>
            <h2 class="surface-card__title">Listado de gastos</h2>
            <p class="surface-card__subtitle">Tabla de control con ordenamiento, edicion y eliminacion.</p>
        </div>
        <span class="table-count"><?php echo e(number_format($totalItems)); ?> registros</span>
    </div>

    <div class="table-responsive">
        <table class="table modern-table align-middle" id="gastosTable">
            <thead>
                <tr>
                    <?php foreach ($sortableColumns as $columnKey => $columnLabel) : ?>
                        <?php
                        $isActive = $sortBy === $columnKey;
                        $nextDirection = $isActive && $sortDirection === 'asc' ? 'desc' : 'asc';
                        $sortIcon = 'fa-sort';
                        if ($isActive) {
                            $sortIcon = $sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
                        }
                        ?>
                        <th>
                            <a class="table-sort <?php echo $isActive ? 'is-active' : ''; ?>" href="<?php echo e(app_url('modules/gastos/index.php')); ?>?<?php echo e(query_string_with(array('sort' => $columnKey, 'dir' => $nextDirection, 'page' => 1))); ?>">
                                <span><?php echo e($columnLabel); ?></span>
                                <i class="fa-solid <?php echo e($sortIcon); ?>"></i>
                            </a>
                        </th>
                    <?php endforeach; ?>
                    <th>Detalle</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)) : ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state py-4">
                                <i class="fa-regular fa-folder-open"></i>
                                <p>No se encontraron gastos con los filtros actuales.</p>
                            </div>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($records as $record) : ?>
                        <tr data-filter-row>
                            <td data-label="No."><strong>#<?php echo e($record['idgastos']); ?></strong></td>
                            <td data-label="Proveedor">
                                <div class="table-primary-text"><?php echo e($record['proveedor']); ?></div>
                                <small class="text-muted"><?php echo e($record['vendedor']); ?></small>
                            </td>
                            <td data-label="Factura"><?php echo e($record['nfactura']); ?></td>
                            <td data-label="Fecha"><?php echo e($record['fecha']); ?></td>
                            <td data-label="Total"><strong><?php echo e(format_currency($record['total'])); ?></strong></td>
                            <td data-label="Detalle">
                                <div><small>Subtotal: <?php echo e(format_currency($record['subtotal'])); ?></small></div>
                                <div><small>ITBMS: <?php echo e(format_currency($record['itbms'])); ?></small></div>
                                <div><small>Doc: <?php echo e($record['documento'] !== '' ? $record['documento'] : 'N/A'); ?></small></div>
                            </td>
                            <td data-label="Acciones">
                                <div class="d-flex justify-content-end gap-1 table-actions">
                                    <a class="tbl-action tbl-action--edit" href="<?php echo e(app_url('modules/gastos/index.php')); ?>?action=edit&id=<?php echo e($record['idgastos']); ?>" title="Editar" aria-label="Editar gasto <?php echo e($record['idgastos']); ?>">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <form method="post" onsubmit="return confirm('Seguro que deseas eliminar este gasto?');" data-loading-form>
                                        <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                                        <input type="hidden" name="form_action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo e($record['idgastos']); ?>">
                                        <button type="submit" class="tbl-action tbl-action--delete" title="Eliminar" aria-label="Eliminar gasto <?php echo e($record['idgastos']); ?>" data-loading-button>
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 pt-3">
        <div class="text-muted small">Pagina <?php echo e($pagination['current_page']); ?> de <?php echo e($pagination['total_pages']); ?></div>
        <nav aria-label="Paginacion de gastos">
            <ul class="pagination mb-0">
                <li class="page-item <?php echo $pagination['current_page'] <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo e(app_url('modules/gastos/index.php')); ?>?<?php echo e(query_string_with(array('page' => max(1, $pagination['current_page'] - 1)))); ?>">Anterior</a>
                </li>
                <?php for ($pageNumber = 1; $pageNumber <= $pagination['total_pages']; $pageNumber++) : ?>
                    <li class="page-item <?php echo $pageNumber === $pagination['current_page'] ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo e(app_url('modules/gastos/index.php')); ?>?<?php echo e(query_string_with(array('page' => $pageNumber))); ?>"><?php echo e($pageNumber); ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $pagination['current_page'] >= $pagination['total_pages'] ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo e(app_url('modules/gastos/index.php')); ?>?<?php echo e(query_string_with(array('page' => min($pagination['total_pages'], $pagination['current_page'] + 1)))); ?>">Siguiente</a>
                </li>
            </ul>
        </nav>
    </div>
</section>