-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema HemoRS
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Schema HemoRS
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `HemoRS` DEFAULT CHARACTER SET utf8 ;
USE `HemoRS` ;

-- -----------------------------------------------------
-- Table `HemoRS`.`usuario`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `HemoRS`.`usuario` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(150) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `senha` VARCHAR(255) NOT NULL,
  `perfil` ENUM('recepcao', 'enfermagem', 'gestor') NOT NULL,
  `status` ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `email_UNIQUE` (`email` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `HemoRS`.`unidade`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `HemoRS`.`unidade` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(150) NOT NULL,
  `cidade` VARCHAR(150) NOT NULL,
  `capacidade_diaria` INT NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `HemoRS`.`doador`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `HemoRS`.`doador` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(150) NOT NULL,
  `cpf` VARCHAR(11) NOT NULL,
  `data_de_nascimento` DATE NOT NULL,
  `sexo` ENUM('masculino', 'feminino', 'outros') NOT NULL,
  `tipo_sanguineo` ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
  `telefone` VARCHAR(20) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `status` ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
  `autorizacao_responsavel` ENUM('sim', 'não') NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `cpf_UNIQUE` (`cpf` ASC) ,
  UNIQUE INDEX `email_UNIQUE` (`email` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `HemoRS`.`doacao`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `HemoRS`.`doacao` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `doador_id` INT NOT NULL,
  `unidade_id` INT NOT NULL,
  `data_e_hora_agendada` DATETIME NOT NULL,
  `status` VARCHAR(45) NOT NULL,
  `peso` VARCHAR(45) NOT NULL,
  `hemoglobina` VARCHAR(45) NOT NULL,
  `motivo_da_recusa` VARCHAR(255) NOT NULL,
  `volume_coletado` INT NOT NULL,
  `coletado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_doacao_usuario_idx` (`usuario_id` ASC) ,
  INDEX `fk_doacao_unidade_idx` (`unidade_id` ASC) ,
  INDEX `fk_doacao_doador_idx` (`doador_id` ASC) ,
  CONSTRAINT `fk_doacao_usuario`
    FOREIGN KEY (`usuario_id`)
    REFERENCES `HemoRS`.`usuario` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_doacao_unidade`
    FOREIGN KEY (`unidade_id`)
    REFERENCES `HemoRS`.`unidade` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_doacao_doador`
    FOREIGN KEY (`doador_id`)
    REFERENCES `HemoRS`.`doador` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `HemoRS`.`bolsa`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `HemoRS`.`bolsa` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `doacao_id` INT NOT NULL,
  `codigo` VARCHAR(45) NOT NULL,
  `tipo_sanguineo` ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
  `coletado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `vence_em` DATETIME NULL,
  `status` ENUM('disponivel', 'reservada', 'transfundida', 'descartada') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `doacao_id_UNIQUE` (`doacao_id` ASC) ,
  UNIQUE INDEX `codigo_UNIQUE` (`codigo` ASC) ,
  CONSTRAINT `fk_bolsa_doacao`
    FOREIGN KEY (`doacao_id`)
    REFERENCES `HemoRS`.`doacao` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `HemoRS`.`doacao_historico`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `HemoRS`.`doacao_historico` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `doacao_id` INT NULL,
  `status_de_origem` VARCHAR(150) NOT NULL,
  `status_de_destino` VARCHAR(150) NOT NULL,
  `usuario_id` INT NULL,
  `motivo` VARCHAR(255) NOT NULL,
  `data_e_hora` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `fk_doacao_historico_usuario_idx` (`usuario_id` ASC) ,
  INDEX `fk_doacao_historico_doacao_idx` (`doacao_id` ASC) ,
  CONSTRAINT `fk_doacao_historico_usuario`
    FOREIGN KEY (`usuario_id`)
    REFERENCES `HemoRS`.`usuario` (`id`)
    ON DELETE SET NULL
    ON UPDATE SET NULL,
  CONSTRAINT `fk_doacao_historico_doacao`
    FOREIGN KEY (`doacao_id`)
    REFERENCES `HemoRS`.`doacao` (`id`)
    ON DELETE SET NULL
    ON UPDATE SET NULL)
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
