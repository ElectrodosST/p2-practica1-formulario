-- Instalador de la tienda para MySQL/MariaDB (XAMPP).
-- Se puede importar mas de una vez; no elimina usuarios ni pedidos existentes.
CREATE DATABASE IF NOT EXISTS `shein_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `shein_db`;

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `productos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `categoria` varchar(30) NOT NULL,
  `imagen` varchar(500) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `categoria` (`categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `carritos` (
  `carrito_id` varchar(64) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `actualizado` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`carrito_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `carritos_usuario_fk`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `carrito_detalles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `carrito_id` varchar(64) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `carrito_producto` (`carrito_id`,`producto_id`),
  CONSTRAINT `carrito_detalles_carrito_fk`
    FOREIGN KEY (`carrito_id`) REFERENCES `carritos` (`carrito_id`) ON DELETE CASCADE,
  CONSTRAINT `carrito_detalles_producto_fk`
    FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pedidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `pedidos_usuario_fk`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pedido_detalles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `nombre_producto` varchar(120) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `cantidad` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `pedido_detalles_pedido_fk`
    FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pedido_detalles_producto_fk`
    FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cuenta de demostracion. La aplicacion admite esta clave inicial y la actualiza
-- a un hash seguro cuando se inicia sesion por primera vez.
INSERT INTO `usuarios` (`nombre`, `usuario`, `contrasena`)
VALUES ('kevin', 'kevin', '123456')
ON DUPLICATE KEY UPDATE `usuario` = VALUES(`usuario`);

INSERT INTO `productos` (`id`,`nombre`,`descripcion`,`categoria`,`imagen`,`precio`) VALUES
(1,'Camisa estampada','Camisa casual de flores','ropa','ropa/camisas/camisa2.jpg',299.00),
(2,'Camisa negra','Camisa negra para cualquier ocasión','ropa','ropa/camisas/camisa3.jpg',349.00),
(3,'Camisa clásica','Camisa casual de manga corta','ropa','ropa/camisas/camisa4.jpg',329.00),
(4,'Camisa urbana','Camisa estilo urbano','ropa','ropa/camisas/camisa5.jpg',399.00),
(5,'Camisa verde','Camisa casual color verde','ropa','ropa/camisas/camisa6.jpg',329.00),
(6,'Camisa moderna','Camisa de estilo moderno','ropa','ropa/camisas/camisa7.jpg',379.00),
(7,'Pantalón clásico','Pantalón casual','ropa','ropa/pantalon/pantalon1.jpg',499.00),
(8,'Pantalón urbano','Pantalón de corte moderno','ropa','ropa/pantalon/pantalon2.jpg',549.00),
(9,'Pantalón recto','Pantalón cómodo de uso diario','ropa','ropa/pantalon/pantalon3.jpg',519.00),
(10,'Pantalón casual','Pantalón estilo casual','ropa','ropa/pantalon/pantalon4.jpg',499.00),
(11,'Pantalón moderno','Pantalón de corte actual','ropa','ropa/pantalon/pantalon5.jpg',579.00),
(12,'Pantalón relajado','Pantalón cómodo','ropa','ropa/pantalon/pantalon6.jpg',529.00),
(13,'Accesorio clásico','Accesorio para complementar tu estilo','accesorios','ropa/accesorios/accesorio1.jpg',149.00),
(14,'Accesorio urbano','Diseño urbano para uso diario','accesorios','ropa/accesorios/accesorio2.jpg',179.00),
(15,'Accesorio moderno','Accesorio de estilo moderno','accesorios','ropa/accesorios/accesorio3.jpg',199.00),
(16,'Accesorio esencial','Un básico para tu colección','accesorios','ropa/accesorios/accesorio4.jpg',129.00),
(17,'Accesorio especial','Complemento para cualquier ocasión','accesorios','ropa/accesorios/accesorio5.jpg',219.00),
(18,'Cosmético esencial','Producto de belleza para tu rutina','cosmeticos','https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTQanBohvqYf4WaS3F-zFltqS-IJffsMOK48dHf0jpRhA&s=10',189.00),
(19,'Cosmético diario','Un básico para todos los días','cosmeticos','https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQtJRTB3sJfQGQy_y9tP-mbLdNkT830sProm6Bw6UGG4g&s=10',159.00),
(20,'Cosmético natural','Ideal para completar tu rutina','cosmeticos','https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSElR8xazTOUgzcrJ7OjgsvYMcRpbYS4qyp7rJWafRGgQ&s=10',219.00),
(21,'Maquillaje clásico','Color y estilo para tu look','maquillaje','https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTyEt7BYtPjxlwfWDeBwzKrxNXlbrWXLkw89wCfluepFw&s',199.00),
(22,'Maquillaje diario','Un toque especial para cada día','maquillaje','https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcROkSKncVDJDrxne0-b-0KCt4vOWbNVx4m3OPYV7cw_yQ&s',179.00),
(23,'Maquillaje moderno','Completa tu colección de belleza','maquillaje','https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcShJjNmELKQuVWQI6wpsB2RB5QH9zBR29lUtp62DhunQA&s=10',229.00)
ON DUPLICATE KEY UPDATE
  `nombre` = VALUES(`nombre`),
  `descripcion` = VALUES(`descripcion`),
  `categoria` = VALUES(`categoria`),
  `imagen` = VALUES(`imagen`),
  `precio` = VALUES(`precio`),
  `activo` = 1;
