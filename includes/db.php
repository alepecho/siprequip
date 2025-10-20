<?php

//conexion 
try {
    // Ruta al archivo de tu base de datos SQLite
    $db = new PDO('sqlite:' . __DIR__ . '/../database/empleados.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

//Iniciar la sesion

session_start();