<?php
$host = "localhost";      // Servidor (normalmente localhost)
$user = "root";           // Usuario por defecto en XAMPP
$password = "";           // Contraseña (vacía por defecto en XAMPP)
$dbname = "empleados";    // Nombre de tu base de datos

<<<<<<< HEAD
//conexion 
$server='localhost';
$username='root';
$password='';
$database='';
$db=mysqli_connect($server, $username, $password,$database);

mysqli_query($db, "SET NAMES 'utf8'");

//Iniciar la sesion

session_start();
=======
// Crear conexión
$conn = new mysqli($host, $user, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
echo "Conexión exitosa";
?>
>>>>>>> BackEnd
