<?php

/**
 * MonEspaceTâches — Page de connexion (login.php)
 *
 * Vérifie les identifiants, ouvre la session PHP et enregistre
 * automatiquement l'heure de connexion dans la table activity.
 */

require_once __DIR__ . '/includes/functions.php';
demarrer_session();

// Déjà connecté → tableau de bord
if (est_connecte()) {
    rediriger('dashboard.php');
}

// Traitement du formulaire (traiter_connexion() redirige toujours)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    traiter_connexion();
}

$titre_page = 'Connexion';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-auth">
    <div class="carte-auth">
        <span class="icone-entete">🔐</span>
        <h1>Se connecter</h1>
        <p class="sous-titre">Accédez à votre espace personnel de gestion des tâches.</p>

        <form method="post" action="login.php" class="formulaire" novalidate>
            <?= champ_jeton() ?>

            <div class="champ">
                <label for="email">Adresse email</label>
                <input type="email" id="email" name="email" required maxlength="190"
                       placeholder="exemple@domaine.com" value="<?= e($_POST['email'] ?? '') ?>">
            </div>

            <div class="champ">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required
                       maxlength="255" placeholder="••••••••">
            </div>

            <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
        </form>

        <p class="pied-lien">Pas encore de compte&nbsp;? <a href="register.php">S'inscrire</a></p>
        <p class="pied-lien"><a href="index.php">← Retour à l'accueil</a></p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
