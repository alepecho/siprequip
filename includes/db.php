<?php

//conexion 
$server='localhost';
$username='root';
$password='';
$database='empleados';
$db=mysqli_connect($server, $username, $password,$database);

mysqli_query($db, "SET NAMES 'utf8'");

//Iniciar la sesion

session_start();