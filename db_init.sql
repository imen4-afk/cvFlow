-- db_init.sql - Initialize the cv_editor database and tables

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS cv_editor;
USE cv_editor;

-- Create utilisateurs table
CREATE TABLE IF NOT EXISTS utilisateurs (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL
);

-- Create cv table
CREATE TABLE IF NOT EXISTS cv (
    id_cv INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    titre VARCHAR(255),
    presentation TEXT,
    FOREIGN KEY (id_user) REFERENCES utilisateurs(id_user) ON DELETE CASCADE
);

-- Create experiences table
CREATE TABLE IF NOT EXISTS experiences (
    id_exp INT AUTO_INCREMENT PRIMARY KEY,
    id_cv INT NOT NULL,
    titre_poste VARCHAR(255) NOT NULL,
    entreprise VARCHAR(255) NOT NULL,
    date_debut DATE,
    date_fin DATE,
    description TEXT,
    FOREIGN KEY (id_cv) REFERENCES cv(id_cv) ON DELETE CASCADE
);

-- Create formations table
CREATE TABLE IF NOT EXISTS formations (
    id_formation INT AUTO_INCREMENT PRIMARY KEY,
    id_cv INT NOT NULL,
    diplome VARCHAR(255) NOT NULL,
    ecole VARCHAR(255) NOT NULL,
    annee YEAR,
    FOREIGN KEY (id_cv) REFERENCES cv(id_cv) ON DELETE CASCADE
);