<article class="surface-card h-100 crm-animate-in block-comprobante" id="blockFormComprobante">
    <div class="surface-card__header">
        <div>
            <h2 class="surface-card__title"><?php echo $formMode === 'edit' ? 'Editar comprobante' : 'Crear comprobante'; ?></h2>
            <p class="surface-card__subtitle">Formulario principal para registrar nuevos comprobantes y actualizar eventos existentes.</p>
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

    $horaInput = isset($formData['hora']) ? trim((string) $formData['hora']) : '';
    $horaDate = DateTime::createFromFormat('h:i A', $horaInput);
    if ($horaDate instanceof DateTime) {
        $horaInput = $horaDate->format('h:i A');
    }
    ?>

    <form method="post" class="row g-3 admin-form comprobante-form-grid" data-loading-form>
        <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="form_action" value="<?php echo $formMode === 'edit' ? 'update' : 'create'; ?>">
        <?php if ($formMode === 'edit') : ?>
            <input type="hidden" name="id" value="<?php echo e($editingId); ?>">
        <?php endif; ?>

        <div class="col-12 col-md-6 col-xl-3">
            <label class="form-label">Nombre completo</label>
            <div class="input-icon-wrap">
                <i class="bi bi-person input-icon"></i>
                <input type="text" name="nombre" class="form-control input-with-icon <?php echo isset($errors['nombre']) ? 'is-invalid' : ''; ?>" value="<?php echo e($formData['nombre']); ?>" placeholder="Nombre del cliente">
            </div>
            <?php if (isset($errors['nombre'])) : ?><div class="invalid-feedback"><?php echo e($errors['nombre']); ?></div><?php endif; ?>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <label class="form-label">Cedula</label>
            <div class="input-icon-wrap">
                <i class="bi bi-person-vcard input-icon"></i>
                <input type="text" name="cedula" class="form-control input-with-icon <?php echo isset($errors['cedula']) ? 'is-invalid' : ''; ?>" value="<?php echo e($formData['cedula']); ?>" placeholder="Numero de cedula">
            </div>
            <?php if (isset($errors['cedula'])) : ?><div class="invalid-feedback"><?php echo e($errors['cedula']); ?></div><?php endif; ?>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <label class="form-label">Celular</label>
            <div class="input-icon-wrap">
                <i class="bi bi-telephone input-icon"></i>
                <input type="text" name="celular" class="form-control input-with-icon <?php echo isset($errors['celular']) ? 'is-invalid' : ''; ?>" value="<?php echo e($formData['celular']); ?>" placeholder="Telefono de contacto">
            </div>
            <?php if (isset($errors['celular'])) : ?><div class="invalid-feedback"><?php echo e($errors['celular']); ?></div><?php endif; ?>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <label class="form-label">Email</label>
            <div class="input-icon-wrap">
                <i class="bi bi-envelope input-icon"></i>
                <input type="email" name="email" class="form-control input-with-icon <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" value="<?php echo e($formData['email']); ?>" placeholder="correo@ejemplo.com">
            </div>
            <?php if (isset($errors['email'])) : ?><div class="invalid-feedback"><?php echo e($errors['email']); ?></div><?php endif; ?>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <label class="form-label">Fecha del evento</label>
            <div class="input-icon-wrap">
                <i class="bi bi-calendar-event input-icon"></i>
                <input type="text" name="fecha" data-datepicker class="form-control input-with-icon <?php echo isset($errors['fecha']) ? 'is-invalid' : ''; ?>" value="<?php echo e($fechaInput); ?>" placeholder="DD-MM-YYYY" autocomplete="off">
            </div>
            <?php if (isset($errors['fecha'])) : ?><div class="invalid-feedback"><?php echo e($errors['fecha']); ?></div><?php endif; ?>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <label class="form-label">Hora del evento</label>
            <div class="input-icon-wrap">
                <i class="bi bi-clock input-icon"></i>
                <input type="text" name="hora" data-timepicker class="form-control input-with-icon <?php echo isset($errors['hora']) ? 'is-invalid' : ''; ?>" value="<?php echo e($horaInput); ?>" placeholder="02:30 PM" autocomplete="off">
            </div>
            <?php if (isset($errors['hora'])) : ?><div class="invalid-feedback"><?php echo e($errors['hora']); ?></div><?php endif; ?>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <label class="form-label">Lugar</label>
            <div class="input-icon-wrap">
                <i class="bi bi-geo-alt input-icon"></i>
                <input type="text" name="lugar" class="form-control input-with-icon <?php echo isset($errors['lugar']) ? 'is-invalid' : ''; ?>" value="<?php echo e($formData['lugar']); ?>" placeholder="Ubicacion del evento">
            </div>
            <?php if (isset($errors['lugar'])) : ?><div class="invalid-feedback"><?php echo e($errors['lugar']); ?></div><?php endif; ?>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <label class="form-label">Precio</label>
            <div class="input-icon-wrap">
                <i class="bi bi-currency-dollar input-icon"></i>
                <input type="number" step="0.01" min="0" name="precio_show" class="form-control input-with-icon <?php echo isset($errors['precio_show']) ? 'is-invalid' : ''; ?>" value="<?php echo e($formData['precio_show']); ?>" placeholder="0.00">
            </div>
            <?php if (isset($errors['precio_show'])) : ?><div class="invalid-feedback"><?php echo e($errors['precio_show']); ?></div><?php endif; ?>
        </div>
        <div class="col-12">
            <label class="form-label">Comentarios</label>
            <textarea name="comentarios" rows="5" class="form-control form-textarea-soft <?php echo isset($errors['comentarios']) ? 'is-invalid' : ''; ?>" placeholder="Describe detalles relevantes del servicio o evento"><?php echo e($formData['comentarios']); ?></textarea>
            <?php if (isset($errors['comentarios'])) : ?><div class="invalid-feedback"><?php echo e($errors['comentarios']); ?></div><?php endif; ?>
        </div>
        <div class="col-12 d-flex flex-wrap gap-2 form-actions-wrap">
            <button type="submit" class="btn btn-primary" data-loading-button>
                <i class="fa-solid fa-floppy-disk me-2"></i><?php echo $formMode === 'edit' ? 'Guardar cambios' : 'Crear comprobante'; ?>
            </button>
            <?php if ($formMode === 'edit') : ?>
                <a class="btn btn-outline-secondary" href="<?php echo e(app_url('comprobantes.php')); ?>">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</article>
