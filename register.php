<?php

/**
 * MonEspaceTâches — Page d'inscription (register.php)
 *
 * Formulaire : Nom, Prénom, Email, Mot de passe.
 * Les données sont validées puis insérées dans la table users (mot de passe haché).
 */

require_once __DIR__ . '/includes/functions.php';
demarrer_session();

// Déjà connecté → tableau de bord
if (est_connecte()) {
    rediriger('dashboard.php');
}

// Traitement du formulaire (traiter_inscription() redirige toujours)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    traiter_inscription();
}

// Récupération des erreurs et des valeurs saisies après la redirection
$erreurs = $_SESSION['erreurs_inscription'] ?? [];
$donnees = $_SESSION['donnees_inscription'] ?? ['nom' => '', 'prenom' => '', 'email' => ''];
unset($_SESSION['erreurs_inscription'], $_SESSION['donnees_inscription']);

$titre_page = 'Inscription';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-auth">
    <div class="carte-auth">
        <span class="icone-entete">📝</span>
        <h1>Créer un compte</h1>
        <p class="sous-titre">Rejoignez MonEspaceTâches et organisez vos tâches dès maintenant.</p>

        <form method="post" action="register.php" class="formulaire" novalidate>
            <?= champ_jeton() ?>

            <div class="formulaire-ligne">
                <div class="champ">
                    <label for="nom">Nom</label>
                    <input type="text" id="nom" name="nom" required maxlength="100"
                           placeholder="Votre nom"
                           class="<?= isset($erreurs['nom']) ? 'erreur' : '' ?>"
                           value="<?= e($donnees['nom']) ?>">
                    <?php if (isset($erreurs['nom'])): ?>
                        <span class="champ-erreur"><?= e($erreurs['nom']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="champ">
                    <label for="prenom">Prénom</label>
                    <input type="text" id="prenom" name="prenom" required maxlength="100"
                           placeholder="Votre prénom"
                           class="<?= isset($erreurs['prenom']) ? 'erreur' : '' ?>"
                           value="<?= e($donnees['prenom']) ?>">
                    <?php if (isset($erreurs['prenom'])): ?>
                        <span class="champ-erreur"><?= e($erreurs['prenom']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="champ">
                <label for="email">Adresse email</label>
                <input type="email" id="email" name="email" required maxlength="190"
                       placeholder="exemple@domaine.com"
                       class="<?= isset($erreurs['email']) ? 'erreur' : '' ?>"
                       value="<?= e($donnees['email']) ?>">
                <?php if (isset($erreurs['email'])): ?>
                    <span class="champ-erreur"><?= e($erreurs['email']) ?></span>
                <?php endif; ?>
            </div>

            <div class="champ">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required minlength="8"
                       maxlength="255" placeholder="••••••••"
                       class="<?= isset($erreurs['mot_de_passe']) ? 'erreur' : '' ?>">
                <span class="indice">8 caractères minimum.</span>
                <?php if (isset($erreurs['mot_de_passe'])): ?>
                    <span class="champ-erreur"><?= e($erreurs['mot_de_passe']) ?></span>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Créer mon compte</button>
        </form>

        <p class="pied-lien">Déjà un compte&nbsp;? <a href="login.php">Se connecter</a></p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
