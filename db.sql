delimiter $$

CREATE DATABASE `evaluacion` /*!40100 DEFAULT CHARACTER SET utf8 */$$

delimiter $$

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `documento` varchar(11) NOT NULL,
  `sexo` varchar(1) NOT NULL,
  `puesto` enum('SUPERVISOR','SEMI-JUNIOR','JUNIOR') NOT NULL,
  `user` varchar(255) NOT NULL,
  `pass` varchar(255) NOT NULL,
  `imagen` varchar(255) NOT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `id_2` (`id_usuario`),
  UNIQUE KEY `user` (`user`),
  KEY `id` (`id_usuario`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=latin1$$

delimiter $$

CREATE TABLE `ventas` (
  `venta_id` int(11) NOT NULL AUTO_INCREMENT,
  `venta_monto` double(19,4) NOT NULL,
  `venta_fecha` datetime NOT NULL,
  `id_usuario` int(11) NOT NULL,
  PRIMARY KEY (`venta_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=latin1$$