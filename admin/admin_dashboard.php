<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

// --- Validar si el usuario es admin ---
/*if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}*/

// Obtener solicitudes y procesar entrega
$solicitudes = obtenerSolicitudes();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entregar'])) {
    $id = intval($_POST['solicitud_id']);
    marcarEntregado($id);
    header("Location: admin_dashboard.php");
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Panel Administrador - Inventario</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="estilo2.css">
</head>
<body class="dashboard-body">
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold" href="#">Inventario CGI - Admin</a>
      <div class="d-flex">
        <span class="navbar-text me-3 text-white">Bienvenido</span>
        <a href="logout.php" class="btn btn-light btn-sm">Cerrar sesión</a>
      </div>
    </div>
  </nav>

  <div class="dashboard-wrapper">
    <div class="container">
      <div class="row justify-content-center">
      <div class="col-md-10">
          <h3 class="card-title text-primary fw-bold mb-4 text-center">Registro de Solicitudes</h3>

          <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
              <thead class="table-primary text-center">
                <tr>
                  <th>#</th>
                  <th>Usuario</th>
                  <th>Departamento</th>
                  <th>Equipo</th>
                  <th>Cantidad</th>
                  <th>Estado</th>
                  <th>Comentario</th>
                  <th>Fecha solicitud</th>
                  <th>Entrega</th>
                  <th>Acción</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($solicitudes)): ?>
                  <tr>
                    <td colspan="10" class="text-center text-muted">No hay solicitudes registradas</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($solicitudes as $s): ?>
                    <tr>
                      <td><?= $s['id'] ?></td>
                      <td><?= htmlspecialchars($s['nombre_usuario']) ?></td>
                      <td><?= htmlspecialchars($s['departamento']) ?></td>
                      <td><?= htmlspecialchars($s['equipo']) ?></td>
                      <td><?= $s['cantidad'] ?></td>
                      <td>
                        <span class="badge <?= $s['estado'] === 'Disponible' ? 'bg-success' : 'bg-danger' ?>">
                          <?= $s['estado'] ?>
                        </span>
                      </td>
                      <td><?= htmlspecialchars($s['comentario']) ?></td>
                      <td><?= $s['fecha_solicitud'] ?></td>
                      <td><?= $s['fecha_entrega'] ? $s['fecha_entrega'] : '<span class="text-muted">Pendiente</span>' ?></td>
                      <td class="text-center">
                        <?php if (!$s['fecha_entrega']): ?>
                          <form method="post" style="display:inline;">
                            <input type="hidden" name="solicitud_id" value="<?= $s['id'] ?>">
                            <button class="btn btn-sm btn-success" name="entregar">Marcar Entregado</button>
                          </form>
                        <?php else: ?>
                          <span class="text-success fw-semibold">Entregado</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>