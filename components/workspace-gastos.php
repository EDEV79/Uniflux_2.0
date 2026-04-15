<article class="surface-card h-100 crm-animate-in block-workspace" id="blockWorkspaceGastos">
    <div class="surface-card__header">
        <div>
            <h2 class="surface-card__title">Workspace</h2>
            <p class="surface-card__subtitle">Busqueda, paginacion y control rapido del listado de gastos.</p>
        </div>
    </div>

    <form class="row g-3 align-items-end" method="get" data-loading-form>
        <input type="hidden" name="action" value="index">
        <input type="hidden" name="sort" value="<?php echo e($sortBy); ?>">
        <input type="hidden" name="dir" value="<?php echo e($sortDirection); ?>">

        <div class="col-12 col-md-7 col-lg-5">
            <label for="tableQuickFilterGastos" class="form-label">Buscar en la pagina</label>
            <input id="tableQuickFilterGastos" type="text" class="form-control" data-table-filter="#gastosTable" placeholder="Filtra por proveedor, factura o vendedor">
        </div>

        <div class="col-12 col-md-5 col-lg-4 ms-lg-auto">
            <label for="searchServer" class="form-label">Buscar en base de datos</label>
            <input id="searchServer" type="text" class="form-control" name="search" value="<?php echo e($search); ?>" placeholder="Proveedor, factura, documento, vendedor">
        </div>

        <div class="col-12 col-md-4 col-lg-3">
            <label class="form-label">Registros por pagina</label>
            <select class="form-select" name="per_page">
                <?php foreach (array(10, 25, 50, 100) as $size) : ?>
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