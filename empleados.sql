-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 31-10-2025 a las 16:25:03
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
  `contrasena` char(64) DEFAULT NULL,
  `id_rol` int(11) DEFAULT NULL,
  `id_servicio` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empleados`
--

INSERT INTO `empleados` (`id_empleados`, `usuario_caja`, `cedula`, `nombre`, `apellido1`, `apellido2`, `correo_caja`, `contrasena`, `id_rol`, `id_servicio`) VALUES
(1, 'fmora', 305070671, 'Fiorella', 'Mora', 'Garita', 'fmoragarita@ccss.ac.cr', '$2y$10$xlSXO0Wv.Aq2gXdjY5U/VOvS4K0lumkwuyxF09Mx5FYRt89RNwMPe', 1, 2),
(2, 'bsanchez', 2147483647, 'Brandon', 'Sánchez', 'Pacheco', 'bsanchezpacheco@ccss.ac.cr', '$2y$10$FNU1QMPhD3DlPt3amEKrD.AjB4efJoyVKrWXSGl8dYvqWOZsgqAMK', 1, 2),
(3, 'sfigueroam', 119270992, 'stanley', 'Figueroa', 'Mayorga', 'stanleymayorga94@gmail.com', '$2y$10$b2.N7Yi1T58jAr2FDthveuqTjw9MrMGjCNB3ZHbPNC1MjgLGreVMm', 1, 19),
(4, 'ARAMIRAS', 113070260, 'ALEJANDRO', 'RAMIREZ', 'ASTÚA', 'aramiras@ccss.sa.cr', '$2y$10$KH7q71zHGN5I9wbB3sfnYOQZYcSfj27fvTCjshJPSTFUJ.z9OaAnO', 3, 19),
(5, 'GFCHAVES', 110310565, 'GERARDO', 'CHAVES', 'VEGA', 'gfchaves@ccss.sa.cr', '$2y$10$CaZ28eJyklzYVuYvoNJnmustMZtrhKf8fD88fOMb1lRJPnir9vNlm', 3, 19),
(6, 'BASALAZAR', 116120335, 'BRYAN', 'SALAZAR', 'UVEDA', 'basalazar@ccss.sa.cr', '$2y$10$VL8KVbXlC.vr1au7nqKQruLxyfaGxr5tywB.voZwxwoUAyjBentd2', 3, 19);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estado`
--

CREATE TABLE `estado` (
  `id_estado` int(11) NOT NULL,
  `nombre` varchar(55) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `estado`
--

INSERT INTO `estado` (`id_estado`, `nombre`) VALUES
(1, 'DISPONIBLE'),
(2, 'OCUPADO');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario`
--

CREATE TABLE `inventario` (
  `id_inventario` int(11) NOT NULL,
  `articulo` varchar(55) NOT NULL,
  `cantidad` int(55) NOT NULL,
  `id_estado` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `inventario`
--

INSERT INTO `inventario` (`id_inventario`, `articulo`, `cantidad`, `id_estado`) VALUES
(1, 'LAPTOP', 2, 1),
(2, 'PROYECTOR', 1, 1),
(3, 'PANTALLA', 1, 1),
(4, 'MIFI', 1, 1),
(5, 'MONITORES', 4, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registro_detalle`
--

CREATE TABLE `registro_detalle` (
  `id_registro` int(11) NOT NULL,
  `fecha_de_salida` date NOT NULL DEFAULT current_timestamp(),
  `fecha_de_retorno` date NOT NULL DEFAULT current_timestamp(),
  `cantidad` int(11) NOT NULL,
  `id_empleados` int(11) NOT NULL,
  `id_estado` int(11) NOT NULL,
  `id_servicio` int(11) NOT NULL,
  `id_inventario` int(55) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `registro_detalle`
--

INSERT INTO `registro_detalle` (`id_registro`, `fecha_de_salida`, `fecha_de_retorno`, `cantidad`, `id_empleados`, `id_estado`, `id_servicio`, `id_inventario`) VALUES
(1, '2025-10-31', '2025-10-31', 1, 1, 1, 2, 1),
(2, '2025-10-31', '2025-10-31', 1, 1, 1, 2, 5),
(3, '2025-10-31', '2025-10-31', 1, 1, 1, 2, 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `nombre_rol` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `nombre_rol`) VALUES
(1, 'Empleado'),
(3, 'ADMINISTRADOR');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicio`
--

CREATE TABLE `servicio` (
  `id_servicio` int(11) NOT NULL,
  `nombre_servicio` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `servicio`
--

INSERT INTO `servicio` (`id_servicio`, `nombre_servicio`) VALUES
(1, 'SERVICIOS_GENERALES'),
(2, 'AGEBYS'),
(3, 'FINANCIERO_CONTABLE'),
(4, 'MANTENIMIENTO'),
(5, 'TRANSPORTE_ROPERIA'),
(6, 'RH'),
(7, 'NUTRICION'),
(8, 'PSICOLOGIA'),
(9, 'DIRECCION_MEDICA'),
(10, 'REDES'),
(11, 'DISPOSITIVO_COMUNITARIO'),
(12, 'ADMINISTRACION'),
(13, 'ENFERMERIA'),
(14, 'FARMACIA'),
(15, 'LABORATORIA'),
(16, 'TERAPIA_FISICA'),
(17, 'TERAPIA_OCUPACIONAL'),
(18, 'TRABAJO_SOCIAL'),
(19, 'INFORMATICA'),
(20, 'ASESORIA_LEGAL'),
(21, 'CONTRALORIA_DE_SERVICIOS'),
(22, 'TERAPIA_RESPIRATORIA');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `devolución`
--
ALTER TABLE `devolución`
  ADD PRIMARY KEY (`id_devolución`),
  ADD KEY `devolución_ibfk_1` (`id_registro`),
  ADD KEY `empleados` (`id_empleados`);

--
-- Indices de la tabla `empleados`
--
ALTER TABLE `empleados`
  ADD PRIMARY KEY (`id_empleados`),
  ADD KEY `id_rol` (`id_rol`),
  ADD KEY `id_servicio` (`id_servicio`);

--
-- Indices de la tabla `estado`
--
ALTER TABLE `estado`
  ADD PRIMARY KEY (`id_estado`);

--
-- Indices de la tabla `inventario`
--
ALTER TABLE `inventario`
  ADD PRIMARY KEY (`id_inventario`),
  ADD KEY `estado` (`id_estado`);

--
-- Indices de la tabla `registro_detalle`
--
ALTER TABLE `registro_detalle`
  ADD PRIMARY KEY (`id_registro`),
  ADD KEY `id_inventario` (`id_inventario`),
  ADD KEY `servicio` (`id_servicio`),
  ADD KEY `id_estado` (`id_estado`),
  ADD KEY `id_empleados` (`id_empleados`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`);

--
-- Indices de la tabla `servicio`
--
ALTER TABLE `servicio`
  ADD PRIMARY KEY (`id_servicio`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `empleados`
--
ALTER TABLE `empleados`
  MODIFY `id_empleados` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `registro_detalle`
--
ALTER TABLE `registro_detalle`
  MODIFY `id_registro` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `servicio`
--
ALTER TABLE `servicio`
  MODIFY `id_servicio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

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
  ADD CONSTRAINT `id_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`),
  ADD CONSTRAINT `id_servicio` FOREIGN KEY (`id_servicio`) REFERENCES `servicio` (`id_servicio`);

--
-- Filtros para la tabla `inventario`
--
ALTER TABLE `inventario`
  ADD CONSTRAINT `estado` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_estado`);

--
-- Filtros para la tabla `registro_detalle`
--
ALTER TABLE `registro_detalle`
  ADD CONSTRAINT `id_empleados` FOREIGN KEY (`id_empleados`) REFERENCES `empleados` (`id_empleados`),
  ADD CONSTRAINT `id_estado` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_estado`),
  ADD CONSTRAINT `id_inventario` FOREIGN KEY (`id_inventario`) REFERENCES `inventario` (`id_inventario`),
  ADD CONSTRAINT `servicio` FOREIGN KEY (`id_servicio`) REFERENCES `servicio` (`id_servicio`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
