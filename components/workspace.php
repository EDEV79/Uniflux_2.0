<article class="surface-card h-100 crm-animate-in block-workspace" id="blockWorkspace">
    <div class="surface-card__header">
        <div>
            <h2 class="surface-card__title">Workspace</h2>
            <p class="surface-card__subtitle">Busqueda, filtros y acciones rapidas para navegar el listado.</p>
        </div>
    </div>

    <form class="row g-3 align-items-end" method="get" data-loading-form>
        <input type="hidden" name="action" value="index">
        <input type="hidden" name="sort" value="<?php echo e($sortBy); ?>">
        <input type="hidden" name="dir" value="<?php echo e($sortDirection); ?>">

        <div class="col-12 col-md-7 col-lg-5">
            <label for="tableQuickFilter" class="form-label">Buscar comprobante</label>
            <input id="tableQuickFilter" type="text" class="form-control" data-table-filter="#comprobantesTable" placeholder="Filtra en la pagina actual">
        </div>
        <div class="col-12 col-md-5 col-lg-4 ms-lg-auto">
            <label class="form-label">Registros por pagina</label>
            <select class="form-select" name="per_page">
                <?php foreach (array(10, 25, 50, 100) as $size) : ?>
                    <option value="<?php echo e($size); ?>" <?php echo $perPage === $size ? 'selected' : ''; ?>><?php echo e($size); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</article>