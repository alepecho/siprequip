<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$success = false;
$errors = [];

/*if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departamento = trim($_POST['departamento']);
    $equipo = trim($_POST['equipo']);
    $cantidad = intval($_POST['cantidad']);
    $estado = isset($_POST['estado']) ? $_POST['estado'] : 'Agotado';
    $comentario = trim($_POST['comentario']);
    $usuario_id = $_SESSION['user_id'];

    if (!$departamento || !$equipo || $cantidad <= 0) {
        $errors[] = "Por favor completa todos los campos correctamente.";
    } else {
        if (crearSolicitud($usuario_id, $departamento, $equipo, $cantidad, $estado, $comentario)) {
            enviarNotificacionAdmin($departamento, $equipo, $cantidad, $estado, $comentario, $_SESSION['user_name']);
            $success = true;
        } else {
            $errors[] = "Error al registrar la solicitud.";
        }
    }
}*/
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Panel Principal - Inventario</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/styles.css">
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold" href="#">Inventario GSI</a>
      <div class="d-flex">
        <span class="navbar-text me-3 text-white">
          Bienvenido, <?= htmlspecialchars($_SESSION['user_name']); ?>
        </span>
        <a href="logout.php" class="btn btn-light btn-sm">Cerrar sesión</a>
      </div>
    </div>
  </nav>

  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="card shadow-lg border-0 rounded-4">
          <div class="card-body p-4">
            <h3 class="card-title text-center text-primary fw-bold mb-4">Solicitud de Equipo</h3>

            <?php if ($success): ?>
              <div class="alert alert-success text-center">Solicitud enviada correctamente.</div>
            <?php elseif ($errors): ?>
              <div class="alert alert-danger">
                <?php foreach($errors as $e) echo "<div>$e</div>"; ?>
              </div>
            <?php endif; ?>

            <form method="post">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold">Departamento / Servicio</label>
                  <input class="form-control" name="departamento" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold">Nombre del equipo</label>
                  <input class="form-control" name="equipo" required>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold">Cantidad</label>
                  <input class="form-control" name="cantidad" type="number" min="1" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold">Estado</label><br>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="estado" value="Disponible" id="disponible" checked>
                    <label class="form-check-label" for="disponible">Disponible</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="estado" value="Agotado" id="agotado">
                    <label class="form-check-label" for="agotado">Agotado</label>
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold">Comentario (uso o propósito)</label>
                <textarea class="form-control" name="comentario" rows="3" placeholder="Ej: Para capacitación del personal" required></textarea>
              </div>

              <button class="btn btn-primary w-100 rounded-3">Enviar solicitud</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>