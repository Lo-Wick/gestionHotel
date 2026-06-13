-- ============================================
-- Hôtel Réservation - Schéma de Base de Données
-- ============================================

CREATE DATABASE IF NOT EXISTS hotel_reservation 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE hotel_reservation;

-- Table CHAMBRE
DROP TABLE IF EXISTS paiement;
DROP TABLE IF EXISTS reservation;
DROP TABLE IF EXISTS client;
DROP TABLE IF EXISTS chambre;

CREATE TABLE chambre (
    id_chambre INT PRIMARY KEY AUTO_INCREMENT,
    numero VARCHAR(10) NOT NULL UNIQUE,
    type ENUM('Standard', 'Confort', 'Suite', 'Présidentielle') NOT NULL,
    prix_nuit DECIMAL(10,2) NOT NULL,
    capacite INT NOT NULL,
    description TEXT,
    disponibilite BOOLEAN DEFAULT TRUE,
    image_url VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table CLIENT
CREATE TABLE client (
    id_client INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    telephone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('client', 'admin') DEFAULT 'client',
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table RESERVATION
CREATE TABLE reservation (
    id_reservation INT PRIMARY KEY AUTO_INCREMENT,
    id_client INT NOT NULL,
    id_chambre INT NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    nombre_adultes INT DEFAULT 1,
    nombre_enfants INT DEFAULT 0,
    statut ENUM('En attente', 'Confirmée', 'Annulée', 'Terminée') DEFAULT 'En attente',
    montant_total DECIMAL(10,2) NOT NULL,
    date_reservation DATETIME DEFAULT CURRENT_TIMESTAMP,
    remarques TEXT,
    FOREIGN KEY (id_client) REFERENCES client(id_client) ON DELETE CASCADE,
    FOREIGN KEY (id_chambre) REFERENCES chambre(id_chambre) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table PAIEMENT
CREATE TABLE paiement (
    id_paiement INT PRIMARY KEY AUTO_INCREMENT,
    id_reservation INT NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    mode_paiement ENUM('Carte', 'Espèces', 'Virement', 'Mobile Money') NOT NULL,
    statut ENUM('En attente', 'Payé', 'Remboursé') DEFAULT 'En attente',
    date_paiement DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_reservation) REFERENCES reservation(id_reservation) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- DONNÉES DE TEST
-- ============================================

-- Chambres
INSERT INTO chambre (numero, type, prix_nuit, capacite, description, disponibilite, image_url) VALUES
('101', 'Standard', 80.00, 2, 'Chambre élégante avec lit double, climatisation et WiFi gratuit. Vue sur le jardin intérieur.', TRUE, 'assets/img/room_standard.png'),
('102', 'Standard', 80.00, 2, 'Chambre lumineuse avec vue sur jardin, lit double et salle de bain privée.', TRUE, 'assets/img/room_standard.png'),
('201', 'Confort', 120.00, 3, 'Chambre spacieuse avec lit king size, canapé-lit et minibar. Idéale pour les familles.', TRUE, 'assets/img/room_confort.png'),
('202', 'Confort', 120.00, 3, 'Chambre avec balcon vue mer, lit king size et coin salon.', TRUE, 'assets/img/room_confort.png'),
('301', 'Suite', 200.00, 4, 'Suite élégante avec salon séparé, baignoire spa et service room inclus.', TRUE, 'assets/img/room_suite.png'),
('302', 'Suite', 200.00, 4, 'Suite panoramique avec vue 180°, salon et salle de bain en marbre.', TRUE, 'assets/img/room_suite.png'),
('401', 'Présidentielle', 500.00, 6, 'Suite présidentielle avec terrasse privée, jacuzzi, deux salles de bain et majordome dédié.', TRUE, 'assets/img/room_suite.png'),
('402', 'Présidentielle', 500.00, 6, 'Appartement présidentiel : deux chambres, salle à manger, cuisine privée et terrasse.', TRUE, 'assets/img/room_suite.png');

-- Admin (password: Admin123!)
INSERT INTO client (nom, prenom, email, telephone, password, role) VALUES
('Admin', 'Hôtel', 'admin@hotel.com', '+33600000000', '$2y$10$YQEzGODG5NQJbq3NiZC0duPFbKVXfnGOSz5hKN5bCLzFzklmDKR9O', 'admin');

-- Client test (password: Client123!)
INSERT INTO client (nom, prenom, email, telephone, password, role) VALUES
('Dupont', 'Jean', 'client@test.com', '+33612345678', '$2y$10$8K1p/a0dL1LXMw0H1H8lheYV5eOMaEZ/CQs9E3JfZuJbXhp.3K/ey', 'client');

-- Réservations test
INSERT INTO reservation (id_client, id_chambre, date_debut, date_fin, nombre_adultes, nombre_enfants, statut, montant_total, remarques) VALUES
(2, 1, '2026-05-15', '2026-05-18', 2, 0, 'Confirmée', 240.00, 'Arrivée tardive prévue vers 22h'),
(2, 3, '2026-06-01', '2026-06-05', 2, 1, 'En attente', 480.00, 'Lit bébé souhaité');

-- Paiements test
INSERT INTO paiement (id_reservation, montant, mode_paiement, statut) VALUES
(1, 240.00, 'Carte', 'Payé'),
(2, 480.00, 'Mobile Money', 'En attente');
