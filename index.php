<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/connection/conexion.php';

$mensaje = '';

if (!empty($_SESSION['usuario'])) {
    header('Location: ' . app_url('inicio.php'));
    exit;
}

if (isset($_POST['log'])) {
    $user = isset($_POST['celular']) ? trim($_POST['celular']) : '';
    $clave = isset($_POST['clave']) ? sha1($_POST['clave']) : '';

    $stmt = mysqli_prepare(
        $conexion,
        'SELECT id, nombre, apellido, usuario, permiso FROM usuario WHERE celular = ? AND contrasena = ? LIMIT 1'
    );
    mysqli_stmt_bind_param($stmt, 'ss', $user, $clave);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $con_row = mysqli_fetch_assoc($res);
    $con = $con_row ? 1 : 0;

    if ($con == 0) {
        $mensaje = 'Credenciales invalidas. Verifica tu celular y contrasena.';
    } else {
        $_SESSION['MM_NombApe'] = $con_row['nombre'] . ' ' . $con_row['apellido'];
        $_SESSION['usuario'] = $con_row['usuario'];
        $_SESSION['permiso'] = $con_row['permiso'];

        header('Location: ' . app_url('inicio.php'));
        exit;
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
                    <span class="auth-kicker"><i class="bi bi-grid-1x2-fill"></i> Admin dashboard</span>
                    <h1>Admin Huamart Entertainment</h1>
                    <p>Una experiencia visual con foco financiero</p>
                    <p>Y claridad operativa.</p>
                </div>
            </div>
            <div class="auth-form-panel">
                <div class="auth-logo-row">
                    <div class="auth-logo">AH</div>
                    <div>
                        <h2>Iniciar sesion</h2>
                        <p>Accede al panel administrativo y continua con tus operaciones.</p>
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