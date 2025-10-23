<?php
$host = "localhost";      // Servidor (normalmente localhost)
$user = "root";           // Usuario por defecto en XAMPP
$password = "";           // Contraseña (vacía por defecto en XAMPP)
$dbname = "empleados";    // Nombre de tu base de datos


<<<<<<< HEAD

=======
>>>>>>> 9c326f7858dbe2d3b4e417b824449b9e31bccea6
// Crear conexión
$conn = new mysqli($host, $user, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
echo "Conexión exitosa";
?>
<<<<<<< HEAD

=======
>>>>>>> 9c326f7858dbe2d3b4e417b824449b9e31bccea6
