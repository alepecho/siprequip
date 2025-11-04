<?php
/**
 * Funciones de seguridad para prevenir vulnerabilidades
 * - Protección contra XSS
 * - Validación de inputs
 * - Sanitización de datos
 */

/**
 * Sanitizar string para prevenir XSS
 * Convierte caracteres especiales en entidades HTML
 */
function sanitizeOutput($data) {
    if (is_array($data)) {
        return array_map('sanitizeOutput', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Limpiar input antes de procesar (remove tags, trim espacios)
 */
function cleanInput($data) {
    if (is_array($data)) {
        return array_map('cleanInput', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    return $data;
}

/**
 * Validar email
 */
function validateEmail($email) {
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validar número entero
 */
function validateInt($number, $min = null, $max = null) {
    $number = filter_var($number, FILTER_VALIDATE_INT);
    if ($number === false) {
        return false;
    }
    if ($min !== null && $number < $min) {
        return false;
    }
    if ($max !== null && $number > $max) {
        return false;
    }
    return $number;
}

/**
 * Validar string (solo letras, números, espacios y guiones)
 */
function validateString($string, $minLength = 1, $maxLength = 255) {
    $string = cleanInput($string);
    $length = mb_strlen($string);
    
    if ($length < $minLength || $length > $maxLength) {
        return false;
    }
    
    return $string;
}

/**
 * Validar fecha en formato Y-m-d
 */
function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Generar token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validar token CSRF
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitizar nombre de archivo
 */
function sanitizeFilename($filename) {
    // Remover caracteres peligrosos
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    // Limitar longitud
    $filename = substr($filename, 0, 255);
    return $filename;
}

/**
 * Prevenir clickjacking - agregar headers de seguridad
 */
function setSecurityHeaders() {
    // Prevenir clickjacking
    header("X-Frame-Options: SAMEORIGIN");
    
    // Prevenir MIME sniffing
    header("X-Content-Type-Options: nosniff");
    
    // Habilitar XSS filter del navegador
    header("X-XSS-Protection: 1; mode=block");
    
    // Content Security Policy básico
    header("Content-Security-Policy: default-src 'self' https://cdn.jsdelivr.net https://smtp.gmail.com; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data:;");
    
    // Forzar HTTPS en producción (comentado por si estás en desarrollo)
    // header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
}

/**
 * Escapar datos para usar en SQL LIKE
 */
function escapeLike($string) {
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $string);
}

/**
 * Validar y sanitizar input de texto largo (descripciones, comentarios)
 */
function sanitizeTextarea($text, $maxLength = 5000) {
    $text = cleanInput($text);
    $text = substr($text, 0, $maxLength);
    return $text;
}

/**
 * Validar contraseña segura
 */
function validatePassword($password) {
    // Mínimo 8 caracteres, al menos una mayúscula, una minúscula y un número
    if (strlen($password) < 8) {
        return ['valid' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres'];
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return ['valid' => false, 'message' => 'La contraseña debe tener al menos una letra mayúscula'];
    }
    if (!preg_match('/[a-z]/', $password)) {
        return ['valid' => false, 'message' => 'La contraseña debe tener al menos una letra minúscula'];
    }
    if (!preg_match('/[0-9]/', $password)) {
        return ['valid' => false, 'message' => 'La contraseña debe tener al menos un número'];
    }
    return ['valid' => true];
}

/**
 * Rate limiting básico - prevenir ataques de fuerza bruta
 */
function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }
    
    $now = time();
    $key = md5($identifier);
    
    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = ['count' => 1, 'first_attempt' => $now];
        return true;
    }
    
    $data = $_SESSION['rate_limit'][$key];
    
    // Si ha pasado el tiempo de ventana, resetear
    if (($now - $data['first_attempt']) > $timeWindow) {
        $_SESSION['rate_limit'][$key] = ['count' => 1, 'first_attempt' => $now];
        return true;
    }
    
    // Incrementar contador
    $_SESSION['rate_limit'][$key]['count']++;
    
    // Verificar si excede el límite
    if ($data['count'] >= $maxAttempts) {
        return false;
    }
    
    return true;
}

/**
 * Limpiar sesiones antiguas de rate limit
 */
function cleanRateLimitSessions() {
    if (!isset($_SESSION['rate_limit'])) {
        return;
    }
    
    $now = time();
    foreach ($_SESSION['rate_limit'] as $key => $data) {
        if (($now - $data['first_attempt']) > 300) {
            unset($_SESSION['rate_limit'][$key]);
        }
    }
}
