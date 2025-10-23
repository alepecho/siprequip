-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 23-10-2025 a las 18:28:48
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `empleados`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `devolución`
--

CREATE TABLE `devolución` (
  `id_devolución` int(11) NOT NULL,
  `articulo` varchar(55) NOT NULL,
  `estado` varchar(55) NOT NULL,
  `cantidad` int(50) NOT NULL,
  `id_registro` int(11) NOT NULL,
  `id_empleados` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleados`
--

CREATE TABLE `empleados` (
  `id_empleados` int(11) NOT NULL,
  `usuario_caja` varchar(255) DEFAULT NULL,
  `cedula` int(55) DEFAULT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido1` varchar(20) DEFAULT NULL,
  `apellido2` varchar(20) DEFAULT NULL,
  `correo_caja` varchar(70) DEFAULT NULL,
  `contraseña` char(64) DEFAULT NULL,
  `id_rol` int(11) NOT NULL,
  `id_servicio` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estado`
--

CREATE TABLE `estado` (
  `id_estado` int(11) NOT NULL,
  `nombre` varchar(55) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inevntario`
--

CREATE TABLE `inevntario` (
  `id_inventario` int(11) NOT NULL,
  `articulo` varchar(55) NOT NULL,
  `cantidad` int(55) NOT NULL,
  `id_estado` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registro_detalle`
--

CREATE TABLE `registro_detalle` (
  `id_registro` int(11) NOT NULL,
  `fecha_de_salida` date NOT NULL DEFAULT current_timestamp(),
  `fecha_de_retorno` date NOT NULL DEFAULT current_timestamp(),
  `id_empleados` int(11) NOT NULL,
  `id_estado` int(11) NOT NULL,
  `id_servicio` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol`
--

CREATE TABLE `rol` (
  `id_rol` int(11) NOT NULL,
  `det_rol` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicio`
--

CREATE TABLE `servicio` (
  `id_servicio` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `devolución`
--
ALTER TABLE `devolución`
  ADD PRIMARY KEY (`id_devolución`),
  ADD KEY `id_registro` (`id_registro`),
  ADD KEY `empleados` (`id_empleados`);

--
-- Indices de la tabla `empleados`
--
ALTER TABLE `empleados`
  ADD PRIMARY KEY (`id_empleados`),
  ADD KEY `id_rol` (`id_rol`),
  ADD KEY `Servicio` (`id_servicio`);

--
-- Indices de la tabla `estado`
--
ALTER TABLE `estado`
  ADD PRIMARY KEY (`id_estado`);

--
-- Indices de la tabla `inevntario`
--
ALTER TABLE `inevntario`
  ADD PRIMARY KEY (`id_inventario`),
  ADD KEY `estado` (`id_estado`);

--
-- Indices de la tabla `registro_detalle`
--
ALTER TABLE `registro_detalle`
  ADD PRIMARY KEY (`id_registro`),
  ADD KEY `id_empleados` (`id_empleados`),
  ADD KEY `id_estado` (`id_estado`),
  ADD KEY `id_servicio` (`id_servicio`);

--
-- Indices de la tabla `rol`
--
ALTER TABLE `rol`
  ADD PRIMARY KEY (`id_rol`),
  ADD KEY `id_rol` (`id_rol`);

--
-- Indices de la tabla `servicio`
--
ALTER TABLE `servicio`
  ADD PRIMARY KEY (`id_servicio`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `devolución`
--
ALTER TABLE `devolución`
  MODIFY `id_devolución` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `empleados`
--
ALTER TABLE `empleados`
  MODIFY `id_empleados` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inevntario`
--
ALTER TABLE `inevntario`
  MODIFY `id_inventario` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `registro_detalle`
--
ALTER TABLE `registro_detalle`
  MODIFY `id_registro` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `devolución`
--
ALTER TABLE `devolución`
  ADD CONSTRAINT `devolución_ibfk_1` FOREIGN KEY (`id_registro`) REFERENCES `registro_detalle` (`id_registro`),
  ADD CONSTRAINT `empleados` FOREIGN KEY (`id_empleados`) REFERENCES `empleados` (`id_empleados`);

--
-- Filtros para la tabla `empleados`
--
ALTER TABLE `empleados`
  ADD CONSTRAINT `Servicio` FOREIGN KEY (`id_servicio`) REFERENCES `servicio` (`id_servicio`),
  ADD CONSTRAINT `id_rol` FOREIGN KEY (`id_rol`) REFERENCES `empleados` (`id_empleados`);

--
-- Filtros para la tabla `inevntario`
--
ALTER TABLE `inevntario`
  ADD CONSTRAINT `estado` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_estado`);

--
-- Filtros para la tabla `registro_detalle`
--
ALTER TABLE `registro_detalle`
  ADD CONSTRAINT `id_empleados` FOREIGN KEY (`id_empleados`) REFERENCES `empleados` (`id_empleados`),
  ADD CONSTRAINT `id_estado` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_estado`),
  ADD CONSTRAINT `id_servicio` FOREIGN KEY (`id_servicio`) REFERENCES `servicio` (`id_servicio`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
