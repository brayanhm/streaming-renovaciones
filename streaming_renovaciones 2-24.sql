-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 24-02-2026 a las 22:44:51
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
-- Base de datos: `streaming_renovaciones`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `notas` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `nombre`, `telefono`, `notas`, `created_at`) VALUES
(3, 'TEST MSG MENOS 2', '79625801', 'Cliente de prueba para MENOS_2', '2026-02-24 07:37:46'),
(4, 'TEST MSG MENOS 1', '79625801', 'Cliente de prueba para MENOS_1', '2026-02-24 07:37:46'),
(5, 'TEST MSG REC 7', '79625801', 'Cliente de prueba para REC_7', '2026-02-24 07:37:46'),
(6, 'TEST MSG REC 15', '79625801', 'Cliente de prueba para REC_15', '2026-02-24 07:37:46'),
(7, 'TEST MSG ACTIVO', '79625801', 'Cliente de prueba para ACTIVO', '2026-02-24 07:37:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modalidades`
--

CREATE TABLE `modalidades` (
  `id` int(11) NOT NULL,
  `plataforma_id` int(11) NOT NULL,
  `nombre_modalidad` varchar(100) NOT NULL,
  `tipo_cuenta` enum('CUENTA_COMPLETA','POR_DISPOSITIVOS','AMBOS') NOT NULL DEFAULT 'CUENTA_COMPLETA',
  `duracion_meses` int(11) NOT NULL DEFAULT 1,
  `dispositivos` int(11) DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `modalidades`
--

INSERT INTO `modalidades` (`id`, `plataforma_id`, `nombre_modalidad`, `tipo_cuenta`, `duracion_meses`, `dispositivos`, `precio`, `created_at`) VALUES
(2, 1, 'Mes', 'CUENTA_COMPLETA', 1, NULL, 20.00, '2026-02-24 07:20:48');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos`
--

CREATE TABLE `movimientos` (
  `id` int(11) NOT NULL,
  `suscripcion_id` int(11) NOT NULL,
  `tipo` enum('RENOVACION') DEFAULT 'RENOVACION',
  `meses` int(11) NOT NULL,
  `monto` decimal(10,2) DEFAULT NULL,
  `fecha` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `plataformas`
--

CREATE TABLE `plataformas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo_servicio` enum('RENOVABLE','DESECHABLE') NOT NULL,
  `duraciones_disponibles` varchar(100) DEFAULT NULL,
  `dato_renovacion` varchar(20) NOT NULL DEFAULT 'NO_APLICA',
  `mensaje_menos_2` text DEFAULT NULL,
  `mensaje_menos_1` text DEFAULT NULL,
  `mensaje_rec_7` text DEFAULT NULL,
  `mensaje_rec_15` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `plataformas`
--

INSERT INTO `plataformas` (`id`, `nombre`, `tipo_servicio`, `duraciones_disponibles`, `dato_renovacion`, `mensaje_menos_2`, `mensaje_menos_1`, `mensaje_rec_7`, `mensaje_rec_15`, `created_at`) VALUES
(1, 'Spotify', 'DESECHABLE', '1,3', 'NO_APLICA', 'Hola {NOMBRE}, te recordamos que tu suscripcion de {PLATAFORMA} ({PLAN}) vence el {FECHA_VENCE}. Para mantener tu acceso sin interrupciones, la renovacion es de {PRECIO}. Si deseas renovar, te ayudamos por este medio.', 'Hola {NOMBRE}, tu suscripcion de {PLATAFORMA} ({PLAN}) vence manana ({FECHA_VENCE}). El monto de renovacion es {PRECIO}. Si deseas continuar con el servicio, te la activamos hoy mismo.', 'Hola {NOMBRE}, tu suscripcion de {PLATAFORMA} ({PLAN}) vencio recientemente. Aun podemos reactivarla hoy por {PRECIO} para que recuperes el acceso de inmediato. Te la activo?', 'Hola {NOMBRE}, seguimos disponibles para reactivar tu suscripcion de {PLATAFORMA} ({PLAN}). El valor actual es {PRECIO}. Si te interesa, te ayudamos por aqui.', '2026-02-24 07:08:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `suscripciones`
--

CREATE TABLE `suscripciones` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `plataforma_id` int(11) NOT NULL,
  `modalidad_id` int(11) NOT NULL,
  `precio_venta` decimal(10,2) DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `estado` enum('CONTACTAR_2D','REENVIAR_1D','ESPERA','ACTIVO','VENCIDO','RECUP') DEFAULT 'ACTIVO',
  `ultimo_contacto_fecha` datetime DEFAULT NULL,
  `ultimo_contacto_tipo` enum('MENOS_2','MENOS_1','REC_7','REC_15') DEFAULT NULL,
  `usuario_proveedor` varchar(150) DEFAULT NULL,
  `flag_no_renovo` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `suscripciones`
--

INSERT INTO `suscripciones` (`id`, `cliente_id`, `plataforma_id`, `modalidad_id`, `precio_venta`, `fecha_inicio`, `fecha_vencimiento`, `estado`, `ultimo_contacto_fecha`, `ultimo_contacto_tipo`, `usuario_proveedor`, `flag_no_renovo`, `created_at`) VALUES
(3, 3, 1, 2, 20.00, '2026-01-26', '2026-02-26', 'ESPERA', '2026-02-24 07:40:35', 'MENOS_2', NULL, 0, '2026-02-24 07:37:46'),
(4, 4, 1, 2, 20.00, '2026-01-25', '2026-02-25', 'ESPERA', '2026-02-24 07:38:34', 'MENOS_1', NULL, 0, '2026-02-24 07:37:46'),
(5, 5, 1, 2, 20.00, '2026-01-16', '2026-02-16', 'RECUP', NULL, NULL, NULL, 1, '2026-02-24 07:37:46'),
(6, 6, 1, 2, 20.00, '2026-01-04', '2026-02-04', 'RECUP', NULL, NULL, NULL, 1, '2026-02-24 07:37:46'),
(7, 7, 1, 2, 20.00, '2026-02-16', '2026-03-16', 'ACTIVO', NULL, NULL, NULL, 0, '2026-02-24 07:37:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `username` varchar(60) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol` enum('admin','operador') DEFAULT 'operador',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password_hash`, `rol`, `created_at`) VALUES
(1, 'admin', '$2y$10$PiQrgN6CxQL7/Hzx/yjO0OJ0OYE1NteT.Z3Vw54Km5Jivsie8Aehq', 'admin', '2026-02-24 06:42:51');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_clientes_telefono` (`telefono`);

--
-- Indices de la tabla `modalidades`
--
ALTER TABLE `modalidades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plataforma_id` (`plataforma_id`);

--
-- Indices de la tabla `movimientos`
--
ALTER TABLE `movimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `suscripcion_id` (`suscripcion_id`);

--
-- Indices de la tabla `plataformas`
--
ALTER TABLE `plataformas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `suscripciones`
--
ALTER TABLE `suscripciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `plataforma_id` (`plataforma_id`),
  ADD KEY `modalidad_id` (`modalidad_id`),
  ADD KEY `idx_suscripciones_estado` (`estado`),
  ADD KEY `idx_suscripciones_vencimiento` (`fecha_vencimiento`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `modalidades`
--
ALTER TABLE `modalidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `movimientos`
--
ALTER TABLE `movimientos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `plataformas`
--
ALTER TABLE `plataformas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `suscripciones`
--
ALTER TABLE `suscripciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `modalidades`
--
ALTER TABLE `modalidades`
  ADD CONSTRAINT `modalidades_ibfk_1` FOREIGN KEY (`plataforma_id`) REFERENCES `plataformas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `movimientos`
--
ALTER TABLE `movimientos`
  ADD CONSTRAINT `movimientos_ibfk_1` FOREIGN KEY (`suscripcion_id`) REFERENCES `suscripciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `suscripciones`
--
ALTER TABLE `suscripciones`
  ADD CONSTRAINT `suscripciones_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `suscripciones_ibfk_2` FOREIGN KEY (`plataforma_id`) REFERENCES `plataformas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `suscripciones_ibfk_3` FOREIGN KEY (`modalidad_id`) REFERENCES `modalidades` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
