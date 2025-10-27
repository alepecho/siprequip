<?php
// includes/config.php

// Database settings
define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // cambia según tu XAMPP
define('DB_PASS', '');            // cambia si tienes password
define('DB_NAME', '');

// SMTP / PHPMailer settings (configura con tus datos SMTP)
define('SMTP_HOST', 'smtp.tudominio.com');
define('SMTP_USER', 'tu@correo.com');
define('SMTP_PASS', 'tu_password_smtp');
define('SMTP_PORT', 587); // 587 o 465

// Administradores: si quieres 3 correos predefinidos (pueden estar también en tabla admins)
$ADMIN_EMAILS = [
    'admin1@ejemplo.com',
    'admin2@ejemplo.com',
    'admin3@ejemplo.com'
];