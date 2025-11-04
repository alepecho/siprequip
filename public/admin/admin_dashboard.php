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
    
    // Obtener información para el correo
    $stmt = $conn->prepare("
        SELECT 
            CONCAT(e.nombre, ' ', e.apellido1, ' ', e.apellido2) as empleado,
            i.articulo,
            rd.fecha_de_salida,
            rd.fecha_de_retorno,
            rd.id_registro
        FROM registro_detalle rd
        INNER JOIN empleados e ON rd.id_empleados = e.id_empleados
        INNER JOIN inventario i ON rd.id_inventario = i.id_inventario
        WHERE rd.id_registro = ?
    ");
    $stmt->bind_param("i", $id_registro);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $info = $resultado->fetch_assoc();
    $stmt->close();
    
    // Enviar correo de notificación
    if ($info) {
        enviarCorreoEntrega($info);
    }
    
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
              i.placa,
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
    // Marcar como entregado
    if (isset($_POST['entregar'])) {
        $id = intval($_POST['solicitud_id']);
        marcarEntregado($id);
        // Redirigir preservando filtro y página si vienen por GET
        $redir = 'admin_dashboard.php';
        if (isset($_GET['servicio']) && $_GET['servicio'] !== '') {
            $redir .= '?servicio=' . urlencode($_GET['servicio']);
            if (isset($_GET['page'])) {
                $redir .= '&page=' . intval($_GET['page']);
            }
        }
        header("Location: " . $redir); // Refresca la página
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

// ==============================
// PAGINACIÓN
// ==============================
$perPage = 10; // registros por página
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$totalItems = count($solicitudes);
$totalPages = ($totalItems === 0) ? 1 : ceil($totalItems / $perPage);
$offset = ($currentPage - 1) * $perPage;
$pageSolicitudes = array_slice($solicitudes, $offset, $perPage);

// Vista seleccionada: 'registro' o 'log'
$view = isset($_GET['view']) ? $_GET['view'] : 'registro';

// Si la vista es log, obtener los datos para el log
$pageLogs = [];
$totalLogPages = 1;
$currentLogPage = isset($_GET['log_page']) ? max(1, intval($_GET['log_page'])) : 1;
if ($view === 'log') {
    // Verificar si la tabla registro_mail_log existe
    $table_check = $conn->query("SHOW TABLES LIKE 'registro_mail_log'");
    $table_exists = $table_check && $table_check->num_rows > 0;
    
    if ($table_exists) {
        $sql = "SELECT 
            r.id_registro,
            e.correo_caja,
            r.id_estado,
            COALESCE(ml.fecha_envio, r.fecha_de_salida) as fecha_envio,
            COALESCE(IF(ml.estado_envio = 'SI', 'Sí', 'No'), 'No') as registrado
        FROM registro_detalle r 
        JOIN empleados e ON r.id_empleados = e.id_empleados
        LEFT JOIN registro_mail_log ml ON r.id_registro = ml.id_registro
        GROUP BY r.id_registro
        ORDER BY r.fecha_de_salida DESC";
    } else {
        // Si la tabla no existe, mostrar valores por defecto
        $sql = "SELECT 
            r.id_registro,
            e.correo_caja,
            r.id_estado,
            r.fecha_de_salida as fecha_envio,
            'No' as registrado
        FROM registro_detalle r 
        JOIN empleados e ON r.id_empleados = e.id_empleados
        ORDER BY r.fecha_de_salida DESC";
    }
    
    $res = $conn->query($sql);
    $logs = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) $logs[] = $row;
    }
    $totalLogs = count($logs);
    $totalLogPages = ($totalLogs === 0) ? 1 : ceil($totalLogs / $perPage);
    $logOffset = ($currentLogPage - 1) * $perPage;
    $pageLogs = array_slice($logs, $logOffset, $perPage);
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
         <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
             <span class="navbar-toggler-icon"></span>
         </button>
         <div class="collapse navbar-collapse" id="navbarContent">
             <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                 <li class="nav-item">
                     <a class="nav-link <?= ($view === 'registro') ? 'active' : '' ?>" href="?view=registro">Registro de Solicitudes</a>
                 </li>
                 <li class="nav-item">
                     <a class="nav-link <?= ($view === 'log') ? 'active' : '' ?>" href="?view=log">Registro de Correos</a>
                 </li>
             </ul>
             <div class="d-flex align-items-center">
                 <span class="navbar-text me-3 text-white">
                     Bienvenido, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Administrador'); ?>
                 </span>
                 <a href="cambiar_contraseña.php" class="btn btn-warning btn-sm me-2">Cambiar contraseña</a>
                 <a href="../logout.php" class="btn btn-light btn-sm">Cerrar sesión</a>
             </div>
         </div>
     </div>
</nav>

<div class="dashboard-wrapper py-5">
     <div class="container">
         <div class="row justify-content-center">
             <div class="col-md-11">
                 <h3 class="titulo-principal">
                     <?= ($view === 'registro') ? 'Registro de Solicitudes' : 'Log de Correos' ?>
                 </h3>
                  <?php if ($view === 'registro'): ?>
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

                        <!-- 'Borrar todo' eliminado según petición -->
                     </div>
                      <table class="table table-bordered table-hover align-middle">
                          <thead class="table-primary text-center">
                              <tr>
                                  <th>#</th>
                                  <th>Usuario</th>
                                  <th>Servicio</th>
                                  <th>Equipo</th>
                                  <th>Placa</th>
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
                                  <?php foreach ($pageSolicitudes as $s): ?>
                                      <tr>
                                          <td class="text-center"><?= $s['id_registro'] ?></td>
                                          <td><?= htmlspecialchars($s['usuario']) ?></td>
                                          <td class="text-center">Servicio #<?= htmlspecialchars($s['id_servicio']) ?></td>
                                          <td><?= htmlspecialchars($s['equipo']) ?></td>
                                          <td class="text-center"><?= isset($s['placa']) && $s['placa'] ? $s['placa'] : 'N/A' ?></td>
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
                                              <!-- Botón de borrar por fila eliminado -->
                                          </td>
                                      </tr>
                                  <?php endforeach; ?>
                              <?php endif; ?>
                          </tbody>
                      </table>
                      <!-- Paginación -->
                      <?php if ($totalPages > 1): ?>
                          <nav aria-label="Paginación solicitudes">
                              <ul class="pagination justify-content-center mt-3">
                                  <?php
                                      $baseParams = [];
                                      if (isset($_GET['servicio']) && $_GET['servicio'] !== '') {
                                          $baseParams['servicio'] = $_GET['servicio'];
                                      }
                                      // Previous
                                      $prev = max(1, $currentPage - 1);
                                      $baseParams['page'] = $prev;
                                      $prevUrl = 'admin_dashboard.php?' . http_build_query($baseParams);
                                  ?>
                                  <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                                      <a class="page-link" href="<?= $prevUrl ?>" aria-label="Anterior">&laquo;</a>
                                  </li>
                                  <?php for ($p = 1; $p <= $totalPages; $p++):
                                      $baseParams['page'] = $p;
                                      $url = 'admin_dashboard.php?' . http_build_query($baseParams);
                                  ?>
                                      <li class="page-item <?= ($p == $currentPage) ? 'active' : '' ?>"><a class="page-link" href="<?= $url ?>"><?= $p ?></a></li>
                                  <?php endfor; ?>
                                  <?php
                                      // Next
                                      $next = min($totalPages, $currentPage + 1);
                                      $baseParams['page'] = $next;
                                      $nextUrl = 'admin_dashboard.php?' . http_build_query($baseParams);
                                  ?>
                                  <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                                      <a class="page-link" href="<?= $nextUrl ?>" aria-label="Siguiente">&raquo;</a>
                                  </li>
                              </ul>
                          </nav>
                      <?php endif; ?>
                  </div>
                  <?php else: ?>
                  <div class="table-responsive shadow-sm">
                      <table class="table table-bordered table-hover align-middle">
                          <thead class="table-primary text-center">
                              <tr>
                                  <th># Préstamo</th>
                                  <th>Correo</th>
                                  <th>Registrado</th>
                                  <th>Recibido</th>
                                  <th>Fecha de envío</th>
                              </tr>
                          </thead>
                          <tbody>
                              <?php if (empty($pageLogs)): ?>
                                  <tr><td colspan="5" class="text-center text-muted">No hay logs</td></tr>
                              <?php else: ?>
                                  <?php foreach ($pageLogs as $l): ?>
                                      <tr>
                                          <td class="text-center"><?= htmlspecialchars($l['id_registro']) ?></td>
                                          <td><?= htmlspecialchars($l['correo_caja']) ?></td>
                                          <td class="text-center">
                                              <span class="badge <?= ($l['registrado'] === 'Sí') ? 'bg-success' : 'bg-secondary' ?>">
                                                  <?= htmlspecialchars($l['registrado']) ?>
                                              </span>
                                          </td>
                                          <td class="text-center"><?= ($l['id_estado'] == 1) ? 'Sí' : 'No' ?></td>
                                          <td><?= htmlspecialchars($l['fecha_envio']) ?></td>
                                      </tr>
                                  <?php endforeach; ?>
                              <?php endif; ?>
                          </tbody>
                      </table>
                      <!-- Paginación Log -->
                      <?php if ($totalLogPages > 1): ?>
                        <nav aria-label="Paginación logs">
                          <ul class="pagination justify-content-center mt-3">
                            <?php
                              $logBase = ['view' => 'log'];
                              $prevLog = max(1, $currentLogPage - 1);
                              $logBase['log_page'] = $prevLog;
                              $prevLogUrl = 'admin_dashboard.php?' . http_build_query($logBase);
                            ?>
                            <li class="page-item <?= ($currentLogPage <= 1) ? 'disabled' : '' ?>">
                              <a class="page-link" href="<?= $prevLogUrl ?>">&laquo;</a>
                            </li>
                            <?php for ($pp = 1; $pp <= $totalLogPages; $pp++):
                                $logBase['log_page'] = $pp;
                                $logUrl = 'admin_dashboard.php?' . http_build_query($logBase);
                            ?>
                                <li class="page-item <?= ($pp == $currentLogPage) ? 'active' : '' ?>"><a class="page-link" href="<?= $logUrl ?>"><?= $pp ?></a></li>
                            <?php endfor; ?>
                            <?php
                              $nextLog = min($totalLogPages, $currentLogPage + 1);
                              $logBase['log_page'] = $nextLog;
                              $nextLogUrl = 'admin_dashboard.php?' . http_build_query($logBase);
                            ?>
                            <li class="page-item <?= ($currentLogPage >= $totalLogPages) ? 'disabled' : '' ?>">
                              <a class="page-link" href="<?= $nextLogUrl ?>">&raquo;</a>
                            </li>
                          </ul>
                        </nav>
                      <?php endif; ?>
                  </div>
                  <?php endif; ?>
              </div>
         </div>
     </div>
</div>
</body> 
</html>


