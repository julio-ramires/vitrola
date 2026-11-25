-- Vitrola: script de criação do banco e da tabela "usuarios"
-- Como usar: phpMyAdmin > aba SQL > colar e executar
--            (ou: mysql -u root -p < database.sql)

CREATE DATABASE IF NOT EXISTS sistema_login
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sistema_login;

-- ATENÇÃO: a linha abaixo apaga a tabela antiga (a versão com a coluna
-- "usuario") e todos os cadastros de teste que estavam nela.
DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
    id        INT NOT NULL AUTO_INCREMENT,
    nome      VARCHAR(100) NOT NULL,
    email     VARCHAR(150) NOT NULL,
    senha     VARCHAR(255) NOT NULL,          -- hash gerado por password_hash()
    criado_em TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_email (email)      -- impede e-mail repetido
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
