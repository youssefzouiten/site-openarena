<?php
session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ad.php';

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function currentUser() {
    return $_SESSION['user'] ?? null;
}

function isAdmin() {
    return isset($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'admin';
}

function redirectTo($page, $extra = '') {
    header('Location: index.php?page=' . urlencode($page) . $extra);
    exit;
}

function flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function showFlash() {
    if (!isset($_SESSION['flash'])) return;
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    echo '<div class="alert alert-' . e($f['type']) . '">' . e($f['message']) . '</div>';
}

function refreshSession(PDO $pdo) {
    if (!isset($_SESSION['user']['pseudo'])) return;

    $stmt = $pdo->prepare("
        SELECT id, pseudo, email, ville, role, score, kills, deaths, matchs, createAt, keybinds
        FROM joueurs
        WHERE pseudo = ?
    ");
    $stmt->execute([$_SESSION['user']['pseudo']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['user'] = $user;
    }
}

function getJoueurs(PDO $pdo, $includeAdmins = false) {
    if ($includeAdmins) {
        $stmt = $pdo->query("
            SELECT id, pseudo, email, ville, role, score, kills, deaths, matchs, createAt, keybinds
            FROM joueurs
            ORDER BY score DESC, pseudo ASC
        ");
    } else {
        $stmt = $pdo->query("
            SELECT id, pseudo, email, ville, role, score, kills, deaths, matchs, createAt, keybinds
            FROM joueurs
            WHERE role <> 'admin'
            ORDER BY score DESC, pseudo ASC
        ");
    }

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRDV(PDO $pdo) {
    $stmt = $pdo->query("SELECT * FROM rendezvous ORDER BY date ASC, time ASC, createAt DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getParties(PDO $pdo) {
    $stmt = $pdo->query("
        SELECT id, map, mode_jeu AS mode, nb_joueurs AS nbJoueurs, temps, kills_max AS kills, statut, createdAt
        FROM parties
        ORDER BY createdAt DESC
        LIMIT 50
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function avancerVainqueur($pdo, $match_id) {
    $stmt = $pdo->prepare("SELECT * FROM tournoi_matches WHERE id = ?");
    $stmt->execute([$match_id]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$match || $match['round'] >= 3) return;

    $tournoi_id = $match['tournoi_id'];
    $round_actuel = $match['round'];
    $next_round = $round_actuel + 1;

    $stmt = $pdo->prepare("
        SELECT * FROM tournoi_matches
        WHERE tournoi_id = ? AND round = ?
        ORDER BY match_number
    ");
    $stmt->execute([$tournoi_id, $round_actuel]);
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($matches as $m) {
        if ($m['statut'] !== 'finished') {
            return;
        }
    }

    if ($next_round === 2) {
        $stmt = $pdo->prepare("
            INSERT INTO tournoi_matches (tournoi_id, round, match_number, player1, player2, statut)
            VALUES (?, 2, 1, ?, ?, 'pending')
        ");
        $stmt->execute([$tournoi_id, $matches[0]['winner'], $matches[1]['winner']]);

        $stmt = $pdo->prepare("
            INSERT INTO tournoi_matches (tournoi_id, round, match_number, player1, player2, statut)
            VALUES (?, 2, 2, ?, ?, 'pending')
        ");
        $stmt->execute([$tournoi_id, $matches[2]['winner'], $matches[3]['winner']]);
    }

    if ($next_round === 3) {
        $stmt = $pdo->prepare("
            INSERT INTO tournoi_matches (tournoi_id, round, match_number, player1, player2, statut)
            VALUES (?, 3, 1, ?, ?, 'pending')
        ");
        $stmt->execute([$tournoi_id, $matches[0]['winner'], $matches[1]['winner']]);
    }

    $pdo->prepare("UPDATE tournois SET round_actuel = ? WHERE id = ?")
        ->execute([$next_round, $tournoi_id]);
}

$validPages = [
    'accueil',
    'classement',
    'rdv',
    'joueurs',
    'touches',
    'profil',
    'admin',
    'tournoi',
    'login',
    'register'
];

$page = $_GET['page'] ?? 'accueil';

if (!in_array($page, $validPages, true)) {
    $page = 'accueil';
}

$rdvTab = $_GET['tab'] ?? 'publier';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {

        if ($action === 'register') {
            $pseudo = trim($_POST['pseudo'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $password2 = $_POST['password_confirm'] ?? '';
            $ville = trim($_POST['ville'] ?? 'Marseille');
            $role = isAdmin() ? trim($_POST['role'] ?? 'joueur') : 'joueur';

            if ($pseudo === '' || $email === '' || $password === '') {
                throw new Exception('Champs manquants.');
            }

            if ($password !== $password2) {
                throw new Exception('Les mots de passe ne correspondent pas.');
            }

            if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $pseudo)) {
                throw new Exception('Pseudo invalide.');
            }

            if (!in_array($role, ['joueur', 'admin'], true)) {
                $role = 'joueur';
            }

            $stmt = $pdo->prepare("SELECT id FROM joueurs WHERE pseudo = ? OR email = ?");
            $stmt->execute([$pseudo, $email]);

            if ($stmt->fetch()) {
                throw new Exception('Pseudo ou email déjà utilisé.');
            }

            if (!ad_create_user($pseudo, $email)) {
                throw new Exception("Erreur : impossible de créer l'utilisateur dans Active Directory.");
            }

            $stmt = $pdo->prepare("
                INSERT INTO joueurs (pseudo, email, password, ville, role)
                VALUES (?, ?, '', ?, ?)
            ");
            $stmt->execute([$pseudo, $email, $ville, $role]);

            if (isAdmin()) {
                flash('success', 'Joueur ajouté dans Active Directory et OpenArena.');
                redirectTo('admin');
            }

            $stmt = $pdo->prepare("
                SELECT id, pseudo, email, ville, role, score, kills, deaths, matchs, createAt, keybinds
                FROM joueurs
                WHERE pseudo = ?
            ");
            $stmt->execute([$pseudo]);
            $_SESSION['user'] = $stmt->fetch(PDO::FETCH_ASSOC);

            flash('success', 'Compte créé dans Active Directory et OpenArena.');
            redirectTo('profil');
        }

        if ($action === 'login') {
            $pseudo = trim($_POST['pseudo'] ?? '');
            $password = $_POST['password'] ?? '';

            if (!ad_login($pseudo, $password)) {
                throw new Exception('Pseudo ou mot de passe Active Directory incorrect.');
            }

            $stmt = $pdo->prepare("SELECT * FROM joueurs WHERE pseudo = ?");
            $stmt->execute([$pseudo]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $role = ($pseudo === 'Administrator') ? 'admin' : 'joueur';

                $stmt = $pdo->prepare("
                    INSERT INTO joueurs (pseudo, email, password, ville, role)
                    VALUES (?, ?, '', 'Marseille', ?)
                ");
                $stmt->execute([
                    $pseudo,
                    $pseudo . '@openarena.local',
                    $role
                ]);

                $stmt = $pdo->prepare("SELECT * FROM joueurs WHERE pseudo = ?");
                $stmt->execute([$pseudo]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            unset($user['password']);
            $_SESSION['user'] = $user;

            flash('success', 'Connexion Active Directory réussie.');
            redirectTo('profil');
        }
                if ($action === 'logout') {
            session_destroy();
            header('Location: index.php');
            exit;
        }

        if ($action === 'update_profile') {

            if (!currentUser()) {
                throw new Exception('Connexion requise.');
            }

            $pseudo = trim($_POST['pseudo'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $ville = trim($_POST['ville'] ?? '');

            if ($pseudo === '' || $email === '') {
                throw new Exception('Pseudo et email requis.');
            }

            $stmt = $pdo->prepare("
                UPDATE joueurs
                SET pseudo = ?, email = ?, ville = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $pseudo,
                $email,
                $ville,
                $_SESSION['user']['id']
            ]);

            refreshSession($pdo);

            flash('success', 'Profil mis à jour.');
            redirectTo('profil');
        }

        if ($action === 'save_keybinds') {

            if (!currentUser()) {
                throw new Exception('Connexion requise.');
            }

            $keybinds = [
                'forward' => trim($_POST['forward'] ?? 'Z'),
                'backward' => trim($_POST['backward'] ?? 'S'),
                'left' => trim($_POST['left'] ?? 'Q'),
                'right' => trim($_POST['right'] ?? 'D')
            ];

            $stmt = $pdo->prepare("
                UPDATE joueurs
                SET keybinds = ?
                WHERE id = ?
            ");

            $stmt->execute([
                json_encode($keybinds),
                $_SESSION['user']['id']
            ]);

            refreshSession($pdo);

            flash('success', 'Touches sauvegardées.');
            redirectTo('touches');
        }

        if ($action === 'create_rdv') {

            if (!currentUser()) {
                throw new Exception('Connexion requise.');
            }

            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $date = $_POST['date'] ?? '';
            $time = $_POST['time'] ?? '';

            if ($title === '' || $date === '' || $time === '') {
                throw new Exception('Champs manquants.');
            }

            $stmt = $pdo->prepare("
                INSERT INTO rendezvous
                (title, description, date, time, author)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $title,
                $description,
                $date,
                $time,
                $_SESSION['user']['pseudo']
            ]);

            flash('success', 'Rendez-vous publié.');
            redirectTo('rdv');
        }

        if ($action === 'delete_rdv') {

            if (!isAdmin()) {
                throw new Exception('Accès refusé.');
            }

            $id = (int)($_POST['id'] ?? 0);

            $stmt = $pdo->prepare("DELETE FROM rendezvous WHERE id = ?");
            $stmt->execute([$id]);

            flash('success', 'Rendez-vous supprimé.');
            redirectTo('rdv');
        }

        if ($action === 'create_partie') {

            if (!isAdmin()) {
                throw new Exception('Accès refusé.');
            }

            $map = trim($_POST['map'] ?? '');
            $mode = trim($_POST['mode'] ?? '');
            $nbJoueurs = (int)($_POST['nb_joueurs'] ?? 0);
            $temps = (int)($_POST['temps'] ?? 0);
            $kills = (int)($_POST['kills_max'] ?? 0);

            $stmt = $pdo->prepare("
                INSERT INTO parties
                (map, mode_jeu, nb_joueurs, temps, kills_max, statut)
                VALUES (?, ?, ?, ?, ?, 'waiting')
            ");

            $stmt->execute([
                $map,
                $mode,
                $nbJoueurs,
                $temps,
                $kills
            ]);

            flash('success', 'Partie créée.');
            redirectTo('admin');
        }

        if ($action === 'delete_joueur') {

            if (!isAdmin()) {
                throw new Exception('Accès refusé.');
            }

            $id = (int)($_POST['id'] ?? 0);

            $stmt = $pdo->prepare("DELETE FROM joueurs WHERE id = ?");
            $stmt->execute([$id]);

            flash('success', 'Joueur supprimé.');
            redirectTo('admin');
        }

    } catch (Throwable $e) {

        flash('danger', $e->getMessage());

        if ($page === 'login') {
            redirectTo('login');
        }

        if ($page === 'register') {
            redirectTo('register');
        }

        redirectTo($page);
    }
}

$joueurs = getJoueurs($pdo, isAdmin());
$rdvs = getRDV($pdo);
$parties = getParties($pdo);

?>
<!DOCTYPE html>
<html lang="fr">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>OpenArena</title>

<link rel="stylesheet" href="style.css">

<style>

body{
    margin:0;
    font-family:Arial,sans-serif;
    background:#0f172a;
    color:white;
}

header{
    background:#111827;
    padding:15px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

nav a{
    color:white;
    text-decoration:none;
    margin-right:15px;
}

.container{
    width:90%;
    margin:auto;
    padding:20px;
}

.card{
    background:#1e293b;
    padding:20px;
    border-radius:10px;
    margin-bottom:20px;
}

input,select,textarea{
    width:100%;
    padding:10px;
    margin-top:5px;
    margin-bottom:15px;
    border:none;
    border-radius:5px;
}

button{
    background:#2563eb;
    color:white;
    border:none;
    padding:10px 20px;
    border-radius:5px;
    cursor:pointer;
}

button:hover{
    background:#1d4ed8;
}

.alert{
    padding:15px;
    border-radius:5px;
    margin-bottom:20px;
}

.alert-success{
    background:#14532d;
}

.alert-danger{
    background:#7f1d1d;
}

table{
    width:100%;
    border-collapse:collapse;
}

table th,
table td{
    padding:10px;
    border-bottom:1px solid #334155;
    text-align:left;
}

</style>

</head>

<body>

<header>

<h2>OpenArena</h2>

<nav>

<a href="index.php">Accueil</a>
<a href="index.php?page=classement">Classement</a>
<a href="index.php?page=joueurs">Joueurs</a>
<a href="index.php?page=rdv">Rendez-vous</a>

<?php if(currentUser()): ?>

<a href="index.php?page=profil">Profil</a>

<?php if(isAdmin()): ?>
<a href="index.php?page=admin">Admin</a>
<?php endif; ?>

<form method="POST" style="display:inline;">
<input type="hidden" name="action" value="logout">
<button type="submit">Déconnexion</button>
</form>

<?php else: ?>

<a href="index.php?page=login">Connexion</a>
<a href="index.php?page=register">Inscription</a>

<?php endif; ?>

</nav>

</header>

<div class="container">

<?php showFlash(); ?>
<?php if($page === 'accueil'): ?>

<div class="card">

<h1>Bienvenue sur OpenArena</h1>

<p>
Plateforme multijoueur avec Active Directory,
tournois, rendez-vous et gestion joueurs.
</p>

</div>

<?php endif; ?>

<?php if($page === 'login'): ?>

<div class="card">

<h2>Connexion</h2>

<form method="POST">

<input type="hidden" name="action" value="login">

<label>Pseudo</label>
<input type="text" name="pseudo" required>

<label>Mot de passe</label>
<input type="password" name="password" required>

<button type="submit">
Connexion
</button>

</form>

</div>

<?php endif; ?>

<?php if($page === 'register'): ?>

<div class="card">

<h2>Inscription</h2>

<form method="POST">

<input type="hidden" name="action" value="register">

<label>Pseudo</label>
<input type="text" name="pseudo" required>

<label>Email</label>
<input type="email" name="email" required>

<label>Mot de passe</label>
<input type="password" name="password" required>

<label>Confirmation mot de passe</label>
<input type="password" name="password_confirm" required>

<label>Ville</label>
<input type="text" name="ville">

<?php if(isAdmin()): ?>

<label>Rôle</label>

<select name="role">
<option value="joueur">Joueur</option>
<option value="admin">Admin</option>
</select>

<?php endif; ?>

<button type="submit">
Créer un compte
</button>

</form>

</div>

<?php endif; ?>

<?php if($page === 'profil' && currentUser()): ?>

<div class="card">

<h2>Profil</h2>

<form method="POST">

<input type="hidden" name="action" value="update_profile">

<label>Pseudo</label>
<input type="text" name="pseudo"
value="<?= e(currentUser()['pseudo']) ?>" required>

<label>Email</label>
<input type="email" name="email"
value="<?= e(currentUser()['email']) ?>" required>

<label>Ville</label>
<input type="text" name="ville"
value="<?= e(currentUser()['ville']) ?>">

<button type="submit">
Mettre à jour
</button>

</form>

</div>

<div class="card">

<h2>Statistiques</h2>

<p>Score : <?= e(currentUser()['score']) ?></p>
<p>Kills : <?= e(currentUser()['kills']) ?></p>
<p>Deaths : <?= e(currentUser()['deaths']) ?></p>
<p>Matchs : <?= e(currentUser()['matchs']) ?></p>

</div>

<?php endif; ?>

<?php if($page === 'touches' && currentUser()): ?>

<?php
$keybinds = json_decode(currentUser()['keybinds'] ?? '{}', true);

$forward = $keybinds['forward'] ?? 'Z';
$backward = $keybinds['backward'] ?? 'S';
$left = $keybinds['left'] ?? 'Q';
$right = $keybinds['right'] ?? 'D';
?>

<div class="card">

<h2>Configuration des touches</h2>

<form method="POST">

<input type="hidden" name="action" value="save_keybinds">

<label>Avancer</label>
<input type="text" name="forward" value="<?= e($forward) ?>">

<label>Reculer</label>
<input type="text" name="backward" value="<?= e($backward) ?>">

<label>Gauche</label>
<input type="text" name="left" value="<?= e($left) ?>">

<label>Droite</label>
<input type="text" name="right" value="<?= e($right) ?>">

<button type="submit">
Sauvegarder
</button>

</form>

</div>

<?php endif; ?>

<?php if($page === 'classement'): ?>

<div class="card">

<h2>Classement joueurs</h2>

<table>

<tr>
<th>Pseudo</th>
<th>Score</th>
<th>Kills</th>
<th>Deaths</th>
<th>Matchs</th>
</tr>

<?php foreach($joueurs as $j): ?>

<tr>

<td><?= e($j['pseudo']) ?></td>
<td><?= e($j['score']) ?></td>
<td><?= e($j['kills']) ?></td>
<td><?= e($j['deaths']) ?></td>
<td><?= e($j['matchs']) ?></td>

</tr>

<?php endforeach; ?>

</table>

</div>

<?php endif; ?>

<?php if($page === 'joueurs'): ?>

<div class="card">

<h2>Liste des joueurs</h2>

<table>

<tr>
<th>Pseudo</th>
<th>Email</th>
<th>Ville</th>
<th>Rôle</th>
</tr>

<?php foreach($joueurs as $j): ?>

<tr>

<td><?= e($j['pseudo']) ?></td>
<td><?= e($j['email']) ?></td>
<td><?= e($j['ville']) ?></td>
<td><?= e($j['role']) ?></td>

</tr>

<?php endforeach; ?>

</table>

</div>

<?php endif; ?>
<?php if($page === 'rdv'): ?>

<div class="card">

<h2>Rendez-vous</h2>

<?php if(currentUser()): ?>

<form method="POST">

<input type="hidden" name="action" value="create_rdv">

<label>Titre</label>
<input type="text" name="title" required>

<label>Description</label>
<textarea name="description"></textarea>

<label>Date</label>
<input type="date" name="date" required>

<label>Heure</label>
<input type="time" name="time" required>

<button type="submit">
Publier
</button>

</form>

<?php else: ?>

<p>Connecte-toi pour publier un rendez-vous.</p>

<?php endif; ?>

</div>

<div class="card">

<h2>Liste des rendez-vous</h2>

<?php foreach($rdvs as $r): ?>

<div class="card">

<h3><?= e($r['title'] ?? '') ?></h3>

<p><?= e($r['description'] ?? '') ?></p>

<p>
<?= e($r['date'] ?? '') ?>
à
<?= e($r['time'] ?? '') ?>
</p>

<p>
Publié par :
<?= e($r['author'] ?? '') ?>
</p>

<?php if(isAdmin()): ?>

<form method="POST">

<input type="hidden" name="action" value="delete_rdv">
<input type="hidden" name="id" value="<?= e($r['id']) ?>">

<button type="submit">
Supprimer
</button>

</form>

<?php endif; ?>

</div>

<?php endforeach; ?>

</div>

<?php endif; ?>

<?php if($page === 'admin' && isAdmin()): ?>

<div class="card">

<h2>Panel admin</h2>

<h3>Créer une partie</h3>

<form method="POST">

<input type="hidden" name="action" value="create_partie">

<label>Map</label>
<input type="text" name="map" required>

<label>Mode</label>
<input type="text" name="mode" required>

<label>Nombre de joueurs</label>
<input type="number" name="nb_joueurs" required>

<label>Temps</label>
<input type="number" name="temps" required>

<label>Kills max</label>
<input type="number" name="kills_max" required>

<button type="submit">
Créer la partie
</button>

</form>

</div>

<div class="card">

<h2>Parties</h2>

<table>

<tr>
<th>Map</th>
<th>Mode</th>
<th>Joueurs</th>
<th>Temps</th>
<th>Kills</th>
<th>Statut</th>
</tr>

<?php foreach($parties as $p): ?>

<tr>

<td><?= e($p['map']) ?></td>
<td><?= e($p['mode']) ?></td>
<td><?= e($p['nbJoueurs']) ?></td>
<td><?= e($p['temps']) ?></td>
<td><?= e($p['kills']) ?></td>
<td><?= e($p['statut']) ?></td>

</tr>

<?php endforeach; ?>

</table>

</div>

<div class="card">

<h2>Gestion des joueurs</h2>

<table>

<tr>
<th>Pseudo</th>
<th>Email</th>
<th>Ville</th>
<th>Rôle</th>
<th>Action</th>
</tr>

<?php foreach($joueurs as $j): ?>

<tr>

<td><?= e($j['pseudo']) ?></td>
<td><?= e($j['email']) ?></td>
<td><?= e($j['ville']) ?></td>
<td><?= e($j['role']) ?></td>

<td>

<form method="POST">

<input type="hidden" name="action" value="delete_joueur">
<input type="hidden" name="id" value="<?= e($j['id']) ?>">

<button type="submit">
Supprimer
</button>

</form>

</td>

</tr>

<?php endforeach; ?>

</table>

</div>

<?php endif; ?>

<?php if($page === 'admin' && !isAdmin()): ?>

<div class="card">

<h2>Accès refusé</h2>

<p>Cette page est réservée aux administrateurs.</p>

</div>

<?php endif; ?>

</div>

</body>
</html>