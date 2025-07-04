-- phpMyAdmin SQL Dump
-- version 4.9.11
-- https://www.phpmyadmin.net/
--
-- Servidor: db5016197746.hosting-data.io
-- Tiempo de generación: 26-06-2025 a las 04:45:47
-- Versión del servidor: 10.11.7-MariaDB-log
-- Versión de PHP: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `dbs13181300`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `archivos_entrega_recogida`
--

CREATE TABLE `archivos_entrega_recogida` (
  `id` int(11) NOT NULL,
  `porte_id` int(11) NOT NULL,
  `tipo` varchar(20) DEFAULT NULL,
  `archivo_nombre` varchar(255) NOT NULL,
  `fecha_subida` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `autologin_tokens`
--

CREATE TABLE `autologin_tokens` (
  `id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_creado` datetime NOT NULL,
  `fecha_expira` datetime NOT NULL,
  `usado` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cambios_titularidad`
--

CREATE TABLE `cambios_titularidad` (
  `id` int(11) NOT NULL,
  `usuario_id_1` int(11) NOT NULL,
  `usuario_id_2` int(11) NOT NULL,
  `porte_id` int(11) NOT NULL,
  `fecha` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `camioneros`
--

CREATE TABLE `camioneros` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `tipo_carnet` varchar(10) NOT NULL,
  `num_licencia` varchar(20) DEFAULT NULL,
  `fecha_caducidad` date DEFAULT NULL,
  `caducidad_profesional` date DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `fecha_contratacion` date DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contactos`
--

CREATE TABLE `contactos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `contacto_usuario_id` int(11) NOT NULL,
  `visibilidad` enum('basico','completo') NOT NULL DEFAULT 'basico',
  `fecha_agregado` timestamp NULL DEFAULT current_timestamp(),
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `direcciones`
--

CREATE TABLE `direcciones` (
  `id` int(11) NOT NULL,
  `nombre_via` varchar(255) NOT NULL,
  `numero` varchar(50) DEFAULT NULL,
  `complemento` varchar(255) DEFAULT NULL,
  `ciudad` varchar(100) NOT NULL,
  `estado_provincia` varchar(100) DEFAULT NULL,
  `codigo_postal` varchar(20) DEFAULT NULL,
  `pais` varchar(100) NOT NULL,
  `region` varchar(100) DEFAULT NULL,
  `tipo_direccion` enum('fiscal','recogida_entrega') NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `codigo_pais` varchar(3) NOT NULL,
  `coordenadas` point DEFAULT NULL,
  `tiempo_medio_recogida` time DEFAULT NULL,
  `tiempo_medio_entrega` time DEFAULT NULL,
  `telefono_contacto` varchar(20) DEFAULT NULL,
  `email_contacto` varchar(100) DEFAULT NULL,
  `uid_fiscal` int(11) GENERATED ALWAYS AS (case when `tipo_direccion` = 'fiscal' then `usuario_id` end) VIRTUAL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documentos_camioneros`
--

CREATE TABLE `documentos_camioneros` (
  `id` int(11) NOT NULL,
  `camionero_id` int(11) NOT NULL,
  `tipo_documento` varchar(50) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta_archivo` text NOT NULL,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documentos_usuarios`
--

CREATE TABLE `documentos_usuarios` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo_documento` enum('dni','contrato','otros') DEFAULT 'otros',
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta_archivo` varchar(255) NOT NULL,
  `fecha_subida` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documentos_vehiculos`
--

CREATE TABLE `documentos_vehiculos` (
  `id` int(11) NOT NULL,
  `vehiculo_id` int(11) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta_archivo` text NOT NULL,
  `tipo_mime` varchar(100) DEFAULT NULL,
  `tamano_kb` int(11) DEFAULT NULL,
  `fecha_subida` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `entidades`
--

CREATE TABLE `entidades` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `cif` varchar(50) DEFAULT NULL,
  `registrado` tinyint(1) DEFAULT 0,
  `creado_en_porte` tinyint(1) DEFAULT 0,
  `Observaciones` text DEFAULT NULL,
  `fecha_ultimo_uso` datetime DEFAULT NULL,
  `entidad_usuario_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `entidad_cedente`
--

CREATE TABLE `entidad_cedente` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `cif` varchar(50) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eventos`
--

CREATE TABLE `eventos` (
  `id` int(11) NOT NULL,
  `porte_id` int(11) NOT NULL,
  `tipo_evento` varchar(10) NOT NULL COMMENT 'recogida o entrega',
  `hora_llegada` datetime DEFAULT NULL,
  `geolocalizacion_llegada` varchar(100) DEFAULT NULL,
  `subida_llegada` tinyint(1) NOT NULL DEFAULT 0,
  `hora_salida` datetime DEFAULT NULL,
  `geolocalizacion_salida` varchar(100) DEFAULT NULL,
  `subida_salida` tinyint(1) NOT NULL DEFAULT 0,
  `estado_mercancia` varchar(50) DEFAULT NULL,
  `subida_estado` tinyint(1) NOT NULL DEFAULT 0,
  `observaciones` text DEFAULT NULL,
  `fecha_observaciones` datetime DEFAULT NULL,
  `firma` varchar(255) DEFAULT NULL,
  `nombre_firmante` varchar(100) DEFAULT NULL,
  `identificacion_firmante` varchar(50) DEFAULT NULL,
  `fecha_firma` datetime DEFAULT NULL,
  `subida_firma` tinyint(1) NOT NULL DEFAULT 0,
  `geolocalizacion_firma` varchar(100) DEFAULT NULL,
  `firma_local` text DEFAULT NULL,
  `resultado_operacion` enum('pendiente','recogida_ok','no_recogida','entrega_ok','no_entrega') DEFAULT 'pendiente',
  `motivo_no_recogida` varchar(10) DEFAULT NULL,
  `obs_no_recogida` varchar(255) DEFAULT NULL,
  `subida_resultado` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Disparadores `eventos`
--
DELIMITER $$
CREATE TRIGGER `tr_marcar_porte_finalizado` AFTER UPDATE ON `eventos` FOR EACH ROW BEGIN
    IF NEW.tipo_evento='entrega' AND NEW.hora_salida IS NOT NULL THEN
        UPDATE portes
           SET estado_recogida_entrega='Entregado'
         WHERE id=NEW.porte_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_marcar_porte_recogido` AFTER UPDATE ON `eventos` FOR EACH ROW BEGIN
    IF NEW.tipo_evento = 'recogida' 
       AND NEW.hora_salida IS NOT NULL THEN
        UPDATE portes
           SET estado_recogida_entrega = 'Recogido'
         WHERE id = NEW.porte_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facturas`
--

CREATE TABLE `facturas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tren_id` int(11) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `foto_local` varchar(260) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facturas_saas`
--

CREATE TABLE `facturas_saas` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `periodo_ini` date NOT NULL,
  `periodo_fin` date NOT NULL,
  `usuarios_cobrados` int(11) NOT NULL,
  `almacenamiento_mb` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `iva` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `estado` enum('pendiente','pagado','fallido') DEFAULT 'pendiente',
  `pdf_url` varchar(255) DEFAULT NULL,
  `fecha_creada` timestamp NULL DEFAULT current_timestamp(),
  `serie` varchar(10) DEFAULT 'SAAS-2025',
  `num_factura` int(11) DEFAULT 0,
  `cuota_gratis` tinyint(1) DEFAULT 0,
  `users_base` int(11) DEFAULT 0,
  `gb_exceso` decimal(8,3) DEFAULT 0.000,
  `base_usuarios` decimal(10,2) DEFAULT 0.00,
  `base_memoria` decimal(10,2) DEFAULT 0.00,
  `iva_pct` decimal(5,2) DEFAULT 21.00,
  `stripe_session` varchar(191) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `mensaje` text NOT NULL,
  `fecha` timestamp NULL DEFAULT current_timestamp(),
  `estado` enum('abierto','en_progreso','cerrado') DEFAULT 'abierto',
  `respuesta` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grupos`
--

CREATE TABLE `grupos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grupo_contactos`
--

CREATE TABLE `grupo_contactos` (
  `id` int(11) NOT NULL,
  `grupo_id` int(11) NOT NULL,
  `contacto_id` int(11) DEFAULT NULL,
  `fecha_agregado` timestamp NULL DEFAULT current_timestamp(),
  `entidad_id` int(11) DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `informacion_pago`
--

CREATE TABLE `informacion_pago` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `metodo_pago` varchar(50) DEFAULT NULL,
  `numero_tarjeta` varchar(20) DEFAULT NULL,
  `fecha_expiracion` varchar(7) DEFAULT NULL,
  `codigo_cvv` varchar(4) DEFAULT NULL,
  `direccion_facturacion` varchar(255) DEFAULT NULL,
  `nombre_facturacion` varchar(100) DEFAULT NULL,
  `email_facturacion` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `invitaciones`
--

CREATE TABLE `invitaciones` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` char(64) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `fecha_invitacion` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `login_tokens`
--

CREATE TABLE `login_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `fecha_creado` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `log_impersonacion`
--

CREATE TABLE `log_impersonacion` (
  `id` int(11) NOT NULL,
  `super_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_ini` datetime NOT NULL,
  `fecha_fin` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `monedas`
--

CREATE TABLE `monedas` (
  `id` int(11) NOT NULL,
  `codigo` varchar(3) NOT NULL,
  `nombre_en` varchar(50) NOT NULL,
  `nombre_es` varchar(50) NOT NULL,
  `nombre_zh` varchar(50) DEFAULT NULL,
  `nombre_ar` varchar(50) DEFAULT NULL,
  `nombre_ru` varchar(50) DEFAULT NULL,
  `nombre_fr` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `multimedia_recogida_entrega`
--

CREATE TABLE `multimedia_recogida_entrega` (
  `id` int(11) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `tipo_archivo` varchar(10) NOT NULL COMMENT 'foto o video',
  `url_archivo` text DEFAULT NULL,
  `geolocalizacion` varchar(100) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `tamano` int(11) DEFAULT NULL COMMENT 'Tamaño del archivo en KB',
  `categoria` enum('documento','carga','otros') DEFAULT NULL,
  `porte_id` int(11) DEFAULT NULL,
  `tipo_evento` varchar(255) DEFAULT NULL,
  `ruta_local` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ofertas_externas`
--

CREATE TABLE `ofertas_externas` (
  `id` int(11) NOT NULL,
  `porte_id` int(11) NOT NULL,
  `ofertante_id` int(11) NOT NULL,
  `ofertante_gestor_id` int(11) DEFAULT NULL,
  `entidad_id` int(11) DEFAULT NULL,
  `usuario_gestor_id` int(11) DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `enlace` varchar(255) DEFAULT NULL,
  `estado` enum('pendiente','aceptado','rechazado','seleccionado','caducado') NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `deadline` datetime NOT NULL,
  `fecha_seleccion` datetime DEFAULT NULL,
  `precio_externo` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ofertas_varios`
--

CREATE TABLE `ofertas_varios` (
  `id` int(11) NOT NULL,
  `porte_id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `estado_oferta` enum('pendiente','asignado','asignado_a_otro','rechazado','caducado') NOT NULL,
  `deadline` datetime NOT NULL,
  `ofertante_id` int(11) DEFAULT NULL,
  `fecha_oferta` datetime DEFAULT current_timestamp(),
  `precio` decimal(10,2) NOT NULL,
  `moneda` varchar(3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `ofertas_varios`
--
DELIMITER $$
CREATE TRIGGER `crear_seleccionados_oferta` AFTER UPDATE ON `ofertas_varios` FOR EACH ROW BEGIN
    IF NEW.estado_oferta = 'asignado' THEN
        INSERT INTO seleccionados_oferta (oferta_id, usuario_id, porte_id, ofertante_id)
        VALUES (NEW.id, NEW.usuario_id, NEW.porte_id, NEW.ofertante_id)
        ON DUPLICATE KEY UPDATE
            usuario_id = NEW.usuario_id,
            porte_id = NEW.porte_id,
            ofertante_id = NEW.ofertante_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id` int(11) NOT NULL,
  `porte_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `monto` decimal(10,2) DEFAULT NULL,
  `estado_pago` enum('pendiente','completado','fallido') NOT NULL,
  `fecha_pago` timestamp NULL DEFAULT current_timestamp(),
  `metodo_pago` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `portes`
--

CREATE TABLE `portes` (
  `id` int(11) NOT NULL,
  `usuario_creador_id` int(11) DEFAULT NULL,
  `mercancia_descripcion` text DEFAULT NULL,
  `mercancia_conservacion` enum('Ninguna','Cadena de frío','Refrigerado','Congelado','Isotérmico','Seco','ATM') NOT NULL,
  `mercancia_temperatura` decimal(5,2) DEFAULT NULL,
  `tipo_camion` enum('Camión Cerrado','Camión Abierto','Camión Frigorífico','Camión Cisterna','Camión de Animales','Camión de Plataforma','Camión de Lona') NOT NULL,
  `cantidad` varchar(11) DEFAULT NULL,
  `peso_total` decimal(10,2) DEFAULT NULL,
  `volumen_total` decimal(10,2) DEFAULT NULL,
  `se_puede_remontar` tinyint(1) DEFAULT NULL,
  `tipo_carga` enum('Grupaje','Camion_entero') NOT NULL DEFAULT 'Grupaje',
  `observaciones` text DEFAULT NULL,
  `localizacion_recogida` varchar(255) DEFAULT NULL,
  `fecha_recogida` date DEFAULT NULL,
  `recogida_hora_inicio` time NOT NULL,
  `observaciones_recogida` text DEFAULT NULL,
  `localizacion_entrega` varchar(255) DEFAULT NULL,
  `fecha_entrega` date DEFAULT NULL,
  `entrega_hora_inicio` time NOT NULL,
  `observaciones_entrega` text DEFAULT NULL,
  `no_transbordos` tinyint(1) DEFAULT NULL,
  `no_delegacion_transporte` tinyint(1) DEFAULT NULL,
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp(),
  `adr` tinyint(1) DEFAULT 0,
  `paletizado` tinyint(1) DEFAULT NULL,
  `intercambio_palets` tinyint(1) DEFAULT NULL,
  `dimensiones_maximas` varchar(50) DEFAULT NULL,
  `recogida_hora_fin` time DEFAULT NULL,
  `entrega_hora_fin` time DEFAULT NULL,
  `temperatura_minima` decimal(5,2) DEFAULT NULL,
  `temperatura_maxima` decimal(5,2) DEFAULT NULL,
  `cadena_frio` tinyint(1) DEFAULT 0,
  `destinatario_usuario_id` int(11) DEFAULT NULL,
  `destinatario_entidad_id` int(11) DEFAULT NULL,
  `nombre_destinatario` varchar(255) DEFAULT NULL,
  `expedidor_usuario_id` int(11) DEFAULT NULL,
  `expedidor_entidad_id` int(11) DEFAULT NULL,
  `nombre_expedidor` varchar(255) DEFAULT NULL,
  `cliente_usuario_id` int(11) DEFAULT NULL,
  `cliente_entidad_id` int(11) DEFAULT NULL,
  `tipo_palet` enum('ninguno','europeo','americano') DEFAULT 'ninguno',
  `estado_recogida_entrega` varchar(20) DEFAULT 'Pendiente',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `portes_importados`
--

CREATE TABLE `portes_importados` (
  `id` int(11) NOT NULL,
  `porte_id` int(11) NOT NULL,
  `cedente_id` int(11) NOT NULL,
  `fecha_importado` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `porte_tren`
--

CREATE TABLE `porte_tren` (
  `id` int(11) NOT NULL,
  `porte_id` int(11) NOT NULL,
  `tren_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `inicio_tren` datetime NOT NULL,
  `fin_tren` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seleccionados_oferta`
--

CREATE TABLE `seleccionados_oferta` (
  `id` int(11) NOT NULL,
  `oferta_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `porte_id` int(11) NOT NULL,
  `fecha_seleccion` datetime DEFAULT NULL,
  `ofertante_id` int(11) NOT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `moneda` varchar(3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Disparadores `seleccionados_oferta`
--
DELIMITER $$
CREATE TRIGGER `insertar_fecha_seleccion` BEFORE INSERT ON `seleccionados_oferta` FOR EACH ROW BEGIN
    -- Establecer la fecha actual en el campo fecha_seleccion antes de la inserción
    SET NEW.fecha_seleccion = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tren`
--

CREATE TABLE `tren` (
  `id` int(11) NOT NULL,
  `tren_nombre` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tren_camionero`
--

CREATE TABLE `tren_camionero` (
  `id` int(11) NOT NULL,
  `tren_id` int(11) NOT NULL,
  `camionero_id` int(11) NOT NULL,
  `inicio_tren_camionero` datetime NOT NULL,
  `fin_tren_camionero` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tren_vehiculos`
--

CREATE TABLE `tren_vehiculos` (
  `id` int(11) NOT NULL,
  `tren_id` int(11) NOT NULL,
  `vehiculo_id` int(11) NOT NULL,
  `inicio_vehiculo_tren` datetime NOT NULL,
  `fin_vehiculo_tren` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `uso_mensual_empresa`
--

CREATE TABLE `uso_mensual_empresa` (
  `empresa_id` int(11) NOT NULL,
  `anio` smallint(6) NOT NULL,
  `mes` tinyint(4) NOT NULL,
  `usuarios_activos` int(11) NOT NULL,
  `almacenamiento_mb` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `fecha_registro` timestamp NULL DEFAULT current_timestamp(),
  `cif` varchar(50) DEFAULT NULL,
  `nombre_usuario` varchar(255) DEFAULT NULL,
  `apellidos` varchar(50) NOT NULL,
  `rol` enum('administrador','gestor','camionero','asociado','superadmin') NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `token_verificacion` varchar(64) DEFAULT NULL,
  `email_verificado` tinyint(1) DEFAULT 0,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `intentos_fallidos` int(11) NOT NULL DEFAULT 0,
  `bloqueo_hasta` datetime DEFAULT NULL,
  `expiracion_token` datetime DEFAULT NULL,
  `titulacion_gestor` varchar(255) DEFAULT NULL,
  `token_sesion` varchar(64) DEFAULT NULL,
  `nombre_empresa` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vehiculos`
--

CREATE TABLE `vehiculos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `nivel_1` enum('camion_rigido','cabeza_tractora','semirremolque','remolque') DEFAULT NULL,
  `nivel_2` varchar(50) DEFAULT NULL,
  `nivel_3` varchar(255) DEFAULT NULL,
  `matricula` varchar(20) NOT NULL,
  `marca` varchar(50) DEFAULT NULL,
  `modelo` varchar(50) DEFAULT NULL,
  `capacidad` decimal(10,2) DEFAULT NULL,
  `capacidad_arrastre` decimal(10,2) DEFAULT NULL,
  `largo` decimal(10,2) DEFAULT NULL,
  `ancho` decimal(10,2) DEFAULT NULL,
  `alto` decimal(10,2) DEFAULT NULL,
  `adr` tinyint(1) DEFAULT 0,
  `doble_conductor` tinyint(1) DEFAULT 0,
  `plataforma_elevadora` tinyint(1) DEFAULT 0,
  `telefono` varchar(15) DEFAULT NULL,
  `ano_fabricacion` year(4) DEFAULT NULL,
  `temperatura_controlada` tinyint(1) DEFAULT 0,
  `volumen` decimal(10,2) DEFAULT NULL,
  `forma_carga_lateral` tinyint(1) DEFAULT 0,
  `forma_carga_detras` tinyint(1) DEFAULT 0,
  `forma_carga_arriba` tinyint(1) DEFAULT 0,
  `numero_ejes` int(11) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `eliminado` enum('no','sí') DEFAULT 'no'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_memoria_empresa_kb`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_memoria_empresa_kb` (
`emp_id` int(11)
,`total_kb` decimal(32,0)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_empresas_con_ubicacion`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_empresas_con_ubicacion` (
`empresa_id` int(11)
,`nombre_empresa` varchar(255)
,`codigo_pais` varchar(3)
,`pais` varchar(100)
,`region` varchar(100)
,`ciudad` varchar(100)
,`estado_provincia` varchar(100)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_totales_pais`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_totales_pais` (
`pais` varchar(100)
,`codigo_pais` varchar(3)
,`empresas` bigint(21)
,`usuarios_totales` decimal(42,0)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_totales_region`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_totales_region` (
`pais` varchar(100)
,`region` varchar(100)
,`empresas` bigint(21)
,`usuarios_totales` decimal(42,0)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_uso_pais_mes`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_uso_pais_mes` (
`codigo_pais` varchar(3)
,`pais` varchar(100)
,`region` varchar(100)
,`anio` smallint(6)
,`mes` tinyint(4)
,`empresas` bigint(21)
,`usuarios_activos` decimal(32,0)
,`almacenamiento_mb` decimal(32,0)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_usuarios_empresa`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_usuarios_empresa` (
`empresa_id` int(11)
,`usuarios_totales` bigint(21)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_memoria_empresa_kb`
--
DROP TABLE IF EXISTS `vw_memoria_empresa_kb`;

CREATE ALGORITHM=UNDEFINED DEFINER=`o13181300`@`%` SQL SECURITY DEFINER VIEW `vw_memoria_empresa_kb`  AS SELECT `t`.`emp_id` AS `emp_id`, sum(`t`.`size_kb`) AS `total_kb` FROM (select `u`.`admin_id` AS `emp_id`,`me`.`tamano` AS `size_kb` from ((`multimedia_recogida_entrega` `me` join `portes` `p` on(`p`.`id` = `me`.`porte_id`)) join `usuarios` `u` on(`u`.`id` = `p`.`usuario_creador_id`)) union all select `u`.`admin_id` AS `emp_id`,`dv`.`tamano_kb` AS `size_kb` from ((`documentos_vehiculos` `dv` join `vehiculos` `v` on(`v`.`id` = `dv`.`vehiculo_id`)) join `usuarios` `u` on(`u`.`id` = `v`.`usuario_id`))) AS `t` GROUP BY `t`.`emp_id``emp_id` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_empresas_con_ubicacion`
--
DROP TABLE IF EXISTS `v_empresas_con_ubicacion`;

CREATE ALGORITHM=UNDEFINED DEFINER=`o13181300`@`%` SQL SECURITY DEFINER VIEW `v_empresas_con_ubicacion`  AS SELECT `u`.`id` AS `empresa_id`, `u`.`nombre_empresa` AS `nombre_empresa`, `d`.`codigo_pais` AS `codigo_pais`, `d`.`pais` AS `pais`, `d`.`region` AS `region`, `d`.`ciudad` AS `ciudad`, `d`.`estado_provincia` AS `estado_provincia` FROM (`usuarios` `u` left join `direcciones` `d` on(`d`.`usuario_id` = `u`.`id` and `d`.`tipo_direccion` = 'fiscal')) WHERE `u`.`rol` = 'administrador''administrador' ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_totales_pais`
--
DROP TABLE IF EXISTS `v_totales_pais`;

CREATE ALGORITHM=UNDEFINED DEFINER=`o13181300`@`%` SQL SECURITY DEFINER VIEW `v_totales_pais`  AS SELECT `v`.`pais` AS `pais`, `v`.`codigo_pais` AS `codigo_pais`, count(distinct `v`.`empresa_id`) AS `empresas`, sum(coalesce(`ue`.`usuarios_totales`,0)) AS `usuarios_totales` FROM (`v_empresas_con_ubicacion` `v` left join `v_usuarios_empresa` `ue` on(`ue`.`empresa_id` = `v`.`empresa_id`)) GROUP BY `v`.`pais`, `v`.`codigo_pais` ORDER BY count(distinct `v`.`empresa_id`) DESCdesc ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_totales_region`
--
DROP TABLE IF EXISTS `v_totales_region`;

CREATE ALGORITHM=UNDEFINED DEFINER=`o13181300`@`%` SQL SECURITY DEFINER VIEW `v_totales_region`  AS SELECT `v`.`pais` AS `pais`, `v`.`region` AS `region`, count(distinct `v`.`empresa_id`) AS `empresas`, sum(coalesce(`ue`.`usuarios_totales`,0)) AS `usuarios_totales` FROM (`v_empresas_con_ubicacion` `v` left join `v_usuarios_empresa` `ue` on(`ue`.`empresa_id` = `v`.`empresa_id`)) GROUP BY `v`.`pais`, `v`.`region` ORDER BY count(distinct `v`.`empresa_id`) DESCdesc ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_uso_pais_mes`
--
DROP TABLE IF EXISTS `v_uso_pais_mes`;

CREATE ALGORITHM=UNDEFINED DEFINER=`o13181300`@`%` SQL SECURITY DEFINER VIEW `v_uso_pais_mes`  AS SELECT `e`.`codigo_pais` AS `codigo_pais`, `e`.`pais` AS `pais`, `e`.`region` AS `region`, `um`.`anio` AS `anio`, `um`.`mes` AS `mes`, count(0) AS `empresas`, sum(`um`.`usuarios_activos`) AS `usuarios_activos`, sum(`um`.`almacenamiento_mb`) AS `almacenamiento_mb` FROM (`uso_mensual_empresa` `um` join `v_empresas_con_ubicacion` `e` on(`e`.`empresa_id` = `um`.`empresa_id`)) GROUP BY `e`.`codigo_pais`, `e`.`pais`, `e`.`region`, `um`.`anio`, `um`.`mes``mes` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_usuarios_empresa`
--
DROP TABLE IF EXISTS `v_usuarios_empresa`;

CREATE ALGORITHM=UNDEFINED DEFINER=`o13181300`@`%` SQL SECURITY DEFINER VIEW `v_usuarios_empresa`  AS SELECT CASE WHEN `u`.`rol` = 'administrador' THEN `u`.`id` ELSE `u`.`admin_id` END AS `empresa_id`, count(0) AS `usuarios_totales` FROM `usuarios` AS `u` WHERE `u`.`estado` = 'activo' GROUP BY CASE WHEN `u`.`rol` = 'administrador' THEN `u`.`id` ELSE `u`.`admin_id` ENDend ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `archivos_entrega_recogida`
--
ALTER TABLE `archivos_entrega_recogida`
  ADD PRIMARY KEY (`id`),
  ADD KEY `porte_id` (`porte_id`);

--
-- Indices de la tabla `autologin_tokens`
--
ALTER TABLE `autologin_tokens`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cambios_titularidad`
--
ALTER TABLE `cambios_titularidad`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id_1` (`usuario_id_1`),
  ADD KEY `usuario_id_2` (`usuario_id_2`),
  ADD KEY `porte_id` (`porte_id`);

--
-- Indices de la tabla `camioneros`
--
ALTER TABLE `camioneros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `contactos`
--
ALTER TABLE `contactos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `contacto_usuario_id` (`contacto_usuario_id`) USING BTREE;

--
-- Indices de la tabla `direcciones`
--
ALTER TABLE `direcciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unq_fiscal_1` (`uid_fiscal`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `documentos_camioneros`
--
ALTER TABLE `documentos_camioneros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `camionero_id` (`camionero_id`);

--
-- Indices de la tabla `documentos_usuarios`
--
ALTER TABLE `documentos_usuarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `documentos_vehiculos`
--
ALTER TABLE `documentos_vehiculos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehiculo_id` (`vehiculo_id`);

--
-- Indices de la tabla `entidades`
--
ALTER TABLE `entidades`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `entidad_cedente`
--
ALTER TABLE `entidad_cedente`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `eventos`
--
ALTER TABLE `eventos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_evento` (`porte_id`,`tipo_evento`) USING BTREE;

--
-- Indices de la tabla `facturas`
--
ALTER TABLE `facturas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `facturas_saas`
--
ALTER TABLE `facturas_saas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `grupos`
--
ALTER TABLE `grupos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `grupo_contactos`
--
ALTER TABLE `grupo_contactos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grupo_id` (`grupo_id`),
  ADD KEY `contacto_id` (`contacto_id`);

--
-- Indices de la tabla `informacion_pago`
--
ALTER TABLE `informacion_pago`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `invitaciones`
--
ALTER TABLE `invitaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indices de la tabla `login_tokens`
--
ALTER TABLE `login_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`);

--
-- Indices de la tabla `log_impersonacion`
--
ALTER TABLE `log_impersonacion`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `monedas`
--
ALTER TABLE `monedas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `multimedia_recogida_entrega`
--
ALTER TABLE `multimedia_recogida_entrega`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `ofertas_externas`
--
ALTER TABLE `ofertas_externas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `porte_id` (`porte_id`),
  ADD KEY `ofertante_id` (`ofertante_id`),
  ADD KEY `fk_entidad_id` (`entidad_id`);

--
-- Indices de la tabla `ofertas_varios`
--
ALTER TABLE `ofertas_varios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ofertas_varios_porte` (`porte_id`),
  ADD KEY `fk_ofertas_varios_usuario` (`usuario_id`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `porte_id` (`porte_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `portes`
--
ALTER TABLE `portes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cliente_usuario` (`cliente_usuario_id`),
  ADD KEY `fk_cliente_entidad` (`cliente_entidad_id`),
  ADD KEY `fk_expedidor_usuario` (`expedidor_usuario_id`),
  ADD KEY `fk_expedidor_entidad` (`expedidor_entidad_id`),
  ADD KEY `fk_destinatario_usuario` (`destinatario_usuario_id`),
  ADD KEY `fk_destinatario_entidad` (`destinatario_entidad_id`);

--
-- Indices de la tabla `portes_importados`
--
ALTER TABLE `portes_importados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pi_porte` (`porte_id`),
  ADD KEY `fk_pi_cedente` (`cedente_id`);

--
-- Indices de la tabla `porte_tren`
--
ALTER TABLE `porte_tren`
  ADD PRIMARY KEY (`id`),
  ADD KEY `porte_id` (`porte_id`),
  ADD KEY `tren_id` (`tren_id`);

--
-- Indices de la tabla `seleccionados_oferta`
--
ALTER TABLE `seleccionados_oferta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oferta_id` (`oferta_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `tren`
--
ALTER TABLE `tren`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tren_camionero`
--
ALTER TABLE `tren_camionero`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tren_id` (`tren_id`),
  ADD KEY `camionero_id` (`camionero_id`);

--
-- Indices de la tabla `tren_vehiculos`
--
ALTER TABLE `tren_vehiculos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `uso_mensual_empresa`
--
ALTER TABLE `uso_mensual_empresa`
  ADD PRIMARY KEY (`empresa_id`,`anio`,`mes`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `vehiculos`
--
ALTER TABLE `vehiculos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `archivos_entrega_recogida`
--
ALTER TABLE `archivos_entrega_recogida`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `autologin_tokens`
--
ALTER TABLE `autologin_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cambios_titularidad`
--
ALTER TABLE `cambios_titularidad`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `camioneros`
--
ALTER TABLE `camioneros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `contactos`
--
ALTER TABLE `contactos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `direcciones`
--
ALTER TABLE `direcciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `documentos_camioneros`
--
ALTER TABLE `documentos_camioneros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `documentos_usuarios`
--
ALTER TABLE `documentos_usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `documentos_vehiculos`
--
ALTER TABLE `documentos_vehiculos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `entidades`
--
ALTER TABLE `entidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `entidad_cedente`
--
ALTER TABLE `entidad_cedente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `eventos`
--
ALTER TABLE `eventos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `facturas`
--
ALTER TABLE `facturas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `facturas_saas`
--
ALTER TABLE `facturas_saas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `grupos`
--
ALTER TABLE `grupos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `grupo_contactos`
--
ALTER TABLE `grupo_contactos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `informacion_pago`
--
ALTER TABLE `informacion_pago`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `invitaciones`
--
ALTER TABLE `invitaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `login_tokens`
--
ALTER TABLE `login_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `log_impersonacion`
--
ALTER TABLE `log_impersonacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `monedas`
--
ALTER TABLE `monedas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `multimedia_recogida_entrega`
--
ALTER TABLE `multimedia_recogida_entrega`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ofertas_externas`
--
ALTER TABLE `ofertas_externas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ofertas_varios`
--
ALTER TABLE `ofertas_varios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `portes`
--
ALTER TABLE `portes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `portes_importados`
--
ALTER TABLE `portes_importados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `porte_tren`
--
ALTER TABLE `porte_tren`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `seleccionados_oferta`
--
ALTER TABLE `seleccionados_oferta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tren`
--
ALTER TABLE `tren`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tren_camionero`
--
ALTER TABLE `tren_camionero`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tren_vehiculos`
--
ALTER TABLE `tren_vehiculos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `vehiculos`
--
ALTER TABLE `vehiculos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `archivos_entrega_recogida`
--
ALTER TABLE `archivos_entrega_recogida`
  ADD CONSTRAINT `archivos_entrega_recogida_ibfk_1` FOREIGN KEY (`porte_id`) REFERENCES `portes` (`id`);

--
-- Filtros para la tabla `cambios_titularidad`
--
ALTER TABLE `cambios_titularidad`
  ADD CONSTRAINT `cambios_titularidad_ibfk_1` FOREIGN KEY (`usuario_id_1`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `cambios_titularidad_ibfk_2` FOREIGN KEY (`usuario_id_2`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `cambios_titularidad_ibfk_3` FOREIGN KEY (`porte_id`) REFERENCES `portes` (`id`);

--
-- Filtros para la tabla `camioneros`
--
ALTER TABLE `camioneros`
  ADD CONSTRAINT `camioneros_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `contactos`
--
ALTER TABLE `contactos`
  ADD CONSTRAINT `contactos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contactos_ibfk_2` FOREIGN KEY (`contacto_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `direcciones`
--
ALTER TABLE `direcciones`
  ADD CONSTRAINT `direcciones_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `documentos_camioneros`
--
ALTER TABLE `documentos_camioneros`
  ADD CONSTRAINT `documentos_camioneros_ibfk_1` FOREIGN KEY (`camionero_id`) REFERENCES `camioneros` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `documentos_usuarios`
--
ALTER TABLE `documentos_usuarios`
  ADD CONSTRAINT `documentos_usuarios_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `documentos_vehiculos`
--
ALTER TABLE `documentos_vehiculos`
  ADD CONSTRAINT `documentos_vehiculos_ibfk_1` FOREIGN KEY (`vehiculo_id`) REFERENCES `vehiculos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `eventos`
--
ALTER TABLE `eventos`
  ADD CONSTRAINT `eventos_ibfk_1` FOREIGN KEY (`porte_id`) REFERENCES `portes` (`id`);

--
-- Filtros para la tabla `facturas`
--
ALTER TABLE `facturas`
  ADD CONSTRAINT `facturas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `grupos`
--
ALTER TABLE `grupos`
  ADD CONSTRAINT `grupos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `grupo_contactos`
--
ALTER TABLE `grupo_contactos`
  ADD CONSTRAINT `grupo_contactos_ibfk_1` FOREIGN KEY (`grupo_id`) REFERENCES `grupos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grupo_contactos_ibfk_2` FOREIGN KEY (`contacto_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `informacion_pago`
--
ALTER TABLE `informacion_pago`
  ADD CONSTRAINT `informacion_pago_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `invitaciones`
--
ALTER TABLE `invitaciones`
  ADD CONSTRAINT `invitaciones_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ofertas_externas`
--
ALTER TABLE `ofertas_externas`
  ADD CONSTRAINT `fk_entidad_id` FOREIGN KEY (`entidad_id`) REFERENCES `entidades` (`id`),
  ADD CONSTRAINT `ofertas_externas_ibfk_1` FOREIGN KEY (`porte_id`) REFERENCES `portes` (`id`),
  ADD CONSTRAINT `ofertas_externas_ibfk_2` FOREIGN KEY (`ofertante_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `ofertas_varios`
--
ALTER TABLE `ofertas_varios`
  ADD CONSTRAINT `fk_ofertas_varios_porte` FOREIGN KEY (`porte_id`) REFERENCES `portes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ofertas_varios_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ofertas_varios_ibfk_1` FOREIGN KEY (`porte_id`) REFERENCES `portes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ofertas_varios_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`porte_id`) REFERENCES `portes` (`id`),
  ADD CONSTRAINT `pagos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `portes`
--
ALTER TABLE `portes`
  ADD CONSTRAINT `fk_cliente_entidad` FOREIGN KEY (`cliente_entidad_id`) REFERENCES `entidades` (`id`),
  ADD CONSTRAINT `fk_cliente_usuario` FOREIGN KEY (`cliente_usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_destinatario_entidad` FOREIGN KEY (`destinatario_entidad_id`) REFERENCES `entidades` (`id`),
  ADD CONSTRAINT `fk_destinatario_usuario` FOREIGN KEY (`destinatario_usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_expedidor_entidad` FOREIGN KEY (`expedidor_entidad_id`) REFERENCES `entidades` (`id`),
  ADD CONSTRAINT `fk_expedidor_usuario` FOREIGN KEY (`expedidor_usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `portes_importados`
--
ALTER TABLE `portes_importados`
  ADD CONSTRAINT `fk_pi_cedente` FOREIGN KEY (`cedente_id`) REFERENCES `entidad_cedente` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pi_porte` FOREIGN KEY (`porte_id`) REFERENCES `portes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `porte_tren`
--
ALTER TABLE `porte_tren`
  ADD CONSTRAINT `porte_tren_ibfk_1` FOREIGN KEY (`porte_id`) REFERENCES `portes` (`id`),
  ADD CONSTRAINT `porte_tren_ibfk_2` FOREIGN KEY (`tren_id`) REFERENCES `tren` (`id`);

--
-- Filtros para la tabla `seleccionados_oferta`
--
ALTER TABLE `seleccionados_oferta`
  ADD CONSTRAINT `seleccionados_oferta_ibfk_1` FOREIGN KEY (`oferta_id`) REFERENCES `ofertas_varios` (`id`),
  ADD CONSTRAINT `seleccionados_oferta_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `tren_camionero`
--
ALTER TABLE `tren_camionero`
  ADD CONSTRAINT `tren_camionero_ibfk_1` FOREIGN KEY (`tren_id`) REFERENCES `tren` (`id`),
  ADD CONSTRAINT `tren_camionero_ibfk_2` FOREIGN KEY (`camionero_id`) REFERENCES `camioneros` (`id`);

--
-- Filtros para la tabla `vehiculos`
--
ALTER TABLE `vehiculos`
  ADD CONSTRAINT `vehiculos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
