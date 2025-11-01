<div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content" style="background-color:#f7f9fb;">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="registerModalLabel"><center>Crear cuenta</center></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form method="POST">
          <div class="mb-3">
            <label for="cedula" class="form-label">Cédula</label>
            <input type="text" class="form-control" id="cedula" name="cedula" required>
          </div>
          <div class="mb-3">
            <label for="usuario_caja" class="form-label">Usuario Caja</label>
            <input type="text" class="form-control" id="usuario_caja" name="usuario_caja" required>
          </div>
          <div class="mb-3">
            <label for="correo_caja" class="form-label">Correo Caja</label>
            <input type="email" class="form-control" id="correo_caja" name="correo_caja" required>
          </div>
          <div class="mb-3">
            <label for="nombre" class="form-label">Nombre</label>
            <input type="text" class="form-control" id="nombre" name="nombre" required>
          </div>
          <div class="mb-3">
            <label for="apellido1" class="form-label">Apellido 1</label>
            <input type="text" class="form-control" id="apellido1" name="apellido1" required>
          </div>
          <div class="mb-3">
            <label for="apellido2" class="form-label">Apellido 2</label>
            <input type="text" class="form-control" id="apellido2" name="apellido2">
          </div>

          <!-- Servicios -->
          <div class="mb-3">
            <label for="id_servicio" class="form-label">Servicio o Depto</label>
            <select class="form-control" id="id_servicio" name="id_servicio">
              <option value="">Seleccione un servicio...</option>
              <?php
                $servicios = $conn->query("SELECT id_servicio, nombre_servicio FROM servicio");
                while ($row = $servicios->fetch_assoc()) {
                    echo '<option value="'.$row['id_servicio'].'">'.$row['nombre_servicio'].'</option>';
                }
              ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Contraseña</label>
            <input class="form-control" name="contrasena" type="password" id="passwordRegister" placeholder="********" required>
            
            <!-- 🔹 NUEVO: Indicador de fuerza -->
            <small id="passwordStrengthText" class="form-text mt-1 fw-semibold"></small>

            <!-- 🔹 NUEVO: Requisitos de la contraseña -->
            <ul id="passwordRequirements" class="list-unstyled small mt-2">
              <li id="req-length">• Al menos 8 caracteres</li>
              <li id="req-upper">• Una letra mayúscula</li>
              <li id="req-lower">• Una letra minúscula</li>
              <li id="req-number">• Un número</li>
              <li id="req-special">• Un carácter especial (!@#$%^&*)</li>
            </ul>
          </div>

          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="showPasswordRegister">
            <label class="form-check-label" for="showPasswordRegister">Mostrar contraseña</label>
          </div>

          <!-- 🔹 Botón modificado: con ID y desactivado al inicio -->
          <button type="submit" class="btn btn-primary w-100" id="registerButton" disabled>Registrarse</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
  const loginCheckbox = document.getElementById("showPasswordLogin");
  const loginInput = document.getElementById("passwordLogin");
  loginCheckbox?.addEventListener("change", () => {
    loginInput.type = loginCheckbox.checked ? "text" : "password";
  });

  const registerCheckbox = document.getElementById("showPasswordRegister");
  const registerInput = document.getElementById("passwordRegister");
  registerCheckbox?.addEventListener("change", () => {
    registerInput.type = registerCheckbox.checked ? "text" : "password";
  });

  // 🔹 NUEVO: Verificación de fuerza de contraseña
  const strengthText = document.getElementById("passwordStrengthText");
  const registerButton = document.getElementById("registerButton");
  const requirements = {
    length: document.getElementById("req-length"),
    upper: document.getElementById("req-upper"),
    lower: document.getElementById("req-lower"),
    number: document.getElementById("req-number"),
    special: document.getElementById("req-special")
  };

  registerInput.addEventListener("input", () => {
    const val = registerInput.value;

    const hasLength = val.length >= 8;
    const hasUpper = /[A-Z]/.test(val);
    const hasLower = /[a-z]/.test(val);
    const hasNumber = /\d/.test(val);
    const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(val);

    updateRequirement(requirements.length, hasLength);
    updateRequirement(requirements.upper, hasUpper);
    updateRequirement(requirements.lower, hasLower);
    updateRequirement(requirements.number, hasNumber);
    updateRequirement(requirements.special, hasSpecial);

    const score = [hasLength, hasUpper, hasLower, hasNumber, hasSpecial].filter(Boolean).length;

    // Mostrar nivel de fuerza
    if (val.length === 0) {
      strengthText.textContent = "";
      registerButton.disabled = true; // 🔹 Bloquea si está vacío
    } else if (score <= 2) {
      strengthText.textContent = "Fuerza: Débil";
      strengthText.style.color = "red";
      registerButton.disabled = true;
    } else if (score === 3 || score === 4) {
      strengthText.textContent = "Fuerza: Media";
      strengthText.style.color = "orange";
      registerButton.disabled = true;
    } else {
      strengthText.textContent = "Fuerza: Fuerte";
      strengthText.style.color = "green";
      registerButton.disabled = false; // 🔹 Solo se habilita si cumple todo
    }
  });

  function updateRequirement(element, isValid) {
    element.style.color = isValid ? "green" : "red";
    element.style.fontWeight = isValid ? "bold" : "normal";
  }
});
</script>
