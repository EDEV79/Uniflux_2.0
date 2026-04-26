<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/connection/conexion.php';

$mensaje = '';

if (!empty($_GET['blocked'])) {
    $mensaje = 'Tu acceso SaaS fue suspendido por inactividad o mora en la suscripcion. Contacta al administrador para reactivar el pago.';
}

if (!empty($_SESSION['usuario']) || !empty($_SESSION['user_id'])) {
    header('Location: ' . app_url('inicio.php'));
    exit;
}

if (isset($_POST['log'])) {
    $user = isset($_POST['celular']) ? trim($_POST['celular']) : '';
    $clave = isset($_POST['clave']) ? (string) $_POST['clave'] : '';

    $hasSuscripcionHasta = false;
    $hasSubscriptionStatus = false;
    $hasTenantId = false;
    $columnCheck = mysqli_query($conexion, "SHOW COLUMNS FROM usuario LIKE 'suscripcion_hasta'");
    if ($columnCheck instanceof mysqli_result) {
        $hasSuscripcionHasta = mysqli_num_rows($columnCheck) > 0;
        mysqli_free_result($columnCheck);
    }

    $columnCheck = mysqli_query($conexion, "SHOW COLUMNS FROM usuario LIKE 'subscription_status'");
    if ($columnCheck instanceof mysqli_result) {
        $hasSubscriptionStatus = mysqli_num_rows($columnCheck) > 0;
        mysqli_free_result($columnCheck);
    }

    $columnCheck = mysqli_query($conexion, "SHOW COLUMNS FROM usuario LIKE 'tenant_id'");
    if ($columnCheck instanceof mysqli_result) {
        $hasTenantId = mysqli_num_rows($columnCheck) > 0;
        mysqli_free_result($columnCheck);
    }

    $selectFields = 'id, nombre, apellido, usuario, permiso, status';
    if ($hasSuscripcionHasta) {
        $selectFields .= ', suscripcion_hasta';
    }
    if ($hasSubscriptionStatus) {
        $selectFields .= ', subscription_status';
    }
    if ($hasTenantId) {
        $selectFields .= ', tenant_id';
    }

    $selectFields .= ', contrasena';

    $query = 'SELECT ' . $selectFields . ' FROM usuario WHERE celular = ? LIMIT 1';
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 's', $user);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $con_row = mysqli_fetch_assoc($res);

    if (!$con_row || !app_verify_password($clave, $con_row['contrasena'])) {
        $mensaje = 'Credenciales invalidas. Verifica tu celular y contrasena.';
    } else {
        if (app_password_needs_rehash($con_row['contrasena'])) {
            $newHash = app_hash_password($clave);
            $updateStmt = mysqli_prepare($conexion, 'UPDATE usuario SET contrasena = ? WHERE id = ? LIMIT 1');
            if ($updateStmt) {
                $userId = (int) $con_row['id'];
                mysqli_stmt_bind_param($updateStmt, 'si', $newHash, $userId);
                mysqli_stmt_execute($updateStmt);
                mysqli_stmt_close($updateStmt);
            }
        }

        $isActive = (int) $con_row['status'] === 1;
        $subscriptionValid = true;
        $subscriptionStateValid = true;
        $tenantValid = true;

        if ($hasSubscriptionStatus && !empty($con_row['subscription_status'])) {
            $subscriptionStatus = strtolower(trim((string) $con_row['subscription_status']));
            if (in_array($subscriptionStatus, array('past_due', 'suspended', 'cancelled'), true)) {
                $subscriptionStateValid = false;
            }
        }

        if ($hasSuscripcionHasta && !empty($con_row['suscripcion_hasta'])) {
            $today = new DateTime('today');
            $until = DateTime::createFromFormat('Y-m-d', $con_row['suscripcion_hasta']);
            if ($until instanceof DateTime && $until < $today) {
                $subscriptionValid = false;
            }
        }

        if ($hasTenantId && !empty($con_row['tenant_id'])) {
            $tenantFields = 'status';
            $tenantHasSubscriptionUntil = false;
            $tenantHasSubscriptionStatus = false;

            $tenantColumnCheck = mysqli_query($conexion, "SHOW COLUMNS FROM saas_tenants LIKE 'suscripcion_hasta'");
            if ($tenantColumnCheck instanceof mysqli_result) {
                $tenantHasSubscriptionUntil = mysqli_num_rows($tenantColumnCheck) > 0;
                mysqli_free_result($tenantColumnCheck);
            }

            $tenantColumnCheck = mysqli_query($conexion, "SHOW COLUMNS FROM saas_tenants LIKE 'subscription_status'");
            if ($tenantColumnCheck instanceof mysqli_result) {
                $tenantHasSubscriptionStatus = mysqli_num_rows($tenantColumnCheck) > 0;
                mysqli_free_result($tenantColumnCheck);
            }

            if ($tenantHasSubscriptionUntil) {
                $tenantFields .= ', suscripcion_hasta';
            }
            if ($tenantHasSubscriptionStatus) {
                $tenantFields .= ', subscription_status';
            }

            $tenantStmt = mysqli_prepare($conexion, 'SELECT ' . $tenantFields . ' FROM saas_tenants WHERE id = ? LIMIT 1');
            if ($tenantStmt) {
                $tenantId = (int) $con_row['tenant_id'];
                mysqli_stmt_bind_param($tenantStmt, 'i', $tenantId);
                mysqli_stmt_execute($tenantStmt);
                $tenantRes = mysqli_stmt_get_result($tenantStmt);
                $tenantRow = mysqli_fetch_assoc($tenantRes);
                mysqli_stmt_close($tenantStmt);

                if ($tenantRow) {
                    if (strtolower((string) $tenantRow['status']) !== 'active') {
                        $tenantValid = false;
                    }

                    if ($tenantHasSubscriptionStatus && !empty($tenantRow['subscription_status'])) {
                        $tenantSubscriptionStatus = strtolower(trim((string) $tenantRow['subscription_status']));
                        if (in_array($tenantSubscriptionStatus, array('past_due', 'suspended', 'cancelled'), true)) {
                            $tenantValid = false;
                        }
                    }

                    if ($tenantHasSubscriptionUntil && !empty($tenantRow['suscripcion_hasta'])) {
                        $today = new DateTime('today');
                        $until = DateTime::createFromFormat('Y-m-d', $tenantRow['suscripcion_hasta']);
                        if ($until instanceof DateTime && $until < $today) {
                            $tenantValid = false;
                        }
                    }
                }
            }
        }

        if (!$isActive || !$subscriptionValid || !$subscriptionStateValid || !$tenantValid) {
            $mensaje = 'Acceso suspendido: usuario inactivo o suscripcion vencida.';
        } else {
            $_SESSION['user_id'] = (int) $con_row['id'];
            $_SESSION['MM_NombApe'] = $con_row['nombre'] . ' ' . $con_row['apellido'];
            $_SESSION['usuario'] = trim((string) $con_row['usuario']) !== '' ? $con_row['usuario'] : $user;
            $_SESSION['permiso'] = (int) $con_row['permiso'];
            $_SESSION['tenant_id'] = $hasTenantId && !empty($con_row['tenant_id']) ? (int) $con_row['tenant_id'] : 0;

            header('Location: ' . app_url('inicio.php'));
            exit;
        }
    }

    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?php echo e(app_name()); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(asset_url('css/saas-admin.css')); ?>">
</head>

<body class="admin-body theme-dark">
    <main class="auth-screen">
        <section class="auth-card crm-animate-in">
            <div class="auth-hero">
                <div>
                    <span class="auth-kicker"><i class="bi bi-grid-1x2-fill"></i> Plataforma SaaS multicliente</span>
                    <h1>UniFlux</h1>
                    <p>Gestiona clientes, cobros y operaciones desde un solo lugar.</p>
                    <p>Escalable, claro y listo para crecer contigo.</p>
                </div>
            </div>
            <div class="auth-form-panel">
                <div class="auth-logo-row">
                    <div class="auth-logo">UF</div>
                    <div>
                        <h2>Iniciar sesion</h2>
                        <p>Accede al centro de control de UniFlux y continua tu operacion diaria.</p>
                    </div>
                </div>

                <?php if ($mensaje !== '') : ?>
                    <div class="alert alert-danger mb-0"><?php echo e($mensaje); ?></div>
                <?php endif; ?>

                <form method="post" class="row g-3">
                    <div class="col-12 auth-input-group">
                        <label class="form-label">Celular</label>
                        <input type="text" name="celular" class="form-control" placeholder="Celular sin guiones" pattern="[0-9]+(?:\.[0-9]+)?" required>
                    </div>
                    <div class="col-12 auth-input-group">
                        <label class="form-label">Contrasena</label>
                        <input type="password" name="clave" class="form-control" placeholder="Contrasena" required id="loginPassword">
                    </div>
                    <div class="col-12 auth-actions">
                        <a class="auth-link" href="<?php echo e(app_url('cambiopass.php')); ?>">Actualizar tu contrasena</a>
                        <button class="btn btn-outline-light" type="button" id="togglePasswordButton">Mostrar</button>
                    </div>
                    <div class="col-12 d-grid">
                        <button type="submit" name="log" class="btn btn-primary btn-lg">Ingresar al panel</button>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo e(asset_url('js/admin-ui.js')); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var passwordField = document.getElementById('loginPassword');
            var toggleButton = document.getElementById('togglePasswordButton');

            if (!passwordField || !toggleButton) {
                return;
            }

            toggleButton.addEventListener('click', function() {
                var isHidden = passwordField.type === 'password';
                passwordField.type = isHidden ? 'text' : 'password';
                toggleButton.textContent = isHidden ? 'Ocultar' : 'Mostrar';
            });
        });
    </script>
</body>

</html>
