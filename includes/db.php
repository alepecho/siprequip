<?php
$host = "localhost";      // Servidor (normalmente localhost)
$user = "root";           // Usuario por defecto en XAMPP
$password = "";           // Contraseña (vacía por defecto en XAMPP)
$dbname = "empleados";    // Nombre de tu base de datos

<<<<<<< HEAD
<<<<<<< HEAD
=======

>>>>>>> origin/FrontEnd
=======

>>>>>>> parent of 0b3617a (Merge branch 'FrontEnd' of https://github.com/alepecho/siprequip into FrontEnd)
// Crear conexión
$conn = new mysqli($host, $user, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
echo "Conexión exitosa";
<<<<<<< HEAD
<<<<<<< HEAD
?>
=======
?>
>>>>>>> origin/FrontEnd
=======
?>
>>>>>>> parent of 0b3617a (Merge branch 'FrontEnd' of https://github.com/alepecho/siprequip into FrontEnd)
