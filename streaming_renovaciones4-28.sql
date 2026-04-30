-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 29-04-2026 a las 00:08:31
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
-- Estructura de tabla para la tabla `backup_vencimientos_20260302_flujotv`
--

CREATE TABLE `backup_vencimientos_20260302_flujotv` (
  `backup_id` int(11) NOT NULL,
  `suscripcion_id` int(11) NOT NULL,
  `usuario_proveedor` varchar(150) DEFAULT NULL,
  `old_fecha_vencimiento` date DEFAULT NULL,
  `new_fecha_vencimiento` date DEFAULT NULL,
  `respaldo_en` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `backup_vencimientos_20260302_flujotv`
--

INSERT INTO `backup_vencimientos_20260302_flujotv` (`backup_id`, `suscripcion_id`, `usuario_proveedor`, `old_fecha_vencimiento`, `new_fecha_vencimiento`, `respaldo_en`) VALUES
(1, 128, 'Antonio2945', '2026-02-24', '2026-04-02', '2026-03-02 16:31:57'),
(2, 132, 'TVGUTIERREZ64', '2026-02-26', '2026-03-26', '2026-03-02 16:31:57'),
(3, 138, 'Mgtvplusbo613', '2026-03-03', '2026-06-03', '2026-03-02 16:31:57'),
(4, 144, 'NAVAJASITURRE', '2026-03-05', '2026-06-05', '2026-03-02 16:31:57'),
(5, 167, 'JonCris', '2026-03-25', '2027-05-25', '2026-03-02 16:31:57');

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
(119, 'CLM113', '78129454', 'Importado desde usuarios_combinados.xlsx | Usuario: Antonio2945 | Clave: mgtv2232 | Credito: 24 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(120, 'CLM130', '62126392', 'Importado desde usuarios_combinados.xlsx | Usuario: Kevinb548 | Clave: mgtv1337 | Credito: 22 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(121, 'CLM65', '59162747094', 'Importado desde usuarios_combinados.xlsx | Usuario: Luis10450 | Clave: fjtv9087 | Credito: 1 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(122, 'CLM16', '62000726', 'Importado desde usuarios_combinados.xlsx | Usuario: JavierS89 | Clave: 2857177 | Credito: 30 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(123, 'CLM155', '71541343', 'Importado desde usuarios_combinados.xlsx | Usuario: TVGUTIERREZ64 | Clave: blanca123 | Credito: 16 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(124, 'CLM200', '59170042648', 'Importado desde usuarios_combinados.xlsx | Usuario: CeciliaR294 | Clave: fjtv1956 | Credito: 7 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(125, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Fjtvgh177 | Clave: fjtv11w8 | Credito: 7 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(126, 'CLM111', '63262563', 'Importado desde usuarios_combinados.xlsx | Usuario: crsirpa | Clave: mgtv1253 | Credito: 19 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(127, 'CLM173', '59173190643', 'Importado desde usuarios_combinados.xlsx | Usuario: VladimirM64 | Clave: fjtv2251 | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(128, 'CLM115', '59172096446', 'Importado desde usuarios_combinados.xlsx | Usuario: MiguelT74 | Clave: thiagoymia09 | Credito: 21 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(129, 'CLM110', '68100041', 'Importado desde usuarios_combinados.xlsx | Usuario: Mgtvplusbo613 | Clave: matilda2024 | Credito: 20 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(130, 'CLM90', '79872569', 'Importado desde usuarios_combinados.xlsx | Usuario: javier7762 | Clave: usuario130 | Credito: 42 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/meufksdiq1fb', '2026-02-24 21:44:17'),
(131, 'CLM106', '74699663', 'Importado desde usuarios_combinados.xlsx | Usuario: Jimenam24 | Clave: mgtv1428 | Credito: 23 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(132, 'CLM168', '59171948321', 'Importado desde usuarios_combinados.xlsx | Usuario: NinoskaClavel5 | Clave: Yarkoamor47 | Credito: 16 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(133, 'CLM129', '76115292', 'Importado desde usuarios_combinados.xlsx | Usuario: ArielD131 | Clave: 5668635isaH | Credito: 21 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(134, 'CLM201', '59175803332', 'Importado desde usuarios_combinados.xlsx | Usuario: AlejandraT85 | Clave: fjtvw987 | Credito: 3 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(135, 'CLM202', '59163789129', 'Importado desde usuarios_combinados.xlsx | Usuario: NAVAJASITURRE | Clave: fjtv1804 | Credito: 6 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(136, 'CLM204', '59178026740', 'Importado desde usuarios_combinados.xlsx | Usuario: Severiche64 | Clave: sev3578254 | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(137, 'CLM17', '76988244', 'Importado desde usuarios_combinados.xlsx | Usuario: Ber_Rios | Clave: CUA016 | Credito: 41 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/tj65hrdjgfpo', '2026-02-24 21:44:17'),
(138, 'CLM10', '72266006', 'Importado desde usuarios_combinados.xlsx | Usuario: Mgtvplusbo211 | Clave: mgtv2116 | Credito: 33 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(139, 'CLM205', '59174221744', 'Importado desde usuarios_combinados.xlsx | Usuario: Nehemias2014 | Clave: nemo0314 | Credito: 17 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(140, 'CLM211', '59171462370', 'Importado desde usuarios_combinados.xlsx | Usuario: Mgtvplusbo193 | Clave: mgtv1348 | Credito: 40 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/n8wfax3vdbu3', '2026-02-24 21:44:17'),
(141, 'CLM206', '59170640577', 'Importado desde usuarios_combinados.xlsx | Usuario: Franks87 | Clave: fjyv866 | Credito: 5 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(142, 'CLM47', '70834111', 'Importado desde usuarios_combinados.xlsx | Usuario: sandram115 | Clave: fjtv1434 | Credito: 41 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/jv3hin0l9i52', '2026-02-24 21:44:17'),
(143, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Fjtvgh176 | Clave: fjtv1105 | Credito: 4 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(144, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Fjtvgh179 | Clave: fjtv1029 | Credito: 2 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(145, 'CLM121', '59178513398', 'Importado desde usuarios_combinados.xlsx | Usuario: BrayanR875 | Clave: Lia2015 | Credito: 16 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(146, 'CLM114', '76220056', 'Importado desde usuarios_combinados.xlsx | Usuario: akf75 | Clave: mgtv1026 | Credito: 23 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(147, 'Clm', '59170415924', 'Importado desde usuarios_combinados.xlsx | Usuario: Fjtvgh181 | Clave: fjtv1024 | Credito: 1 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(148, 'CLM124', '79452752', 'Importado desde usuarios_combinados.xlsx | Usuario: Caroline2412 | Clave: mgyv1954 | Credito: 21 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(149, 'CLM163', '59177400183', 'Importado desde usuarios_combinados.xlsx | Usuario: pablocachi1234 | Clave: pablo1234 | Credito: 10 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(150, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Fltv7890 | Clave: fjtv007r | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(151, 'CLM207', '59169364339', 'Importado desde usuarios_combinados.xlsx | Usuario: JoseA75 | Clave: fjtv1459 | Credito: 7 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(152, 'CLM163', '59177400183', 'Importado desde usuarios_combinados.xlsx | Usuario: vickycampos2003 | Clave: 9876542003 | Credito: 16 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(153, 'CLM125', '72202331', 'Importado desde usuarios_combinados.xlsx | Usuario: AmnerCh | Clave: mgtv2125 | Credito: 18 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(154, 'CLM207', '59167551271', 'Importado desde usuarios_combinados.xlsx | Usuario: RoldanH | Clave: rold4795 | Credito: 13 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(155, 'Yo', '79625801', 'Importado desde usuarios_combinados.xlsx | Usuario: ghostbh | Clave: caiman | Credito: 43 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/bqh8o0wopod2', '2026-02-24 21:44:17'),
(156, 'CLM112', '72649663', 'Importado desde usuarios_combinados.xlsx | Usuario: Mgtvplusbo501 | Clave: vcg12345 | Credito: 37 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(157, 'CLM21', '63723171', 'Importado desde usuarios_combinados.xlsx | Usuario: OvidioMB | Clave: usuario172 | Credito: 42 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/pzwpuko737zk', '2026-02-24 21:44:17'),
(158, 'CLM177', '59175963646', 'Importado desde usuarios_combinados.xlsx | Usuario: JonCris | Clave: fltv958 | Credito: 17 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(159, 'CLM153', '59173529142', 'Importado desde usuarios_combinados.xlsx | Usuario: Brayan2945 | Clave: fjtv1220 | Credito: 10 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(160, 'CLM81', '77364727', 'Importado desde usuarios_combinados.xlsx | Usuario: EloyManjares | Clave: conejo22 | Credito: 31 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(161, 'CL0', '78108323', 'Importado desde usuarios_combinados.xlsx | Usuario: jorgeh104 | Clave: 6365946 | Credito: 46 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/dxigcmf2478r', '2026-02-24 21:44:17'),
(162, 'CLM208', '59163741673', 'Importado desde usuarios_combinados.xlsx | Usuario: Juan6713bedrega | Clave: 1758fjtv | Credito: 7 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(163, 'CLM40', '70764330', 'Importado desde usuarios_combinados.xlsx | Usuario: gustavo187 | Clave: tavin87 | Credito: 41 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/dlcqf40p4l9q', '2026-02-24 21:44:17'),
(164, 'CLM209', '59171045939', 'Importado desde usuarios_combinados.xlsx | Usuario: Paula1100 | Clave: fjtv186 | Credito: 7 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(165, 'CLM161', '59176647361', 'Importado desde usuarios_combinados.xlsx | Usuario: Andresss43 | Clave: fjtv1645 | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(166, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: JhonLu76 | Clave: fjtv2755 | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(167, 'CLM158', '59161381559', 'Importado desde usuarios_combinados.xlsx | Usuario: Hernan864 | Clave: 4339sc | Credito: 17 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(168, 'CLM210', '59175850088', 'Importado desde usuarios_combinados.xlsx | Usuario: Toshi2025 | Clave: toshito007 | Credito: 11 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(169, 'CLM', '59175570011', 'Importado desde usuarios_combinados.xlsx | Usuario: Mgtvgh925 | Clave: mgtv1056 | Credito: 16 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(170, 'CLM211', '59175907134', 'Importado desde usuarios_combinados.xlsx | Usuario: Ademar2024 | Clave: mgtv1985 | Credito: 24 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(171, 'CLM164', '59173602215', 'Importado desde usuarios_combinados.xlsx | Usuario: Fbismarck | Clave: candy123 | Credito: 15 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(172, 'CLM159', '70691669', 'Importado desde usuarios_combinados.xlsx | Usuario: Jcalderon107 | Clave: lotuspush666 | Credito: 18 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(173, 'CLM85', '67026226', 'Importado desde usuarios_combinados.xlsx | Usuario: Vania197 | Clave: vania1004 | Credito: 33 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(174, 'CLM23', '70744358', 'Importado desde usuarios_combinados.xlsx | Usuario: Dego | Clave: 4199338 | Credito: 43 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/6c8y6e1zgpzf', '2026-02-24 21:44:17'),
(175, 'CLM175', '59169996578', 'Importado desde usuarios_combinados.xlsx | Usuario: Carmencita85 | Clave: Carmencita36 | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(176, 'CLM133', '78954277', 'Importado desde usuarios_combinados.xlsx | Usuario: Dabarca | Clave: Dobby2024 | Credito: 27 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(177, 'CLM41', '79308067', 'Importado desde usuarios_combinados.xlsx | Usuario: AlisonSR | Clave: mgtv1829 | Credito: 42 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/aqefbms9rae6', '2026-02-24 21:44:17'),
(178, 'CLM212', '59176626221', 'Importado desde usuarios_combinados.xlsx | Usuario: Elluz64 | Clave: emvc2018 | Credito: 16 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(179, 'CLM175', '59169996578', 'Importado desde usuarios_combinados.xlsx | Usuario: Josesitos | Clave: Josesitos5 | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(180, 'CLM51', '69840476', 'Importado desde usuarios_combinados.xlsx | Usuario: Edson02023 | Clave: mgtv2014 | Credito: 42 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(181, 'CLM52', '70308940', 'Importado desde usuarios_combinados.xlsx | Usuario: ArielG186 | Clave: lluviax2 | Credito: 39 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(182, 'CLM89', '59179734109', 'Importado desde usuarios_combinados.xlsx | Usuario: Andreslr | Clave: mgtv1630 | Credito: 28 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(183, 'CLM213', '59178954277', 'Importado desde usuarios_combinados.xlsx | Usuario: Irisabarca | Clave: Milu2025 | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(184, 'CLM214', '59172737927', 'Importado desde usuarios_combinados.xlsx | Usuario: Elbac | Clave: edc7935 | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(185, 'CLM59', '59177990990', 'Importado desde usuarios_combinados.xlsx | Usuario: Dhaflujo | Clave: h251016ft | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(186, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Mateo2945 | Clave: fjtv2036 | Credito: 7 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(187, 'CLM37', '70418842', 'Importado desde usuarios_combinados.xlsx | Usuario: AbastoflorF | Clave: mgtv2015 | Credito: 43 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/uotne6mms5rn', '2026-02-24 21:44:17'),
(188, 'CLM105', '59177832179', 'Importado desde usuarios_combinados.xlsx | Usuario: Jessica1212 | Clave: 121294J | Credito: 22 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(189, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Caneltina | Clave: fjtv858 | Credito: 9 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(190, 'CLM123', '77487975', 'Importado desde usuarios_combinados.xlsx | Usuario: Rrossmar81 | Clave: mgtv1750 | Credito: 24 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(191, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Fjtvgh174 | Clave: fjtv1423 | Credito: 10 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(192, 'CLM5', '70648578', 'Importado desde usuarios_combinados.xlsx | Usuario: juanaugusto75 | Clave: roman10DIEZ | Credito: 45 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/u8jxfwuopyk6', '2026-02-24 21:44:17'),
(193, 'CLM167', '59176050638', 'Importado desde usuarios_combinados.xlsx | Usuario: Andres2405 | Clave: 2025RC | Credito: 19 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(194, 'CLM44', '60406041', 'Importado desde usuarios_combinados.xlsx | Usuario: Jannette | Clave: mgtv1646 | Credito: 43 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/ngwg9ggjbw81', '2026-02-24 21:44:17'),
(195, 'CLM27', '78584562', 'Importado desde usuarios_combinados.xlsx | Usuario: MiguelFerrufino | Clave: 12345678 | Credito: 44 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/aqqf2kqogk59', '2026-02-24 21:44:17'),
(196, 'CLM54', '59172481772', 'Importado desde usuarios_combinados.xlsx | Usuario: Harold195 | Clave: mgtv1143 | Credito: 36 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/n6r4ai81oots', '2026-02-24 21:44:17'),
(197, 'CLM70', '65108575', 'Importado desde usuarios_combinados.xlsx | Usuario: MarcioG25 | Clave: STERCIO8 | Credito: 39 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(198, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: magisgt102 | Clave: salazar1122 | Credito: 35 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(199, 'CLM135', '68640161', 'Importado desde usuarios_combinados.xlsx | Usuario: Gato4924 | Clave: lariat1033 | Credito: 28 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(200, 'CLM136', '68118183', 'Importado desde usuarios_combinados.xlsx | Usuario: Taveras | Clave: huevos | Credito: 21 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(201, 'CLM137', '76136870', 'Importado desde usuarios_combinados.xlsx | Usuario: Omarll07 | Clave: mgtb845 | Credito: 31 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(202, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Gabysil | Clave: Jade2015 | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(203, 'CLM138', '74327387', 'Importado desde usuarios_combinados.xlsx | Usuario: camperoj1507 | Clave: andre2025 | Credito: 21 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(204, 'CLM139', '77260895', 'Importado desde usuarios_combinados.xlsx | Usuario: PachecoRiva123 | Clave: RickHunter2706 | Credito: 24 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(205, 'CLM142', '77775563', 'Importado desde usuarios_combinados.xlsx | Usuario: IvanT28 | Clave: 3T4TP10TT | Credito: 31 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(206, 'CLM173', '59173190643', 'Importado desde usuarios_combinados.xlsx | Usuario: VladimirM65 | Clave: fjtv2876 | Credito: 7 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(207, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Fjtvgh172 | Clave: fjtb2202 | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(208, 'CLM132', '71165025', 'Importado desde usuarios_combinados.xlsx | Usuario: Raul2945 | Clave: mgtv1655 | Credito: 25 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(209, 'CLM117', '70806193', 'Importado desde usuarios_combinados.xlsx | Usuario: Richardo47 | Clave: mgtv1415 | Credito: 32 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(210, 'CLM13', '73979035', 'Importado desde usuarios_combinados.xlsx | Usuario: LUISG151 | Clave: 08011987 | Credito: 46 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/j7ujlt5i59sr', '2026-02-24 21:44:17'),
(211, 'CLM169', '59162654483', 'Importado desde usuarios_combinados.xlsx | Usuario: Miguel11099 | Clave: Ross57 | Credito: 21 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(212, 'CLM149', '73676180', 'Importado desde usuarios_combinados.xlsx | Usuario: Carla175 | Clave: mgtv1936 | Credito: 44 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/lu22d9qsjq75', '2026-02-24 21:44:17'),
(213, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Fjtvgh201 | Clave: 511158 | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(214, 'CLM116', '73144265', 'Importado desde usuarios_combinados.xlsx | Usuario: DanielQ25 | Clave: mgtv1233 | Credito: 43 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(215, 'CLM215', '59176906200', 'Importado desde usuarios_combinados.xlsx | Usuario: Waltru75 | Clave: fjtv1970 | Credito: 16 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(216, 'CLM3', '59173054483', 'Importado desde usuarios_combinados.xlsx | Usuario: DACA25 | Clave: walter92 | Credito: 10 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(217, 'CLM66', '59175545062', 'Importado desde usuarios_combinados.xlsx | Usuario: Lisett32 | Clave: mgtv2305 | Credito: 45 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(218, 'CLM157', '59176691837', 'Importado desde usuarios_combinados.xlsx | Usuario: Fernando7669183 | Clave: fjtv1334 | Credito: 7 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(219, 'CLM140', '77293034', 'Importado desde usuarios_combinados.xlsx | Usuario: IvaZoeSah | Clave: mgtv735 | Credito: 33 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(220, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Erlan1100 | Clave: smjm1422 | Credito: 7 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(221, 'CLM77', '73428416', 'Importado desde usuarios_combinados.xlsx | Usuario: Rey21 | Clave: Lugarey81 | Credito: 45 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(222, 'CLM159', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Mgtvgh908 | Clave: mgtv2217 | Credito: 28 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(223, 'CLM19', '69179565', 'Importado desde usuarios_combinados.xlsx | Usuario: augustogs | Clave: 123456 | Credito: 48 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/085qvqxn72bs', '2026-02-24 21:44:17'),
(224, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Mgtvgh907 | Clave: mgtv216 | Credito: 28 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(225, 'CLM78', '77130635', 'Importado desde usuarios_combinados.xlsx | Usuario: FERNANDAGV | Clave: 081401 | Credito: 45 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(226, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Fjtvgh182 | Clave: fjtv1331 | Credito: 7 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(227, 'CLM141', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Gatico0330 | Clave: Camilo0330 | Credito: 27 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(228, 'CLM146', '71337914', 'Importado desde usuarios_combinados.xlsx | Usuario: QUEZADAE | Clave: mgtv212 | Credito: 28 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(229, 'CLM147', '76063835', 'Importado desde usuarios_combinados.xlsx | Usuario: bmoreno287 | Clave: Vitaminac0 | Credito: 29 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(230, 'CLM148', '68366158', 'Importado desde usuarios_combinados.xlsx | Usuario: ARIELHC48 | Clave: 68366158 | Credito: 37 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(231, 'CLM216', '59160550556', 'Importado desde usuarios_combinados.xlsx | Usuario: Jtorrico54 | Clave: 71566351 | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(232, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Fjtvbo2945 | Clave: fjtv2386 | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(233, 'CLM104', '72007784', 'Importado desde usuarios_combinados.xlsx | Usuario: Carlos282 | Clave: mgtv1331 | Credito: 50 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(234, 'CLM165', '59160746626', 'Importado desde usuarios_combinados.xlsx | Usuario: CamAgus53 | Clave: fjtv1203 | Credito: 27 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(235, 'CLM59', '59177990990', 'Importado desde usuarios_combinados.xlsx | Usuario: OctavioH75 | Clave: Norka52 | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(236, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Fjtvgh156 | Clave: fjtv111 | Credito: 28 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(237, 'Erick287', '', 'Importado desde 1.xlsx (2026-03-02)', '2026-03-02 16:34:01'),
(238, 'abryan113', '', 'Importado desde 1.xlsx (2026-03-02)', '2026-03-02 16:34:01'),
(239, '', '59169501244', NULL, '2026-03-05 06:30:49'),
(240, '', '59172888813', NULL, '2026-03-05 06:36:47'),
(241, '', '59167714266', NULL, '2026-03-05 06:38:00'),
(242, '', '59175587574', NULL, '2026-03-05 06:39:11'),
(243, '', '59171327361', NULL, '2026-03-05 06:39:59'),
(244, '', '59167735423', NULL, '2026-03-05 06:41:29'),
(245, '', '59177233177', NULL, '2026-04-06 10:25:33'),
(246, 'CLM14', '59167324920', NULL, '2026-04-08 12:30:59'),
(247, 'Fjtvgh1047', '', 'Importado desde 1777135850657.csv - Flujo TV por dispositivos', '2026-04-25 13:10:50'),
(248, 'Fjtvcg1031', '', 'Importado desde 1777135850657.csv - Flujo TV por dispositivos', '2026-04-25 13:10:50'),
(249, 'Fjtvgh1046', '', 'Importado desde 1777135850657.csv - Flujo TV por dispositivos', '2026-04-25 13:10:50'),
(250, 'Fjtvgh1045', '', 'Importado desde 1777135850657.csv - Flujo TV por dispositivos', '2026-04-25 13:10:50'),
(251, 'Fjtvgh1044', '', 'Importado desde 1777135850657.csv - Flujo TV por dispositivos', '2026-04-25 13:10:50'),
(252, 'Fjtvgh1043', '', 'Importado desde 1777135850657.csv - Flujo TV por dispositivos', '2026-04-25 13:10:50'),
(253, 'Fjtvgh1042', '', 'Importado desde 1777135850657.csv - Flujo TV por dispositivos', '2026-04-25 13:10:50'),
(254, 'Fjtvcg1030', '', 'Importado desde 1777135850657.csv - Flujo TV por dispositivos', '2026-04-25 13:10:50'),
(255, 'Fjtvcg1029', '', 'Importado desde 1777135850657.csv - Flujo TV por dispositivos', '2026-04-25 13:10:50'),
(256, 'Fjtvcg1028', '', 'Importado desde 1777135850657.csv - Flujo TV por dispositivos', '2026-04-25 13:10:50'),
(257, 'Fjtvgh1041', '', 'Importado desde 1777135850657.csv - Flujo TV por dispositivos', '2026-04-25 13:10:50'),
(258, 'Fjtvgh1040', '', 'Importado desde 1777135850657.csv - Flujo TV por dispositivos', '2026-04-25 13:10:50'),
(259, 'Fjtvcg1027', '', 'Importado desde 1777135850657.csv - Flujo TV por dispositivos', '2026-04-25 13:10:50'),
(260, 'Fjtvcg1026', '', 'Importado desde 1777135850657.csv - Flujo TV por dispositivos', '2026-04-25 13:10:50'),
(261, 'Fjtvcg1025', '', 'Importado desde 1777135850657.csv - Flujo TV por dispositivos', '2026-04-25 13:10:50'),
(262, 'Fjtvcg1024', '', 'Importado desde 1777135850657.csv - Flujo TV por dispositivos', '2026-04-25 13:10:50'),
(263, 'Fjtvcg1023', '', 'Importado desde 1777135850657.csv - Flujo TV por dispositivos', '2026-04-25 13:10:50'),
(264, 'Fjtvcg1022', '', 'Importado desde 1777135850657.csv - Flujo TV por dispositivos', '2026-04-25 13:10:50'),
(265, 'Fjtvcg1021', '', 'Importado desde 1777135850657.csv - Flujo TV por dispositivos', '2026-04-25 13:10:50'),
(266, 'Fjtvcg1020', '', 'Importado desde 1777135850657.csv - Flujo TV por dispositivos', '2026-04-25 13:10:50'),
(267, 'Fjtvcg1019', '', 'Importado desde 1777135850657.csv - Flujo TV por dispositivos', '2026-04-25 13:10:50'),
(268, 'Fjtvcg1018', '', 'Importado desde 1777135850657.csv - Flujo TV por dispositivos', '2026-04-25 13:10:50'),
(269, 'Fjtvcg1017', '', 'Importado desde 1777135850657.csv - Flujo TV por dispositivos', '2026-04-25 13:10:50'),
(270, 'Fjtvcg957', '', 'Importado desde 1777135850657.csv - Flujo TV por dispositivos', '2026-04-25 13:10:50'),
(271, 'AstridA20', '', 'Importado desde 1777135850657.csv - Flujo TV por dispositivos', '2026-04-25 13:10:50'),
(272, 'Mgtvlg839', '', 'Importado desde 1777135850657.csv - Flujo TV por dispositivos', '2026-04-25 13:10:50'),
(273, 'Fanola', '', 'Importado desde us cc.csv - Flujo TV cuenta completa', '2026-04-25 13:26:52'),
(274, 'Victort1979', '', 'Importado desde us cc.csv - Flujo TV cuenta completa', '2026-04-25 13:26:52'),
(275, 'Fjtvgh185', '', 'Importado desde us cc.csv - Flujo TV cuenta completa', '2026-04-25 13:26:53'),
(276, 'Fjtvgh184', '', 'Importado desde us cc.csv - Flujo TV cuenta completa', '2026-04-25 13:26:53'),
(277, 'Fjtvgh183', '', 'Importado desde us cc.csv - Flujo TV cuenta completa', '2026-04-25 13:26:53');

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
  `costo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `modalidades`
--

INSERT INTO `modalidades` (`id`, `plataforma_id`, `nombre_modalidad`, `tipo_cuenta`, `duracion_meses`, `dispositivos`, `precio`, `costo`, `created_at`) VALUES
(3, 4, 'Cuenta completa', 'CUENTA_COMPLETA', 1, NULL, 35.00, 18.00, '2026-02-24 21:41:10'),
(4, 4, 'Cuenta completa', 'CUENTA_COMPLETA', 3, NULL, 100.00, 54.00, '2026-02-25 06:56:20'),
(5, 4, 'Cuenta completa', 'CUENTA_COMPLETA', 7, NULL, 210.00, 108.00, '2026-02-25 06:56:20'),
(7, 4, 'Cuenta completa', 'CUENTA_COMPLETA', 14, NULL, 420.00, 216.00, '2026-02-25 07:01:55'),
(8, 6, '3 dispositivos', 'POR_DISPOSITIVOS', 1, 3, 35.00, 18.00, '2026-02-25 07:09:26'),
(9, 6, '3 dispositivos', 'POR_DISPOSITIVOS', 3, 3, 100.00, 54.00, '2026-02-25 07:09:26'),
(10, 6, '3 dispositivos', 'POR_DISPOSITIVOS', 7, 3, 210.00, 108.00, '2026-02-25 07:09:26'),
(11, 7, 'Cuenta completa', 'CUENTA_COMPLETA', 1, NULL, 1.00, 1.00, '2026-02-25 07:19:01'),
(12, 6, '1 dispositivo', 'POR_DISPOSITIVOS', 1, 1, 15.00, 6.00, '2026-03-05 06:03:41'),
(13, 6, '1 dispositivo', 'POR_DISPOSITIVOS', 3, 1, 40.00, 18.00, '2026-03-05 06:03:41'),
(14, 6, '1 dispositivo', 'POR_DISPOSITIVOS', 7, 1, 90.00, 36.00, '2026-03-05 06:03:41'),
(15, 6, '2 dispositivos', 'POR_DISPOSITIVOS', 1, 2, 25.00, 12.00, '2026-03-05 06:03:41'),
(16, 6, '2 dispositivos', 'POR_DISPOSITIVOS', 3, 2, 70.00, 36.00, '2026-03-05 06:03:41'),
(17, 6, '2 dispositivos', 'POR_DISPOSITIVOS', 7, 2, 150.00, 72.00, '2026-03-05 06:03:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos`
--

CREATE TABLE `movimientos` (
  `id` int(11) NOT NULL,
  `suscripcion_id` int(11) DEFAULT NULL,
  `plataforma_id` int(11) DEFAULT NULL,
  `plataforma_nombre` varchar(100) DEFAULT NULL,
  `tipo` enum('RENOVACION') DEFAULT 'RENOVACION',
  `meses` int(11) NOT NULL,
  `monto` decimal(10,2) DEFAULT NULL,
  `costo` decimal(10,2) DEFAULT NULL,
  `utilidad` decimal(10,2) DEFAULT NULL,
  `fecha` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `movimientos`
--

INSERT INTO `movimientos` (`id`, `suscripcion_id`, `plataforma_id`, `plataforma_nombre`, `tipo`, `meses`, `monto`, `costo`, `utilidad`, `fecha`) VALUES
(2, 140, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 1, 23.00, 1.00, 22.00, '2026-03-02 15:00:16'),
(3, 137, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 1, 21.00, 1.00, 20.00, '2026-03-03 06:55:25'),
(4, 129, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 1, 22.00, 1.00, 21.00, '2026-03-03 07:14:51'),
(5, 141, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 1, 16.00, 1.00, 15.00, '2026-03-03 07:19:24'),
(6, 143, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 3, 3.00, 1.00, 2.00, '2026-03-03 07:40:11'),
(7, 142, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 3, 21.00, 1.00, 20.00, '2026-03-03 07:41:01'),
(8, 139, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 3, 42.00, 1.00, 41.00, '2026-03-03 10:06:07'),
(9, 149, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 3, 40.00, 1.00, 39.00, '2026-03-07 11:50:36'),
(10, 146, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 1, 41.00, 1.00, 40.00, '2026-03-07 11:53:59'),
(11, 148, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 3, 17.00, 1.00, 16.00, '2026-03-08 11:11:26'),
(12, 135, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 1, 19.00, 1.00, 18.00, '2026-03-08 13:17:02'),
(13, 145, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 7, 14.00, 1.00, 13.00, '2026-03-08 14:40:20'),
(14, 155, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 1, 23.00, 1.00, 22.00, '2026-03-14 10:59:27'),
(15, 156, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 1, 1.00, 1.00, 0.00, '2026-03-16 21:36:47'),
(16, 157, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 1, 21.00, 1.00, 20.00, '2026-03-20 14:38:10'),
(17, 164, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 1, 43.00, 1.00, 42.00, '2026-03-22 10:54:40'),
(18, 160, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 1, 7.00, 1.00, 6.00, '2026-03-22 10:57:07'),
(19, 165, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 1, 37.00, 1.00, 36.00, '2026-03-22 10:59:59'),
(20, 162, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 3, 18.00, 1.00, 17.00, '2026-03-24 14:20:03'),
(21, 166, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 7, 42.00, 1.00, 41.00, '2026-03-24 14:24:19'),
(22, 128, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 3, 24.00, 1.00, 23.00, '2026-04-04 07:25:37'),
(23, 137, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 1, 21.00, 1.00, 20.00, '2026-04-04 07:53:03'),
(24, 140, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 1, 23.00, 1.00, 22.00, '2026-04-04 07:53:56'),
(25, 129, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 3, 100.00, 54.00, 46.00, '2026-04-04 07:55:27'),
(26, 171, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 3, 100.00, 54.00, 46.00, '2026-04-06 10:32:15'),
(27, 141, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 1, 16.00, 1.00, 15.00, '2026-04-06 10:33:22'),
(28, 172, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 7, 210.00, 108.00, 102.00, '2026-04-07 10:59:08'),
(29, 135, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 1, 19.00, 1.00, 18.00, '2026-04-07 13:52:00'),
(30, 146, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 1, 41.00, 1.00, 40.00, '2026-04-08 09:42:20'),
(31, 173, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 7, 210.00, 108.00, 102.00, '2026-04-10 11:31:38'),
(32, 147, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 1, 33.00, 1.00, 32.00, '2026-04-11 10:54:29'),
(33, 177, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 3, 100.00, 54.00, 46.00, '2026-04-13 10:27:29'),
(34, 155, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 1, 23.00, 1.00, 22.00, '2026-04-13 11:10:50'),
(35, 178, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 3, 100.00, 54.00, 46.00, '2026-04-14 10:26:06'),
(36, 176, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 3, 100.00, 54.00, 46.00, '2026-04-14 17:12:30'),
(37, 157, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 1, 21.00, 1.00, 20.00, '2026-04-21 10:13:19'),
(38, 180, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 3, 100.00, 54.00, 46.00, '2026-04-21 10:26:02'),
(39, 160, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 1, 7.00, 1.00, 6.00, '2026-04-21 16:26:02'),
(40, 182, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 3, 100.00, 54.00, 46.00, '2026-04-21 16:26:25'),
(41, 165, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 1, 37.00, 1.00, 36.00, '2026-04-22 11:40:44'),
(42, 164, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 1, 43.00, 1.00, 42.00, '2026-04-22 11:40:52'),
(43, 132, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 1, 16.00, 1.00, 15.00, '2026-04-25 10:24:30'),
(44, 185, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 7, 210.00, 108.00, 102.00, '2026-04-25 10:25:52'),
(45, 183, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 7, 210.00, 108.00, 102.00, '2026-04-25 12:23:58'),
(46, 186, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 1, 42.00, 1.00, 41.00, '2026-04-28 16:59:14'),
(47, 168, 4, 'Flujo TV (Cuenta Completa)', 'RENOVACION', 1, 10.00, 1.00, 9.00, '2026-04-28 17:00:22');

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
(4, 'Flujo TV (Cuenta Completa)', 'RENOVABLE', '1,3,7,14', 'USUARIO', '🌟 *Buen día soy su proveedor del servicio FlujoTV.*  \r\nLe informo que su suscripción a *FlujoTV* vence en *3 días* 📅  \r\nPara evitar la suspensión del servicio y garantizar continuidad en el acceso a sus contenidos 📺, puede realizar su renovación con anticipación.  \r\n🎟️ *Planes disponibles:*  \r\n✅ *1 mes* → 35 Bs  \r\n✅ *3 meses* → 100 Bs  \r\n✅ *6 meses* → 210 Bs + *1 mes GRATIS*  \r\n✅ *12 meses* → 420 Bs + *2 meses GRATIS*  \r\n💳 *Métodos de pago:* QR, Yape o Tigo Money  \r\n📩 Por favor, indíqueme el plan de su preferencia para proceder con la activación correspondiente.', '🔔 *Buen día.*  \r\nLe informo que su suscripción a *FlujoTV* vence el día de hoy 📅  \r\nPara evitar la suspensión del servicio y la interrupción del acceso a los contenidos 📺, le recomiendo realizar la renovación dentro del día.  \r\n🎟️ *Planes disponibles:*  \r\n✅ *1 mes* → 35 Bs  \r\n✅ *3 meses* → 100 Bs  \r\n✅ *6 meses* → 210 Bs + *1 mes GRATIS*  \r\n✅ *12 meses* → 420 Bs + *2 meses GRATIS*  \r\n💳 *Métodos de pago:* QR, Yape o Tigo Money  \r\n📩 Por favor, indíqueme el plan de su preferencia para proceder con la activación inmediata.', '🔔 *Buen día.*  \r\nSu suscripción a *FlujoTV* se encuentra suspendida desde hace 3 días 📅  \r\nPara facilitar su reactivación, hemos habilitado un *beneficio especial válido solo por hoy*:  \r\n🎁 *10% de descuento* en los planes de 1 y 3 meses.  \r\n🎟️ *Planes disponibles:*  \r\n✅ *1 mes* → 31.5 Bs (antes 35 Bs)  \r\n✅ *3 meses* → 90 Bs (antes 100 Bs)  \r\n🔥 *6 meses* → 210 Bs + *1 mes GRATIS*  \r\n🔥 *12 meses* → 420 Bs + *2 meses GRATIS*  \r\n💳 *Métodos de pago:* QR, Yape o Tigo Money  \r\n📩 Indíqueme el plan de su preferencia para proceder con la activación inmediata.', '🔔 *Buen día.*  \r\nSu suscripción a *FlujoTV* se encuentra suspendida desde hace 3 días 📅  \r\nPara facilitar su reactivación, hemos habilitado un *beneficio especial válido solo por hoy*:  \r\n🎁 *10% de descuento* en los planes de 1 y 3 meses.  \r\n🎟️ *Planes disponibles:*  \r\n✅ *1 mes* → 31.5 Bs (antes 35 Bs)  \r\n✅ *3 meses* → 90 Bs (antes 100 Bs)  \r\n🔥 *6 meses* → 210 Bs + *1 mes GRATIS*  \r\n🔥 *12 meses* → 420 Bs + *2 meses GRATIS*  \r\n💳 *Métodos de pago:* QR, Yape o Tigo Money  \r\n📩 Indíqueme el plan de su preferencia para proceder con la activación inmediata.', '2026-02-24 21:41:10'),
(6, 'Flujo TV (Por dispositivos)', 'DESECHABLE', '1,3,7', 'NO_APLICA', '¡Hola, buen día! 🌟\r\nSoy tu proveedor de FlujoTV y tengo excelentes noticias para ti. 🎉\r\nSi deseas ampliar el tiempo de tu suscripción, aquí tienes nuestras promociones:\r\n✅ 1 Dispositivo :\r\n-1 Mes 15 Bs\r\n-3 Meses 40 Bs\r\n-6 Meses+1 mes gratis 90 Bs\r\n✅ 2 Dispositivos :\r\n-1 Mes 25 Bs\r\n-3 Meses 70 Bs\r\n-6 Meses +1 mes gratis 150 Bs\r\n✅ 3 Dispositivos :\r\n-1 Mes 35 Bs\r\n-3 Meses 100 Bs\r\n-6 Meses +1 mes gratis 210 Bs\r\n💳 Puedes realizar el pago a través de:\r\nPago Simple QR, Yape, Tigo Money\r\nEscríbeme para más detalles o confirmar tu renovación. ¡No te quedes sin disfrutar de tus series y películas favoritas! 🎬✨', '¡Hola, buen día! 🌟\r\nSoy tu proveedor de FlujoTV y tengo excelentes noticias para ti. 🎉\r\nSi deseas ampliar el tiempo de tu suscripción, aquí tienes nuestras promociones:\r\n✅ 1 Dispositivo :\r\n-1 Mes 15 Bs\r\n-3 Meses 40 Bs\r\n-6 Meses+1 mes gratis 90 Bs\r\n✅ 2 Dispositivos :\r\n-1 Mes 25 Bs\r\n-3 Meses 70 Bs\r\n-6 Meses +1 mes gratis 150 Bs\r\n✅ 3 Dispositivos :\r\n-1 Mes 35 Bs\r\n-3 Meses 100 Bs\r\n-6 Meses +1 mes gratis 210 Bs\r\n💳 Puedes realizar el pago a través de:\r\nPago Simple QR, Yape, Tigo Money\r\nEscríbeme para más detalles o confirmar tu renovación. ¡No te quedes sin disfrutar de tus series y películas favoritas! 🎬✨', NULL, NULL, '2026-02-25 07:06:16'),
(7, 'Netflix', 'DESECHABLE', '1', 'NO_APLICA', NULL, NULL, NULL, NULL, '2026-02-25 07:19:01');

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
  `costo_base` decimal(10,2) DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `estado` enum('CONTACTAR_2D','REENVIAR_1D','ESPERA','ACTIVO','VENCIDO','RECUP') DEFAULT 'ACTIVO',
  `ultimo_contacto_fecha` datetime DEFAULT NULL,
  `ultimo_contacto_tipo` enum('MENOS_2','MENOS_1','REC_7','REC_15') DEFAULT NULL,
  `usuario_proveedor` varchar(150) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `flag_no_renovo` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `suscripciones`
--

INSERT INTO `suscripciones` (`id`, `cliente_id`, `plataforma_id`, `modalidad_id`, `precio_venta`, `costo_base`, `fecha_inicio`, `fecha_vencimiento`, `estado`, `ultimo_contacto_fecha`, `ultimo_contacto_tipo`, `usuario_proveedor`, `notas`, `flag_no_renovo`, `created_at`) VALUES
(128, 119, 4, 3, 24.00, 1.00, '2023-12-27', '2026-05-04', 'ACTIVO', '2026-04-04 07:49:14', 'MENOS_2', 'Antonio2945', NULL, 0, '2026-02-24 21:44:17'),
(129, 120, 4, 4, 100.00, 54.00, '2024-03-26', '2026-07-04', 'ACTIVO', NULL, NULL, 'Kevinb548', NULL, 0, '2026-02-24 21:44:17'),
(130, 121, 4, 3, 1.00, 1.00, '2026-01-25', '2026-02-25', 'VENCIDO', NULL, NULL, 'Luis10450', NULL, 1, '2026-02-24 21:44:17'),
(131, 122, 4, 3, 30.00, 1.00, '2023-08-11', '2026-02-25', 'VENCIDO', NULL, NULL, 'JavierS89', NULL, 1, '2026-02-24 21:44:17'),
(132, 123, 4, 3, 16.00, 1.00, '2024-10-21', '2026-05-26', 'ACTIVO', NULL, NULL, 'TVGUTIERREZ64', NULL, 0, '2026-02-24 21:44:17'),
(133, 124, 4, 3, 7.00, 1.00, '2025-07-28', '2026-02-28', 'VENCIDO', NULL, NULL, 'CeciliaR294', NULL, 1, '2026-02-24 21:44:17'),
(134, 125, 4, 3, 7.00, 1.00, '2025-07-28', '2026-02-28', 'VENCIDO', NULL, NULL, 'Fjtvgh177', NULL, 1, '2026-02-24 21:44:17'),
(135, 126, 4, 3, 19.00, 1.00, '2024-06-12', '2026-05-08', 'ACTIVO', NULL, NULL, 'crsirpa', NULL, 0, '2026-02-24 21:44:17'),
(136, 127, 4, 3, 14.00, 1.00, '2024-12-28', '2026-03-01', 'VENCIDO', NULL, NULL, 'VladimirM64', NULL, 1, '2026-02-24 21:44:17'),
(137, 128, 4, 3, 21.00, 1.00, '2024-04-04', '2026-05-04', 'ACTIVO', NULL, NULL, 'MiguelT74', NULL, 0, '2026-02-24 21:44:17'),
(138, 129, 4, 3, 20.00, 1.00, '2024-07-02', '2026-06-03', 'ACTIVO', NULL, NULL, 'Mgtvplusbo613', NULL, 0, '2026-02-24 21:44:17'),
(139, 130, 4, 3, 42.00, 1.00, '2022-08-30', '2026-06-04', 'ACTIVO', NULL, NULL, 'javier7762', NULL, 0, '2026-02-24 21:44:17'),
(140, 131, 4, 3, 23.00, 1.00, '2024-03-27', '2026-05-04', 'ACTIVO', NULL, NULL, 'Jimenam24', NULL, 0, '2026-02-24 21:44:17'),
(141, 132, 4, 3, 16.00, 1.00, '2024-11-04', '2026-05-04', 'ACTIVO', NULL, NULL, 'NinoskaClavel5', NULL, 0, '2026-02-24 21:44:17'),
(142, 133, 4, 3, 21.00, 1.00, '2024-06-04', '2026-06-04', 'ACTIVO', NULL, NULL, 'ArielD131', NULL, 0, '2026-02-24 21:44:17'),
(143, 134, 4, 3, 3.00, 1.00, '2025-12-05', '2026-06-05', 'ACTIVO', NULL, NULL, 'AlejandraT85', NULL, 0, '2026-02-24 21:44:17'),
(144, 135, 4, 3, 6.00, 1.00, '2025-09-05', '2026-06-05', 'ACTIVO', NULL, NULL, 'NAVAJASITURRE', NULL, 0, '2026-02-24 21:44:17'),
(145, 136, 4, 3, 14.00, 1.00, '2025-01-06', '2026-10-08', 'ACTIVO', NULL, NULL, 'Severiche64', NULL, 0, '2026-02-24 21:44:17'),
(146, 137, 4, 3, 41.00, 1.00, '2022-09-07', '2026-05-08', 'ACTIVO', NULL, NULL, 'Ber_Rios', NULL, 0, '2026-02-24 21:44:17'),
(147, 138, 4, 3, 33.00, 1.00, '2023-03-02', '2026-05-11', 'ACTIVO', NULL, NULL, 'Mgtvplusbo211', NULL, 0, '2026-02-24 21:44:17'),
(148, 139, 4, 3, 17.00, 1.00, '2024-03-19', '2026-06-08', 'ACTIVO', NULL, NULL, 'Nehemias2014', NULL, 0, '2026-02-24 21:44:17'),
(149, 140, 4, 3, 40.00, 1.00, '2022-10-22', '2026-06-08', 'ACTIVO', NULL, NULL, 'Mgtvplusbo193', NULL, 0, '2026-02-24 21:44:17'),
(150, 141, 4, 3, 5.00, 1.00, '2025-10-08', '2026-03-09', 'VENCIDO', '2026-03-07 07:54:07', 'MENOS_2', 'Franks87', NULL, 1, '2026-02-24 21:44:17'),
(151, 142, 4, 3, 41.00, 1.00, '2022-08-18', '2026-03-10', 'VENCIDO', '2026-03-10 07:14:25', 'MENOS_2', 'sandram115', NULL, 1, '2026-02-24 21:44:17'),
(152, 143, 4, 3, 4.00, 1.00, '2025-08-05', '2026-07-01', 'ACTIVO', NULL, NULL, 'Fjtvgh176', NULL, 0, '2026-02-24 21:44:17'),
(153, 144, 4, 3, 2.00, 1.00, '2026-01-10', '2026-03-11', 'VENCIDO', NULL, NULL, 'Fjtvgh179', NULL, 1, '2026-02-24 21:44:17'),
(154, 145, 4, 3, 16.00, 1.00, '2024-07-10', '2026-03-11', 'VENCIDO', '2026-03-13 07:27:35', 'MENOS_1', 'BrayanR875', NULL, 1, '2026-02-24 21:44:17'),
(155, 146, 4, 3, 23.00, 1.00, '2024-03-25', '2026-05-14', 'ACTIVO', NULL, NULL, 'akf75', NULL, 0, '2026-02-24 21:44:17'),
(156, 147, 4, 3, 1.00, 1.00, '2026-02-16', '2026-04-16', 'RECUP', NULL, NULL, 'Fjtvgh181', NULL, 0, '2026-02-24 21:44:17'),
(157, 148, 4, 3, 21.00, 1.00, '2024-06-15', '2026-05-21', 'ACTIVO', NULL, NULL, 'Caroline2412', NULL, 0, '2026-02-24 21:44:17'),
(158, 149, 4, 3, 10.00, 1.00, '2024-11-16', '2026-05-27', 'ACTIVO', NULL, NULL, 'pablocachi1234', NULL, 0, '2026-02-24 21:44:17'),
(159, 150, 4, 3, 14.00, 1.00, '2025-01-19', '2026-03-19', 'VENCIDO', NULL, NULL, 'Fltv7890', NULL, 1, '2026-02-24 21:44:17'),
(160, 151, 4, 3, 7.00, 1.00, '2025-08-19', '2026-05-22', 'ACTIVO', NULL, NULL, 'JoseA75', NULL, 0, '2026-02-24 21:44:17'),
(161, 152, 4, 3, 16.00, 1.00, '2024-11-15', '2026-05-22', 'ACTIVO', '2026-03-19 07:14:28', 'MENOS_2', 'vickycampos2003', NULL, 0, '2026-02-24 21:44:17'),
(162, 153, 4, 3, 18.00, 1.00, '2024-09-12', '2026-06-24', 'ACTIVO', NULL, NULL, 'AmnerCh', NULL, 0, '2026-02-24 21:44:17'),
(163, 154, 4, 3, 13.00, 1.00, '2025-02-17', '2026-03-21', 'VENCIDO', '2026-03-19 07:15:24', 'MENOS_2', 'RoldanH', NULL, 1, '2026-02-24 21:44:17'),
(164, 155, 4, 3, 43.00, 1.00, '2022-05-14', '2026-05-22', 'ACTIVO', NULL, NULL, 'ghostbh', NULL, 0, '2026-02-24 21:44:17'),
(165, 156, 4, 3, 37.00, 1.00, '2022-12-19', '2026-05-22', 'ACTIVO', NULL, NULL, 'Mgtvplusbo501', NULL, 0, '2026-02-24 21:44:17'),
(166, 157, 4, 3, 42.00, 1.00, '2022-09-21', '2026-10-22', 'ACTIVO', NULL, NULL, 'OvidioMB', NULL, 0, '2026-02-24 21:44:17'),
(167, 158, 4, 3, 17.00, 1.00, '2024-10-25', '2027-05-25', 'ACTIVO', NULL, NULL, 'JonCris', NULL, 0, '2026-02-24 21:44:17'),
(168, 159, 4, 3, 10.00, 1.00, '2025-05-10', '2026-05-28', 'ACTIVO', NULL, NULL, 'Brayan2945', NULL, 0, '2026-02-24 21:44:17'),
(169, 160, 4, 3, 31.00, 1.00, '2023-03-20', '2026-10-26', 'ACTIVO', NULL, NULL, 'EloyManjares', NULL, 0, '2026-02-24 21:44:17'),
(170, 161, 4, 3, 46.00, 1.00, '2022-05-30', '2026-10-30', 'ACTIVO', NULL, NULL, 'jorgeh104', NULL, 0, '2026-02-24 21:44:17'),
(171, 162, 4, 4, 100.00, 54.00, '2025-09-04', '2026-07-06', 'ACTIVO', NULL, NULL, 'Juan6713bedrega', NULL, 0, '2026-02-24 21:44:17'),
(172, 163, 4, 5, 210.00, 108.00, '2022-10-23', '2026-11-08', 'ACTIVO', NULL, NULL, 'gustavo187', NULL, 0, '2026-02-24 21:44:17'),
(173, 164, 4, 5, 210.00, 108.00, '2025-09-09', '2026-11-10', 'ACTIVO', NULL, NULL, 'Paula1100', NULL, 0, '2026-02-24 21:44:17'),
(174, 165, 4, 3, 14.00, 1.00, '2025-02-06', '2026-06-10', 'ACTIVO', NULL, NULL, 'Andresss43', NULL, 0, '2026-02-24 21:44:17'),
(175, 166, 4, 3, 14.00, 1.00, '2024-07-16', '2026-04-13', 'RECUP', NULL, NULL, 'JhonLu76', NULL, 0, '2026-02-24 21:44:17'),
(176, 167, 4, 4, 100.00, 54.00, '2024-10-26', '2026-07-14', 'ACTIVO', NULL, NULL, 'Hernan864', NULL, 0, '2026-02-24 21:44:17'),
(177, 168, 4, 4, 100.00, 54.00, '2025-05-10', '2026-07-15', 'ACTIVO', NULL, NULL, 'Toshi2025', NULL, 0, '2026-02-24 21:44:17'),
(178, 169, 4, 4, 100.00, 54.00, '2024-09-14', '2026-07-15', 'ACTIVO', NULL, NULL, 'Mgtvgh925', NULL, 0, '2026-02-24 21:44:17'),
(179, 170, 4, 3, 24.00, 1.00, '2024-03-30', '2026-04-18', 'RECUP', NULL, NULL, 'Ademar2024', NULL, 0, '2026-02-24 21:44:17'),
(180, 171, 4, 4, 100.00, 54.00, '2024-12-17', '2026-07-21', 'ACTIVO', NULL, NULL, 'Fbismarck', NULL, 0, '2026-02-24 21:44:17'),
(181, 172, 4, 3, 18.00, 1.00, '2024-10-19', '2026-11-24', 'ACTIVO', NULL, NULL, 'Jcalderon107', NULL, 0, '2026-02-24 21:44:17'),
(182, 173, 4, 4, 100.00, 54.00, '2023-07-22', '2026-07-22', 'ACTIVO', NULL, NULL, 'Vania197', NULL, 0, '2026-02-24 21:44:17'),
(183, 174, 4, 5, 210.00, 108.00, '2022-09-24', '2026-11-28', 'ACTIVO', NULL, NULL, 'Dego', NULL, 0, '2026-02-24 21:44:17'),
(184, 175, 4, 3, 14.00, 1.00, '2025-02-28', '2026-11-28', 'ACTIVO', NULL, NULL, 'Carmencita85', NULL, 0, '2026-02-24 21:44:17'),
(185, 176, 4, 5, 210.00, 108.00, '2024-01-27', '2026-11-28', 'ACTIVO', NULL, NULL, 'Dabarca', NULL, 0, '2026-02-24 21:44:17'),
(186, 177, 4, 3, 42.00, 1.00, '2022-10-24', '2026-05-29', 'ACTIVO', NULL, NULL, 'AlisonSR', NULL, 0, '2026-02-24 21:44:17'),
(187, 178, 4, 3, 16.00, 1.00, '2024-11-30', '2026-05-01', 'ACTIVO', NULL, NULL, 'Elluz64', NULL, 0, '2026-02-24 21:44:17'),
(188, 179, 4, 3, 14.00, 1.00, '2025-03-05', '2026-05-05', 'ACTIVO', NULL, NULL, 'Josesitos', NULL, 0, '2026-02-24 21:44:17'),
(189, 180, 4, 3, 42.00, 1.00, '2022-11-02', '2026-05-06', 'ACTIVO', NULL, NULL, 'Edson02023', NULL, 0, '2026-02-24 21:44:17'),
(190, 181, 4, 3, 39.00, 1.00, '2022-11-03', '2026-05-09', 'ACTIVO', NULL, NULL, 'ArielG186', NULL, 0, '2026-02-24 21:44:17'),
(191, 182, 4, 3, 28.00, 1.00, '2023-09-09', '2026-05-13', 'ACTIVO', NULL, NULL, 'Andreslr', NULL, 0, '2026-02-24 21:44:17'),
(192, 183, 4, 3, 14.00, 1.00, '2025-03-15', '2026-05-15', 'ACTIVO', NULL, NULL, 'Irisabarca', NULL, 0, '2026-02-24 21:44:17'),
(193, 184, 4, 3, 14.00, 1.00, '2025-03-15', '2026-05-15', 'ACTIVO', NULL, NULL, 'Elbac', NULL, 0, '2026-02-24 21:44:17'),
(194, 185, 4, 3, 14.00, 1.00, '2025-03-07', '2026-05-16', 'ACTIVO', NULL, NULL, 'Dhaflujo', NULL, 0, '2026-02-24 21:44:17'),
(195, 186, 4, 3, 7.00, 1.00, '2025-10-17', '2026-05-17', 'ACTIVO', NULL, NULL, 'Mateo2945', NULL, 0, '2026-02-24 21:44:17'),
(196, 187, 4, 3, 43.00, 1.00, '2022-10-19', '2026-05-19', 'ACTIVO', NULL, NULL, 'AbastoflorF', NULL, 0, '2026-02-24 21:44:17'),
(197, 188, 4, 3, 22.00, 1.00, '2024-07-17', '2026-05-21', 'ACTIVO', NULL, NULL, 'Jessica1212', NULL, 0, '2026-02-24 21:44:17'),
(198, 189, 4, 3, 9.00, 1.00, '2025-08-20', '2026-05-21', 'ACTIVO', NULL, NULL, 'Caneltina', NULL, 0, '2026-02-24 21:44:17'),
(199, 190, 4, 3, 24.00, 1.00, '2024-05-10', '2026-05-22', 'ACTIVO', NULL, NULL, 'Rrossmar81', NULL, 0, '2026-02-24 21:44:17'),
(200, 191, 4, 3, 10.00, 1.00, '2025-06-22', '2026-05-22', 'ACTIVO', NULL, NULL, 'Fjtvgh174', NULL, 0, '2026-02-24 21:44:17'),
(201, 192, 4, 3, 45.00, 1.00, '2022-08-22', '2026-05-22', 'ACTIVO', NULL, NULL, 'juanaugusto75', NULL, 0, '2026-02-24 21:44:17'),
(202, 193, 4, 3, 19.00, 1.00, '2024-04-09', '2026-05-29', 'ACTIVO', NULL, NULL, 'Andres2405', NULL, 0, '2026-02-24 21:44:17'),
(203, 194, 4, 3, 43.00, 1.00, '2022-10-28', '2026-05-30', 'ACTIVO', NULL, NULL, 'Jannette', NULL, 0, '2026-02-24 21:44:17'),
(204, 195, 4, 3, 44.00, 1.00, '2022-09-30', '2026-05-31', 'ACTIVO', NULL, NULL, 'MiguelFerrufino', NULL, 0, '2026-02-24 21:44:17'),
(205, 196, 4, 3, 36.00, 1.00, '2022-11-04', '2026-05-31', 'ACTIVO', NULL, NULL, 'Harold195', NULL, 0, '2026-02-24 21:44:17'),
(206, 197, 4, 3, 39.00, 1.00, '2022-11-26', '2026-06-04', 'ACTIVO', NULL, NULL, 'MarcioG25', NULL, 0, '2026-02-24 21:44:17'),
(207, 198, 4, 3, 35.00, 1.00, '2023-07-03', '2026-06-06', 'ACTIVO', NULL, NULL, 'magisgt102', NULL, 0, '2026-02-24 21:44:17'),
(208, 199, 4, 3, 28.00, 1.00, '2024-02-07', '2026-06-07', 'ACTIVO', NULL, NULL, 'Gato4924', NULL, 0, '2026-02-24 21:44:17'),
(209, 200, 4, 3, 21.00, 1.00, '2024-09-11', '2026-06-11', 'ACTIVO', NULL, NULL, 'Taveras', NULL, 0, '2026-02-24 21:44:17'),
(210, 201, 4, 3, 31.00, 1.00, '2023-11-15', '2026-06-15', 'ACTIVO', NULL, NULL, 'Omarll07', NULL, 0, '2026-02-24 21:44:17'),
(211, 202, 4, 3, 14.00, 1.00, '2025-04-16', '2026-06-16', 'ACTIVO', NULL, NULL, 'Gabysil', NULL, 0, '2026-02-24 21:44:17'),
(212, 203, 4, 3, 21.00, 1.00, '2024-09-18', '2026-06-18', 'ACTIVO', NULL, NULL, 'camperoj1507', NULL, 0, '2026-02-24 21:44:17'),
(213, 204, 4, 3, 24.00, 1.00, '2024-06-20', '2026-06-20', 'ACTIVO', NULL, NULL, 'PachecoRiva123', NULL, 0, '2026-02-24 21:44:17'),
(214, 205, 4, 3, 31.00, 1.00, '2023-11-26', '2026-06-27', 'ACTIVO', NULL, NULL, 'IvanT28', NULL, 0, '2026-02-24 21:44:17'),
(215, 206, 4, 3, 7.00, 1.00, '2025-12-03', '2026-07-03', 'ACTIVO', NULL, NULL, 'VladimirM65', NULL, 0, '2026-02-24 21:44:17'),
(216, 207, 4, 3, 14.00, 1.00, '2025-05-03', '2026-07-03', 'ACTIVO', NULL, NULL, 'Fjtvgh172', NULL, 0, '2026-02-24 21:44:17'),
(217, 208, 4, 3, 25.00, 1.00, '2024-06-05', '2026-07-05', 'ACTIVO', NULL, NULL, 'Raul2945', NULL, 0, '2026-02-24 21:44:17'),
(218, 209, 4, 3, 32.00, 1.00, '2023-11-10', '2026-07-10', 'ACTIVO', NULL, NULL, 'Richardo47', NULL, 0, '2026-02-24 21:44:17'),
(219, 210, 4, 3, 46.00, 1.00, '2022-09-01', '2026-07-15', 'ACTIVO', NULL, NULL, 'LUISG151', NULL, 0, '2026-02-24 21:44:17'),
(220, 211, 4, 3, 21.00, 1.00, '2024-10-15', '2026-07-15', 'ACTIVO', NULL, NULL, 'Miguel11099', NULL, 0, '2026-02-24 21:44:17'),
(221, 212, 4, 3, 44.00, 1.00, '2022-11-10', '2026-08-07', 'ACTIVO', NULL, NULL, 'Carla175', NULL, 0, '2026-02-24 21:44:17'),
(222, 213, 4, 3, 14.00, 1.00, '2025-06-11', '2026-08-11', 'ACTIVO', NULL, NULL, 'Fjtvgh201', NULL, 0, '2026-02-24 21:44:17'),
(223, 214, 4, 3, 43.00, 1.00, '2022-12-05', '2026-08-12', 'ACTIVO', NULL, NULL, 'DanielQ25', NULL, 0, '2026-02-24 21:44:17'),
(224, 215, 4, 3, 16.00, 1.00, '2025-04-19', '2026-08-20', 'ACTIVO', NULL, NULL, 'Waltru75', NULL, 0, '2026-02-24 21:44:17'),
(225, 216, 4, 3, 10.00, 1.00, '2025-10-22', '2026-08-22', 'ACTIVO', NULL, NULL, 'DACA25', NULL, 0, '2026-02-24 21:44:17'),
(226, 217, 4, 3, 45.00, 1.00, '2022-11-22', '2026-08-22', 'ACTIVO', NULL, NULL, 'Lisett32', NULL, 0, '2026-02-24 21:44:17'),
(227, 218, 4, 3, 7.00, 1.00, '2026-01-30', '2026-08-30', 'ACTIVO', NULL, NULL, 'Fernando7669183', NULL, 0, '2026-02-24 21:44:17'),
(228, 219, 4, 3, 33.00, 1.00, '2023-11-23', '2026-09-01', 'ACTIVO', NULL, NULL, 'IvaZoeSah', NULL, 0, '2026-02-24 21:44:17'),
(229, 220, 4, 3, 7.00, 1.00, '2026-02-02', '2026-09-02', 'ACTIVO', NULL, NULL, 'Erlan1100', NULL, 0, '2026-02-24 21:44:17'),
(230, 221, 4, 3, 45.00, 1.00, '2022-12-09', '2026-09-09', 'ACTIVO', NULL, NULL, 'Rey21', NULL, 0, '2026-02-24 21:44:17'),
(231, 222, 4, 3, 28.00, 1.00, '2024-04-29', '2026-09-09', 'ACTIVO', NULL, NULL, 'Mgtvgh908', NULL, 0, '2026-02-24 21:44:17'),
(232, 223, 4, 3, 48.00, 1.00, '2022-09-11', '2026-09-13', 'ACTIVO', NULL, NULL, 'augustogs', NULL, 0, '2026-02-24 21:44:17'),
(233, 224, 4, 3, 28.00, 1.00, '2024-03-24', '2026-09-18', 'ACTIVO', NULL, NULL, 'Mgtvgh907', NULL, 0, '2026-02-24 21:44:17'),
(234, 225, 4, 3, 45.00, 1.00, '2022-12-13', '2026-09-21', 'ACTIVO', NULL, NULL, 'FERNANDAGV', NULL, 0, '2026-02-24 21:44:17'),
(235, 226, 4, 3, 7.00, 1.00, '2026-02-22', '2026-09-22', 'ACTIVO', NULL, NULL, 'Fjtvgh182', NULL, 0, '2026-02-24 21:44:17'),
(236, 227, 4, 3, 27.00, 1.00, '2024-06-23', '2026-09-23', 'ACTIVO', NULL, NULL, 'Gatico0330', NULL, 0, '2026-02-24 21:44:17'),
(237, 228, 4, 3, 28.00, 1.00, '2024-07-03', '2026-11-04', 'ACTIVO', NULL, NULL, 'QUEZADAE', NULL, 0, '2026-02-24 21:44:17'),
(238, 229, 4, 3, 29.00, 1.00, '2024-07-02', '2026-12-06', 'ACTIVO', NULL, NULL, 'bmoreno287', NULL, 0, '2026-02-24 21:44:17'),
(239, 230, 4, 3, 37.00, 1.00, '2023-11-21', '2026-12-21', 'ACTIVO', NULL, NULL, 'ARIELHC48', NULL, 0, '2026-02-24 21:44:17'),
(240, 231, 4, 3, 14.00, 1.00, '2025-11-29', '2027-01-29', 'ACTIVO', NULL, NULL, 'Jtorrico54', NULL, 0, '2026-02-24 21:44:17'),
(241, 232, 4, 3, 14.00, 1.00, '2025-11-30', '2027-01-30', 'ACTIVO', NULL, NULL, 'Fjtvbo2945', NULL, 0, '2026-02-24 21:44:17'),
(242, 233, 4, 3, 50.00, 1.00, '2022-12-04', '2027-02-04', 'ACTIVO', NULL, NULL, 'Carlos282', NULL, 0, '2026-02-24 21:44:17'),
(243, 234, 4, 3, 27.00, 1.00, '2024-11-19', '2027-03-06', 'ACTIVO', NULL, NULL, 'CamAgus53', NULL, 0, '2026-02-24 21:44:17'),
(244, 235, 4, 3, 14.00, 1.00, '2026-01-15', '2027-03-15', 'ACTIVO', NULL, NULL, 'OctavioH75', NULL, 0, '2026-02-24 21:44:17'),
(245, 236, 4, 3, 28.00, 1.00, '2024-12-14', '2027-04-14', 'ACTIVO', NULL, NULL, 'Fjtvgh156', NULL, 0, '2026-02-24 21:44:17'),
(246, 237, 4, 3, 23.00, 1.00, '2024-03-22', '2026-05-26', 'ACTIVO', NULL, NULL, 'Erick287', NULL, 0, '2026-03-02 16:34:01'),
(247, 238, 4, 3, 32.00, 1.00, '2022-08-15', '2026-03-27', 'VENCIDO', NULL, NULL, 'abryan113', NULL, 1, '2026-03-02 16:34:01'),
(248, 239, 6, 8, 35.00, 18.00, '2026-02-10', '2026-03-10', 'VENCIDO', '2026-03-10 07:13:45', 'MENOS_2', NULL, NULL, 1, '2026-03-05 06:30:49'),
(249, 240, 6, 8, 35.00, 18.00, '2026-02-10', '2026-03-10', 'VENCIDO', '2026-03-10 07:13:11', 'MENOS_2', NULL, NULL, 1, '2026-03-05 06:36:47'),
(250, 241, 6, 8, 35.00, 18.00, '2026-02-10', '2026-03-10', 'VENCIDO', '2026-03-10 07:07:48', 'MENOS_2', NULL, NULL, 1, '2026-03-05 06:38:00'),
(251, 242, 6, 8, 35.00, 18.00, '2026-02-12', '2026-03-12', 'VENCIDO', NULL, NULL, NULL, NULL, 1, '2026-03-05 06:39:11'),
(252, 243, 6, 8, 35.00, 18.00, '2026-02-12', '2026-03-12', 'VENCIDO', NULL, NULL, NULL, NULL, 1, '2026-03-05 06:39:59'),
(253, 244, 6, 8, 35.00, 18.00, '2026-02-12', '2026-03-12', 'VENCIDO', NULL, NULL, NULL, NULL, 1, '2026-03-05 06:41:29'),
(254, 245, 4, 3, 35.00, 18.00, '2026-03-13', '2026-05-13', 'ACTIVO', NULL, NULL, '04WILSON', NULL, 0, '2026-04-06 10:25:33'),
(256, 246, 4, 5, 210.00, 108.00, '2025-07-09', '2026-11-08', 'ACTIVO', NULL, NULL, 'Oscar2945', NULL, 0, '2026-04-08 12:30:59'),
(257, 247, 6, 12, 15.00, 6.00, '2026-04-21', '2026-05-21', 'ACTIVO', NULL, NULL, 'Fjtvgh1047', 'Importado desde 1777135850657.csv | Inicio original: 21/04/2026 22:49 | Vencimiento original: 21/05/2026 22:49 | Dias: 30', 0, '2026-04-25 13:10:50'),
(258, 248, 6, 13, 40.00, 18.00, '2026-04-21', '2026-07-21', 'ACTIVO', NULL, NULL, 'Fjtvcg1031', 'Importado desde 1777135850657.csv | Inicio original: 21/04/2026 3:12 | Vencimiento original: 21/07/2026 3:12 | Dias: 91', 0, '2026-04-25 13:10:50'),
(259, 249, 6, 12, 15.00, 6.00, '2026-04-13', '2026-05-13', 'ACTIVO', NULL, NULL, 'Fjtvgh1046', 'Importado desde 1777135850657.csv | Inicio original: 13/04/2026 17:43 | Vencimiento original: 13/05/2026 17:43 | Dias: 30', 0, '2026-04-25 13:10:50'),
(260, 250, 6, 12, 15.00, 6.00, '2026-04-06', '2026-05-06', 'ACTIVO', NULL, NULL, 'Fjtvgh1045', 'Importado desde 1777135850657.csv | Inicio original: 06/04/2026 16:47 | Vencimiento original: 06/05/2026 16:47 | Dias: 30', 0, '2026-04-25 13:10:50'),
(261, 251, 6, 12, 15.00, 6.00, '2026-04-03', '2026-05-03', 'ACTIVO', NULL, NULL, 'Fjtvgh1044', 'Importado desde 1777135850657.csv | Inicio original: 03/04/2026 21:35 | Vencimiento original: 03/05/2026 21:35 | Dias: 30', 0, '2026-04-25 13:10:50'),
(262, 252, 6, 12, 15.00, 6.00, '2026-04-03', '2026-05-03', 'ACTIVO', NULL, NULL, 'Fjtvgh1043', 'Importado desde 1777135850657.csv | Inicio original: 03/04/2026 13:10 | Vencimiento original: 03/05/2026 13:10 | Dias: 30', 0, '2026-04-25 13:10:50'),
(263, 253, 6, 12, 15.00, 6.00, '2026-03-31', '2026-05-01', 'ACTIVO', NULL, NULL, 'Fjtvgh1042', 'Importado desde 1777135850657.csv | Inicio original: 31/03/2026 20:13 | Vencimiento original: 01/05/2026 20:13 | Dias: 31', 0, '2026-04-25 13:10:50'),
(264, 254, 6, 13, 40.00, 18.00, '2026-03-30', '2026-06-30', 'ACTIVO', NULL, NULL, 'Fjtvcg1030', 'Importado desde 1777135850657.csv | Inicio original: 30/03/2026 14:13 | Vencimiento original: 30/06/2026 14:13 | Dias: 92', 0, '2026-04-25 13:10:50'),
(265, 255, 6, 13, 40.00, 18.00, '2026-03-29', '2026-06-29', 'ACTIVO', NULL, NULL, 'Fjtvcg1029', 'Importado desde 1777135850657.csv | Inicio original: 29/03/2026 21:20 | Vencimiento original: 29/06/2026 21:20 | Dias: 92', 0, '2026-04-25 13:10:50'),
(266, 256, 6, 13, 40.00, 18.00, '2026-03-28', '2026-06-28', 'ACTIVO', NULL, NULL, 'Fjtvcg1028', 'Importado desde 1777135850657.csv | Inicio original: 28/03/2026 0:43 | Vencimiento original: 28/06/2026 0:43 | Dias: 92', 0, '2026-04-25 13:10:50'),
(267, 257, 6, 12, 15.00, 6.00, '2026-03-26', '2026-04-26', 'VENCIDO', NULL, NULL, 'Fjtvgh1041', 'Importado desde 1777135850657.csv | Inicio original: 26/03/2026 17:06 | Vencimiento original: 26/04/2026 17:06 | Dias: 31', 0, '2026-04-25 13:10:50'),
(268, 258, 6, 12, 15.00, 6.00, '2026-03-26', '2026-04-26', 'VENCIDO', NULL, NULL, 'Fjtvgh1040', 'Importado desde 1777135850657.csv | Inicio original: 26/03/2026 14:36 | Vencimiento original: 26/04/2026 14:36 | Dias: 31', 0, '2026-04-25 13:10:50'),
(269, 259, 6, 13, 40.00, 18.00, '2026-03-25', '2026-06-25', 'ACTIVO', NULL, NULL, 'Fjtvcg1027', 'Importado desde 1777135850657.csv | Inicio original: 25/03/2026 20:43 | Vencimiento original: 25/06/2026 20:43 | Dias: 92', 0, '2026-04-25 13:10:50'),
(270, 260, 6, 13, 40.00, 18.00, '2026-03-25', '2026-06-25', 'ACTIVO', NULL, NULL, 'Fjtvcg1026', 'Importado desde 1777135850657.csv | Inicio original: 25/03/2026 23:35 | Vencimiento original: 25/06/2026 23:35 | Dias: 92', 0, '2026-04-25 13:10:50'),
(271, 261, 6, 13, 40.00, 18.00, '2026-03-15', '2026-06-15', 'ACTIVO', NULL, NULL, 'Fjtvcg1025', 'Importado desde 1777135850657.csv | Inicio original: 15/03/2026 20:02 | Vencimiento original: 15/06/2026 20:02 | Dias: 92', 0, '2026-04-25 13:10:50'),
(272, 262, 6, 13, 40.00, 18.00, '2026-03-11', '2026-06-11', 'ACTIVO', NULL, NULL, 'Fjtvcg1024', 'Importado desde 1777135850657.csv | Inicio original: 11/03/2026 19:27 | Vencimiento original: 11/06/2026 19:27 | Dias: 92', 0, '2026-04-25 13:10:50'),
(273, 263, 6, 13, 40.00, 18.00, '2026-03-03', '2026-06-03', 'ACTIVO', NULL, NULL, 'Fjtvcg1023', 'Importado desde 1777135850657.csv | Inicio original: 03/03/2026 17:48 | Vencimiento original: 03/06/2026 17:48 | Dias: 92', 0, '2026-04-25 13:10:50'),
(274, 264, 6, 13, 40.00, 18.00, '2026-02-25', '2026-05-25', 'ACTIVO', NULL, NULL, 'Fjtvcg1022', 'Importado desde 1777135850657.csv | Inicio original: 25/02/2026 23:45 | Vencimiento original: 25/05/2026 23:45 | Dias: 89', 0, '2026-04-25 13:10:50'),
(275, 265, 6, 13, 40.00, 18.00, '2026-02-22', '2026-05-22', 'ACTIVO', NULL, NULL, 'Fjtvcg1021', 'Importado desde 1777135850657.csv | Inicio original: 22/02/2026 22:10 | Vencimiento original: 22/05/2026 22:10 | Dias: 89', 0, '2026-04-25 13:10:50'),
(276, 266, 6, 13, 40.00, 18.00, '2026-02-18', '2026-05-18', 'ACTIVO', NULL, NULL, 'Fjtvcg1020', 'Importado desde 1777135850657.csv | Inicio original: 18/02/2026 23:39 | Vencimiento original: 18/05/2026 23:39 | Dias: 89', 0, '2026-04-25 13:10:50'),
(277, 267, 6, 13, 40.00, 18.00, '2026-02-02', '2026-05-02', 'ACTIVO', NULL, NULL, 'Fjtvcg1019', 'Importado desde 1777135850657.csv | Inicio original: 02/02/2026 17:55 | Vencimiento original: 02/05/2026 17:55 | Dias: 89', 0, '2026-04-25 13:10:50'),
(278, 268, 6, 13, 40.00, 18.00, '2026-01-31', '2026-05-01', 'ACTIVO', NULL, NULL, 'Fjtvcg1018', 'Importado desde 1777135850657.csv | Inicio original: 31/01/2026 0:22 | Vencimiento original: 01/05/2026 0:22 | Dias: 90', 0, '2026-04-25 13:10:50'),
(279, 269, 6, 13, 40.00, 18.00, '2026-01-26', '2026-04-26', 'VENCIDO', NULL, NULL, 'Fjtvcg1017', 'Importado desde 1777135850657.csv | Inicio original: 26/01/2026 12:03 | Vencimiento original: 26/04/2026 12:03 | Dias: 90', 0, '2026-04-25 13:10:50'),
(280, 270, 6, 14, 90.00, 36.00, '2025-04-18', '2026-04-28', 'ACTIVO', NULL, NULL, 'Fjtvcg957', 'Importado desde 1777135850657.csv | Inicio original: 18/04/2025 14:33 | Vencimiento original: 28/04/2026 21:31 | Dias: 375', 0, '2026-04-25 13:10:50'),
(281, 271, 6, 14, 90.00, 36.00, '2024-12-14', '2026-06-30', 'ACTIVO', NULL, NULL, 'AstridA20', 'Importado desde 1777135850657.csv | Inicio original: 14/12/2024 15:38 | Vencimiento original: 30/06/2026 0:45 | Dias: 563', 0, '2026-04-25 13:10:50'),
(282, 272, 6, 14, 90.00, 36.00, '2024-07-01', '2026-07-27', 'ACTIVO', NULL, NULL, 'Mgtvlg839', 'Importado desde 1777135850657.csv | Inicio original: 01/07/2024 5:11 | Vencimiento original: 27/07/2026 14:21 | Dias: 756', 0, '2026-04-25 13:10:50'),
(283, 273, 4, 7, 420.00, 216.00, '2022-08-28', '2026-07-24', 'ACTIVO', NULL, NULL, 'Fanola', 'Importado desde us cc.csv | Inicio original: 2022-08-28 23:15:05 | Vencimiento original: 2026-07-24 23:54:45 | Dias: 1426', 0, '2026-04-25 13:26:52'),
(284, 274, 4, 7, 420.00, 216.00, '2024-05-02', '2026-10-18', 'ACTIVO', NULL, NULL, 'Victort1979', 'Importado desde us cc.csv | Inicio original: 2024-05-02 22:33:11 | Vencimiento original: 2026-10-18 00:58:04 | Dias: 899', 0, '2026-04-25 13:26:52'),
(285, 275, 4, 3, 35.00, 18.00, '2026-03-26', '2026-04-26', 'VENCIDO', NULL, NULL, 'Fjtvgh185', 'Importado desde us cc.csv | Inicio original: 2026-03-26 21:59:09 | Vencimiento original: 2026-04-26 21:59:09 | Dias: 31', 0, '2026-04-25 13:26:53'),
(286, 276, 4, 4, 100.00, 54.00, '2026-03-19', '2026-06-19', 'ACTIVO', NULL, NULL, 'Fjtvgh184', 'Importado desde us cc.csv | Inicio original: 2026-03-19 17:22:34 | Vencimiento original: 2026-06-19 17:22:34 | Dias: 92', 0, '2026-04-25 13:26:53'),
(287, 277, 4, 4, 100.00, 54.00, '2026-03-18', '2026-06-18', 'ACTIVO', NULL, NULL, 'Fjtvgh183', 'Importado desde us cc.csv | Inicio original: 2026-03-18 01:58:33 | Vencimiento original: 2026-06-18 01:58:33 | Dias: 92', 0, '2026-04-25 13:26:53');

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
(1, 'ghostbhm', '$2y$10$LJmnkdVu3SbRVj6yfuzi.eUZsF1uYzslUbpmmzMVcxD2vE20PnduS', 'admin', '2026-02-24 06:42:51');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `backup_vencimientos_20260302_flujotv`
--
ALTER TABLE `backup_vencimientos_20260302_flujotv`
  ADD PRIMARY KEY (`backup_id`);

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
-- AUTO_INCREMENT de la tabla `backup_vencimientos_20260302_flujotv`
--
ALTER TABLE `backup_vencimientos_20260302_flujotv`
  MODIFY `backup_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=278;

--
-- AUTO_INCREMENT de la tabla `modalidades`
--
ALTER TABLE `modalidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `movimientos`
--
ALTER TABLE `movimientos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT de la tabla `plataformas`
--
ALTER TABLE `plataformas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `suscripciones`
--
ALTER TABLE `suscripciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=288;

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
  ADD CONSTRAINT `movimientos_suscripciones_set_null_fk` FOREIGN KEY (`suscripcion_id`) REFERENCES `suscripciones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

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
