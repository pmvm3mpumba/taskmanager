<?php

/**
 * MonEspaceTâches — Connexion à la base SQLite
 *
 * La base `gestion_taches.db` est un simple fichier (dossier data/).
 * Le schéma (users, activity, taches) est créé automatiquement au premier
 * lancement : aucune installation manuelle n'est nécessaire.
 */

// Fuseau horaire utilisé pour toutes les dates enregistrées (Burundi)
date_default_timezone_set('Africa/Bujumbura');

/**
 * Retourne la connexion PDO à la base SQLite (connexion unique).
 */
function bd(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dossier = dirname(__DIR__) . '/data';
        if (!is_dir($dossier)) {
            mkdir($dossier, 0755, true);
        }

        $pdo = new PDO('sqlite:' . $dossier . '/gestion_taches.db');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        creer_tables($pdo);
    }

    return $pdo;
}

/**
 * Crée les trois tables du cahier des charges si elles n'existent pas.
 */
function creer_tables(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nom TEXT NOT NULL,
            prenom TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            mot_de_passe TEXT NOT NULL
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS activity (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            nom_utilisateur TEXT NOT NULL,
            heure_connexion DATETIME NOT NULL,
            heure_deconnexion DATETIME NULL,
            duree_session TEXT NULL
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS taches (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            titre TEXT NOT NULL,
            description TEXT NULL,
            statut TEXT NOT NULL,
            date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
        )'
    );
}
