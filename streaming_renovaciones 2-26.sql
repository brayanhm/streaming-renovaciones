-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 26-02-2026 a las 22:52:47
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
(119, 'CLM113', '78129454', 'Importado desde usuarios_combinados.xlsx | Usuario: Antonio2945 | Clave: mgtv2232 | Credito: 24 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(120, 'CLM130', '62126392', 'Importado desde usuarios_combinados.xlsx | Usuario: Kevinb548 | Clave: mgtv1337 | Credito: 22 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(121, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Luis10450 | Clave: fjtv9087 | Credito: 1 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(122, 'CLM16', '62000726', 'Importado desde usuarios_combinados.xlsx | Usuario: JavierS89 | Clave: 2857177 | Credito: 30 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(123, 'CLM155', '71541343', 'Importado desde usuarios_combinados.xlsx | Usuario: TVGUTIERREZ64 | Clave: blanca123 | Credito: 16 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(124, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: CeciliaR294 | Clave: fjtv1956 | Credito: 7 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(125, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Fjtvgh177 | Clave: fjtv11w8 | Credito: 7 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(126, 'CLM111', '63262563', 'Importado desde usuarios_combinados.xlsx | Usuario: crsirpa | Clave: mgtv1253 | Credito: 19 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(127, 'CLM173', '', 'Importado desde usuarios_combinados.xlsx | Usuario: VladimirM64 | Clave: fjtv2251 | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(128, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: MiguelT74 | Clave: thiagoymia09 | Credito: 21 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(129, 'CLM110', '68100041', 'Importado desde usuarios_combinados.xlsx | Usuario: Mgtvplusbo613 | Clave: matilda2024 | Credito: 20 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(130, 'CLM90', '79872569', 'Importado desde usuarios_combinados.xlsx | Usuario: javier7762 | Clave: usuario130 | Credito: 42 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/meufksdiq1fb', '2026-02-24 21:44:17'),
(131, 'CLM106', '74699663', 'Importado desde usuarios_combinados.xlsx | Usuario: Jimenam24 | Clave: mgtv1428 | Credito: 23 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(132, 'CLM168', '', 'Importado desde usuarios_combinados.xlsx | Usuario: NinoskaClavel5 | Clave: Yarkoamor47 | Credito: 16 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(133, 'CLM129', '76115292', 'Importado desde usuarios_combinados.xlsx | Usuario: ArielD131 | Clave: 5668635isaH | Credito: 21 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(134, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: AlejandraT85 | Clave: fjtvw987 | Credito: 3 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(135, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: NAVAJASITURRE | Clave: fjtv1804 | Credito: 6 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(136, 'CLM174', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Severiche64 | Clave: sev3578254 | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(137, 'CLM17', '76988244', 'Importado desde usuarios_combinados.xlsx | Usuario: Ber_Rios | Clave: CUA016 | Credito: 41 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/tj65hrdjgfpo', '2026-02-24 21:44:17'),
(138, 'CLM10', '72266006', 'Importado desde usuarios_combinados.xlsx | Usuario: Mgtvplusbo211 | Clave: mgtv2116 | Credito: 33 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(139, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Nehemias2014 | Clave: nemo0314 | Credito: 17 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(140, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Mgtvplusbo193 | Clave: mgtv1348 | Credito: 40 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/n8wfax3vdbu3', '2026-02-24 21:44:17'),
(141, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Franks87 | Clave: fjyv866 | Credito: 5 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(142, 'CLM47', '70834111', 'Importado desde usuarios_combinados.xlsx | Usuario: sandram115 | Clave: fjtv1434 | Credito: 41 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/jv3hin0l9i52', '2026-02-24 21:44:17'),
(143, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Fjtvgh176 | Clave: fjtv1105 | Credito: 4 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(144, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Fjtvgh179 | Clave: fjtv1029 | Credito: 2 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(145, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: BrayanR875 | Clave: Lia2015 | Credito: 16 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(146, 'CLM114', '76220056', 'Importado desde usuarios_combinados.xlsx | Usuario: akf75 | Clave: mgtv1026 | Credito: 23 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(147, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Fjtvgh181 | Clave: fjtv1024 | Credito: 1 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(148, 'CLM124', '79452752', 'Importado desde usuarios_combinados.xlsx | Usuario: Caroline2412 | Clave: mgyv1954 | Credito: 21 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(149, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: pablocachi1234 | Clave: pablo1234 | Credito: 10 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(150, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Fltv7890 | Clave: fjtv007r | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(151, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: JoseA75 | Clave: fjtv1459 | Credito: 7 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(152, 'CLM163', '', 'Importado desde usuarios_combinados.xlsx | Usuario: vickycampos2003 | Clave: 9876542003 | Credito: 16 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(153, 'CLM125', '72202331', 'Importado desde usuarios_combinados.xlsx | Usuario: AmnerCh | Clave: mgtv2125 | Credito: 18 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(154, 'MAGIS2', '', 'Importado desde usuarios_combinados.xlsx | Usuario: RoldanH | Clave: rold4795 | Credito: 13 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(155, 'Yo', '79625801', 'Importado desde usuarios_combinados.xlsx | Usuario: ghostbh | Clave: caiman | Credito: 43 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/bqh8o0wopod2', '2026-02-24 21:44:17'),
(156, 'CLM112', '72649663', 'Importado desde usuarios_combinados.xlsx | Usuario: Mgtvplusbo501 | Clave: vcg12345 | Credito: 37 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(157, 'CLM21', '63723171', 'Importado desde usuarios_combinados.xlsx | Usuario: OvidioMB | Clave: usuario172 | Credito: 42 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/pzwpuko737zk', '2026-02-24 21:44:17'),
(158, 'CLM177', '', 'Importado desde usuarios_combinados.xlsx | Usuario: JonCris | Clave: fltv958 | Credito: 17 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(159, 'clm153', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Brayan2945 | Clave: fjtv1220 | Credito: 10 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(160, 'CLM81', '77364727', 'Importado desde usuarios_combinados.xlsx | Usuario: EloyManjares | Clave: conejo22 | Credito: 31 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(161, 'CL0', '78108323', 'Importado desde usuarios_combinados.xlsx | Usuario: jorgeh104 | Clave: 6365946 | Credito: 46 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/dxigcmf2478r', '2026-02-24 21:44:17'),
(162, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Juan6713bedrega | Clave: 1758fjtv | Credito: 7 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(163, 'CLM40', '70764330', 'Importado desde usuarios_combinados.xlsx | Usuario: gustavo187 | Clave: tavin87 | Credito: 41 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/dlcqf40p4l9q', '2026-02-24 21:44:17'),
(164, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Paula1100 | Clave: fjtv186 | Credito: 7 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(165, 'CLM161', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Andresss43 | Clave: fjtv1645 | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(166, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: JhonLu76 | Clave: fjtv2755 | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(167, 'CLM158', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Hernan864 | Clave: 4339sc | Credito: 17 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(168, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Toshi2025 | Clave: toshito007 | Credito: 11 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(169, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Mgtvgh925 | Clave: mgtv1056 | Credito: 16 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(170, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Ademar2024 | Clave: mgtv1985 | Credito: 24 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(171, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Fbismarck | Clave: candy123 | Credito: 15 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(172, 'CLM159', '70691669', 'Importado desde usuarios_combinados.xlsx | Usuario: Jcalderon107 | Clave: lotuspush666 | Credito: 18 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(173, 'CLM85', '67026226', 'Importado desde usuarios_combinados.xlsx | Usuario: Vania197 | Clave: vania1004 | Credito: 33 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(174, 'CLM23', '70744358', 'Importado desde usuarios_combinados.xlsx | Usuario: Dego | Clave: 4199338 | Credito: 43 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/6c8y6e1zgpzf', '2026-02-24 21:44:17'),
(175, 'CLM175', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Carmencita85 | Clave: Carmencita36 | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(176, 'CLM133', '78954277', 'Importado desde usuarios_combinados.xlsx | Usuario: Dabarca | Clave: Dobby2024 | Credito: 27 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(177, 'CLM41', '79308067', 'Importado desde usuarios_combinados.xlsx | Usuario: AlisonSR | Clave: mgtv1829 | Credito: 42 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/aqefbms9rae6', '2026-02-24 21:44:17'),
(178, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Elluz64 | Clave: emvc2018 | Credito: 16 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(179, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Josesitos | Clave: Josesitos5 | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(180, 'CLM51', '69840476', 'Importado desde usuarios_combinados.xlsx | Usuario: Edson02023 | Clave: mgtv2014 | Credito: 42 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(181, 'CLM52', '70308940', 'Importado desde usuarios_combinados.xlsx | Usuario: ArielG186 | Clave: lluviax2 | Credito: 39 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(182, 'CLM89', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Andreslr | Clave: mgtv1630 | Credito: 28 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(183, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Irisabarca | Clave: Milu2025 | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(184, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Elbac | Clave: edc7935 | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(185, 'CLM59', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Dhaflujo | Clave: h251016ft | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(186, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Mateo2945 | Clave: fjtv2036 | Credito: 7 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(187, 'CLM37', '70418842', 'Importado desde usuarios_combinados.xlsx | Usuario: AbastoflorF | Clave: mgtv2015 | Credito: 43 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/uotne6mms5rn', '2026-02-24 21:44:17'),
(188, 'CLM105', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Jessica1212 | Clave: 121294J | Credito: 22 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(189, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Caneltina | Clave: fjtv858 | Credito: 9 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(190, 'CLM123', '77487975', 'Importado desde usuarios_combinados.xlsx | Usuario: Rrossmar81 | Clave: mgtv1750 | Credito: 24 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(191, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Fjtvgh174 | Clave: fjtv1423 | Credito: 10 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(192, 'CLM5', '70648578', 'Importado desde usuarios_combinados.xlsx | Usuario: juanaugusto75 | Clave: roman10DIEZ | Credito: 45 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/u8jxfwuopyk6', '2026-02-24 21:44:17'),
(193, 'CLM167', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Andres2405 | Clave: 2025RC | Credito: 19 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(194, 'CLM44', '60406041', 'Importado desde usuarios_combinados.xlsx | Usuario: Jannette | Clave: mgtv1646 | Credito: 43 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/ngwg9ggjbw81', '2026-02-24 21:44:17'),
(195, 'CLM27', '78584562', 'Importado desde usuarios_combinados.xlsx | Usuario: MiguelFerrufino | Clave: 12345678 | Credito: 44 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/aqqf2kqogk59', '2026-02-24 21:44:17'),
(196, 'CLM54', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Harold195 | Clave: mgtv1143 | Credito: 36 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/n6r4ai81oots', '2026-02-24 21:44:17'),
(197, 'CLM70', '65108575', 'Importado desde usuarios_combinados.xlsx | Usuario: MarcioG25 | Clave: STERCIO8 | Credito: 39 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(198, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: magisgt102 | Clave: salazar1122 | Credito: 35 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(199, 'CLM135', '68640161', 'Importado desde usuarios_combinados.xlsx | Usuario: Gato4924 | Clave: lariat1033 | Credito: 28 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(200, 'CLM136', '68118183', 'Importado desde usuarios_combinados.xlsx | Usuario: Taveras | Clave: huevos | Credito: 21 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(201, 'CLM137', '76136870', 'Importado desde usuarios_combinados.xlsx | Usuario: Omarll07 | Clave: mgtb845 | Credito: 31 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(202, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Gabysil | Clave: Jade2015 | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(203, 'CLM138', '74327387', 'Importado desde usuarios_combinados.xlsx | Usuario: camperoj1507 | Clave: andre2025 | Credito: 21 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(204, 'CLM139', '77260895', 'Importado desde usuarios_combinados.xlsx | Usuario: PachecoRiva123 | Clave: RickHunter2706 | Credito: 24 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(205, 'CLM142', '77775563', 'Importado desde usuarios_combinados.xlsx | Usuario: IvanT28 | Clave: 3T4TP10TT | Credito: 31 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(206, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: VladimirM65 | Clave: fjtv2876 | Credito: 7 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(207, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Fjtvgh172 | Clave: fjtb2202 | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(208, 'CLM132', '71165025', 'Importado desde usuarios_combinados.xlsx | Usuario: Raul2945 | Clave: mgtv1655 | Credito: 25 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(209, 'CLM117', '70806193', 'Importado desde usuarios_combinados.xlsx | Usuario: Richardo47 | Clave: mgtv1415 | Credito: 32 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(210, 'CLM13', '73979035', 'Importado desde usuarios_combinados.xlsx | Usuario: LUISG151 | Clave: 08011987 | Credito: 46 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/j7ujlt5i59sr', '2026-02-24 21:44:17'),
(211, 'CLM169', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Miguel11099 | Clave: Ross57 | Credito: 21 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(212, 'CLM149', '73676180', 'Importado desde usuarios_combinados.xlsx | Usuario: Carla175 | Clave: mgtv1936 | Credito: 44 | Estado Excel: V?lido | SmartTV: http://iptv.magisapk.com/get/lu22d9qsjq75', '2026-02-24 21:44:17'),
(213, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Fjtvgh201 | Clave: 511158 | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(214, 'CLM116', '73144265', 'Importado desde usuarios_combinados.xlsx | Usuario: DanielQ25 | Clave: mgtv1233 | Credito: 43 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(215, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Waltru75 | Clave: fjtv1970 | Credito: 16 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(216, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: DACA25 | Clave: walter92 | Credito: 10 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(217, '', '75545062', 'Importado desde usuarios_combinados.xlsx | Usuario: Lisett32 | Clave: mgtv2305 | Credito: 45 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(218, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Fernando7669183 | Clave: fjtv1334 | Credito: 7 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
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
(231, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Jtorrico54 | Clave: 71566351 | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(232, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Fjtvbo2945 | Clave: fjtv2386 | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(233, 'CLM104', '72007784', 'Importado desde usuarios_combinados.xlsx | Usuario: Carlos282 | Clave: mgtv1331 | Credito: 50 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(234, 'CLM165', '', 'Importado desde usuarios_combinados.xlsx | Usuario: CamAgus53 | Clave: fjtv1203 | Credito: 27 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(235, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: OctavioH75 | Clave: Norka52 | Credito: 14 | Estado Excel: V?lido', '2026-02-24 21:44:17'),
(236, '', '', 'Importado desde usuarios_combinados.xlsx | Usuario: Fjtvgh156 | Clave: fjtv111 | Credito: 28 | Estado Excel: V?lido', '2026-02-24 21:44:17');

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
(4, 4, 'Cuenta completa', 'CUENTA_COMPLETA', 3, NULL, 1.00, 1.00, '2026-02-25 06:56:20'),
(5, 4, 'Cuenta completa', 'CUENTA_COMPLETA', 7, NULL, 1.00, 1.00, '2026-02-25 06:56:20'),
(7, 4, 'Cuenta completa', 'CUENTA_COMPLETA', 14, NULL, 1.00, 1.00, '2026-02-25 07:01:55'),
(8, 6, 'Cuenta completa', 'CUENTA_COMPLETA', 1, NULL, 1.00, 1.00, '2026-02-25 07:09:26'),
(9, 6, 'Cuenta completa', 'CUENTA_COMPLETA', 3, NULL, 1.00, 1.00, '2026-02-25 07:09:26'),
(10, 6, 'Cuenta completa', 'CUENTA_COMPLETA', 7, NULL, 1.00, 1.00, '2026-02-25 07:09:26'),
(11, 7, 'Cuenta completa', 'CUENTA_COMPLETA', 1, NULL, 1.00, 1.00, '2026-02-25 07:19:01');

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
  `costo` decimal(10,2) DEFAULT NULL,
  `utilidad` decimal(10,2) DEFAULT NULL,
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
(4, 'Flujo TV (Cuenta Completa)', 'RENOVABLE', '1,3,7,14', 'USUARIO', '🌟 *Buen día soy su proveedor del servicio FlujoTV.*  \r\nLe informo que su suscripción a *FlujoTV* vence en *3 días* 📅  \r\nPara evitar la suspensión del servicio y garantizar continuidad en el acceso a sus contenidos 📺, puede realizar su renovación con anticipación.  \r\n🎟️ *Planes disponibles:*  \r\n✅ *1 mes* → 35 Bs  \r\n✅ *3 meses* → 100 Bs  \r\n✅ *6 meses* → 210 Bs + *1 mes GRATIS*  \r\n✅ *12 meses* → 420 Bs + *2 meses GRATIS*  \r\n💳 *Métodos de pago:* QR, Yape o Tigo Money  \r\n📩 Por favor, indíqueme el plan de su preferencia para proceder con la activación correspondiente.', '🔔 *Buen día.*  \r\nLe informo que su suscripción a *FlujoTV* vence el día de hoy 📅  \r\nPara evitar la suspensión del servicio y la interrupción del acceso a los contenidos 📺, le recomiendo realizar la renovación dentro del día.  \r\n🎟️ *Planes disponibles:*  \r\n✅ *1 mes* → 35 Bs  \r\n✅ *3 meses* → 100 Bs  \r\n✅ *6 meses* → 210 Bs + *1 mes GRATIS*  \r\n✅ *12 meses* → 420 Bs + *2 meses GRATIS*  \r\n💳 *Métodos de pago:* QR, Yape o Tigo Money  \r\n📩 Por favor, indíqueme el plan de su preferencia para proceder con la activación inmediata.', '🔔 *Buen día.*  \r\nSu suscripción a *FlujoTV* se encuentra suspendida desde hace 3 días 📅  \r\nPara facilitar su reactivación, hemos habilitado un *beneficio especial válido solo por hoy*:  \r\n🎁 *10% de descuento* en los planes de 1 y 3 meses.  \r\n🎟️ *Planes disponibles:*  \r\n✅ *1 mes* → 31.5 Bs (antes 35 Bs)  \r\n✅ *3 meses* → 90 Bs (antes 100 Bs)  \r\n🔥 *6 meses* → 210 Bs + *1 mes GRATIS*  \r\n🔥 *12 meses* → 420 Bs + *2 meses GRATIS*  \r\n💳 *Métodos de pago:* QR, Yape o Tigo Money  \r\n📩 Indíqueme el plan de su preferencia para proceder con la activación inmediata.', '🔔 *Buen día.*  \r\nSu suscripción a *FlujoTV* se encuentra suspendida desde hace 3 días 📅  \r\nPara facilitar su reactivación, hemos habilitado un *beneficio especial válido solo por hoy*:  \r\n🎁 *10% de descuento* en los planes de 1 y 3 meses.  \r\n🎟️ *Planes disponibles:*  \r\n✅ *1 mes* → 31.5 Bs (antes 35 Bs)  \r\n✅ *3 meses* → 90 Bs (antes 100 Bs)  \r\n🔥 *6 meses* → 210 Bs + *1 mes GRATIS*  \r\n🔥 *12 meses* → 420 Bs + *2 meses GRATIS*  \r\n💳 *Métodos de pago:* QR, Yape o Tigo Money  \r\n📩 Indíqueme el plan de su preferencia para proceder con la activación inmediata.', '2026-02-24 21:41:10'),
(6, 'Flujo TV (Por dispositivos)', 'DESECHABLE', '1,3,7', 'NO_APLICA', NULL, NULL, NULL, NULL, '2026-02-25 07:06:16'),
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
  `flag_no_renovo` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `suscripciones`
--

INSERT INTO `suscripciones` (`id`, `cliente_id`, `plataforma_id`, `modalidad_id`, `precio_venta`, `costo_base`, `fecha_inicio`, `fecha_vencimiento`, `estado`, `ultimo_contacto_fecha`, `ultimo_contacto_tipo`, `usuario_proveedor`, `flag_no_renovo`, `created_at`) VALUES
(128, 119, 4, 3, 24.00, 1.00, '2023-12-27', '2026-02-24', 'VENCIDO', '2026-02-25 12:39:02', 'MENOS_1', 'Antonio2945', 1, '2026-02-24 21:44:17'),
(129, 120, 4, 3, 22.00, 1.00, '2024-03-26', '2026-02-24', 'VENCIDO', '2026-02-25 11:39:13', 'MENOS_1', 'Kevinb548', 1, '2026-02-24 21:44:17'),
(130, 121, 4, 3, 1.00, 1.00, '2026-01-25', '2026-02-25', 'VENCIDO', NULL, NULL, 'Luis10450', 1, '2026-02-24 21:44:17'),
(131, 122, 4, 3, 30.00, 1.00, '2023-08-11', '2026-02-25', 'VENCIDO', NULL, NULL, 'JavierS89', 1, '2026-02-24 21:44:17'),
(132, 123, 4, 3, 16.00, 1.00, '2024-10-21', '2026-02-26', 'REENVIAR_1D', NULL, NULL, 'TVGUTIERREZ64', 0, '2026-02-24 21:44:17'),
(133, 124, 4, 3, 7.00, 1.00, '2025-07-28', '2026-02-28', 'ESPERA', NULL, NULL, 'CeciliaR294', 0, '2026-02-24 21:44:17'),
(134, 125, 4, 3, 7.00, 1.00, '2025-07-28', '2026-02-28', 'ESPERA', NULL, NULL, 'Fjtvgh177', 0, '2026-02-24 21:44:17'),
(135, 126, 4, 3, 19.00, 1.00, '2024-06-12', '2026-02-28', 'ESPERA', '2026-02-25 12:40:26', 'MENOS_2', 'crsirpa', 0, '2026-02-24 21:44:17'),
(136, 127, 4, 3, 14.00, 1.00, '2024-12-28', '2026-03-01', 'CONTACTAR_2D', NULL, NULL, 'VladimirM64', 0, '2026-02-24 21:44:17'),
(137, 128, 4, 3, 21.00, 1.00, '2024-04-04', '2026-03-02', 'ACTIVO', NULL, NULL, 'MiguelT74', 0, '2026-02-24 21:44:17'),
(138, 129, 4, 3, 20.00, 1.00, '2024-07-02', '2026-03-03', 'ACTIVO', NULL, NULL, 'Mgtvplusbo613', 0, '2026-02-24 21:44:17'),
(139, 130, 4, 3, 42.00, 1.00, '2022-08-30', '2026-03-04', 'ACTIVO', NULL, NULL, 'javier7762', 0, '2026-02-24 21:44:17'),
(140, 131, 4, 3, 23.00, 1.00, '2024-03-27', '2026-03-04', 'ACTIVO', NULL, NULL, 'Jimenam24', 0, '2026-02-24 21:44:17'),
(141, 132, 4, 3, 16.00, 1.00, '2024-11-04', '2026-03-04', 'ACTIVO', NULL, NULL, 'NinoskaClavel5', 0, '2026-02-24 21:44:17'),
(142, 133, 4, 3, 21.00, 1.00, '2024-06-04', '2026-03-04', 'ACTIVO', NULL, NULL, 'ArielD131', 0, '2026-02-24 21:44:17'),
(143, 134, 4, 3, 3.00, 1.00, '2025-12-05', '2026-03-05', 'ACTIVO', NULL, NULL, 'AlejandraT85', 0, '2026-02-24 21:44:17'),
(144, 135, 4, 3, 6.00, 1.00, '2025-09-05', '2026-03-05', 'ACTIVO', NULL, NULL, 'NAVAJASITURRE', 0, '2026-02-24 21:44:17'),
(145, 136, 4, 3, 14.00, 1.00, '2025-01-06', '2026-03-06', 'ACTIVO', NULL, NULL, 'Severiche64', 0, '2026-02-24 21:44:17'),
(146, 137, 4, 3, 41.00, 1.00, '2022-09-07', '2026-03-07', 'ACTIVO', NULL, NULL, 'Ber_Rios', 0, '2026-02-24 21:44:17'),
(147, 138, 4, 3, 33.00, 1.00, '2023-03-02', '2026-03-07', 'ACTIVO', NULL, NULL, 'Mgtvplusbo211', 0, '2026-02-24 21:44:17'),
(148, 139, 4, 3, 17.00, 1.00, '2024-03-19', '2026-03-08', 'ACTIVO', NULL, NULL, 'Nehemias2014', 0, '2026-02-24 21:44:17'),
(149, 140, 4, 3, 40.00, 1.00, '2022-10-22', '2026-03-08', 'ACTIVO', NULL, NULL, 'Mgtvplusbo193', 0, '2026-02-24 21:44:17'),
(150, 141, 4, 3, 5.00, 1.00, '2025-10-08', '2026-03-09', 'ACTIVO', NULL, NULL, 'Franks87', 0, '2026-02-24 21:44:17'),
(151, 142, 4, 3, 41.00, 1.00, '2022-08-18', '2026-03-10', 'ACTIVO', NULL, NULL, 'sandram115', 0, '2026-02-24 21:44:17'),
(152, 143, 4, 3, 4.00, 1.00, '2025-08-05', '2026-03-11', 'ACTIVO', NULL, NULL, 'Fjtvgh176', 0, '2026-02-24 21:44:17'),
(153, 144, 4, 3, 2.00, 1.00, '2026-01-10', '2026-03-11', 'ACTIVO', NULL, NULL, 'Fjtvgh179', 0, '2026-02-24 21:44:17'),
(154, 145, 4, 3, 16.00, 1.00, '2024-07-10', '2026-03-11', 'ACTIVO', NULL, NULL, 'BrayanR875', 0, '2026-02-24 21:44:17'),
(155, 146, 4, 3, 23.00, 1.00, '2024-03-25', '2026-03-14', 'ACTIVO', NULL, NULL, 'akf75', 0, '2026-02-24 21:44:17'),
(156, 147, 4, 3, 1.00, 1.00, '2026-02-16', '2026-03-16', 'ACTIVO', NULL, NULL, 'Fjtvgh181', 0, '2026-02-24 21:44:17'),
(157, 148, 4, 3, 21.00, 1.00, '2024-06-15', '2026-03-18', 'ACTIVO', NULL, NULL, 'Caroline2412', 0, '2026-02-24 21:44:17'),
(158, 149, 4, 3, 10.00, 1.00, '2024-11-16', '2026-03-18', 'ACTIVO', NULL, NULL, 'pablocachi1234', 0, '2026-02-24 21:44:17'),
(159, 150, 4, 3, 14.00, 1.00, '2025-01-19', '2026-03-19', 'ACTIVO', NULL, NULL, 'Fltv7890', 0, '2026-02-24 21:44:17'),
(160, 151, 4, 3, 7.00, 1.00, '2025-08-19', '2026-03-20', 'ACTIVO', NULL, NULL, 'JoseA75', 0, '2026-02-24 21:44:17'),
(161, 152, 4, 3, 16.00, 1.00, '2024-11-15', '2026-03-20', 'ACTIVO', NULL, NULL, 'vickycampos2003', 0, '2026-02-24 21:44:17'),
(162, 153, 4, 3, 18.00, 1.00, '2024-09-12', '2026-03-20', 'ACTIVO', NULL, NULL, 'AmnerCh', 0, '2026-02-24 21:44:17'),
(163, 154, 4, 3, 13.00, 1.00, '2025-02-17', '2026-03-21', 'ACTIVO', NULL, NULL, 'RoldanH', 0, '2026-02-24 21:44:17'),
(164, 155, 4, 3, 43.00, 1.00, '2022-05-14', '2026-03-21', 'ACTIVO', NULL, NULL, 'ghostbh', 0, '2026-02-24 21:44:17'),
(165, 156, 4, 3, 37.00, 1.00, '2022-12-19', '2026-03-21', 'ACTIVO', NULL, NULL, 'Mgtvplusbo501', 0, '2026-02-24 21:44:17'),
(166, 157, 4, 3, 42.00, 1.00, '2022-09-21', '2026-03-22', 'ACTIVO', NULL, NULL, 'OvidioMB', 0, '2026-02-24 21:44:17'),
(167, 158, 4, 3, 17.00, 1.00, '2024-10-25', '2026-03-25', 'ACTIVO', NULL, NULL, 'JonCris', 0, '2026-02-24 21:44:17'),
(168, 159, 4, 3, 10.00, 1.00, '2025-05-10', '2026-03-25', 'ACTIVO', NULL, NULL, 'Brayan2945', 0, '2026-02-24 21:44:17'),
(169, 160, 4, 3, 31.00, 1.00, '2023-03-20', '2026-03-25', 'ACTIVO', NULL, NULL, 'EloyManjares', 0, '2026-02-24 21:44:17'),
(170, 161, 4, 3, 46.00, 1.00, '2022-05-30', '2026-03-30', 'ACTIVO', NULL, NULL, 'jorgeh104', 0, '2026-02-24 21:44:17'),
(171, 162, 4, 3, 7.00, 1.00, '2025-09-04', '2026-04-05', 'ACTIVO', NULL, NULL, 'Juan6713bedrega', 0, '2026-02-24 21:44:17'),
(172, 163, 4, 3, 41.00, 1.00, '2022-10-23', '2026-04-08', 'ACTIVO', NULL, NULL, 'gustavo187', 0, '2026-02-24 21:44:17'),
(173, 164, 4, 3, 7.00, 1.00, '2025-09-09', '2026-04-09', 'ACTIVO', NULL, NULL, 'Paula1100', 0, '2026-02-24 21:44:17'),
(174, 165, 4, 3, 14.00, 1.00, '2025-02-06', '2026-04-10', 'ACTIVO', NULL, NULL, 'Andresss43', 0, '2026-02-24 21:44:17'),
(175, 166, 4, 3, 14.00, 1.00, '2024-07-16', '2026-04-13', 'ACTIVO', NULL, NULL, 'JhonLu76', 0, '2026-02-24 21:44:17'),
(176, 167, 4, 3, 17.00, 1.00, '2024-10-26', '2026-04-14', 'ACTIVO', NULL, NULL, 'Hernan864', 0, '2026-02-24 21:44:17'),
(177, 168, 4, 3, 11.00, 1.00, '2025-05-10', '2026-04-15', 'ACTIVO', NULL, NULL, 'Toshi2025', 0, '2026-02-24 21:44:17'),
(178, 169, 4, 3, 16.00, 1.00, '2024-09-14', '2026-04-15', 'ACTIVO', NULL, NULL, 'Mgtvgh925', 0, '2026-02-24 21:44:17'),
(179, 170, 4, 3, 24.00, 1.00, '2024-03-30', '2026-04-18', 'ACTIVO', NULL, NULL, 'Ademar2024', 0, '2026-02-24 21:44:17'),
(180, 171, 4, 3, 15.00, 1.00, '2024-12-17', '2026-04-19', 'ACTIVO', NULL, NULL, 'Fbismarck', 0, '2026-02-24 21:44:17'),
(181, 172, 4, 3, 18.00, 1.00, '2024-10-19', '2026-04-21', 'ACTIVO', NULL, NULL, 'Jcalderon107', 0, '2026-02-24 21:44:17'),
(182, 173, 4, 3, 33.00, 1.00, '2023-07-22', '2026-04-22', 'ACTIVO', NULL, NULL, 'Vania197', 0, '2026-02-24 21:44:17'),
(183, 174, 4, 3, 43.00, 1.00, '2022-09-24', '2026-04-28', 'ACTIVO', NULL, NULL, 'Dego', 0, '2026-02-24 21:44:17'),
(184, 175, 4, 3, 14.00, 1.00, '2025-02-28', '2026-04-28', 'ACTIVO', NULL, NULL, 'Carmencita85', 0, '2026-02-24 21:44:17'),
(185, 176, 4, 3, 27.00, 1.00, '2024-01-27', '2026-04-28', 'ACTIVO', NULL, NULL, 'Dabarca', 0, '2026-02-24 21:44:17'),
(186, 177, 4, 3, 42.00, 1.00, '2022-10-24', '2026-04-29', 'ACTIVO', NULL, NULL, 'AlisonSR', 0, '2026-02-24 21:44:17'),
(187, 178, 4, 3, 16.00, 1.00, '2024-11-30', '2026-05-01', 'ACTIVO', NULL, NULL, 'Elluz64', 0, '2026-02-24 21:44:17'),
(188, 179, 4, 3, 14.00, 1.00, '2025-03-05', '2026-05-05', 'ACTIVO', NULL, NULL, 'Josesitos', 0, '2026-02-24 21:44:17'),
(189, 180, 4, 3, 42.00, 1.00, '2022-11-02', '2026-05-06', 'ACTIVO', NULL, NULL, 'Edson02023', 0, '2026-02-24 21:44:17'),
(190, 181, 4, 3, 39.00, 1.00, '2022-11-03', '2026-05-09', 'ACTIVO', NULL, NULL, 'ArielG186', 0, '2026-02-24 21:44:17'),
(191, 182, 4, 3, 28.00, 1.00, '2023-09-09', '2026-05-13', 'ACTIVO', NULL, NULL, 'Andreslr', 0, '2026-02-24 21:44:17'),
(192, 183, 4, 3, 14.00, 1.00, '2025-03-15', '2026-05-15', 'ACTIVO', NULL, NULL, 'Irisabarca', 0, '2026-02-24 21:44:17'),
(193, 184, 4, 3, 14.00, 1.00, '2025-03-15', '2026-05-15', 'ACTIVO', NULL, NULL, 'Elbac', 0, '2026-02-24 21:44:17'),
(194, 185, 4, 3, 14.00, 1.00, '2025-03-07', '2026-05-16', 'ACTIVO', NULL, NULL, 'Dhaflujo', 0, '2026-02-24 21:44:17'),
(195, 186, 4, 3, 7.00, 1.00, '2025-10-17', '2026-05-17', 'ACTIVO', NULL, NULL, 'Mateo2945', 0, '2026-02-24 21:44:17'),
(196, 187, 4, 3, 43.00, 1.00, '2022-10-19', '2026-05-19', 'ACTIVO', NULL, NULL, 'AbastoflorF', 0, '2026-02-24 21:44:17'),
(197, 188, 4, 3, 22.00, 1.00, '2024-07-17', '2026-05-21', 'ACTIVO', NULL, NULL, 'Jessica1212', 0, '2026-02-24 21:44:17'),
(198, 189, 4, 3, 9.00, 1.00, '2025-08-20', '2026-05-21', 'ACTIVO', NULL, NULL, 'Caneltina', 0, '2026-02-24 21:44:17'),
(199, 190, 4, 3, 24.00, 1.00, '2024-05-10', '2026-05-22', 'ACTIVO', NULL, NULL, 'Rrossmar81', 0, '2026-02-24 21:44:17'),
(200, 191, 4, 3, 10.00, 1.00, '2025-06-22', '2026-05-22', 'ACTIVO', NULL, NULL, 'Fjtvgh174', 0, '2026-02-24 21:44:17'),
(201, 192, 4, 3, 45.00, 1.00, '2022-08-22', '2026-05-22', 'ACTIVO', NULL, NULL, 'juanaugusto75', 0, '2026-02-24 21:44:17'),
(202, 193, 4, 3, 19.00, 1.00, '2024-04-09', '2026-05-29', 'ACTIVO', NULL, NULL, 'Andres2405', 0, '2026-02-24 21:44:17'),
(203, 194, 4, 3, 43.00, 1.00, '2022-10-28', '2026-05-30', 'ACTIVO', NULL, NULL, 'Jannette', 0, '2026-02-24 21:44:17'),
(204, 195, 4, 3, 44.00, 1.00, '2022-09-30', '2026-05-31', 'ACTIVO', NULL, NULL, 'MiguelFerrufino', 0, '2026-02-24 21:44:17'),
(205, 196, 4, 3, 36.00, 1.00, '2022-11-04', '2026-05-31', 'ACTIVO', NULL, NULL, 'Harold195', 0, '2026-02-24 21:44:17'),
(206, 197, 4, 3, 39.00, 1.00, '2022-11-26', '2026-06-04', 'ACTIVO', NULL, NULL, 'MarcioG25', 0, '2026-02-24 21:44:17'),
(207, 198, 4, 3, 35.00, 1.00, '2023-07-03', '2026-06-06', 'ACTIVO', NULL, NULL, 'magisgt102', 0, '2026-02-24 21:44:17'),
(208, 199, 4, 3, 28.00, 1.00, '2024-02-07', '2026-06-07', 'ACTIVO', NULL, NULL, 'Gato4924', 0, '2026-02-24 21:44:17'),
(209, 200, 4, 3, 21.00, 1.00, '2024-09-11', '2026-06-11', 'ACTIVO', NULL, NULL, 'Taveras', 0, '2026-02-24 21:44:17'),
(210, 201, 4, 3, 31.00, 1.00, '2023-11-15', '2026-06-15', 'ACTIVO', NULL, NULL, 'Omarll07', 0, '2026-02-24 21:44:17'),
(211, 202, 4, 3, 14.00, 1.00, '2025-04-16', '2026-06-16', 'ACTIVO', NULL, NULL, 'Gabysil', 0, '2026-02-24 21:44:17'),
(212, 203, 4, 3, 21.00, 1.00, '2024-09-18', '2026-06-18', 'ACTIVO', NULL, NULL, 'camperoj1507', 0, '2026-02-24 21:44:17'),
(213, 204, 4, 3, 24.00, 1.00, '2024-06-20', '2026-06-20', 'ACTIVO', NULL, NULL, 'PachecoRiva123', 0, '2026-02-24 21:44:17'),
(214, 205, 4, 3, 31.00, 1.00, '2023-11-26', '2026-06-27', 'ACTIVO', NULL, NULL, 'IvanT28', 0, '2026-02-24 21:44:17'),
(215, 206, 4, 3, 7.00, 1.00, '2025-12-03', '2026-07-03', 'ACTIVO', NULL, NULL, 'VladimirM65', 0, '2026-02-24 21:44:17'),
(216, 207, 4, 3, 14.00, 1.00, '2025-05-03', '2026-07-03', 'ACTIVO', NULL, NULL, 'Fjtvgh172', 0, '2026-02-24 21:44:17'),
(217, 208, 4, 3, 25.00, 1.00, '2024-06-05', '2026-07-05', 'ACTIVO', NULL, NULL, 'Raul2945', 0, '2026-02-24 21:44:17'),
(218, 209, 4, 3, 32.00, 1.00, '2023-11-10', '2026-07-10', 'ACTIVO', NULL, NULL, 'Richardo47', 0, '2026-02-24 21:44:17'),
(219, 210, 4, 3, 46.00, 1.00, '2022-09-01', '2026-07-15', 'ACTIVO', NULL, NULL, 'LUISG151', 0, '2026-02-24 21:44:17'),
(220, 211, 4, 3, 21.00, 1.00, '2024-10-15', '2026-07-15', 'ACTIVO', NULL, NULL, 'Miguel11099', 0, '2026-02-24 21:44:17'),
(221, 212, 4, 3, 44.00, 1.00, '2022-11-10', '2026-08-07', 'ACTIVO', NULL, NULL, 'Carla175', 0, '2026-02-24 21:44:17'),
(222, 213, 4, 3, 14.00, 1.00, '2025-06-11', '2026-08-11', 'ACTIVO', NULL, NULL, 'Fjtvgh201', 0, '2026-02-24 21:44:17'),
(223, 214, 4, 3, 43.00, 1.00, '2022-12-05', '2026-08-12', 'ACTIVO', NULL, NULL, 'DanielQ25', 0, '2026-02-24 21:44:17'),
(224, 215, 4, 3, 16.00, 1.00, '2025-04-19', '2026-08-20', 'ACTIVO', NULL, NULL, 'Waltru75', 0, '2026-02-24 21:44:17'),
(225, 216, 4, 3, 10.00, 1.00, '2025-10-22', '2026-08-22', 'ACTIVO', NULL, NULL, 'DACA25', 0, '2026-02-24 21:44:17'),
(226, 217, 4, 3, 45.00, 1.00, '2022-11-22', '2026-08-22', 'ACTIVO', NULL, NULL, 'Lisett32', 0, '2026-02-24 21:44:17'),
(227, 218, 4, 3, 7.00, 1.00, '2026-01-30', '2026-08-30', 'ACTIVO', NULL, NULL, 'Fernando7669183', 0, '2026-02-24 21:44:17'),
(228, 219, 4, 3, 33.00, 1.00, '2023-11-23', '2026-09-01', 'ACTIVO', NULL, NULL, 'IvaZoeSah', 0, '2026-02-24 21:44:17'),
(229, 220, 4, 3, 7.00, 1.00, '2026-02-02', '2026-09-02', 'ACTIVO', NULL, NULL, 'Erlan1100', 0, '2026-02-24 21:44:17'),
(230, 221, 4, 3, 45.00, 1.00, '2022-12-09', '2026-09-09', 'ACTIVO', NULL, NULL, 'Rey21', 0, '2026-02-24 21:44:17'),
(231, 222, 4, 3, 28.00, 1.00, '2024-04-29', '2026-09-09', 'ACTIVO', NULL, NULL, 'Mgtvgh908', 0, '2026-02-24 21:44:17'),
(232, 223, 4, 3, 48.00, 1.00, '2022-09-11', '2026-09-13', 'ACTIVO', NULL, NULL, 'augustogs', 0, '2026-02-24 21:44:17'),
(233, 224, 4, 3, 28.00, 1.00, '2024-03-24', '2026-09-18', 'ACTIVO', NULL, NULL, 'Mgtvgh907', 0, '2026-02-24 21:44:17'),
(234, 225, 4, 3, 45.00, 1.00, '2022-12-13', '2026-09-21', 'ACTIVO', NULL, NULL, 'FERNANDAGV', 0, '2026-02-24 21:44:17'),
(235, 226, 4, 3, 7.00, 1.00, '2026-02-22', '2026-09-22', 'ACTIVO', NULL, NULL, 'Fjtvgh182', 0, '2026-02-24 21:44:17'),
(236, 227, 4, 3, 27.00, 1.00, '2024-06-23', '2026-09-23', 'ACTIVO', NULL, NULL, 'Gatico0330', 0, '2026-02-24 21:44:17'),
(237, 228, 4, 3, 28.00, 1.00, '2024-07-03', '2026-11-04', 'ACTIVO', NULL, NULL, 'QUEZADAE', 0, '2026-02-24 21:44:17'),
(238, 229, 4, 3, 29.00, 1.00, '2024-07-02', '2026-12-06', 'ACTIVO', NULL, NULL, 'bmoreno287', 0, '2026-02-24 21:44:17'),
(239, 230, 4, 3, 37.00, 1.00, '2023-11-21', '2026-12-21', 'ACTIVO', NULL, NULL, 'ARIELHC48', 0, '2026-02-24 21:44:17'),
(240, 231, 4, 3, 14.00, 1.00, '2025-11-29', '2027-01-29', 'ACTIVO', NULL, NULL, 'Jtorrico54', 0, '2026-02-24 21:44:17'),
(241, 232, 4, 3, 14.00, 1.00, '2025-11-30', '2027-01-30', 'ACTIVO', NULL, NULL, 'Fjtvbo2945', 0, '2026-02-24 21:44:17'),
(242, 233, 4, 3, 50.00, 1.00, '2022-12-04', '2027-02-04', 'ACTIVO', NULL, NULL, 'Carlos282', 0, '2026-02-24 21:44:17'),
(243, 234, 4, 3, 27.00, 1.00, '2024-11-19', '2027-03-06', 'ACTIVO', NULL, NULL, 'CamAgus53', 0, '2026-02-24 21:44:17'),
(244, 235, 4, 3, 14.00, 1.00, '2026-01-15', '2027-03-15', 'ACTIVO', NULL, NULL, 'OctavioH75', 0, '2026-02-24 21:44:17'),
(245, 236, 4, 3, 28.00, 1.00, '2024-12-14', '2027-04-14', 'ACTIVO', NULL, NULL, 'Fjtvgh156', 0, '2026-02-24 21:44:17');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=237;

--
-- AUTO_INCREMENT de la tabla `modalidades`
--
ALTER TABLE `modalidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `movimientos`
--
ALTER TABLE `movimientos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `plataformas`
--
ALTER TABLE `plataformas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `suscripciones`
--
ALTER TABLE `suscripciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=246;

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
