<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';

// --- PHPMailer ---
require_once __DIR__ . '/../includes/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../includes/PHPMailer/SMTP.php';
require_once __DIR__ . '/../includes/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Establecer headers de seguridad
setSecurityHeaders();

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Solo empleados (no admin)
if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 3) {
    header("Location: admin_dashboard.php");
    exit;
}

$success = false;
$errors = [];
$mailError = "";

// ==============================
// FUNCIONES
// ==============================

function obtenerEquipos() {
    global $conn;
    $sql = "SELECT inventario.id_inventario, inventario.articulo, inventario.placa, estado.nombre AS nombre_estado
            FROM inventario
            JOIN estado ON inventario.id_estado = estado.id_estado
            ORDER BY inventario.articulo ASC, inventario.placa ASC";
    $result = $conn->query($sql);
    $equipos = [];
    while ($row = $result->fetch_assoc()) {
        $equipos[] = $row;
    }
    return $equipos;
}

function equipoDisponible($id_inventario, $fecha_salida, $fecha_retorno) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM registro_detalle
        WHERE id_inventario = ?
          AND id_estado = 2
          AND (
                (fecha_de_salida <= ? AND fecha_de_retorno >= ?)
                OR
                (fecha_de_salida <= ? AND fecha_de_retorno >= ?)
                OR
                (fecha_de_salida >= ? AND fecha_de_retorno <= ?)
              )
    ");
    $stmt->bind_param(
        "issssss",
        $id_inventario,
        $fecha_salida, $fecha_salida,
        $fecha_retorno, $fecha_retorno,
        $fecha_salida, $fecha_retorno
    );
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count == 0;
}

function enviarNotificacionAdmin($departamento, $equipo, $fecha_salida, $fecha_retorno, $usuario_nombre, &$mailError) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'brandonsanchezpacheco@gmail.com'; // tu Gmail
        $mail->Password = 'arnk lcsj gqyv joiu';             // contraseña de aplicación (sin espacios)
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom('aramiras@ccss.sa.cr', 'Sistema Inventario');
        $mail->addAddress('bsanchez25031@gmail.com');
        $mail->addAddress('');
        $mail->addAddress('');
        $mail->isHTML(true);
        $mail->Subject = 'Nueva solicitud de equipo';
        $mail->Body = "
            <h2>Nueva solicitud de equipo</h2>
            <p><strong>Usuario:</strong> $usuario_nombre </p>
            <p><strong>Departamento/Servicio:</strong> $departamento</p>
            <p><strong>Equipo:</strong> $equipo</p>
            <p><strong>Fecha de Entrega:</strong> $fecha_salida</p>
            <p><strong>Fecha de Devolución:</strong> $fecha_retorno</p>
        ";
        $mail->send();
        return true;
    } catch (Exception $e) {
        $mailError = "Error al enviar correo: " . $mail->ErrorInfo;
        return false;
    }
}

function crearSolicitud($id_usuario, $departamento, $id_inventario, $fecha_salida, $fecha_retorno, $usuario_nombre, &$mailError) {
    global $conn;

    // Datos del equipo
    $stmt = $conn->prepare("SELECT id_estado, articulo FROM inventario WHERE id_inventario = ?");
    $stmt->bind_param("i", $id_inventario);
    $stmt->execute();
    $stmt->bind_result($id_estado, $articulo);
    $stmt->fetch();
    $stmt->close();

    if ($id_estado != 1) return false;
    if (!equipoDisponible($id_inventario, $fecha_salida, $fecha_retorno)) return false;

    // id_servicio del empleado
    $stmt = $conn->prepare("SELECT id_servicio FROM empleados WHERE id_empleados = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->bind_result($id_servicio);
    $stmt->fetch();
    $stmt->close();

    if (!$id_servicio) return false;

    // Insertar solicitud con cantidad = 1
    $cantidad = 1;
    $id_devolucion = null; // NULL para nueva solicitud (sin devolución aún)
    
    $stmt = $conn->prepare("
        INSERT INTO registro_detalle 
        (fecha_de_salida, fecha_de_retorno, id_empleados, id_estado, id_inventario, id_servicio, cantidad, id_devolución)
        VALUES (?, ?, ?, 2, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        error_log("Error preparando statement: " . $conn->error);
        return false;
    }
    $stmt->bind_param("ssiiiii", $fecha_salida, $fecha_retorno, $id_usuario, $id_inventario, $id_servicio, $cantidad, $id_devolucion);
    $resultado = $stmt->execute();
    
    if (!$resultado) {
        error_log("Error ejecutando insert: " . $stmt->error);
    }
    
    $stmt->close();

    if ($resultado) {
        // Cambiar estado del equipo a ocupado
        $stmt = $conn->prepare("UPDATE inventario SET id_estado = 2 WHERE id_inventario = ?");
        $stmt->bind_param("i", $id_inventario);
        $stmt->execute();
        $stmt->close();

        // Notificar a los admins
        enviarNotificacionAdmin($departamento, $articulo, $fecha_salida, $fecha_retorno, $usuario_nombre, $mailError);
        return true;
    }
    return false;
}

// ==============================
// PROCESAR FORMULARIO
// ==============================
$equipos = obtenerEquipos();

// Servicio del usuario
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT s.nombre_servicio 
    FROM empleados e 
    JOIN servicio s ON e.id_servicio = s.id_servicio 
    WHERE e.id_empleados = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($nombre_servicio);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Validar CSRF token
  if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
      $errors[] = "Token de seguridad inválido. Por favor, recarga la página.";
  } else {
      $departamento = validateString(cleanInput($_POST['departamento']), 2, 100);
      $fecha_salida = cleanInput($_POST['fecha_salida']);
      $fecha_retorno = cleanInput($_POST['fecha_retorno']);
      $id_usuario = $_SESSION['user_id'];

      // Validar fechas
      if (!validateDate($fecha_salida)) {
          $errors[] = "Fecha de salida inválida.";
      }
      if (!validateDate($fecha_retorno)) {
          $errors[] = "Fecha de retorno inválida.";
      }
      if (!$departamento) {
          $errors[] = "Departamento inválido.";
      }

      $hoy = date('Y-m-d');
      if ($fecha_salida < $hoy) $errors[] = "La fecha de entrega no puede ser anterior a hoy.";
      if ($fecha_retorno < $fecha_salida) $errors[] = "La fecha de devolución no puede ser anterior a la fecha de entrega.";

      // Soporta dos modos: carrito múltiple (cart_items JSON) o envío único (equipo + cantidad_solicitada)
      $cart_items_json = isset($_POST['cart_items']) ? cleanInput($_POST['cart_items']) : '';

      $items_to_process = [];

      if ($cart_items_json !== '') {
        $decoded = json_decode($cart_items_json, true);
        if (!is_array($decoded) || count($decoded) === 0) {
          $errors[] = "El carrito está vacío. Agrega al menos un equipo antes de enviar.";
        } else {
          // cada elemento debe tener id (int)
          foreach ($decoded as $it) {
            $iid = validateInt($it['id'] ?? 0, 1);
            if ($iid === false) {
              $errors[] = "Hay un artículo con datos inválidos en el carrito.";
              break;
            }
            $items_to_process[] = ['id' => $iid];
          }
        }
      } else {
        $errors[] = "El carrito está vacío. Agrega al menos un equipo antes de enviar.";
      }

      if (empty($errors) && count($items_to_process) > 0) {
        $all_ok = true;
        foreach ($items_to_process as $it) {
          $ok = crearSolicitud($id_usuario, $departamento, $it['id'], $fecha_salida, $fecha_retorno, sanitizeOutput($_SESSION['user_name']), $mailError);
          if (!$ok) {
            $all_ok = false;
            $errors[] = "El equipo con ID {$it['id']} no está disponible o ocurrió un error.";
            // continuar para reportar todos los fallos
          }
        }
        if ($all_ok) $success = true;
      }
  }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Solicitud de Equipo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/estilo1.css">
</head>
<body class="dashboard-body">

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold d-flex align-items-center" href="#">
      <img src="img/Logo-CCSS-CostaRica-negro.png" alt="Logo" class="logo me-2">
      Inventario CGI
    </a>
    <div class="d-flex align-items-center">
      <span class="navbar-text me-3 text-white">
        Bienvenido, <?= htmlspecialchars($_SESSION['user_name']); ?>
      </span>
      <a href="logout.php" class="btn btn-light btn-sm fw-semibold">Cerrar sesión</a>
    </div>
  </div>
</nav>

<!-- CONTENIDO -->
<div class="dashboard-wrapper">
  <div class="card form-card p-4">
    <h3 class="card-title text-center mb-4 fw-bold">Solicitud de Equipo</h3>

    <?php if ($success): ?>
      <div class="alert alert-success shadow-sm">
        ✅ Solicitud enviada correctamente.
        <?php if ($mailError) echo "<br><small class='text-danger'>$mailError</small>"; ?>
      </div>
    <?php elseif ($errors): ?>
      <div class="alert alert-danger shadow-sm">
        <?php foreach($errors as $e) echo "<div>$e</div>"; ?>
      </div>
    <?php endif; ?>

    <form method="post">
      <!-- Token CSRF -->
      <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
      
      <div class="mb-3">
        <label class="form-label">Departamento / Servicio</label>
        <input type="text" name="departamento" class="form-control" 
          value="<?= sanitizeOutput($nombre_servicio) ?>" readonly>
      </div>

      <div class="mb-3">
        <label class="form-label">Equipo (por placa)</label>
        <select id="equipo-select" name="equipo" class="form-select">
          <option value="">-- Selecciona un equipo --</option>
          <?php foreach($equipos as $e): 
            $estado = $e['nombre_estado'];
            $nombre = sanitizeOutput($e['articulo']);
            $placa = sanitizeOutput($e['placa']);
            $icon = strtolower($estado) === 'disponible' ? '✅' : (strtolower($estado) === 'ocupado' ? '❌' : '⚠️');
            $disabled = strtolower($estado) !== 'disponible' ? 'disabled' : '';
          ?>
            <option value="<?= $e['id_inventario'] ?>" <?= $disabled ?>>
              <?= "$icon $nombre - Placa: $placa ($estado)" ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-3">
        <div class="d-grid">
          <button type="button" id="add-to-cart" class="btn btn-outline-primary">Agregar al carrito</button>
        </div>
        <small class="text-muted">Puedes agregar múltiples equipos; verás las entradas abajo.</small>
      </div>

      <!-- Carrito dinámico -->
      <div class="mb-3">
        <label class="form-label">Artículos en el carrito</label>
        <div class="table-responsive">
          <table class="table table-sm" id="cart-table">
            <thead>
              <tr>
                <th>Artículo</th>
                <th>Placa</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <!-- filas agregadas por JS -->
            </tbody>
          </table>
        </div>
        <input type="hidden" name="cart_items" id="cart-items-input" value="">
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Fecha de Préstamo</label>
          <input type="date" name="fecha_salida" class="form-control"
            min="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Fecha de Devolución</label>
          <input type="date" name="fecha_retorno" class="form-control"
            min="<?= date('Y-m-d') ?>" required>
        </div>
      </div>

      <button class="btn boton w-100 py-2">Enviar Solicitud</button>
    </form>
  </div>
</div>

<!-- SPINNER DE CARGA -->
<div id="loading-overlay">
  <div class="spinner-border text-light" role="status" style="width: 3rem; height: 3rem;"></div>
  <p class="text-white mt-3">Procesando solicitud...</p>
</div>

<script>
const equipoSelect = document.getElementById('equipo-select');
const addToCartBtn = document.getElementById('add-to-cart');
const cartTableBody = document.querySelector('#cart-table tbody');
const cartItemsInput = document.getElementById('cart-items-input');

let cart = [];

addToCartBtn.addEventListener('click', () => {
  const selected = equipoSelect.selectedOptions[0];
  if (!selected || !selected.value) {
    alert('Selecciona un equipo antes de agregar.');
    return;
  }
  const id = parseInt(selected.value);
  const name = selected.textContent.trim();
  
  // Verificar si el equipo ya está en el carrito
  if (cart.some(item => item.id === id)) {
    alert('Este equipo ya está en el carrito.');
    return;
  }
  
  // Extraer la placa del texto
  const placaMatch = name.match(/Placa:\s*(\d+)/);
  const placa = placaMatch ? placaMatch[1] : 'N/A';
  
  // Añadir al carrito
  cart.push({id: id, nombre: name, placa: placa});
  updateCartDisplay();
  
  // Deshabilitar la opción seleccionada
  selected.disabled = true;
  equipoSelect.value = '';
});

function updateCartDisplay() {
  cartTableBody.innerHTML = '';
  cart.forEach((it, idx) => {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td>${escapeHtml(it.nombre)}</td><td>${escapeHtml(it.placa)}</td><td><button type="button" data-idx="${idx}" class="btn btn-sm btn-danger remove-item">Eliminar</button></td>`;
    cartTableBody.appendChild(tr);
  });
  // Actualizar input oculto como JSON (solo id)
  cartItemsInput.value = JSON.stringify(cart.map(i => ({id: i.id})));
  // Añadir listeners a botones eliminar
  document.querySelectorAll('.remove-item').forEach(b => b.addEventListener('click', (e) => {
    const i = parseInt(e.currentTarget.dataset.idx);
    if (!isNaN(i)) {
      const removedItem = cart[i];
      // Rehabilitar la opción en el select
      const option = equipoSelect.querySelector(`option[value="${removedItem.id}"]`);
      if (option) option.disabled = false;
      cart.splice(i, 1);
      updateCartDisplay();
    }
  }));
}

function escapeHtml(text) {
  return text.replace(/[&<>\"']/g, function(m) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[m]; });
}

// Spinner al enviar formulario
document.querySelector("form").addEventListener("submit", function(e) {
  // Validar que el carrito no esté vacío
  if (cart.length === 0) {
    e.preventDefault();
    alert('Debes agregar al menos un equipo al carrito antes de enviar la solicitud.');
    return false;
  }
  document.getElementById("loading-overlay").style.display = "flex";
});
</script>

<style>
/* ===================================================
   ESTILO INSTITUCIONAL / MINIMALISTA - INVENTARIO CGI
   =================================================== */

/* ======== ESTRUCTURA GENERAL ======== */
.dashboard-body {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  font-family: 'Segoe UI', sans-serif;
  background-color: #f4f6fa;
  color: #1e3c72;
}

/* ======== NAVBAR ======== */
.navbar {
  background-color: #1e3c72;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  padding: 0.6rem 1rem;
  z-index: 10;
}
.navbar-brand {
  font-size: 1.2rem;
  font-weight: 600;
  color: #ffffff;
}
.navbar-brand:hover {
  color: #dce3f5;
}
.logo {
  max-width: 42px;
  height: auto;
  border-radius: 6px;
}

/* ======== CONTENEDOR PRINCIPAL ======== */
.dashboard-wrapper {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 50px 15px;
}

/* ======== TARJETA ======== */
.form-card {
  background-color: #ffffff;
  border: 1px solid #d8dee9;
  border-radius: 12px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
  width: 100%;
  max-width: 520px;
  padding: 30px;
  animation: fadeUp 0.5s ease;
}
.card-title {
  color: #1e3c72;
  font-weight: 700;
  text-align: center;
  margin-bottom: 1.5rem;
}

/* ======== FORMULARIO ======== */
.form-label {
  color: #1e3c72;
  font-weight: 600;
}
.form-control,
.form-select {
  border-radius: 6px;
  border: 1px solid #ccd3e0;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.form-control:focus,
.form-select:focus {
  border-color: #1e3c72;
  box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.15);
  outline: none;
}

/* ======== BOTÓN ======== */
.boton {
  background-color: #1e3c72;
  border: none;
  border-radius: 6px;
  color: #ffffff;
  font-weight: 600;
  padding: 10px 0;
  transition: background-color 0.2s ease, transform 0.1s ease;
}
.boton:hover {
  background-color: #284c9a;
  transform: translateY(-1px);
}

/* ======== ALERTAS ======== */
.alert {
  border-radius: 8px;
  border: none;
  font-weight: 500;
}
.alert-success {
  background-color: #e7f6ea;
  color: #1a5c2b;
}
.alert-danger {
  background-color: #fdecea;
  color: #822029;
}

/* ======== SPINNER DE CARGA ======== */
#loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(30, 60, 114, 0.7);
  display: none;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  z-index: 2000;
}
#loading-overlay p {
  font-size: 1rem;
  color: #ffffff;
  margin-top: 12px;
}

/* ======== ANIMACIONES ======== */
@keyframes fadeUp {
  from { transform: translateY(10px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}


    </style>
</body>
</html>