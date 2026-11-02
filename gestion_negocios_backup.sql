-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: gestion_negocios
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `empresas`
--

DROP TABLE IF EXISTS `empresas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `empresas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) NOT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `ultimo_numero_pedido` int(10) unsigned NOT NULL DEFAULT 0,
  `ultimo_numero_compra` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `empresas`
--

LOCK TABLES `empresas` WRITE;
/*!40000 ALTER TABLE `empresas` DISABLE KEYS */;
INSERT INTO `empresas` VALUES (1,'Restaurante Aurora',1,5,2),(2,'Restaurante Roble',1,0,0);
/*!40000 ALTER TABLE `empresas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `insumos`
--

DROP TABLE IF EXISTS `insumos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `insumos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `cantidad_actual` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock_minimo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unidad_medida` varchar(20) NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `proveedor_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_insumo_empresa_nombre` (`empresa_id`,`nombre`),
  UNIQUE KEY `uq_insumo_empresa_id` (`empresa_id`,`id`),
  KEY `ix_insumo_empresa` (`empresa_id`),
  KEY `fk_insumo_proveedor` (`empresa_id`,`proveedor_id`),
  CONSTRAINT `fk_insumo_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  CONSTRAINT `fk_insumo_proveedor` FOREIGN KEY (`empresa_id`, `proveedor_id`) REFERENCES `proveedores` (`empresa_id`, `id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `insumos`
--

LOCK TABLES `insumos` WRITE;
/*!40000 ALTER TABLE `insumos` DISABLE KEYS */;
INSERT INTO `insumos` VALUES (1,1,'Tomate',6.00,7.00,'kg',NULL,1,NULL),(2,1,'Salsa',10.00,8.00,'l',NULL,1,NULL),(3,1,'Pasta',8.00,9.00,'kg',NULL,1,NULL),(4,1,'Queso',100.00,30.00,'g',NULL,1,NULL),(5,1,'Carne',5.00,3.00,'kg',NULL,1,NULL),(7,1,'Botella CocaCola',156.00,20.00,'unidad',NULL,1,1),(8,1,'Pan',30.00,10.00,'unidad',NULL,1,NULL),(9,1,'Zanahorias',2.00,15.00,'unidad',NULL,1,NULL),(10,1,'Lechuga',2.00,3.00,'unidad',NULL,1,NULL),(11,1,'Lavaplatos',10.00,3.00,'unidad',NULL,1,NULL),(12,1,'Pollo',5.00,3.00,'kg',NULL,1,NULL);
/*!40000 ALTER TABLE `insumos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_intentos`
--

DROP TABLE IF EXISTS `login_intentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_intentos` (
  `clave` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `intentos` bigint(20) unsigned NOT NULL,
  `inicio` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`clave`),
  KEY `ix_intento_inicio` (`inicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_intentos`
--

LOCK TABLES `login_intentos` WRITE;
/*!40000 ALTER TABLE `login_intentos` DISABLE KEYS */;
INSERT INTO `login_intentos` VALUES ('227a139b2d0f158050b5542327a01af02caf27028d0e765c5c3b769a71257468',1,1793601454);
/*!40000 ALTER TABLE `login_intentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movimientos_inventario`
--

DROP TABLE IF EXISTS `movimientos_inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `movimientos_inventario` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
  `insumo_id` int(10) unsigned NOT NULL,
  `usuario_id` int(10) unsigned NOT NULL,
  `pedido_id` int(10) unsigned DEFAULT NULL,
  `tipo` enum('entrada','salida','ajuste') NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `cantidad_anterior` decimal(10,2) NOT NULL,
  `cantidad_nueva` decimal(10,2) NOT NULL,
  `motivo` varchar(255) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_movimiento_pedido_insumo_tipo` (`empresa_id`,`pedido_id`,`insumo_id`,`tipo`),
  KEY `idx_movimiento_empresa_insumo` (`empresa_id`,`insumo_id`),
  KEY `insumo_id` (`insumo_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `ix_movimiento_empresa_pedido` (`empresa_id`,`pedido_id`),
  CONSTRAINT `fk_movimiento_pedido_empresa` FOREIGN KEY (`empresa_id`, `pedido_id`) REFERENCES `pedidos` (`empresa_id`, `id`),
  CONSTRAINT `movimientos_inventario_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  CONSTRAINT `movimientos_inventario_ibfk_2` FOREIGN KEY (`insumo_id`) REFERENCES `insumos` (`id`),
  CONSTRAINT `movimientos_inventario_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimientos_inventario`
--

LOCK TABLES `movimientos_inventario` WRITE;
/*!40000 ALTER TABLE `movimientos_inventario` DISABLE KEYS */;
INSERT INTO `movimientos_inventario` VALUES (1,1,7,1,NULL,'entrada',1000.00,10.00,1010.00,'Compra','2026-10-14 23:12:12'),(2,1,7,1,NULL,'salida',200.00,1010.00,810.00,'Conteo','2026-10-14 23:21:46'),(3,1,9,1,NULL,'entrada',2.00,0.00,2.00,'Existencia inicial','2026-10-15 15:58:06'),(4,1,10,1,NULL,'entrada',2.00,0.00,2.00,'Existencia inicial','2026-10-16 11:07:09'),(5,1,11,1,NULL,'entrada',10.00,0.00,10.00,'Existencia inicial','2026-10-16 11:07:37'),(6,1,12,1,NULL,'entrada',5.00,0.00,5.00,'Existencia inicial','2026-10-16 11:07:58'),(7,1,7,1,NULL,'salida',700.00,810.00,110.00,'Conteo','2026-10-16 11:10:45'),(8,1,7,1,4,'salida',2.00,110.00,108.00,'Salida por confirmación del pedido ID 4','2026-10-16 11:13:00'),(9,1,7,1,NULL,'ajuste',100.00,108.00,100.00,'Conteo','2026-10-16 11:15:16'),(10,1,7,1,NULL,'entrada',10.00,100.00,110.00,'Compra de producto','2026-10-16 11:24:01'),(11,1,7,1,5,'salida',3.00,110.00,107.00,'Salida por confirmación del pedido ID 5','2026-10-16 12:40:50'),(12,1,7,1,NULL,'entrada',10.00,107.00,117.00,'compra','2026-11-01 23:43:45'),(13,1,7,1,NULL,'entrada',11.00,117.00,128.00,'asas','2026-11-01 23:44:01'),(14,1,7,1,NULL,'salida',11.00,128.00,117.00,'venta','2026-11-01 23:44:25'),(15,1,7,1,NULL,'entrada',20.00,117.00,137.00,'c','2026-11-01 23:44:46'),(16,1,7,1,NULL,'entrada',11.00,137.00,148.00,'sza','2026-11-01 23:45:01'),(17,1,7,1,NULL,'entrada',22.00,148.00,170.00,'asas','2026-11-01 23:45:18'),(18,1,7,1,NULL,'ajuste',167.00,170.00,167.00,'asd','2026-11-01 23:45:36'),(19,1,7,1,NULL,'entrada',111.00,167.00,278.00,'as','2026-11-01 23:45:50'),(20,1,7,1,NULL,'salida',11.00,278.00,267.00,'as','2026-11-01 23:46:03'),(21,1,7,1,NULL,'salida',11.00,267.00,256.00,'as','2026-11-01 23:46:13'),(22,1,7,1,NULL,'salida',20.00,256.00,236.00,'as','2026-11-01 23:46:27'),(23,1,7,1,NULL,'salida',36.00,236.00,200.00,'venta','2026-11-01 23:46:42'),(24,1,7,1,NULL,'salida',22.00,200.00,178.00,'as','2026-11-01 23:46:54'),(25,1,7,1,NULL,'salida',22.00,178.00,156.00,'ve','2026-11-01 23:47:05');
/*!40000 ALTER TABLE `movimientos_inventario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pago_cuenta_detalles`
--

DROP TABLE IF EXISTS `pago_cuenta_detalles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pago_cuenta_detalles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
  `pedido_id` int(10) unsigned NOT NULL,
  `cuenta_id` int(10) unsigned NOT NULL,
  `detalle_id` int(10) unsigned NOT NULL,
  `cantidad` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cuenta_detalle` (`empresa_id`,`cuenta_id`,`detalle_id`),
  KEY `fk_reparto_cuenta` (`empresa_id`,`pedido_id`,`cuenta_id`),
  KEY `fk_reparto_detalle` (`empresa_id`,`pedido_id`,`detalle_id`),
  CONSTRAINT `fk_reparto_cuenta` FOREIGN KEY (`empresa_id`, `pedido_id`, `cuenta_id`) REFERENCES `pago_cuentas` (`empresa_id`, `pedido_id`, `id`),
  CONSTRAINT `fk_reparto_detalle` FOREIGN KEY (`empresa_id`, `pedido_id`, `detalle_id`) REFERENCES `pedido_detalles` (`empresa_id`, `pedido_id`, `id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pago_cuenta_detalles`
--

LOCK TABLES `pago_cuenta_detalles` WRITE;
/*!40000 ALTER TABLE `pago_cuenta_detalles` DISABLE KEYS */;
INSERT INTO `pago_cuenta_detalles` VALUES (1,1,5,1,5,2),(2,1,5,2,5,1);
/*!40000 ALTER TABLE `pago_cuenta_detalles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pago_cuentas`
--

DROP TABLE IF EXISTS `pago_cuentas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pago_cuentas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
  `pedido_id` int(10) unsigned NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `es_principal` tinyint(3) unsigned DEFAULT NULL,
  `propina` decimal(12,2) unsigned NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) unsigned NOT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cuenta_empresa_pedido_id` (`empresa_id`,`pedido_id`,`id`),
  UNIQUE KEY `uq_cuenta_nombre` (`empresa_id`,`pedido_id`,`nombre`),
  UNIQUE KEY `uq_cuenta_principal` (`empresa_id`,`pedido_id`,`es_principal`),
  CONSTRAINT `fk_cuenta_pedido` FOREIGN KEY (`empresa_id`, `pedido_id`) REFERENCES `pedidos` (`empresa_id`, `id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pago_cuentas`
--

LOCK TABLES `pago_cuentas` WRITE;
/*!40000 ALTER TABLE `pago_cuentas` DISABLE KEYS */;
INSERT INTO `pago_cuentas` VALUES (1,1,5,'Cuenta general',1,4.00,40.00,'2026-10-16 22:21:07'),(2,1,5,'Juan',NULL,2.00,20.00,'2026-10-16 22:21:25');
/*!40000 ALTER TABLE `pago_cuentas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pagos`
--

DROP TABLE IF EXISTS `pagos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pagos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
  `pedido_id` int(10) unsigned NOT NULL,
  `cuenta_id` int(10) unsigned NOT NULL,
  `usuario_id` int(10) unsigned NOT NULL,
  `clave` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `efectivo` decimal(12,2) unsigned NOT NULL DEFAULT 0.00,
  `credito` decimal(12,2) unsigned NOT NULL DEFAULT 0.00,
  `debito` decimal(12,2) unsigned NOT NULL DEFAULT 0.00,
  `recibido` decimal(12,2) unsigned NOT NULL DEFAULT 0.00,
  `cambio` decimal(12,2) unsigned NOT NULL DEFAULT 0.00,
  `propina` decimal(12,2) unsigned NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) unsigned NOT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pago_clave` (`empresa_id`,`clave`),
  KEY `ix_pago_empresa_fecha` (`empresa_id`,`creado_en`),
  KEY `fk_pago_cuenta` (`empresa_id`,`pedido_id`,`cuenta_id`),
  KEY `fk_pago_usuario` (`empresa_id`,`usuario_id`),
  CONSTRAINT `fk_pago_cuenta` FOREIGN KEY (`empresa_id`, `pedido_id`, `cuenta_id`) REFERENCES `pago_cuentas` (`empresa_id`, `pedido_id`, `id`),
  CONSTRAINT `fk_pago_usuario` FOREIGN KEY (`empresa_id`, `usuario_id`) REFERENCES `usuarios` (`empresa_id`, `id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagos`
--

LOCK TABLES `pagos` WRITE;
/*!40000 ALTER TABLE `pagos` DISABLE KEYS */;
INSERT INTO `pagos` VALUES (1,1,5,1,1,'51379101f0ed101e4afdeba7b14667eaa309cb3085f37696f491490786fac423',40.00,0.00,0.00,60.00,20.00,0.00,40.00,'2026-10-16 22:22:42'),(2,1,5,1,1,'125e51d7f6327db8c0eb41a315eccfa4f1b9ee11c2b880a47515d6b0e58f7e1f',4.00,0.00,0.00,4.00,0.00,4.00,4.00,'2026-10-16 22:23:17');
/*!40000 ALTER TABLE `pagos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedido_cancelaciones`
--

DROP TABLE IF EXISTS `pedido_cancelaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pedido_cancelaciones` (
  `pedido_id` int(10) unsigned NOT NULL,
  `empresa_id` int(10) unsigned NOT NULL,
  `usuario_id` int(10) unsigned NOT NULL,
  `motivo` varchar(500) NOT NULL,
  `estado_anterior` varchar(20) NOT NULL,
  `cancelado_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`pedido_id`),
  KEY `ix_cancelacion_empresa` (`empresa_id`,`pedido_id`),
  KEY `fk_cancelacion_usuario` (`usuario_id`),
  CONSTRAINT `fk_cancelacion_pedido_empresa` FOREIGN KEY (`empresa_id`, `pedido_id`) REFERENCES `pedidos` (`empresa_id`, `id`),
  CONSTRAINT `fk_cancelacion_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedido_cancelaciones`
--

LOCK TABLES `pedido_cancelaciones` WRITE;
/*!40000 ALTER TABLE `pedido_cancelaciones` DISABLE KEYS */;
INSERT INTO `pedido_cancelaciones` VALUES (3,1,1,'hola','pendiente','2026-10-14 20:39:23');
/*!40000 ALTER TABLE `pedido_cancelaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedido_detalles`
--

DROP TABLE IF EXISTS `pedido_detalles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pedido_detalles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
  `pedido_id` int(10) unsigned NOT NULL,
  `producto_id` int(10) unsigned NOT NULL,
  `producto_nombre` varchar(150) NOT NULL,
  `cantidad` int(10) unsigned NOT NULL,
  `precio_unitario` decimal(10,2) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_detalle_producto` (`pedido_id`,`producto_id`),
  UNIQUE KEY `uq_detalle_empresa_pedido_id` (`empresa_id`,`pedido_id`,`id`),
  KEY `fk_detalle_producto` (`producto_id`),
  CONSTRAINT `fk_detalle_pedido_empresa` FOREIGN KEY (`empresa_id`, `pedido_id`) REFERENCES `pedidos` (`empresa_id`, `id`),
  CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedido_detalles`
--

LOCK TABLES `pedido_detalles` WRITE;
/*!40000 ALTER TABLE `pedido_detalles` DISABLE KEYS */;
INSERT INTO `pedido_detalles` VALUES (3,1,3,1,'Cuaderno Aurora',1,25.00),(4,1,4,4,'Botella CocaCola',2,20.00),(5,1,5,4,'Botella CocaCola',3,20.00);
/*!40000 ALTER TABLE `pedido_detalles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedidos`
--

DROP TABLE IF EXISTS `pedidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pedidos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
  `numero` int(10) unsigned NOT NULL,
  `usuario_id` int(10) unsigned NOT NULL,
  `referencia` varchar(150) NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'borrador',
  `estado_pago` varchar(20) NOT NULL DEFAULT 'pendiente',
  `total` decimal(12,2) unsigned NOT NULL DEFAULT 0.00,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `confirmado_en` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pedido_empresa_id` (`empresa_id`,`id`),
  UNIQUE KEY `uq_pedido_empresa_numero` (`empresa_id`,`numero`),
  KEY `ix_pedido_empresa_fecha` (`empresa_id`,`creado_en`),
  KEY `fk_pedido_usuario` (`usuario_id`),
  CONSTRAINT `fk_pedido_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  CONSTRAINT `fk_pedido_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedidos`
--

LOCK TABLES `pedidos` WRITE;
/*!40000 ALTER TABLE `pedidos` DISABLE KEYS */;
INSERT INTO `pedidos` VALUES (1,1,1,1,'Pedido 1','borrador','pendiente',0.00,'2026-10-02 18:39:01',NULL),(2,1,2,1,'hola','borrador','pendiente',0.00,'2026-10-10 16:46:37',NULL),(3,1,3,1,'asas','cancelado','pendiente',25.00,'2026-10-12 11:13:58','2026-10-14 20:39:05'),(4,1,4,1,'Mesa 1','pendiente','pendiente',40.00,'2026-10-16 11:12:50','2026-10-16 11:13:00'),(5,1,5,1,'Mesa 2','pendiente','parcial',60.00,'2026-10-16 12:40:05','2026-10-16 12:40:50');
/*!40000 ALTER TABLE `pedidos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permisos`
--

DROP TABLE IF EXISTS `permisos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permisos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(80) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_permiso_codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permisos`
--

LOCK TABLES `permisos` WRITE;
/*!40000 ALTER TABLE `permisos` DISABLE KEYS */;
INSERT INTO `permisos` VALUES (1,'inicio.ver','Ver inicio','Permite acceder a la pagina principal.'),(2,'usuarios.ver','Ver usuarios','Permite consultar usuarios de la empresa.'),(3,'usuarios.gestionar','Gestionar usuarios','Permite crear, editar y desactivar usuarios de la empresa.'),(4,'productos.ver','Ver productos','Permite consultar productos o platillos.'),(5,'productos.gestionar','Gestionar productos','Permite crear y modificar productos o platillos.'),(6,'inventario.ver','Ver inventario','Permite consultar las existencias de la empresa.'),(7,'inventario.gestionar','Gestionar inventario','Permite registrar entradas, salidas y ajustes.'),(8,'proveedores.ver','Ver proveedores','Permite consultar proveedores de la empresa.'),(9,'proveedores.gestionar','Gestionar proveedores','Permite registrar y modificar proveedores y compras.'),(10,'pedidos.ver','Ver pedidos','Permite consultar pedidos de la empresa.'),(11,'pedidos.crear','Crear pedidos','Permite registrar y modificar pedidos abiertos.'),(12,'pedidos.confirmar','Confirmar pedidos','Permite enviar pedidos a preparacion.'),(13,'pedidos.actualizar_cocina','Actualizar cocina','Permite actualizar el estado de preparacion.'),(14,'ventas.ver','Ver ventas','Permite consultar las ventas de la empresa.'),(15,'ventas.cobrar','Registrar cobros','Permite cobrar pedidos.'),(16,'reportes.ver','Ver reportes','Permite consultar reportes administrativos.'),(17,'empresa.configurar','Configurar empresa','Permite modificar la configuracion de la empresa.'),(18,'pedidos.cancelar','Cancelar pedidos','Autoriza cancelar pedidos de su empresa y registrar el motivo.'),(19,'compras.ver','Consultar solicitudes','Ver documentos de compra de la empresa.'),(20,'compras.gestionar','Editar solicitudes','Crear y editar borradores de compra.'),(21,'compras.confirmar','Confirmar solicitudes','Confirmar documentos revisados.'),(22,'compras.exportar','Exportar solicitudes','Copiar o descargar documentos para proveedores.');
/*!40000 ALTER TABLE `permisos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `productos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
  `sku` varchar(40) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `precio` decimal(10,2) unsigned NOT NULL,
  `insumo_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_producto_empresa_sku` (`empresa_id`,`sku`),
  UNIQUE KEY `uq_producto_empresa_insumo` (`empresa_id`,`insumo_id`),
  CONSTRAINT `fk_producto_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  CONSTRAINT `fk_producto_insumo_empresa` FOREIGN KEY (`empresa_id`, `insumo_id`) REFERENCES `insumos` (`empresa_id`, `id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
INSERT INTO `productos` VALUES (1,1,'A-001','Cuaderno Aurora',25.00,NULL),(2,1,'A-002','Lapicero Aurora',8.00,NULL),(3,2,'R-001','Martillo Roble',75.00,NULL),(4,1,'BEB-001','Botella CocaCola',20.00,7);
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proveedores`
--

DROP TABLE IF EXISTS `proveedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `proveedores` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `contacto` varchar(100) NOT NULL DEFAULT '',
  `telefono` varchar(30) NOT NULL DEFAULT '',
  `correo` varchar(190) NOT NULL DEFAULT '',
  `direccion` varchar(255) NOT NULL DEFAULT '',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_proveedor_empresa_id` (`empresa_id`,`id`),
  UNIQUE KEY `uq_proveedor_nombre` (`empresa_id`,`nombre`),
  CONSTRAINT `fk_proveedor_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proveedores`
--

LOCK TABLES `proveedores` WRITE;
/*!40000 ALTER TABLE `proveedores` DISABLE KEYS */;
INSERT INTO `proveedores` VALUES (1,1,'Juan','','1111222223333','saasasasa@hola.com','',1),(2,1,'CocaCola','asasasa','1212121212121','asdasdsa@asdasd.com','asdasdasd',1);
/*!40000 ALTER TABLE `proveedores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rol_permisos`
--

DROP TABLE IF EXISTS `rol_permisos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rol_permisos` (
  `rol_id` int(10) unsigned NOT NULL,
  `permiso_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`rol_id`,`permiso_id`),
  KEY `fk_rol_permiso_permiso` (`permiso_id`),
  CONSTRAINT `fk_rol_permiso_permiso` FOREIGN KEY (`permiso_id`) REFERENCES `permisos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rol_permiso_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rol_permisos`
--

LOCK TABLES `rol_permisos` WRITE;
/*!40000 ALTER TABLE `rol_permisos` DISABLE KEYS */;
INSERT INTO `rol_permisos` VALUES (1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(1,7),(1,8),(1,9),(1,10),(1,11),(1,12),(1,13),(1,14),(1,15),(1,16),(1,17),(1,18),(1,19),(1,20),(1,21),(1,22),(2,1),(2,4),(2,10),(2,11),(2,12),(2,14),(2,15),(3,1),(3,4),(3,10),(3,13);
/*!40000 ALTER TABLE `rol_permisos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(40) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rol_codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'administrador','Administrador','Administra unicamente los recursos de su empresa.'),(2,'cajero','Cajero','Registra pedidos, ventas y cobros de su empresa.'),(3,'cocinero','Cocinero','Consulta y actualiza la preparacion de pedidos de su empresa.');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `solicitud_compra_detalles`
--

DROP TABLE IF EXISTS `solicitud_compra_detalles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `solicitud_compra_detalles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
  `solicitud_id` int(10) unsigned NOT NULL,
  `insumo_id` int(10) unsigned NOT NULL,
  `insumo_nombre` varchar(150) NOT NULL,
  `unidad_medida` varchar(20) NOT NULL,
  `cantidad` decimal(10,2) unsigned NOT NULL,
  `recibido` decimal(10,2) unsigned NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_solicitud_insumo` (`empresa_id`,`solicitud_id`,`insumo_id`),
  UNIQUE KEY `uq_solicitud_detalle_id` (`empresa_id`,`solicitud_id`,`id`),
  KEY `fk_linea_insumo` (`empresa_id`,`insumo_id`),
  CONSTRAINT `fk_linea_insumo` FOREIGN KEY (`empresa_id`, `insumo_id`) REFERENCES `insumos` (`empresa_id`, `id`),
  CONSTRAINT `fk_linea_solicitud` FOREIGN KEY (`empresa_id`, `solicitud_id`) REFERENCES `solicitudes_compra` (`empresa_id`, `id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `solicitud_compra_detalles`
--

LOCK TABLES `solicitud_compra_detalles` WRITE;
/*!40000 ALTER TABLE `solicitud_compra_detalles` DISABLE KEYS */;
INSERT INTO `solicitud_compra_detalles` VALUES (1,1,1,7,'Botella CocaCola','unidad',10.00,0.00),(3,1,2,7,'Botella CocaCola','unidad',10.00,0.00);
/*!40000 ALTER TABLE `solicitud_compra_detalles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `solicitud_compra_eventos`
--

DROP TABLE IF EXISTS `solicitud_compra_eventos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `solicitud_compra_eventos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
  `solicitud_id` int(10) unsigned NOT NULL,
  `usuario_id` int(10) unsigned NOT NULL,
  `accion` varchar(30) NOT NULL,
  `estado_anterior` varchar(20) DEFAULT NULL,
  `estado_nuevo` varchar(20) NOT NULL,
  `comentario` varchar(1000) NOT NULL DEFAULT '',
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ix_evento_solicitud` (`empresa_id`,`solicitud_id`,`id`),
  KEY `fk_evento_usuario` (`empresa_id`,`usuario_id`),
  CONSTRAINT `fk_evento_solicitud` FOREIGN KEY (`empresa_id`, `solicitud_id`) REFERENCES `solicitudes_compra` (`empresa_id`, `id`),
  CONSTRAINT `fk_evento_usuario` FOREIGN KEY (`empresa_id`, `usuario_id`) REFERENCES `usuarios` (`empresa_id`, `id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `solicitud_compra_eventos`
--

LOCK TABLES `solicitud_compra_eventos` WRITE;
/*!40000 ALTER TABLE `solicitud_compra_eventos` DISABLE KEYS */;
INSERT INTO `solicitud_compra_eventos` VALUES (1,1,1,1,'crear',NULL,'borrador','','2026-11-02 00:38:45'),(2,1,1,1,'confirmar','borrador','confirmada','','2026-11-02 00:38:55'),(3,1,2,1,'crear',NULL,'borrador','','2026-11-02 01:18:34'),(4,1,2,1,'editar','borrador','borrador','','2026-11-02 01:18:39');
/*!40000 ALTER TABLE `solicitud_compra_eventos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `solicitudes_compra`
--

DROP TABLE IF EXISTS `solicitudes_compra`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `solicitudes_compra` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
  `numero` int(10) unsigned NOT NULL,
  `proveedor_id` int(10) unsigned NOT NULL,
  `proveedor_nombre` varchar(150) NOT NULL,
  `proveedor_contacto` varchar(100) NOT NULL DEFAULT '',
  `proveedor_telefono` varchar(30) NOT NULL DEFAULT '',
  `proveedor_correo` varchar(190) NOT NULL DEFAULT '',
  `proveedor_direccion` varchar(255) NOT NULL DEFAULT '',
  `fecha` date NOT NULL,
  `observaciones` varchar(1000) NOT NULL DEFAULT '',
  `estado` varchar(20) NOT NULL DEFAULT 'borrador',
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  `clave` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `creado_por` int(10) unsigned NOT NULL,
  `actualizado_por` int(10) unsigned NOT NULL,
  `confirmado_por` int(10) unsigned DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `confirmado_en` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_solicitud_empresa_id` (`empresa_id`,`id`),
  UNIQUE KEY `uq_solicitud_numero` (`empresa_id`,`numero`),
  UNIQUE KEY `uq_solicitud_clave` (`empresa_id`,`clave`),
  KEY `ix_solicitud_proveedor` (`empresa_id`,`proveedor_id`,`fecha`),
  KEY `fk_solicitud_creador` (`empresa_id`,`creado_por`),
  KEY `fk_solicitud_editor` (`empresa_id`,`actualizado_por`),
  KEY `fk_solicitud_confirmador` (`empresa_id`,`confirmado_por`),
  CONSTRAINT `fk_solicitud_confirmador` FOREIGN KEY (`empresa_id`, `confirmado_por`) REFERENCES `usuarios` (`empresa_id`, `id`),
  CONSTRAINT `fk_solicitud_creador` FOREIGN KEY (`empresa_id`, `creado_por`) REFERENCES `usuarios` (`empresa_id`, `id`),
  CONSTRAINT `fk_solicitud_editor` FOREIGN KEY (`empresa_id`, `actualizado_por`) REFERENCES `usuarios` (`empresa_id`, `id`),
  CONSTRAINT `fk_solicitud_proveedor` FOREIGN KEY (`empresa_id`, `proveedor_id`) REFERENCES `proveedores` (`empresa_id`, `id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `solicitudes_compra`
--

LOCK TABLES `solicitudes_compra` WRITE;
/*!40000 ALTER TABLE `solicitudes_compra` DISABLE KEYS */;
INSERT INTO `solicitudes_compra` VALUES (1,1,1,1,'Juan','','1111222223333','saasasasa@hola.com','','2026-11-02','','confirmada',2,'d36e7e438724ed70f18b50b8bcf1a9e4f8affd48c5c488fd4349ea26a7116396',1,1,1,'2026-11-02 00:38:45','2026-11-02 00:38:55','2026-11-02 00:38:55'),(2,1,2,1,'Juan','','1111222223333','saasasasa@hola.com','','2026-11-02','','borrador',2,'fbe8a683574c766c18646d7f68c57a56861f6ce6463fb86689c43f01a9e45704',1,1,NULL,'2026-11-02 01:18:34','2026-11-02 01:18:39',NULL);
/*!40000 ALTER TABLE `solicitudes_compra` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
  `rol_id` int(10) unsigned NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario_correo` (`correo`),
  UNIQUE KEY `uq_usuario_empresa_id` (`empresa_id`,`id`),
  KEY `ix_usuario_empresa` (`empresa_id`),
  KEY `ix_usuario_rol` (`rol_id`),
  CONSTRAINT `fk_usuario_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,1,1,'Ana','ana@aurora.test','$2y$10$nC.37chpEMxmnP/NgOIL5OdwRpNl5AcMocE6/epbs9qZnfZUewr46',1),(2,2,1,'Bruno','bruno@roble.test','$2y$10$nC.37chpEMxmnP/NgOIL5OdwRpNl5AcMocE6/epbs9qZnfZUewr46',1);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-02  1:50:19
