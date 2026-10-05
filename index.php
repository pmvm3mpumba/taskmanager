<?php

/**
 * MonEspaceTâches — Page d'accueil publique (informative)
 *
 * Présente le service, ses avantages et son fonctionnement,
 * avec des liens d'accès direct vers l'inscription et la connexion.
 */

require_once __DIR__ . '/includes/functions.php';
demarrer_session();

$titre_page = 'Accueil';
require_once __DIR__ . '/includes/header.php';
?>

<section class="heros">
    <div class="conteneur heros-contenu">
        <span class="heros-sur-titre">✓ Application web de gestion de tâches</span>
        <h1>Gérez vos tâches.<br>Maîtrisez votre temps.</h1>
        <p>
            MonEspaceTâches vous aide à organiser votre travail au quotidien et à suivre
            précisément le temps passé sur la plateforme. Simple, rapide et sécurisé.
        </p>
        <div class="heros-boutons">
            <a href="register.php" class="btn btn-clair">S'inscrire gratuitement</a>
            <a href="login.php" class="btn btn-outline" style="color:#fff;border-color:rgba(255,255,255,0.6)">Se connecter</a>
        </div>
    </div>
</section>

<section class="section">
    <div class="section-titre">
        <h2>Pourquoi MonEspaceTâches&nbsp;?</h2>
        <p>Tout ce qu'il faut pour rester organisé, sans superflu.</p>
    </div>

    <div class="grille-carte">
        <div class="carte carte-avantage">
            <span class="icone">📋</span>
            <h3>Organisation claire</h3>
            <p>Créez, modifiez et classez vos tâches en un clic grâce à trois statuts simples :
               À faire, En cours et Terminé.</p>
        </div>
        <div class="carte carte-avantage">
            <span class="icone">🕓</span>
            <h3>Suivi des sessions</h3>
            <p>Consultez l'historique complet de vos connexions : heures d'arrivée,
               de départ et durée totale de chaque session.</p>
        </div>
        <div class="carte carte-avantage">
            <span class="icone">⚡</span>
            <h3>Prise en main immédiate</h3>
            <p>Une interface épurée et ergonomique : aucune formation nécessaire,
               tout est à sa place.</p>
        </div>
        <div class="carte carte-avantage">
            <span class="icone">🔒</span>
            <h3>Espace privé sécurisé</h3>
            <p>Vos données vous appartiennent : mots de passe cryptés et accès
               réservé aux utilisateurs authentifiés.</p>
        </div>
    </div>
</section>

<section class="section">
    <div class="section-titre">
        <h2>Comment ça marche&nbsp;?</h2>
        <p>Un parcours simple, en trois étapes.</p>
    </div>

    <div class="grille-etapes">
        <div class="carte carte-etape">
            <span class="numero">1</span>
            <h3>Créez votre compte</h3>
            <p>Inscrivez-vous en quelques secondes avec votre nom, prénom et adresse email.</p>
        </div>
        <div class="carte carte-etape">
            <span class="numero">2</span>
            <h3>Organisez vos tâches</h3>
            <p>Ajoutez vos tâches, décrivez-les et faites-les évoluer du statut
               « À faire » jusqu'à « Terminé ».</p>
        </div>
        <div class="carte carte-etape">
            <span class="numero">3</span>
            <h3>Suivez vos sessions</h3>
            <p>Visualisez à tout moment l'historique de vos connexions et le temps
               passé sur l'application.</p>
        </div>
    </div>
</section>

<section class="bande-cta">
    <h2>Prêt à vous organiser&nbsp;?</h2>
    <p>Rejoignez MonEspaceTâches et prenez le contrôle de vos journées dès aujourd'hui.</p>
    <div class="heros-boutons">
        <a href="register.php" class="btn btn-clair">Créer mon compte</a>
        <a href="login.php" class="btn btn-outline" style="color:#fff;border-color:rgba(255,255,255,0.6)">J'ai déjà un compte</a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
