// public/js/main.js
// Validación sencilla puede agregarse aquí
document.addEventListener('DOMContentLoaded', function(){
  const requestForm = document.getElementById('requestForm');
  if (requestForm) {
    requestForm.addEventListener('submit', function(e) {
      const cantidad = parseInt(requestForm.cantidad.value, 10);
      if (isNaN(cantidad) || cantidad < 1) {
        e.preventDefault();
        alert('La cantidad debe ser un número válido mayor o igual a 1.');
      }
    });
  }
});