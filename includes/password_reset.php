<?php
// Recuperacion de contrasena clasica con CAPTCHA matematico
// Sin dependencias externas: sin SMS, sin correo, sin API de terceros.

// ---------------------------------------------------------------------------
// CAPTCHA matematico (server-side, uso unico, expira en 10 min)
// ---------------------------------------------------------------------------

if (!function_exists('pr_captcha_generate')) {
    function pr_captcha_generate()
    {
        $ops = array('+', '-', 'x');
        $op  = $ops[array_rand($ops)];
        $a   = random_int(2, 15);
        $b   = random_int(1, 12);

        if ($op === '-' && $b > $a) {
            $tmp = $a;
            $a = $b;
            $b = $tmp;
        }

        switch ($op) {
            case '+':
                $answer = $a + $b;
                break;
            case '-':
                $answer = $a - $b;
                break;
            default:
                $answer = $a * $b;
                $op = 'x';
                break;
        }

        $token = bin2hex(random_bytes(12));
        $_SESSION['_captcha'] = array(
            'answer'  => $answer,
            'token'   => $token,
            'expires' => time() + 600,
        );

        return array('question' => "$a $op $b", 'token' => $token);
    }
}

if (!function_exists('pr_captcha_verify')) {
    /**
     * Valida la respuesta al captcha. El captcha es de un solo uso:
     * se elimina de la sesion independientemente del resultado.
     */
    function pr_captcha_verify($submitted, $token)
    {
        if (empty($_SESSION['_captcha'])) {
            return false;
        }

        $data = $_SESSION['_captcha'];
        unset($_SESSION['_captcha']); // Consumir siempre (uso unico)

        if ((int) $data['expires'] < time()) {
            return false;
        }

        if (!hash_equals((string) $data['token'], (string) $token)) {
            return false;
        }

        return ((int) $submitted) === (int) $data['answer'];
    }
}

// ---------------------------------------------------------------------------
// Rate limiting por sesion (sin base de datos)
// ---------------------------------------------------------------------------

if (!function_exists('pr_rate_limit_check')) {
    /**
     * Retorna array ['allowed' => bool, 'wait_seconds' => int].
     * 5 intentos fallidos â†’ bloqueo de 5 minutos.
     */
    function pr_rate_limit_check($key = 'default')
    {
        $maxAttempts = 5;
        $lockSeconds = 300;
        $sessionKey  = '_pr_rl_' . preg_replace('/[^a-z0-9_]/', '', $key);

        if (empty($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = array('attempts' => 0, 'locked_until' => 0);
        }

        $data = &$_SESSION[$sessionKey];

        if ($data['locked_until'] > time()) {
            return array('allowed' => false, 'wait_seconds' => $data['locked_until'] - time());
        }

        if ($data['locked_until'] > 0 && $data['locked_until'] <= time()) {
            $data['attempts']     = 0;
            $data['locked_until'] = 0;
        }

        if ($data['attempts'] >= $maxAttempts) {
            $data['locked_until'] = time() + $lockSeconds;
            $data['attempts']     = 0;
            return array('allowed' => false, 'wait_seconds' => $lockSeconds);
        }

        $data['attempts']++;
        return array('allowed' => true, 'wait_seconds' => 0);
    }
}

if (!function_exists('pr_rate_limit_reset')) {
    function pr_rate_limit_reset($key = 'default')
    {
        $sessionKey = '_pr_rl_' . preg_replace('/[^a-z0-9_]/', '', $key);
        unset($_SESSION[$sessionKey]);
    }
}

// ---------------------------------------------------------------------------
// Logica de usuarios y contrasena
// ---------------------------------------------------------------------------

if (!function_exists('pr_normalize_phone')) {
    function pr_normalize_phone($value)
    {
        return preg_replace('/\D+/', '', (string) $value);
    }
}

if (!function_exists('pr_find_user_by_identity')) {
    /**
     * Busca usuario por celular (`celular`) y correo electronico (`email`).
     * Usa hash_equals para evitar ataques de tiempo.
     */
    function pr_find_user_by_identity(PDO $pdo, $celular, $email)
    {
        $normalizedPhone = pr_normalize_phone($celular);
        $stmt = $pdo->prepare(
            'SELECT id, celular, email, nombre, apellido FROM usuario WHERE celular = :celular LIMIT 1'
        );
        $stmt->execute(array('celular' => $normalizedPhone));
        $row = $stmt->fetch();

        if (!$row) {
            // Consumir tiempo igual que si existiera el usuario
            hash_equals('00000000', $normalizedPhone);
            hash_equals('correo@ejemplo.com', strtolower(trim((string) $email)));
            return null;
        }

        $storedPhone = pr_normalize_phone(isset($row['celular']) ? $row['celular'] : '');
        $storedEmail = strtolower(trim((string) $row['email']));
        $submittedEmail = strtolower(trim((string) $email));

        if (!hash_equals($storedPhone, $normalizedPhone)) {
            return null;
        }

        if (!hash_equals($storedEmail, $submittedEmail)) {
            return null;
        }

        return $row;
    }
}

if (!function_exists('pr_update_password')) {
    function pr_update_password(PDO $pdo, $userId, $newPassword)
    {
        $hash = app_hash_password((string) $newPassword);
        $stmt = $pdo->prepare('UPDATE usuario SET contrasena = :hash WHERE id = :id');
        return $stmt->execute(array('hash' => $hash, 'id' => (int) $userId));
    }
}
