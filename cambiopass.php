<?php

require_once __DIR__ . '/includes/bootstrap.php';
include('connection/conexion.php');

$isLoggedIn = !empty($_SESSION['usuario']);

$mensaje = '';
$mensajeTipo = 'danger';
$celularValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar'])) {
    $celular_raw = isset($_POST['celular']) ? trim($_POST['celular']) : '';
    $clave_raw = isset($_POST['clave']) ? trim($_POST['clave']) : '';
    $celularValue = $celular_raw;

    if (!verify_csrf_token(isset($_POST['_token']) ? $_POST['_token'] : '')) {
        $mensaje = 'La sesion expiro. Recarga la pagina e intenta nuevamente.';
    } elseif ($celular_raw === '' || $clave_raw === '') {
        $mensaje = 'Todos los campos son obligatorios.';
    } else {
        $celular = $celular_raw;
        $clave = sha1($clave_raw);

        $stmt = $conexion->prepare('SELECT id FROM usuario WHERE celular = ?');
        $stmt->bind_param('s', $celular);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 0) {
            $mensaje = 'El usuario no existe.';
        } else {
            $stmt_update = $conexion->prepare('UPDATE usuario SET contrasena = ? WHERE celular = ?');
            $stmt_update->bind_param('ss', $clave, $celular);

            if ($stmt_update->execute()) {
                flash('success', 'Contrasena actualizada correctamente.');
                header('Location: ' . app_url($isLoggedIn ? 'inicio.php' : 'index.php'));
                exit;
            }

            $mensaje = 'No se pudo actualizar la contrasena. Intenta nuevamente.';
        }
    }
}

function render_cambiopass_form($mensaje, $mensajeTipo, $celularValue)
{
?>
    <?php if ($mensaje !== '') : ?>
        <div class="alert alert-<?php echo e($mensajeTipo); ?> mb-3" role="alert">
            <?php echo e($mensaje); ?>
        </div>
    <?php endif; ?>

    <form method="post" class="row g-3 admin-form" data-loading-form>
        <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">

        <div class="col-12">
            <label class="form-label" for="celular">Celular</label>
            <input
                type="text"
                id="celular"
                name="celular"
                class="form-control"
                placeholder="Celular sin guiones"
                pattern="[0-9]+"
                value="<?php echo e($celularValue); ?>"
                required>
        </div>

        <div class="col-12">
            <label class="form-label" for="clave">Nueva contrasena</label>
            <div class="input-group">
                <input
                    type="password"
                    id="clave"
                    name="clave"
                    class="form-control"
                    placeholder="Escribe la nueva contrasena"
                    required>
                <button class="btn btn-outline-secondary" type="button" id="togglePasswordBtn" aria-label="Mostrar u ocultar contrasena">
                    <i class="bi bi-eye-slash" id="togglePasswordIcon"></i>
                </button>
            </div>
        </div>

        <div class="col-12 d-flex flex-wrap gap-2 form-actions-wrap">
            <button type="submit" name="actualizar" class="btn btn-primary" data-loading-button>
                <i class="bi bi-key me-2"></i>Actualizar contrasena
            </button>
            <a href="<?php echo e(app_url('index.php')); ?>" class="btn btn-outline-light">Ir al login</a>
        </div>
    </form>
<?php
}

function render_toggle_password_script()
{
?>
    <script>
        (function() {
            var passwordField = document.getElementById('clave');
            var toggleButton = document.getElementById('togglePasswordBtn');
            var toggleIcon = document.getElementById('togglePasswordIcon');

            if (!passwordField || !toggleButton || !toggleIcon) {
                return;
            }

            toggleButton.addEventListener('click', function() {
                var showing = passwordField.type === 'text';
                passwordField.type = showing ? 'password' : 'text';
                toggleIcon.classList.toggle('bi-eye', !showing);
                toggleIcon.classList.toggle('bi-eye-slash', showing);
            });
        }());
    </script>
<?php
}

if ($isLoggedIn) {
    $pageTitle = 'Actualizar contrasena | ' . app_name();
    $pageHeading = 'Actualizar contrasena';
    $pageDescription = '';
    $currentModule = 'dashboard';
    $pageActions = array(
        array(
            'label' => 'Volver al dashboard',
            'href' => 'inicio.php',
            'icon' => 'fa-solid fa-arrow-left',
            'class' => 'btn-outline-secondary',
        ),
    );

    include APP_ROOT . '/includes/header.php';
?>

    <section class="row g-4">
        <div class="col-12 col-lg-8 col-xl-6">
            <article class="surface-card crm-animate-in">
                <div class="surface-card__header">
                    <div>
                        <h2 class="surface-card__title">Cambiar clave de acceso</h2>
                        <p class="surface-card__subtitle">Actualiza la contrasena de un usuario usando su numero de celular.</p>
                    </div>
                </div>

                <?php render_cambiopass_form($mensaje, $mensajeTipo, $celularValue); ?>
            </article>
        </div>
    </section>

    <?php render_toggle_password_script(); ?>

<?php
    include APP_ROOT . '/includes/footer.php';
} else {
?>
    <!DOCTYPE html>
    <html lang="es">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Actualizar contrasena | <?php echo e(app_name()); ?></title>
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
                        <span class="auth-kicker"><i class="bi bi-shield-lock-fill"></i> Seguridad</span>
                        <h1>Actualizar contrasena</h1>
                        <p>Recupera acceso de forma segura y rapida.</p>
                    </div>
                </div>
                <div class="auth-form-panel">
                    <div class="auth-logo-row">
                        <div class="auth-logo">AH</div>
                        <div>
                            <h2>Cambiar clave</h2>
                            <p>Ingresa tu celular y define una nueva contrasena.</p>
                        </div>
                    </div>

                    <?php render_cambiopass_form($mensaje, $mensajeTipo, $celularValue); ?>
                </div>
            </section>
        </main>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?php echo e(asset_url('js/admin-ui.js')); ?>"></script>
        <?php render_toggle_password_script(); ?>
    </body>

    </html>
<?php
}
?>