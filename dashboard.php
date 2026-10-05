<?php

/**
 * MonEspaceTâches — Tableau de bord privé (dashboard.php)
 *
 * Accessible uniquement après authentification.
 * Module 1 : gestion des tâches (ajout, modification, changement de statut, suppression).
 * Module 2 : historique des sessions (table activity).
 */

require_once __DIR__ . '/includes/functions.php';
demarrer_session();
exiger_connexion();

$utilisateur = utilisateur_actuel();
$userId      = $utilisateur['id'];

/* ---------- Traitement des actions (POST) ---------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    match ($_POST['action'] ?? '') {
        'ajouter'   => traiter_ajout_tache(),
        'modifier'  => traiter_modification_tache(),
        'statut'    => traiter_changement_statut(),
        'supprimer' => traiter_suppression_tache(),
        default     => rediriger('dashboard.php'),
    };
}

/* ---------- Données de la page ---------- */
$taches      = lister_taches($userId);
$sessions    = lister_sessions($userId);
$statistiques = statistiques_taches($userId);

// Mode modification : préparer le formulaire avec la tâche choisie
$tacheEnEdition = null;
if (isset($_GET['modifier'])) {
    $tacheEnEdition = tache_par_id($userId, (int) $_GET['modifier']);
    if ($tacheEnEdition === null) {
        definir_message('Tâche introuvable.', 'erreur');
        rediriger('dashboard.php');
    }
}

// Mode suppression : afficher la demande de confirmation
$tacheASupprimer = null;
if (isset($_GET['supprimer'])) {
    $tacheASupprimer = tache_par_id($userId, (int) $_GET['supprimer']);
    if ($tacheASupprimer === null) {
        definir_message('Tâche introuvable.', 'erreur');
        rediriger('dashboard.php');
    }
}

// Erreurs et valeurs du formulaire de tâche (après validation échouée)
$erreursTache = $_SESSION['erreurs_tache'] ?? [];
$donneesTache = $_SESSION['donnees_tache'] ?? null;
unset($_SESSION['erreurs_tache'], $_SESSION['donnees_tache']);

// Valeurs affichées dans le formulaire (édition > ressaisie > vide)
$valeurTitre = $donneesTache['titre'] ?? $tacheEnEdition['titre'] ?? '';
$valeurDesc  = $donneesTache['description'] ?? $tacheEnEdition['description'] ?? '';
$valeurStatut = $donneesTache['statut'] ?? $tacheEnEdition['statut'] ?? 'À faire';

$titre_page = 'Tableau de bord';
require_once __DIR__ . '/includes/header.php';

/** Classe CSS du badge selon le statut */
function classe_badge(string $statut): string
{
    return match ($statut) {
        'En cours' => 'badge-encours',
        'Terminé'  => 'badge-termine',
        default    => 'badge-a-faire',
    };
}
?>

<div class="entete-page">
    <div>
        <h1>Bonjour, <?= e($utilisateur['prenom']) ?> 👋</h1>
        <p>Gérez vos tâches et consultez l'historique de vos sessions.</p>
    </div>
</div>

<!-- ======== Statistiques rapides ======== -->
<div class="grille-stats">
    <div class="carte-stat">
        <span class="valeur"><?= $statistiques['total'] ?></span>
        <span class="libelle">Tâche(s) au total</span>
    </div>
    <div class="carte-stat stat-a-faire">
        <span class="valeur"><?= $statistiques['a_faire'] ?></span>
        <span class="libelle">À faire</span>
    </div>
    <div class="carte-stat stat-encours">
        <span class="valeur"><?= $statistiques['en_cours'] ?></span>
        <span class="libelle">En cours</span>
    </div>
    <div class="carte-stat stat-termine">
        <span class="valeur"><?= $statistiques['termine'] ?></span>
        <span class="libelle">Terminé</span>
    </div>
</div>

<!-- ======== MODULE 1 : Gestion des tâches ======== -->
<section class="module">
    <div class="module-entete">
        <h2>📋 Mes tâches</h2>
        <span class="compteur"><?= count($taches) ?> tâche(s)</span>
    </div>

    <div class="module-corps">

        <?php if ($tacheASupprimer !== null): ?>
            <!-- Demande de confirmation avant suppression -->
            <div class="boite-confirmation">
                <h3>Supprimer cette tâche&nbsp;?</h3>
                <p>«&nbsp;<?= e($tacheASupprimer['titre']) ?>&nbsp;» sera supprimée définitivement.</p>
                <div class="boutons-confirmation">
                    <form method="post" action="dashboard.php">
                        <?= champ_jeton() ?>
                        <input type="hidden" name="action" value="supprimer">
                        <input type="hidden" name="id" value="<?= (int) $tacheASupprimer['id'] ?>">
                        <button type="submit" class="btn btn-danger">Oui, supprimer</button>
                    </form>
                    <a href="dashboard.php" class="btn btn-lien">Non, annuler</a>
                </div>
            </div>

        <?php else: ?>
            <!-- Formulaire d'ajout rapide / modification -->
            <form method="post" action="dashboard.php" class="formulaire" novalidate>
                <?= champ_jeton() ?>
                <input type="hidden" name="action" value="<?= $tacheEnEdition ? 'modifier' : 'ajouter' ?>">
                <?php if ($tacheEnEdition): ?>
                    <input type="hidden" name="id" value="<?= (int) $tacheEnEdition['id'] ?>">
                <?php endif; ?>

                <h3 style="font-size:1.05rem;"><?= $tacheEnEdition ? '✏️ Modifier la tâche' : '➕ Ajouter une tâche' ?></h3>

                <div class="formulaire-ligne">
                    <div class="champ">
                        <label for="titre">Titre</label>
                        <input type="text" id="titre" name="titre" required maxlength="200"
                               placeholder="Titre de la tâche"
                               class="<?= isset($erreursTache['titre']) ? 'erreur' : '' ?>"
                               value="<?= e($valeurTitre) ?>">
                        <?php if (isset($erreursTache['titre'])): ?>
                            <span class="champ-erreur"><?= e($erreursTache['titre']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="champ">
                        <label for="statut">Statut</label>
                        <select id="statut" name="statut" class="<?= isset($erreursTache['statut']) ? 'erreur' : '' ?>">
                            <?php foreach (STATUTS as $statut): ?>
                                <option value="<?= e($statut) ?>" <?= $valeurStatut === $statut ? 'selected' : '' ?>>
                                    <?= e($statut) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($erreursTache['statut'])): ?>
                            <span class="champ-erreur"><?= e($erreursTache['statut']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="champ">
                    <label for="description">Description <span class="indice">(facultative)</span></label>
                    <textarea id="description" name="description" maxlength="2000"
                              placeholder="Détails de la tâche…"
                              class="<?= isset($erreursTache['description']) ? 'erreur' : '' ?>"><?= e($valeurDesc) ?></textarea>
                    <?php if (isset($erreursTache['description'])): ?>
                        <span class="champ-erreur"><?= e($erreursTache['description']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="cellule-actions">
                    <button type="submit" class="btn btn-primary">
                        <?= $tacheEnEdition ? 'Enregistrer les modifications' : 'Ajouter la tâche' ?>
                    </button>
                    <?php if ($tacheEnEdition): ?>
                        <a href="dashboard.php" class="btn btn-lien">Annuler</a>
                    <?php endif; ?>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <!-- Tableau des tâches -->
    <div class="module-corps module-corps-sans-bordure">
        <?php if (empty($taches)): ?>
            <div class="etat-vide">
                <span class="grosse-icone">🗒️</span>
                <p>Aucune tâche pour le moment.<br>Utilisez le formulaire ci-dessus pour en créer une&nbsp;!</p>
            </div>
        <?php else: ?>
            <div class="tableau-conteneur">
                <table>
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Description</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($taches as $tache): ?>
                            <tr>
                                <td class="titre-tache"><?= e($tache['titre']) ?></td>
                                <td class="desc-tache"><?= e($tache['description'] ?? '') ?></td>
                                <td>
                                    <span class="badge <?= classe_badge($tache['statut']) ?>">
                                        <?= e($tache['statut']) ?>
                                    </span>
                                </td>
                                <td><?= formater_date($tache['date_creation']) ?></td>
                                <td>
                                    <div class="cellule-actions">
                                        <!-- Modifier -->
                                        <a class="btn btn-outline btn-sm"
                                           href="dashboard.php?modifier=<?= (int) $tache['id'] ?>">Modifier</a>

                                        <!-- Changer de statut -->
                                        <form method="post" action="dashboard.php" class="form-statut">
                                            <?= champ_jeton() ?>
                                            <input type="hidden" name="action" value="statut">
                                            <input type="hidden" name="id" value="<?= (int) $tache['id'] ?>">
                                            <select name="nouveau_statut" aria-label="Nouveau statut">
                                                <?php foreach (STATUTS as $statut): ?>
                                                    <option value="<?= e($statut) ?>" <?= $tache['statut'] === $statut ? 'selected' : '' ?>>
                                                        <?= e($statut) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-outline btn-sm">OK</button>
                                        </form>

                                        <!-- Supprimer -->
                                        <a class="btn btn-danger btn-sm"
                                           href="dashboard.php?supprimer=<?= (int) $tache['id'] ?>">Supprimer</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ======== MODULE 2 : Historique des sessions ======== -->
<section class="module">
    <div class="module-entete">
        <h2>🕓 Historique des sessions</h2>
        <span class="compteur"><?= count($sessions) ?> session(s)</span>
    </div>

    <div class="module-corps module-corps-sans-bordure">
        <?php if (empty($sessions)): ?>
            <div class="etat-vide">
                <span class="grosse-icone">🕐</span>
                <p>Aucune session enregistrée pour le moment.</p>
            </div>
        <?php else: ?>
            <div class="tableau-conteneur">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Heure de connexion</th>
                            <th>Heure de déconnexion</th>
                            <th>Durée totale</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $session): ?>
                            <tr>
                                <td><?= formater_date($session['heure_connexion']) ?></td>
                                <td><?= formater_heure($session['heure_connexion']) ?></td>
                                <td>
                                    <?php if ($session['heure_deconnexion'] === null): ?>
                                        <span class="badge badge-encours-session">En cours</span>
                                    <?php else: ?>
                                        <?= formater_heure($session['heure_deconnexion']) ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($session['duree_session'] === null): ?>
                                        <span class="badge badge-encours-session">En cours</span>
                                    <?php else: ?>
                                        <strong><?= e($session['duree_session']) ?></strong>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
