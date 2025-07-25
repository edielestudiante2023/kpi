-- Crear base de datos y usarla
CREATE DATABASE IF NOT EXISTS kpicycloid CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE kpicycloid;

-- Desactivar restricciones de llaves foráneas
SET FOREIGN_KEY_CHECKS = 0;

-- Tabla: roles
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id_roles` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(50) NOT NULL,
  PRIMARY KEY (`id_roles`),
  UNIQUE KEY `nombre_rol` (`nombre_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `roles` VALUES 
(1,'superadmin'),
(2,'admin'),
(3,'jefatura'),
(4,'trabajador');

-- Tabla: areas
DROP TABLE IF EXISTS `areas`;
CREATE TABLE `areas` (
  `id_areas` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_area` varchar(100) NOT NULL,
  `descripcion_area` text DEFAULT NULL,
  `estado_area` enum('activa','inactiva') DEFAULT 'activa',
  PRIMARY KEY (`id_areas`),
  UNIQUE KEY `nombre_area` (`nombre_area`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `areas` VALUES 
(1,'Gestión Humana','Área encargada del desarrollo organizacional y talento humano','activa');

-- Tabla: perfiles_cargo
DROP TABLE IF EXISTS `perfiles_cargo`;
CREATE TABLE `perfiles_cargo` (
  `id_perfil_cargo` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre_cargo` varchar(100) NOT NULL,
  `area` varchar(100) DEFAULT NULL,
  `jefe_inmediato` varchar(100) DEFAULT NULL,
  `colaboradores_a_cargo` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_perfil_cargo`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- (Agrega tus INSERTs aquí si tienes perfiles de cargo)

-- Tabla: users
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id_users` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre_completo` varchar(150) NOT NULL,
  `documento_identidad` varchar(20) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `cargo` varchar(100) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `id_roles` int(11) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `id_areas` int(11) DEFAULT NULL,
  `id_perfil_cargo` int(10) unsigned DEFAULT NULL,
  `id_jefe` int(10) unsigned DEFAULT NULL,
  `primer_login` tinyint(1) NOT NULL DEFAULT 1,
  `token_recuperacion` varchar(64) DEFAULT NULL,
  `token_fecha` datetime DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expira` datetime DEFAULT NULL,
  PRIMARY KEY (`id_users`),
  KEY `id_roles` (`id_roles`),
  KEY `id_areas` (`id_areas`),
  KEY `fk_users_perfilcargo` (`id_perfil_cargo`),
  KEY `fk_users_jefe` (`id_jefe`),
  CONSTRAINT `fk_users_jefe` FOREIGN KEY (`id_jefe`) REFERENCES `users` (`id_users`) ON DELETE SET NULL,
  CONSTRAINT `fk_users_perfilcargo` FOREIGN KEY (`id_perfil_cargo`) REFERENCES `perfiles_cargo` (`id_perfil_cargo`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`id_roles`) REFERENCES `roles` (`id_roles`),
  CONSTRAINT `users_ibfk_2` FOREIGN KEY (`id_areas`) REFERENCES `areas` (`id_areas`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Inserta solo si ya tienes el perfil_cargo con ID 2 creado
INSERT INTO `users` VALUES 
(1,'Edison Cuervo','80039147','edison.cuervo@cycloidtalent.com','desarrollador','$2y$10$xcDjLtjCCNEjDHzIIw67g.JVarNd4tWljt96tn6dS617Z9kb7U7qK',1,1,1,2,2,0,'e3b513aa9f51e76e31457d67e18cad9d492349e909674ab405b20567a3371909','2025-07-15 07:14:59',NULL,NULL);

-- Activar de nuevo las llaves foráneas
SET FOREIGN_KEY_CHECKS = 1;


-- Desactivamos temporalmente las restricciones
SET FOREIGN_KEY_CHECKS = 0;

-- Eliminamos si ya existía
DROP TABLE IF EXISTS `accesos_rol`;

-- Creamos la estructura
CREATE TABLE `accesos_rol` (
  `id_acceso` int(11) NOT NULL AUTO_INCREMENT,
  `id_roles` int(11) NOT NULL,
  `detalle` varchar(150) NOT NULL,
  `enlace` varchar(255) NOT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  PRIMARY KEY (`id_acceso`),
  KEY `id_roles` (`id_roles`),
  CONSTRAINT `accesos_rol_ibfk_1` FOREIGN KEY (`id_roles`) REFERENCES `roles` (`id_roles`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insertamos accesos iniciales para roles 1 (superadmin) y 2 (admin)
INSERT INTO `accesos_rol` VALUES 
(1,1,'Usuarios','/users','activo'),
(2,1,'Areas','/areas','activo'),
(3,1,'Equipos','/equipos','activo'),
(4,1,'Roles','/roles','activo'),
(5,1,'Relacionar Indicador a Perfil de Cargo','/indicadores_perfil','activo'),
(6,1,'Perfiles','/perfiles','activo'),
(7,1,'Indicadores','/indicadores','activo'),
(8,1,'Administración de accesos','/accesosrol','activo'),
(9,1,'Auditoria Modificaciones','/auditoria-indicadores','activo'),
(10,2,'Usuarios','/users','activo'),
(11,2,'Areas','/areas','activo'),
(12,2,'Equipos','/equipos','activo'),
(14,2,'Relacionar Indicador a Perfil de Cargo','/indicadores_perfil','activo'),
(15,2,'Perfiles','/perfiles','activo'),
(16,2,'Indicadores','/indicadores','activo'),
(18,2,'Auditoria Modificaciones','/auditoria-indicadores','activo'),
(19,2,'Modificar indicadores reportados','/historial_indicador','activo'),
(22,1,'Csv Cargue de formulas','/partesformula/upload','activo');

-- Reactivamos las restricciones
SET FOREIGN_KEY_CHECKS = 1;


SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `equipos`;

CREATE TABLE `equipos` (
  `id_equipos` int(11) NOT NULL AUTO_INCREMENT,
  `id_jefe` int(11) NOT NULL,
  `id_subordinado` int(11) NOT NULL,
  `fecha_asignacion` date NOT NULL,
  `estado_relacion` enum('activo','inactivo') DEFAULT 'activo',
  PRIMARY KEY (`id_equipos`),
  KEY `id_jefe` (`id_jefe`),
  KEY `id_subordinado` (`id_subordinado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;


SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `indicadores`;

CREATE TABLE `indicadores` (
  `id_indicador` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `periodicidad` varchar(50) DEFAULT NULL,
  `ponderacion` decimal(5,2) DEFAULT NULL,
  `meta_valor` decimal(10,2) DEFAULT NULL,
  `meta_descripcion` varchar(255) DEFAULT NULL,
  `tipo_meta` enum('mayor_igual','menor_igual','igual','comparativa') DEFAULT 'mayor_igual',
  `metodo_calculo` enum('formula','manual','semiautomatico') DEFAULT 'formula',
  `activo` tinyint(1) DEFAULT 1,
  `unidad` varchar(20) DEFAULT NULL,
  `objetivo_proceso` text DEFAULT NULL,
  `objetivo_calidad` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `tipo_aplicacion` enum('cargo','area') DEFAULT 'cargo',
  PRIMARY KEY (`id_indicador`),
  KEY `idx_tipo_aplicacion` (`tipo_aplicacion`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;


SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `indicadores_perfil`;

CREATE TABLE `indicadores_perfil` (
  `id_indicador_perfil` int(11) NOT NULL AUTO_INCREMENT,
  `id_indicador` int(11) NOT NULL,
  `id_perfil_cargo` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id_indicador_perfil`),
  KEY `fk_indicador` (`id_indicador`),
  KEY `fk_indicadores_perfil_perfil` (`id_perfil_cargo`),
  CONSTRAINT `fk_indicador` FOREIGN KEY (`id_indicador`) REFERENCES `indicadores` (`id_indicador`) ON DELETE CASCADE,
  CONSTRAINT `fk_indicadores_perfil_perfil` FOREIGN KEY (`id_perfil_cargo`) REFERENCES `perfiles_cargo` (`id_perfil_cargo`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;


SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `historial_indicadores`;

CREATE TABLE `historial_indicadores` (
  `id_historial` int(11) NOT NULL AUTO_INCREMENT,
  `id_indicador_perfil` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `periodo` date NOT NULL,
  `valores_json` text DEFAULT NULL,
  `resultado_real` decimal(8,2) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `cumple` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id_historial`),
  KEY `fk_indicador_perfil` (`id_indicador_perfil`),
  KEY `fk_usuario_indicador` (`id_usuario`),
  CONSTRAINT `fk_indicador_perfil` FOREIGN KEY (`id_indicador_perfil`) REFERENCES `indicadores_perfil` (`id_indicador_perfil`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;


SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `indicador_auditoria`;

CREATE TABLE `indicador_auditoria` (
  `id_auditoria` int(11) NOT NULL AUTO_INCREMENT,
  `id_historial` int(11) NOT NULL,
  `editor_id` int(10) unsigned NOT NULL,
  `campo` varchar(50) NOT NULL,
  `valor_anterior` text DEFAULT NULL,
  `valor_nuevo` text DEFAULT NULL,
  `fecha_edicion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_auditoria`),
  KEY `fk_ia_historial` (`id_historial`),
  KEY `fk_ia_editor` (`editor_id`),
  CONSTRAINT `fk_ia_editor` FOREIGN KEY (`editor_id`) REFERENCES `users` (`id_users`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ia_historial` FOREIGN KEY (`id_historial`) REFERENCES `historial_indicadores` (`id_historial`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;


SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `indicadores_area`;

CREATE TABLE `indicadores_area` (
  `id_indicador_area` int(11) NOT NULL AUTO_INCREMENT,
  `id_indicador` int(11) NOT NULL,
  `id_areas` int(11) NOT NULL,
  `meta` decimal(10,2) DEFAULT NULL,
  `ponderacion` decimal(5,2) DEFAULT NULL,
  `periodicidad` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id_indicador_area`),
  KEY `id_indicador` (`id_indicador`),
  KEY `id_areas` (`id_areas`),
  CONSTRAINT `indicadores_area_ibfk_1` FOREIGN KEY (`id_indicador`) REFERENCES `indicadores` (`id_indicador`),
  CONSTRAINT `indicadores_area_ibfk_2` FOREIGN KEY (`id_areas`) REFERENCES `areas` (`id_areas`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;


SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `partes_formula_indicador`;

CREATE TABLE `partes_formula_indicador` (
  `id_parte_formula` int(11) NOT NULL AUTO_INCREMENT,
  `id_indicador` int(11) NOT NULL,
  `tipo_parte` varchar(30) NOT NULL,  -- puede ser: dato, operador, paréntesis_apertura, paréntesis_cierre
  `valor` varchar(255) NOT NULL,      -- el nombre de la variable o el operador (+, -, /, etc.)
  `orden` int(11) NOT NULL,           -- posición dentro de la fórmula
  PRIMARY KEY (`id_parte_formula`),
  KEY `id_indicador` (`id_indicador`),
  CONSTRAINT `partes_formula_indicador_ibfk_1` FOREIGN KEY (`id_indicador`) REFERENCES `indicadores` (`id_indicador`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;


create or replace
algorithm = UNDEFINED view `kpicycloid`.`vw_auditoria_indicadores` as
select
    `ia`.`id_auditoria` as `id_auditoria`,
    `ia`.`fecha_edicion` as `fecha_edicion`,
    `editor`.`nombre_completo` as `editor_nombre`,
    `i`.`nombre` as `nombre_indicador`,
    `ia`.`valor_anterior` as `valor_anterior`,
    `ia`.`valor_nuevo` as `valor_nuevo`,
    `hi`.`fecha_registro` as `fecha_registro_original`,
    `evaluado`.`nombre_completo` as `nombre_usuario_afectado`,
    `evaluado`.`cargo` as `cargo_usuario_afectado`,
    `a`.`nombre_area` as `area_usuario_afectado`
from
    ((((((`kpicycloid`.`indicador_auditoria` `ia`
join `kpicycloid`.`historial_indicadores` `hi` on
    (`ia`.`id_historial` = `hi`.`id_historial`))
join `kpicycloid`.`users` `editor` on
    (`editor`.`id_users` = `ia`.`editor_id`))
join `kpicycloid`.`users` `evaluado` on
    (`evaluado`.`id_users` = `hi`.`id_usuario`))
join `kpicycloid`.`areas` `a` on
    (`evaluado`.`id_areas` = `a`.`id_areas`))
join `kpicycloid`.`indicadores_perfil` `ip` on
    (`hi`.`id_indicador_perfil` = `ip`.`id_indicador_perfil`))
join `kpicycloid`.`indicadores` `i` on
    (`ip`.`id_indicador` = `i`.`id_indicador`));


