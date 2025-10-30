<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';
    $user_id = $_SESSION['user_id'];

    if (!$current_pass || !$new_pass || !$confirm_pass) {
        $errors[] = "Todos los campos son obligatorios.";
    } elseif ($new_pass !== $confirm_pass) {
        $errors[] = "La nueva contraseña y la confirmación no coinciden.";
    } else {
        $pass_errors = [];
        if (strlen($new_pass) < 8) $pass_errors[] = "Al menos 8 caracteres";
        if (!preg_match('/[A-Z]/', $new_pass)) $pass_errors[] = "Al menos una mayúscula";
        if (!preg_match('/[a-z]/', $new_pass)) $pass_errors[] = "Al menos una minúscula";
        if (!preg_match('/[0-9]/', $new_pass)) $pass_errors[] = "Al menos un número";
        if (!preg_match('/[\W]/', $new_pass)) $pass_errors[] = "Al menos un carácter especial";

        if ($pass_errors) $errors[] = "La contraseña debe tener: " . implode(", ", $pass_errors);

        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT contraseña FROM empleados WHERE id_empleados = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->bind_result($hashed_pass);
            $stmt->fetch();
            $stmt->close();

            if (!password_verify($current_pass, $hashed_pass)) {
                $errors[] = "La contraseña actual es incorrecta.";
            } else {
                $new_hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE empleados SET contraseña = ? WHERE id_empleados = ?");
                $stmt->bind_param("si", $new_hashed, $user_id);
                $stmt->execute();
                $stmt->close();

                $success = true;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cambiar Contraseña</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="estilo2.css">
<style>
.pass-checklist div { font-size: 0.9rem; margin-top: 3px; }
.pass-checklist div.valid { color: green; }
.pass-checklist div.invalid { color: red; }
</style>
</head>
<body class="bg-light">

<div class="container py-5">
    <h3 class="text-center mb-4">Cambiar Contraseña</h3>

    <?php if ($success): ?>
        <div class="alert alert-success">✅ Contraseña actualizada correctamente.</div>
    <?php elseif ($errors): ?>
        <div class="alert alert-danger">
            <?php foreach($errors as $e) echo "<div>$e</div>"; ?>
        </div>
    <?php endif; ?>

    <form method="post" class="mx-auto" style="max-width: 450px;">
        <div class="mb-3">
            <label>Contraseña actual</label>
            <input type="password" id="current_password" name="current_password" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Nueva contraseña</label>
            <input type="password" id="new_password" name="new_password" class="form-control" required>
            <div class="pass-checklist mt-2">
                <div id="length" class="invalid">Mínimo 8 caracteres</div>
                <div id="uppercase" class="invalid">Al menos una mayúscula</div>
                <div id="lowercase" class="invalid">Al menos una minúscula</div>
                <div id="number" class="invalid">Al menos un número</div>
                <div id="special" class="invalid">Al menos un carácter especial</div>
            </div>
        </div>

        <div class="mb-3">
            <label>Confirmar nueva contraseña</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="show_passwords">
            <label class="form-check-label" for="show_passwords">
                Mostrar contraseñas
            </label>
        </div>

        <button id="submit_btn" class="btn btn-primary w-100" disabled>Actualizar contraseña</button>
        <a href="admin_dashboard.php" class="btn btn-secondary w-100 mt-2">Volver al panel</a>
    </form>
</div>

<script>
const passwordInput = document.getElementById('new_password');
const submitBtn = document.getElementById('submit_btn');

const checks = {
    length: document.getElementById('length'),
    uppercase: document.getElementById('uppercase'),
    lowercase: document.getElementById('lowercase'),
    number: document.getElementById('number'),
    special: document.getElementById('special')
};

function validatePassword() {
    const value = passwordInput.value;
    checks.length.className = value.length >= 8 ? 'valid' : 'invalid';
    checks.uppercase.className = /[A-Z]/.test(value) ? 'valid' : 'invalid';
    checks.lowercase.className = /[a-z]/.test(value) ? 'valid' : 'invalid';
    checks.number.className = /[0-9]/.test(value) ? 'valid' : 'invalid';
    checks.special.className = /[\W]/.test(value) ? 'valid' : 'invalid';

    const allValid = Object.values(checks).every(el => el.className === 'valid');
    submitBtn.disabled = !allValid;
}

passwordInput.addEventListener('input', validatePassword);

// Mostrar / ocultar contraseñas
document.getElementById('show_passwords').addEventListener('change', function() {
    const type = this.checked ? 'text' : 'password';
    document.getElementById('current_password').type = type;
    document.getElementById('new_password').type = type;
    document.getElementById('confirm_password').type = type;
});
</script>

</body>
</html>

