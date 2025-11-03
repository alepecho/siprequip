<?php 
session_start(); 
require_once __DIR__ . '/../../includes/functions.php'; 
require_once __DIR__ . '/../../includes/db.php'; 

// Validar si el usuario es admin 
/* if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] != 3) {
     header("Location: ../login.php");
     exit;
} 
*/

// ==============================
// FUNCIONES
// ==============================

// Función para marcar como entregado y actualizar inventario
function marcarEntregado($id_registro) {
    global $conn;
    
    // --- INICIO DE MODIFICACIÓN: Obtener id_inventario Y cantidad ---
    // Obtener el id_inventario y la cantidad del registro para la devolución
    $stmt = $conn->prepare("SELECT id_inventario, cantidad FROM registro_detalle WHERE id_registro = ?");
    $stmt->bind_param("i", $id_registro);
    $stmt->execute();
    $stmt->bind_result($id_inventario, $cantidad_devuelta); // AGREGADO: $cantidad_devuelta
    $stmt->fetch();
    $stmt->close();
    
    // Verificación de existencia
    if (!$id_inventario) {
        return false;
    }
    // --- FIN DE MODIFICACIÓN ---

    // Cambiar estado de la solicitud a "Entregado" (id_estado = 1)
    $stmt = $conn->prepare("UPDATE registro_detalle SET id_estado = 1 WHERE id_registro = ?");
    $stmt->bind_param("i", $id_registro);
    $stmt->execute();
    $stmt->close();
    
    // --- INICIO DE MODIFICACIÓN: Actualizar inventario (sumar cantidad y estado) ---
    // Aumentar la cantidad disponible en inventario y cambiar estado a "Disponible" (id_estado = 1)
    $stmt = $conn->prepare("UPDATE inventario SET cantidad = cantidad + ?, id_estado = 1 WHERE id_inventario = ?"); 
    $stmt->bind_param("ii", $cantidad_devuelta, $id_inventario); // Usamos la cantidad obtenida
    $stmt->execute();
    $stmt->close();
    // --- FIN DE MODIFICACIÓN ---
    
    return true;
}

// Función para obtener solicitudes completas 
/*
// **NOTA IMPORTANTE:** Esta función está comentada en tu código original. 
// Asegúrate de DESCOMENTARLA para que la variable $solicitudes tenga datos.
function obtenerSolicitudes() {
    global $conn;
    $sql = "
          SELECT
              r.id_registro,
              e.id_empleados,
              CONCAT(e.nombre, ' ', e.apellido1, ' ', e.apellido2) AS usuario,
              s.id_servicio,
              i.articulo AS equipo,
              i.cantidad,
              es.nombre AS estado,
              r.fecha_de_salida AS fecha_solicitud,
              r.fecha_de_retorno AS fecha_entrega,
              r.id_estado
          FROM registro_detalle r
          JOIN empleados e ON r.id_empleados = e.id_empleados
          JOIN servicio s ON r.id_servicio = s.id_servicio
          JOIN inventario i ON r.id_inventario = i.id_inventario
          JOIN estado es ON r.id_estado = es.id_estado
          ORDER BY r.fecha_de_salida DESC
    ";
    $result = $conn->query($sql);
    $solicitudes = [];
    while ($row = $result->fetch_assoc()) {
        $solicitudes[] = $row;
    }
    return $solicitudes;
}
*/

// **RECUERDA DESCOMENTAR LA FUNCIÓN obtenerSolicitudes() O PROPORCIONAR UNA IMPLEMENTACIÓN VÁLIDA**
// Si la función está comentada, $solicitudes será un array vacío a menos que haya otra implementación.
$solicitudes = obtenerSolicitudes(); // Esto llamará a la función comentada, lo cual podría causar un error si no la descomentas.

// ==============================
// PROCESAR ACCIONES
// ==============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Borrar todo (antes de otras acciones para evitar redirecciones prematuras)
    if (isset($_POST['borrar_todo'])) {
        // Eliminar todos los registros de la tabla registro_detalle
        $stmt = $conn->prepare("DELETE FROM registro_detalle");
        if ($stmt) {
            $stmt->execute();
            $stmt->close();
        } else {
            // En caso de que prepare falle, intentar query directa
            $conn->query("DELETE FROM registro_detalle");
        }
        header("Location: admin_dashboard.php");
        exit;
    }
    // Marcar como entregado
    if (isset($_POST['entregar'])) {
        $id = intval($_POST['solicitud_id']);
        marcarEntregado($id);
        header("Location: admin_dashboard.php"); // Refresca la página
        exit;
    }

    // Borrar registro
    if (isset($_POST['borrar'])) {
        $id = intval($_POST['solicitud_id']);
        if ($id > 0) {
            // Usamos una sentencia preparada para seguridad
            $stmt = $conn->prepare("DELETE FROM registro_detalle WHERE id_registro = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
            }
        }
        header("Location: admin_dashboard.php"); // Refresca la página
        exit;
    }
}

// ==============================
// FILTRO POR SERVICIO (GET)
// ==============================
// Obtener la lista de servicios para el select
$servicios = obtenerServicios();
$selectedServicio = isset($_GET['servicio']) ? intval($_GET['servicio']) : 0;
if ($selectedServicio > 0 && !empty($solicitudes)) {
    $solicitudes = array_values(array_filter($solicitudes, function($s) use ($selectedServicio) {
        return intval($s['id_servicio']) === $selectedServicio;
    }));
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

   <style>
     /* ✅ Título blanco, visible sobre fondo azul */
     .titulo-principal {
        color: #ffffff; /* Blanco puro */
        font-weight: 800;
        text-align: center;
        text-transform: uppercase;
        letter-spacing: 1px;
        text-shadow: 0 2px 5px rgba(0, 0, 0, 0.3); /* da contraste sobre fondo azul */
        margin-bottom: 25px;
     }
   </style>
</head>
<body class="dashboard-body">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
     <div class="container-fluid">
         <a class="navbar-brand fw-bold" href="#">Inventario CGI - Admin</a>
         <div class="d-flex align-items-center">
             <span class="navbar-text me-3 text-white">
                 Bienvenido, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Administrador'); ?>
             </span>
             <a href="cambiar_contraseña.php" class="btn btn-warning btn-sm me-2">Cambiar contraseña</a>
             <a href="../logout.php" class="btn btn-light btn-sm">Cerrar sesión</a>
         </div>
     </div>
</nav>

<div class="dashboard-wrapper py-5">
     <div class="container">
         <div class="row justify-content-center">
             <div class="col-md-11">
                 <h3 class="titulo-principal">Registro de Solicitudes</h3>
                  <div class="table-responsive shadow-sm">
                     <div class="d-flex justify-content-between mb-2 align-items-center">
                         <!-- Formulario de filtro por servicio (GET) -->
                         <form method="get" class="d-flex align-items-center" style="gap:8px;">
                             <label for="servicio" class="mb-0">Filtrar por servicio:</label>
                             <select name="servicio" id="servicio" class="form-select form-select-sm" style="width:auto;">
                                 <option value="0">Todos</option>
                                 <?php foreach ($servicios as $sv): ?>
                                     <option value="<?= $sv['id_servicio'] ?>" <?= ($selectedServicio == $sv['id_servicio']) ? 'selected' : '' ?>><?= htmlspecialchars($sv['nombre'] ?? 'Servicio '.$sv['id_servicio']) ?></option>
                                 <?php endforeach; ?>
                             </select>
                             <button type="submit" class="btn btn-sm btn-primary">Filtrar</button>
                         </form>

                         <form method="post" onsubmit="return confirm('¿Seguro que desea eliminar TODOS los registros? Esta acción no se puede deshacer.');">
                             <button type="submit" name="borrar_todo" class="btn btn-sm btn-danger">Borrar todo</button>
                         </form>
                     </div>
                      <table class="table table-bordered table-hover align-middle">
                          <thead class="table-primary text-center">
                              <tr>
                                  <th>#</th>
                                  <th>Usuario</th>
                                  <th>Servicio</th>
                                  <th>Equipo</th>
                                  <th>Cantidad</th>
                                  <th>Estado</th>
                                  <th>Fecha solicitud</th>
                                  <th>Devolución</th>
                                  <th>Acción</th>
                              </tr>
                          </thead>
                          <tbody>
                              <?php if (empty($solicitudes)): ?>
                                  <tr>
                                      <td colspan="9" class="text-center text-muted">No hay solicitudes registradas</td>
                                  </tr>
                              <?php else: ?>
                                  <?php foreach ($solicitudes as $s): ?>
                                      <tr>
                                          <td class="text-center"><?= $s['id_registro'] ?></td>
                                          <td><?= htmlspecialchars($s['usuario']) ?></td>
                                          <td class="text-center">Servicio #<?= htmlspecialchars($s['id_servicio']) ?></td>
                                          <td><?= htmlspecialchars($s['equipo']) ?></td>
                                          <td class="text-center"><?= $s['cantidad'] ?></td>
                                          <td class="text-center">
                                              <span class="badge <?= strtolower($s['estado']) === 'disponible' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                                  <?= htmlspecialchars($s['estado']) ?>
                                              </span>
                                          </td>
                                          <td><?= htmlspecialchars($s['fecha_solicitud']) ?></td>
                                          <td><?= htmlspecialchars($s['fecha_entrega']) ?></td>
                                          <td class="text-center">
                                              <form method="post" style="display:inline; margin-right:6px;">
                                                  <input type="hidden" name="solicitud_id" value="<?= $s['id_registro'] ?>">
                                                  <?php if ($s['id_estado'] != 1): ?>
                                                      <button class="btn btn-sm btn-success" name="entregar">Marcar Entregado</button>
                                                  <?php else: ?>
                                                      <span class="text-success fw-semibold">Devuelto</span>
                                                  <?php endif; ?>
                                              </form>
                                              <!-- Formulario para borrar registro -->
                                              <form method="post" style="display:inline;" onsubmit="return confirm('¿Seguro que desea borrar este registro?');">
                                                  <input type="hidden" name="solicitud_id" value="<?= $s['id_registro'] ?>">
                                                  <button type="submit" name="borrar" class="btn btn-sm btn-danger">Borrar</button>
                                              </form>
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


