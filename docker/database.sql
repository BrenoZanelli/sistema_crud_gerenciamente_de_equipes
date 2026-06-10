CREATE DATABASE IF NOT EXISTS sistema_equipes CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sistema_equipes;

CREATE TABLE IF NOT EXISTS teams(
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_teams VARCHAR(50) NOT NULL,
    code VARCHAR(10) NOT NULL,
    create_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_team_code (code)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(40) NOT NULL,
    email VARCHAR(40) NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    position VARCHAR(20) NOT NULL DEFAULT 'neutro',
    teams_id INT NULL,
    UNIQUE KEY unique_user_email (email),
    CONSTRAINT fk_users_teams
        FOREIGN KEY (teams_id) REFERENCES teams(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tasks(
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('baixa','media','urgente') NOT NULL DEFAULT 'baixa',
    status ENUM('em espera','em andamento','concluida') NOT NULL DEFAULT 'em espera',
    create_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    finished_at DATETIME NULL,
    difficulty INT NULL,
    feedback_comment TEXT NULL,
    user_id INT NOT NULL,
    teams_id INT NOT NULL,
    CONSTRAINT fk_tasks_users
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_tasks_teams
        FOREIGN KEY (teams_id) REFERENCES teams(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;