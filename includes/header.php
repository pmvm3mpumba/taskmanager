<?php
/**
 * En-tête commun aux pages : ouverture du HTML, barre de navigation.
 * Variables attendues : $titre_page (string)
 */
$message_flash = recuperer_message();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titre_page ?? 'MonEspaceTâches') ?> | MonEspaceTâches</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="conteneur navbar-contenu">
        <a href="index.php" class="marque">
            <span class="marque-logo" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 11l3 3L22 4"></path>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                </svg>
            </span>
            <span>MonEspace<span class="accent">Tâches</span></span>
        </a>

        <div class="navbar-liens">
            <?php if (est_connecte()): ?>
                <a href="dashboard.php" class="lien-nav">Tableau de bord</a>
                <span class="badge-utilisateur"><?= e(utilisateur_actuel()['prenom']) ?></span>
                <a href="logout.php" class="btn btn-outline btn-sm">Se déconnecter</a>
            <?php else: ?>
                <a href="login.php" class="lien-nav">Se connecter</a>
                <a href="register.php" class="btn btn-primary btn-sm">S'inscrire</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="conteneur">
<?php if ($message_flash !== null): ?>
    <div class="alerte alerte-<?= e($message_flash['type']) ?>">
        <?= e($message_flash['texte']) ?>
    </div>
<?php endif; ?>
