<?php

/**
 * MonEspaceTâches — Fonctions de l'application
 *
 * Chaque fonctionnalité du cahier des charges correspond à une fonction :
 *
 *   Inscription ......... inscrire_utilisateur(), traiter_inscription()
 *   Connexion ........... verifier_identifiants(), traiter_connexion()
 *   Déconnexion ......... traiter_deconnexion()
 *   Sessions ............ enregistrer_connexion(), enregistrer_deconnexion(),
 *                         calculer_duree(), lister_sessions()
 *   Tâches .............. creer_tache(), modifier_tache(),
 *                         changer_statut_tache(), supprimer_tache()
 */

require_once __DIR__ . '/../config/database.php';

/** Statuts possibles d'une tâche (ordre du cahier des charges) */
const STATUTS = ['À faire', 'En cours', 'Terminé'];

/* =====================================================================
 * UTILITAIRES
 * ===================================================================== */

/**
 * Échappe une chaîne pour l'affichage HTML (protection XSS).
 */
function e(?string $valeur): string
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Redirige vers une page et arrête le script.
 */
function rediriger(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Démarre la session PHP de façon sécurisée.
 */
function demarrer_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name('MON_ESPACE_TACHES');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/**
 * Définit un message flash (affiché après la prochaine redirection).
 */
function definir_message(string $message, string $type = 'succes'): void
{
    $_SESSION['message']      = $message;
    $_SESSION['type_message'] = $type;
}

/**
 * Récupère (une seule fois) le message flash à afficher.
 */
function recuperer_message(): ?array
{
    if (empty($_SESSION['message'])) {
        return null;
    }

    $message = [
        'texte' => $_SESSION['message'],
        'type'  => $_SESSION['type_message'] ?? 'succes',
    ];
    unset($_SESSION['message'], $_SESSION['type_message']);

    return $message;
}

/* =====================================================================
 * JETON CSRF (protection des formulaires)
 * ===================================================================== */

/**
 * Génère le jeton anti-CSRF de la session.
 */
function generer_jeton(): string
{
    if (empty($_SESSION['jeton'])) {
        $_SESSION['jeton'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['jeton'];
}

/**
 * Champ caché à insérer dans chaque formulaire.
 */
function champ_jeton(): string
{
    return '<input type="hidden" name="jeton" value="' . e(generer_jeton()) . '">';
}

/**
 * Vérifie le jeton envoyé par le formulaire.
 */
function verifier_jeton(): bool
{
    $jeton = $_POST['jeton'] ?? '';

    return is_string($jeton)
        && $jeton !== ''
        && !empty($_SESSION['jeton'])
        && hash_equals($_SESSION['jeton'], $jeton);
}

/* =====================================================================
 * UTILISATEUR CONNECTÉ
 * ===================================================================== */

/**
 * L'utilisateur est-il connecté ?
 */
function est_connecte(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Retourne les informations de l'utilisateur connecté (ou null).
 */
function utilisateur_actuel(): ?array
{
    if (!est_connecte()) {
        return null;
    }

    return [
        'id'          => (int) $_SESSION['user_id'],
        'nom'         => (string) $_SESSION['nom'],
        'prenom'      => (string) $_SESSION['prenom'],
        'email'       => (string) $_SESSION['email'],
        'nom_complet' => (string) $_SESSION['nom_complet'],
    ];
}

/**
 * Force l'accès à une page privée : sans connexion, redirection vers login.php.
 */
function exiger_connexion(): void
{
    if (!est_connecte()) {
        definir_message('Veuillez vous connecter pour accéder à cette page.', 'info');
        rediriger('login.php');
    }
}

/* =====================================================================
 * INSCRIPTION
 * ===================================================================== */

/**
 * Valide les données d'inscription.
 *
 * @return array<string, string> Tableau champ => message d'erreur (vide si OK)
 */
function valider_inscription(array $donnees): array
{
    $erreurs = [];
    $nom     = trim($donnees['nom'] ?? '');
    $prenom  = trim($donnees['prenom'] ?? '');
    $email   = trim($donnees['email'] ?? '');
    $mdp     = (string) ($donnees['mot_de_passe'] ?? '');

    if (mb_strlen($nom) < 2) {
        $erreurs['nom'] = 'Le nom doit contenir au moins 2 caractères.';
    } elseif (mb_strlen($nom) > 100) {
        $erreurs['nom'] = 'Le nom ne peut pas dépasser 100 caractères.';
    }

    if (mb_strlen($prenom) < 2) {
        $erreurs['prenom'] = 'Le prénom doit contenir au moins 2 caractères.';
    } elseif (mb_strlen($prenom) > 100) {
        $erreurs['prenom'] = 'Le prénom ne peut pas dépasser 100 caractères.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs['email'] = 'Veuillez saisir une adresse email valide.';
    } elseif (mb_strlen($email) > 190) {
        $erreurs['email'] = 'Adresse email trop longue.';
    }

    if (strlen($mdp) < 8) {
        $erreurs['mot_de_passe'] = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif (strlen($mdp) > 255) {
        $erreurs['mot_de_passe'] = 'Le mot de passe ne peut pas dépasser 255 caractères.';
    }

    return $erreurs;
}

/**
 * Vrai si l'adresse email est déjà utilisée.
 */
function email_deja_utilise(string $email): bool
{
    $requete = bd()->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
    $requete->execute([$email]);

    return (int) $requete->fetchColumn() > 0;
}

/**
 * Crée un compte utilisateur (mot de passe haché).
 *
 * @return int|null ID du nouveau compte, ou null si l'email existe déjà
 */
function inscrire_utilisateur(string $nom, string $prenom, string $email, string $motDePasse): ?int
{
    $hachage = password_hash($motDePasse, PASSWORD_DEFAULT);

    try {
        $requete = bd()->prepare(
            'INSERT INTO users (nom, prenom, email, mot_de_passe) VALUES (?, ?, ?, ?)'
        );
        $requete->execute([$nom, $prenom, $email, $hachage]);

        return (int) bd()->lastInsertId();
    } catch (PDOException $e) {
        // Contrainte UNIQUE sur l'email
        return null;
    }
}

/**
 * Traite le formulaire d'inscription (register.php).
 */
function traiter_inscription(): void
{
    if (!verifier_jeton()) {
        definir_message('Formulaire expiré, veuillez réessayer.', 'erreur');
        rediriger('register.php');
    }

    $donnees = [
        'nom'           => trim($_POST['nom'] ?? ''),
        'prenom'        => trim($_POST['prenom'] ?? ''),
        'email'         => trim($_POST['email'] ?? ''),
        'mot_de_passe'  => (string) ($_POST['mot_de_passe'] ?? ''),
    ];

    $erreurs = valider_inscription($donnees);

    if (empty($erreurs) && email_deja_utilise($donnees['email'])) {
        $erreurs['email'] = 'Cette adresse email est déjà utilisée.';
    }

    if (!empty($erreurs)) {
        // On réaffiche le formulaire avec les erreurs et les valeurs saisies
        $_SESSION['erreurs_inscription'] = $erreurs;
        $_SESSION['donnees_inscription'] = $donnees;
        rediriger('register.php');
    }

    $id = inscrire_utilisateur(
        $donnees['nom'],
        $donnees['prenom'],
        $donnees['email'],
        $donnees['mot_de_passe']
    );

    if ($id === null) {
        $_SESSION['erreurs_inscription'] = ['email' => 'Cette adresse email est déjà utilisée.'];
        $_SESSION['donnees_inscription'] = $donnees;
        rediriger('register.php');
    }

    definir_message('Compte créé avec succès ! Vous pouvez maintenant vous connecter.', 'succes');
    rediriger('login.php');
}

/* =====================================================================
 * CONNEXION / DÉCONNEXION
 * ===================================================================== */

/**
 * Vérifie l'email et le mot de passe d'un utilisateur.
 *
 * @return array|null Ligne de la table users, ou null si identifiants incorrects
 */
function verifier_identifiants(string $email, string $motDePasse): ?array
{
    $requete = bd()->prepare('SELECT * FROM users WHERE email = ?');
    $requete->execute([$email]);
    $utilisateur = $requete->fetch();

    if (!$utilisateur || !password_verify($motDePasse, $utilisateur['mot_de_passe'])) {
        return null;
    }

    return $utilisateur;
}

/**
 * Ouvre la session de l'utilisateur et enregistre la connexion (table activity).
 */
function ouvrir_session_utilisateur(array $utilisateur): void
{
    session_regenerate_id(true);

    $_SESSION['user_id']     = (int) $utilisateur['id'];
    $_SESSION['nom']         = (string) $utilisateur['nom'];
    $_SESSION['prenom']      = (string) $utilisateur['prenom'];
    $_SESSION['email']       = (string) $utilisateur['email'];
    $_SESSION['nom_complet'] = $utilisateur['prenom'] . ' ' . $utilisateur['nom'];

    enregistrer_connexion((int) $utilisateur['id'], $_SESSION['nom_complet']);
}

/**
 * Traite le formulaire de connexion (login.php).
 */
function traiter_connexion(): void
{
    if (!verifier_jeton()) {
        definir_message('Formulaire expiré, veuillez réessayer.', 'erreur');
        rediriger('login.php');
    }

    $email       = trim($_POST['email'] ?? '');
    $motDePasse  = (string) ($_POST['mot_de_passe'] ?? '');
    $utilisateur = verifier_identifiants($email, $motDePasse);

    if ($utilisateur === null) {
        definir_message('Email ou mot de passe incorrect.', 'erreur');
        rediriger('login.php');
    }

    ouvrir_session_utilisateur($utilisateur);
    definir_message('Bienvenue, ' . $utilisateur['prenom'] . ' !', 'succes');
    rediriger('dashboard.php');
}

/**
 * Déconnecte l'utilisateur : enregistre la fin de session, détruit la session
 * PHP puis redirige vers la page d'accueil (logout.php).
 */
function traiter_deconnexion(): void
{
    if (est_connecte()) {
        enregistrer_deconnexion((int) $_SESSION['user_id']);
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
    rediriger('index.php');
}

/* =====================================================================
 * SUIVI DES SESSIONS (table activity)
 * ===================================================================== */

/**
 * Enregistre l'heure de connexion dans la table activity.
 */
function enregistrer_connexion(int $userId, string $nomUtilisateur): void
{
    $requete = bd()->prepare(
        'INSERT INTO activity (user_id, nom_utilisateur, heure_connexion) VALUES (?, ?, ?)'
    );
    $requete->execute([$userId, $nomUtilisateur, date('Y-m-d H:i:s')]);
}

/**
 * Enregistre l'heure de déconnexion et calcule la durée de la session.
 */
function enregistrer_deconnexion(int $userId): void
{
    $requete = bd()->prepare(
        'SELECT id, heure_connexion FROM activity
         WHERE user_id = ? AND heure_deconnexion IS NULL
         ORDER BY id DESC LIMIT 1'
    );
    $requete->execute([$userId]);
    $session = $requete->fetch();

    if (!$session) {
        return;
    }

    $maintenant = date('Y-m-d H:i:s');
    $duree      = calculer_duree($session['heure_connexion'], $maintenant);

    $miseAJour = bd()->prepare(
        'UPDATE activity SET heure_deconnexion = ?, duree_session = ? WHERE id = ?'
    );
    $miseAJour->execute([$maintenant, $duree, $session['id']]);
}

/**
 * Calcule la durée entre deux dates, au format HH:MM:SS.
 */
function calculer_duree(string $debut, string $fin): string
{
    $secondes = strtotime($fin) - strtotime($debut);
    if ($secondes < 0) {
        $secondes = 0;
    }

    $heures  = intdiv($secondes, 3600);
    $minutes = intdiv($secondes % 3600, 60);
    $restant = $secondes % 60;

    return sprintf('%02d:%02d:%02d', $heures, $minutes, $restant);
}

/**
 * Retourne l'historique des sessions d'un utilisateur (table activity).
 */
function lister_sessions(int $userId): array
{
    $requete = bd()->prepare(
        'SELECT id, heure_connexion, heure_deconnexion, duree_session
         FROM activity WHERE user_id = ? ORDER BY id DESC'
    );
    $requete->execute([$userId]);

    return $requete->fetchAll();
}

/**
 * Formate une date SQL (Y-m-d H:i:s) en jj/mm/aaaa.
 */
function formater_date(?string $date): string
{
    return $date ? date('d/m/Y', strtotime($date)) : '—';
}

/**
 * Formate une heure SQL (Y-m-d H:i:s) en HH:MM:SS.
 */
function formater_heure(?string $date): string
{
    return $date ? date('H:i:s', strtotime($date)) : '—';
}

/* =====================================================================
 * GESTION DES TÂCHES (table taches)
 * ===================================================================== */

/**
 * Vrai si le statut fait partie des trois statuts autorisés.
 */
function statut_valide(string $statut): bool
{
    return in_array($statut, STATUTS, true);
}

/**
 * Valide les données d'une tâche.
 *
 * @return array<string, string> Tableau champ => message d'erreur (vide si OK)
 */
function valider_tache(array $donnees): array
{
    $erreurs = [];
    $titre   = trim($donnees['titre'] ?? '');
    $desc    = trim($donnees['description'] ?? '');
    $statut  = (string) ($donnees['statut'] ?? '');

    if (mb_strlen($titre) < 2) {
        $erreurs['titre'] = 'Le titre doit contenir au moins 2 caractères.';
    } elseif (mb_strlen($titre) > 200) {
        $erreurs['titre'] = 'Le titre ne peut pas dépasser 200 caractères.';
    }

    if (mb_strlen($desc) > 2000) {
        $erreurs['description'] = 'La description ne peut pas dépasser 2000 caractères.';
    }

    if (!statut_valide($statut)) {
        $erreurs['statut'] = 'Veuillez choisir un statut valide.';
    }

    return $erreurs;
}

/**
 * Liste les tâches de l'utilisateur (plus récentes d'abord).
 */
function lister_taches(int $userId): array
{
    $requete = bd()->prepare(
        'SELECT id, titre, description, statut, date_creation
         FROM taches WHERE user_id = ?
         ORDER BY date_creation DESC, id DESC'
    );
    $requete->execute([$userId]);

    return $requete->fetchAll();
}

/**
 * Retourne une tâche appartenant à l'utilisateur (ou null).
 */
function tache_par_id(int $userId, int $id): ?array
{
    $requete = bd()->prepare(
        'SELECT id, titre, description, statut, date_creation
         FROM taches WHERE id = ? AND user_id = ?'
    );
    $requete->execute([$id, $userId]);
    $tache = $requete->fetch();

    return $tache ?: null;
}

/**
 * Statistiques des tâches de l'utilisateur (pour le tableau de bord).
 *
 * @return array{total: int, a_faire: int, en_cours: int, termine: int}
 */
function statistiques_taches(int $userId): array
{
    $requete = bd()->prepare('SELECT statut, COUNT(*) AS nombre FROM taches WHERE user_id = ? GROUP BY statut');
    $requete->execute([$userId]);

    $stats = ['total' => 0, 'a_faire' => 0, 'en_cours' => 0, 'termine' => 0];

    foreach ($requete->fetchAll() as $ligne) {
        $stats['total'] += (int) $ligne['nombre'];

        if ($ligne['statut'] === 'À faire') {
            $stats['a_faire'] += (int) $ligne['nombre'];
        } elseif ($ligne['statut'] === 'En cours') {
            $stats['en_cours'] += (int) $ligne['nombre'];
        } elseif ($ligne['statut'] === 'Terminé') {
            $stats['termine'] += (int) $ligne['nombre'];
        }
    }

    return $stats;
}

/**
 * Ajoute une nouvelle tâche (statut « À faire » par défaut à la création).
 */
function creer_tache(int $userId, string $titre, ?string $description, string $statut = 'À faire'): void
{
    $requete = bd()->prepare(
        'INSERT INTO taches (user_id, titre, description, statut, date_creation) VALUES (?, ?, ?, ?, ?)'
    );
    $requete->execute([$userId, $titre, $description, $statut, date('Y-m-d H:i:s')]);
}

/**
 * Met à jour le contenu d'une tâche appartenant à l'utilisateur.
 */
function modifier_tache(int $userId, int $id, string $titre, ?string $description, string $statut): bool
{
    $requete = bd()->prepare(
        'UPDATE taches SET titre = ?, description = ?, statut = ? WHERE id = ? AND user_id = ?'
    );
    $requete->execute([$titre, $description, $statut, $id, $userId]);

    return $requete->rowCount() > 0;
}

/**
 * Change rapidement le statut d'une tâche.
 */
function changer_statut_tache(int $userId, int $id, string $statut): bool
{
    if (!statut_valide($statut)) {
        return false;
    }

    $requete = bd()->prepare('UPDATE taches SET statut = ? WHERE id = ? AND user_id = ?');
    $requete->execute([$statut, $id, $userId]);

    return $requete->rowCount() > 0;
}

/**
 * Supprime définitivement une tâche.
 */
function supprimer_tache(int $userId, int $id): bool
{
    $requete = bd()->prepare('DELETE FROM taches WHERE id = ? AND user_id = ?');
    $requete->execute([$id, $userId]);

    return $requete->rowCount() > 0;
}

/**
 * Traite le formulaire d'ajout rapide d'une tâche (dashboard.php).
 */
function traiter_ajout_tache(): void
{
    if (!verifier_jeton()) {
        definir_message('Formulaire expiré, veuillez réessayer.', 'erreur');
        rediriger('dashboard.php');
    }

    $donnees = [
        'titre'       => trim($_POST['titre'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'statut'      => (string) ($_POST['statut'] ?? 'À faire'),
    ];

    $erreurs = valider_tache($donnees);

    if (!empty($erreurs)) {
        $_SESSION['erreurs_tache'] = $erreurs;
        $_SESSION['donnees_tache'] = $donnees;
        rediriger('dashboard.php');
    }

    creer_tache(
        (int) $_SESSION['user_id'],
        $donnees['titre'],
        $donnees['description'] !== '' ? $donnees['description'] : null,
        $donnees['statut']
    );

    definir_message('Tâche ajoutée avec succès !', 'succes');
    rediriger('dashboard.php');
}

/**
 * Traite le formulaire de modification d'une tâche (dashboard.php?modifier=ID).
 */
function traiter_modification_tache(): void
{
    if (!verifier_jeton()) {
        definir_message('Formulaire expiré, veuillez réessayer.', 'erreur');
        rediriger('dashboard.php');
    }

    $userId = (int) $_SESSION['user_id'];
    $id     = (int) ($_POST['id'] ?? 0);

    $donnees = [
        'titre'       => trim($_POST['titre'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'statut'      => (string) ($_POST['statut'] ?? ''),
    ];

    $erreurs = valider_tache($donnees);

    if (!empty($erreurs)) {
        $_SESSION['erreurs_tache'] = $erreurs;
        $_SESSION['donnees_tache'] = $donnees;
        $_SESSION['id_edition']    = $id;
        rediriger('dashboard.php?modifier=' . $id);
    }

    if (tache_par_id($userId, $id) === null) {
        definir_message('Tâche introuvable.', 'erreur');
        rediriger('dashboard.php');
    }

    modifier_tache(
        $userId,
        $id,
        $donnees['titre'],
        $donnees['description'] !== '' ? $donnees['description'] : null,
        $donnees['statut']
    );

    definir_message('Tâche mise à jour avec succès !', 'succes');
    rediriger('dashboard.php');
}

/**
 * Traite le changement de statut d'une tâche (bouton « Changer de statut »).
 */
function traiter_changement_statut(): void
{
    if (!verifier_jeton()) {
        definir_message('Formulaire expiré, veuillez réessayer.', 'erreur');
        rediriger('dashboard.php');
    }

    $userId = (int) $_SESSION['user_id'];
    $id     = (int) ($_POST['id'] ?? 0);
    $statut = (string) ($_POST['nouveau_statut'] ?? '');

    if (changer_statut_tache($userId, $id, $statut)) {
        definir_message('Statut mis à jour : ' . $statut . '.', 'succes');
    } else {
        definir_message('Impossible de changer le statut de cette tâche.', 'erreur');
    }

    rediriger('dashboard.php');
}

/**
 * Traite la suppression définitive d'une tâche (après confirmation).
 */
function traiter_suppression_tache(): void
{
    if (!verifier_jeton()) {
        definir_message('Formulaire expiré, veuillez réessayer.', 'erreur');
        rediriger('dashboard.php');
    }

    $userId = (int) $_SESSION['user_id'];
    $id     = (int) ($_POST['id'] ?? 0);

    if (supprimer_tache($userId, $id)) {
        definir_message('Tâche supprimée.', 'succes');
    } else {
        definir_message('Impossible de supprimer cette tâche.', 'erreur');
    }

    rediriger('dashboard.php');
}
