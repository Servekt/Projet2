
-- Mise à jour de la base de données pour intégrer les notes directement dans la table users

CREATE DATABASE IF NOT EXISTS secure_student_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE secure_student_manager;

-- Table des utilisateurs avec champ note (grade)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'prof', 'etudiant') NOT NULL,
    grade FLOAT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table de journalisation (facultatif)
CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insertion d’un admin par défaut
INSERT INTO users (username, password, role) VALUES ('admin', 'changeme', 'admin');
