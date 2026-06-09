<?php
define('APP_PUBLIC_ROOT', __DIR__);
define('APP_ROOT', dirname(__DIR__) . '/apps/uniflux');

require_once APP_ROOT . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/password_reset.php';

$pdo        = app_pdo();
$isLoggedIn = !empty($_SESSION['usuario']);
$mensaje    = '';
$tipo       = 'danger';

// Determinar paso actual
// _pr_verified: ['user_id' => int, 'expires' => timestamp]
$step = 'identity';
if (!empty($_SESSION['_pr_verified']) && (int) $_SESSION['_pr_verified']['expires'] > time()) {
    $step = 'newpass';
}

// -----------------------------------------------------------------------
// Procesar POST
// -----------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token(isset($_POST['_token']) ? $_POST['_token'] : '')) {
        $mensaje = 'La sesion expiro. Recarga la pagina e intenta de nuevo.';
    } elseif (isset($_POST['form_action']) && $_POST['form_action'] === 'verify_identity') {

        $rl = pr_rate_limit_check('identity');
        if (!$rl['allowed']) {
            $mensaje = 'Demasiados intentos fallidos. Espera ' . $rl['wait_seconds'] . ' segundo(s) e intenta de nuevo.';
        } else {
            $celular  = trim((string) (isset($_POST['celular']) ? $_POST['celular'] : ''));
            $email    = trim((string) (isset($_POST['email'])   ? $_POST['email']   : ''));
            $captcha  = trim((string) (isset($_POST['captcha']) ? $_POST['captcha'] : ''));
            $captchaT = trim((string) (isset($_POST['_ct'])     ? $_POST['_ct']     : ''));

            if ($celular === '' || $email === '' || $captcha === '') {
                $mensaje = 'Completa todos los campos del formulario.';
            } elseif (!pr_captcha_verify($captcha, $captchaT)) {
                $mensaje = 'La respuesta al captcha es incorrecta. Intentalo de nuevo.';
            } else {
                $user = pr_find_user_by_identity($pdo, $celular, $email);
                if (!$user) {
                    $mensaje = 'Los datos ingresados no coinciden con ningun usuario registrado.';
                } else {
                    pr_rate_limit_reset('identity');
                    $_SESSION['_pr_verified'] = array(
                        'user_id' => (int) $user['id'],
                        'name'    => e((string) $user['nombre']),
                        'expires' => time() + 900,
                    );
                    $step = 'newpass';
                }
            }
        }
    } elseif (isset($_POST['form_action']) && $_POST['form_action'] === 'set_password') {

        if (empty($_SESSION['_pr_verified']) || (int) $_SESSION['_pr_verified']['expires'] <= time()) {
            unset($_SESSION['_pr_verified']);
            $step    = 'identity';
            $mensaje = 'La sesion de recuperacion expiro. Vuelve a verificar tu identidad.';
        } else {
            $rl = pr_rate_limit_check('newpass');
            if (!$rl['allowed']) {
                $mensaje = 'Demasiados intentos fallidos. Espera ' . $rl['wait_seconds'] . ' segundo(s).';
            } else {
                $clave   = isset($_POST['clave'])         ? $_POST['clave']         : '';
                $confirm = isset($_POST['clave_confirm']) ? $_POST['clave_confirm'] : '';
                $captcha  = trim((string) (isset($_POST['captcha']) ? $_POST['captcha'] : ''));
                $captchaT = trim((string) (isset($_POST['_ct'])     ? $_POST['_ct']     : ''));

                if ($clave === '' || $confirm === '' || $captcha === '') {
                    $mensaje = 'Completa todos los campos.';
                } elseif (!pr_captcha_verify($captcha, $captchaT)) {
                    $mensaje = 'La respuesta al captcha es incorrecta.';
                } elseif (strlen($clave) < 8) {
                    $mensaje = 'La contrasena debe tener al menos 8 caracteres.';
                } elseif ($clave !== $confirm) {
                    $mensaje = 'Las contrasenas no coinciden. Verificalas e intenta de nuevo.';
                } else {
                    $userId = (int) $_SESSION['_pr_verified']['user_id'];
                    if (pr_update_password($pdo, $userId, $clave)) {
                        unset($_SESSION['_pr_verified']);
                        pr_rate_limit_reset('newpass');
                        flash('success', 'Contrasena actualizada correctamente. Ya puedes iniciar sesion.');
                        header('Location: ' . app_url($isLoggedIn ? 'inicio.php' : 'index.php'));
                        exit;
                    }
                    $mensaje = 'No se pudo guardar la nueva contrasena. Intenta de nuevo.';
                }
            }
        }
    }
}

// Generar captcha fresco para el renderizado
$captchaData = pr_captcha_generate();

// -----------------------------------------------------------------------
// Funciones de renderizado
// -----------------------------------------------------------------------

function render_pr_alert($mensaje, $tipo)
{
    if ($mensaje === '') return;
    echo '<div class="alert alert-' . e($tipo) . ' mb-3" role="alert"><i class="bi bi-exclamation-triangle-fill me-2"></i>' . e($mensaje) . '</div>';
}

function render_pr_captcha(array $captchaData)
{
?>
    <div class="col-12">
        <label class="form-label" for="captcha">
            Verificacion anti-robot &nbsp;
            <span class="badge bg-secondary fw-normal">&iquest;Cuanto es <strong><?php echo e($captchaData['question']); ?></strong>?</span>
        </label>
        <input
            type="number"
            id="captcha"
            name="captcha"
            class="form-control"
            placeholder="Escribe el resultado"
            inputmode="numeric"
            autocomplete="off"
            required>
        <input type="hidden" name="_ct" value="<?php echo e($captchaData['token']); ?>">
    </div>
<?php
}

function render_identity_form($mensaje, $tipo, $captchaData)
{
    render_pr_alert($mensaje, $tipo);
?>
    <form method="post" class="row g-3 admin-form">
        <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="form_action" value="verify_identity">

        <div class="col-12">
            <label class="form-label" for="celular">Celular</label>
            <input
                type="tel"
                id="celular"
                name="celular"
                class="form-control"
                placeholder="Tu numero de celular"
                autocomplete="tel"
                required>
        </div>

        <div class="col-12">
            <label class="form-label" for="email">Correo electronico</label>
            <input
                type="email"
                id="email"
                name="email"
                class="form-control"
                placeholder="tu@correo.com"
                autocomplete="email"
                required>
        </div>

        <?php render_pr_captcha($captchaData); ?>

        <div class="col-12 d-flex flex-wrap gap-2 form-actions-wrap">
            <button type="submit" class="btn btn-primary" data-loading-button>
                <i class="bi bi-person-check me-2"></i>Verificar identidad
            </button>
            <a href="<?php echo e(app_url('index.php')); ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Ir al login
            </a>
        </div>
    </form>
<?php
}

function render_newpass_form($mensaje, $tipo, $captchaData)
{
    $nombre = !empty($_SESSION['_pr_verified']['name']) ? $_SESSION['_pr_verified']['name'] : '';
    render_pr_alert($mensaje, $tipo);
?>
    <?php if ($nombre !== '') : ?>
        <div class="alert alert-success mb-3" role="alert">
            <i class="bi bi-shield-check-fill me-2"></i>
            Identidad verificada para <strong><?php echo e($nombre); ?></strong>. Ingresa tu nueva contrasena.
        </div>
    <?php endif; ?>

    <form method="post" class="row g-3 admin-form">
        <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="form_action" value="set_password">

        <div class="col-12">
            <label class="form-label" for="clave">Nueva contrasena</label>
            <div class="input-group">
                <input
                    type="password"
                    id="clave"
                    name="clave"
                    class="form-control"
                    placeholder="Minimo 8 caracteres"
                    minlength="8"
                    autocomplete="new-password"
                    required>
                <button class="btn btn-outline-secondary" type="button" data-password-toggle="#clave" aria-label="Mostrar u ocultar contrasena">
                    <i class="bi bi-eye-slash"></i>
                </button>
            </div>
        </div>

        <div class="col-12">
            <label class="form-label" for="clave_confirm">Confirmar contrasena</label>
            <div class="input-group">
                <input
                    type="password"
                    id="clave_confirm"
                    name="clave_confirm"
                    class="form-control"
                    placeholder="Repite la nueva contrasena"
                    minlength="8"
                    autocomplete="new-password"
                    required>
                <button class="btn btn-outline-secondary" type="button" data-password-toggle="#clave_confirm" aria-label="Mostrar u ocultar confirmacion">
                    <i class="bi bi-eye-slash"></i>
                </button>
            </div>
        </div>

        <?php render_pr_captcha($captchaData); ?>

        <div class="col-12 d-flex flex-wrap gap-2 form-actions-wrap">
            <button type="submit" class="btn btn-primary" data-loading-button>
                <i class="bi bi-lock-fill me-2"></i>Guardar nueva contrasena
            </button>
            <a href="<?php echo e(app_url('cambiopass.php')); ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-counterclockwise me-2"></i>Volver al inicio
            </a>
        </div>
    </form>
<?php
}

function render_toggle_password_script()
{
?>
    <script>
        (function() {
            document.querySelectorAll('[data-password-toggle]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var input = document.querySelector(btn.getAttribute('data-password-toggle'));
                    var icon = btn.querySelector('i');
                    if (!input || !icon) return;
                    var showing = input.type === 'text';
                    input.type = showing ? 'password' : 'text';
                    icon.classList.toggle('bi-eye', !showing);
                    icon.classList.toggle('bi-eye-slash', showing);
                });
            });
        }());
    </script>
<?php
}

// -----------------------------------------------------------------------
// Renderizado de la pagina
// -----------------------------------------------------------------------

if ($isLoggedIn) {
    $pageTitle       = 'Cambiar contrasena | ' . app_name();
    $pageHeading     = 'Cambiar contrasena';
    $pageDescription = '';
    $currentModule   = 'dashboard';
    $pageActions     = array(
        array(
            'label' => 'Volver al dashboard',
            'href'  => 'inicio.php',
            'icon'  => 'fa-solid fa-arrow-left',
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
                        <h2 class="surface-card__title">
                            <?php echo $step === 'newpass' ? 'Nueva contrasena' : 'Verificar identidad'; ?>
                        </h2>
                        <p class="surface-card__subtitle">
                            <?php echo $step === 'newpass'
                                ? 'Elige una contrasena segura de al menos 8 caracteres.'
                                : 'Ingresa tu celular y correo electronico para continuar.'; ?>
                        </p>
                    </div>
                </div>

                <?php if ($step === 'newpass') : ?>
                    <?php render_newpass_form($mensaje, $tipo, $captchaData); ?>
                <?php else : ?>
                    <?php render_identity_form($mensaje, $tipo, $captchaData); ?>
                <?php endif; ?>
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
        <title>Recuperar contrasena | <?php echo e(app_name()); ?></title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
        <link rel="stylesheet" href="<?php echo e(asset_url('assets/css/saas-admin.css')); ?>">
    </head>

    <body class="admin-body theme-dark">
        <main class="auth-screen">
            <section class="auth-card crm-animate-in">
                <div class="auth-hero">
                    <div>
                        <span class="auth-kicker"><i class="bi bi-shield-lock-fill"></i> Seguridad</span>
                        <h1><?php echo $step === 'newpass' ? 'Nueva contrasena' : 'Recuperar contrasena'; ?></h1>
                        <p>
                            <?php echo $step === 'newpass'
                                ? 'Elige una clave segura para tu cuenta.'
                                : 'Ingresa tu celular y correo electronico para verificar tu identidad.'; ?>
                        </p>
                    </div>
                </div>

                <div class="auth-form-panel">
                    <?php if ($step === 'newpass') : ?>
                        <?php render_newpass_form($mensaje, $tipo, $captchaData); ?>
                    <?php else : ?>
                        <?php render_identity_form($mensaje, $tipo, $captchaData); ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?php echo e(asset_url('assets/js/admin-ui.js')); ?>"></script>
        <?php render_toggle_password_script(); ?>
    </body>

    </html>
<?php
}
