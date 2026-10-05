<?php

/**
 * MonEspaceTâches — Déconnexion (logout.php)
 *
 * Enregistre l'heure de déconnexion, calcule la durée de la session
 * (table activity), détruit la session PHP puis redirige vers l'accueil.
 */

require_once __DIR__ . '/includes/functions.php';
demarrer_session();

traiter_deconnexion();
