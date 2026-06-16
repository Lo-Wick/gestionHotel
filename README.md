# Cocotel - Système de Réservation

Application web complète de gestion hôtelière développée avec une architecture modulaire en PHP natif (PDO) et un frontend moderne en Vanilla CSS/JS.

## 🚀 Fonctionnalités

### 👨‍💻 Côté Client
- **Landing Page** : Design premium avec Hero section et recherche rapide.
- **Galerie de Chambres** : Filtres dynamiques (dates, types, budget, capacité) et pagination AJAX.
- **Système de Réservation** : Parcours utilisateur en 3 étapes avec calcul de prix en temps réel.
- **Espace Client** : Gestion du profil et historique des réservations avec politique d'annulation.
- **Paiement Simulé** : Simulation de transaction avec génération de facture imprimable.
- **Responsive & Thème** : Design mobile-first et support natif du mode sombre.

### 🛠️ Côté Administration
- **Dashboard Statistique** : Vue d'ensemble des KPIs (Taux d'occupation, CA, Réservations).
- **Gestion des Chambres** : CRUD complet via interface modale sécurisée.
- **Suivi des Réservations** : Modification des statuts et filtrage avancé.
- **Gestion Clients** : Vue détaillée des comptes utilisateurs.

## 🛠️ Stack Technique
- **Backend** : PHP 8.x (Architecture MVC simplifiée, Singleton PDO).
- **Frontend** : HTML5, CSS3 Moderne (Flexbox, Grid, Variables), JavaScript ES6.
- **Base de données** : MySQL / MariaDB (Clés étrangères, Contraintes d'intégrité).
- **Sécurité** : Protection CSRF, Hashage de mots de passe, Validation côté serveur et client.

## 📦 Installation
1. Cloner le projet dans `htdocs` de votre serveur XAMPP.
2. Importer le fichier `schema.sql` dans votre base de données MySQL.
3. Configurer les accès dans `php/config/database.php`.
4. Accéder via `http://localhost/ReservationChambreHotel/`.

## 👤 Comptes de Test
- **Administrateur** : `admin@hotel.com` / `Admin123!`
- **Client Test** : `client@test.com` / `Client123!`

---
*Réalisé dans le cadre d'un projet de développement web IHM Premium.*
