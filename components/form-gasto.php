<article class="surface-card h-100 crm-animate-in block-comprobante" id="blockFormGasto">
    <div class="surface-card__header">
        <div>
            <h2 class="surface-card__title"><?php echo $formMode === 'edit' ? 'Editar gasto' : 'Registrar gasto'; ?></h2>
            <p class="surface-card__subtitle">Panel de ingreso para proveedores, factura, montos y trazabilidad del gasto.</p>
        </div>
    </div>

    <?php if (!empty($errors['general'])) : ?>
        <div class="alert alert-danger"><?php echo e($errors['general']); ?></div>
    <?php endif; ?>

    <?php
    $fechaInput = isset($formData['fecha']) ? trim((string) $formData['fecha']) : '';
    $fechaDate = DateTime::createFromFormat('d-m-Y', $fechaInput);
    if (!$fechaDate instanceof DateTime) {
        $fechaDate = DateTime::createFromFormat('d/m/Y', $fechaInput);
    }
    if ($fechaDate instanceof DateTime) {
        $fechaInput = $fechaDate->format('Y-m-d');
    }
    ?>

    <form method="post" class="row g-3 admin-form comprobante-form-grid" data-loading-form>
        <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="form_action" value="<?php echo $formMode === 'edit' ? 'update' : 'create'; ?>">
        <?php if ($formMode === 'edit') : ?>
            <input type="hidden" name="id" value="<?php echo e($editingId); ?>">
        <?php endif; ?>

        <div class="col-12 col-md-6 col-xl-3">
            <label class="form-label">Proveedor</label>
            <div class="input-icon-wrap">
                <i class="bi bi-building input-icon"></i>
                <input type="text" name="proveedor" class="form-control input-with-icon <?php echo isset($errors['proveedor']) ? 'is-invalid' : ''; ?>" value="<?php echo e($formData['proveedor']); ?>" placeholder="Nombre del proveedor">
            </div>
            <?php if (isset($errors['proveedor'])) : ?><div class="invalid-feedback"><?php echo e($errors['proveedor']); ?></div><?php endif; ?>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <label class="form-label">No. factura</label>
            <div class="input-icon-wrap">
                <i class="bi bi-receipt input-icon"></i>
                <input type="text" name="nfactura" class="form-control input-with-icon <?php echo isset($errors['nfactura']) ? 'is-invalid' : ''; ?>" value="<?php echo e($formData['nfactura']); ?>" placeholder="Numero de factura">
            </div>
            <?php if (isset($errors['nfactura'])) : ?><div class="invalid-feedback"><?php echo e($errors['nfactura']); ?></div><?php endif; ?>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <label class="form-label">Fecha</label>
            <div class="input-icon-wrap">
                <i class="bi bi-calendar-event input-icon"></i>
                <input type="text" name="fecha" data-datepicker class="form-control input-with-icon <?php echo isset($errors['fecha']) ? 'is-invalid' : ''; ?>" value="<?php echo e($fechaInput); ?>" placeholder="DD-MM-YYYY" autocomplete="off">
            </div>
            <?php if (isset($errors['fecha'])) : ?><div class="invalid-feedback"><?php echo e($errors['fecha']); ?></div><?php endif; ?>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <label class="form-label">Vendedor</label>
            <div class="input-icon-wrap">
                <i class="bi bi-person-badge input-icon"></i>
                <input type="text" name="vendedor" class="form-control input-with-icon <?php echo isset($errors['vendedor']) ? 'is-invalid' : ''; ?>" value="<?php echo e($formData['vendedor']); ?>" placeholder="Nombre del vendedor">
            </div>
            <?php if (isset($errors['vendedor'])) : ?><div class="invalid-feedback"><?php echo e($errors['vendedor']); ?></div><?php endif; ?>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <label class="form-label">Subtotal</label>
            <div class="input-icon-wrap">
                <i class="bi bi-cash-stack input-icon"></i>
                <input type="number" step="0.01" min="0" name="subtotal" class="form-control input-with-icon <?php echo isset($errors['subtotal']) ? 'is-invalid' : ''; ?>" value="<?php echo e($formData['subtotal']); ?>" placeholder="0.00">
            </div>
            <?php if (isset($errors['subtotal'])) : ?><div class="invalid-feedback"><?php echo e($errors['subtotal']); ?></div><?php endif; ?>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <label class="form-label">ITBMS</label>
            <div class="input-icon-wrap">
                <i class="bi bi-percent input-icon"></i>
                <input type="number" step="0.01" min="0" name="itbms" class="form-control input-with-icon <?php echo isset($errors['itbms']) ? 'is-invalid' : ''; ?>" value="<?php echo e($formData['itbms']); ?>" placeholder="0.00">
            </div>
            <?php if (isset($errors['itbms'])) : ?><div class="invalid-feedback"><?php echo e($errors['itbms']); ?></div><?php endif; ?>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <label class="form-label">Total</label>
            <div class="input-icon-wrap">
                <i class="bi bi-currency-dollar input-icon"></i>
                <input type="number" step="0.01" min="0" name="total" class="form-control input-with-icon <?php echo isset($errors['total']) ? 'is-invalid' : ''; ?>" value="<?php echo e($formData['total']); ?>" placeholder="0.00">
            </div>
            <?php if (isset($errors['total'])) : ?><div class="invalid-feedback"><?php echo e($errors['total']); ?></div><?php endif; ?>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <label class="form-label">Documento</label>
            <div class="input-icon-wrap">
                <i class="bi bi-file-earmark-text input-icon"></i>
                <input type="text" name="documento" class="form-control input-with-icon" value="<?php echo e($formData['documento']); ?>" placeholder="Referencia o URL del documento">
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <label class="form-label">Printdate (opcional)</label>
            <div class="input-icon-wrap">
                <i class="bi bi-clock-history input-icon"></i>
                <input type="text" name="printdate" class="form-control input-with-icon <?php echo isset($errors['printdate']) ? 'is-invalid' : ''; ?>" value="<?php echo e($formData['printdate']); ?>" placeholder="YYYY-MM-DD HH:MM:SS">
            </div>
            <?php if (isset($errors['printdate'])) : ?><div class="invalid-feedback"><?php echo e($errors['printdate']); ?></div><?php endif; ?>
        </div>

        <div class="col-12 d-flex flex-wrap gap-2 form-actions-wrap">
            <button type="submit" class="btn btn-primary" data-loading-button>
                <i class="fa-solid fa-floppy-disk me-2"></i><?php echo $formMode === 'edit' ? 'Guardar cambios' : 'Crear gasto'; ?>
            </button>
            <?php if ($formMode === 'edit') : ?>
                <a class="btn btn-outline-secondary" href="<?php echo e(app_url('modules/gastos/index.php')); ?>">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</article>
