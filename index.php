<?php
session_start();
require_once __DIR__ . '/config.php';

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
    $stmt = $pdo->prepare("SELECT id, pseudo, email, ville, role, score, kills, deaths, matchs, createAt, keybinds FROM joueurs WHERE pseudo = ?");
    $stmt->execute([$_SESSION['user']['pseudo']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) $_SESSION['user'] = $user;
}

function getJoueurs(PDO $pdo, $includeAdmins = false) {
    if ($includeAdmins) {
        $stmt = $pdo->query("SELECT id, pseudo, email, ville, role, score, kills, deaths, matchs, createAt, keybinds FROM joueurs ORDER BY score DESC, pseudo ASC");
    } else {
        $stmt = $pdo->query("SELECT id, pseudo, email, ville, role, score, kills, deaths, matchs, createAt, keybinds FROM joueurs WHERE role <> 'admin' ORDER BY score DESC, pseudo ASC");
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRDV(PDO $pdo) {
    $stmt = $pdo->query("SELECT * FROM rendezvous ORDER BY date ASC, time ASC, createAt DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getParties(PDO $pdo) {
    $stmt = $pdo->query("SELECT id, map, mode_jeu AS mode, nb_joueurs AS nbJoueurs, temps, kills_max AS kills, statut, createdAt FROM parties ORDER BY createdAt DESC LIMIT 50");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ====================== AVANCER LE VAINQUEUR ======================
function avancerVainqueur($pdo, $match_id) {
    // Récupérer les infos du match terminé
    $stmt = $pdo->prepare("SELECT * FROM tournoi_matches WHERE id = ?");
    $stmt->execute([$match_id]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$match || $match['round'] >= 3) return; // Pas de match après la finale

    $tournoi_id = $match['tournoi_id'];
    $round_actuel = $match['round'];
    $next_round = $round_actuel + 1;

    // Récupérer tous les matchs du round actuel
    $stmt = $pdo->prepare("SELECT * FROM tournoi_matches WHERE tournoi_id = ? AND round = ? ORDER BY match_number");
    $stmt->execute([$tournoi_id, $round_actuel]);
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Vérifier si tous les matchs du round actuel sont terminés
    $all_finished = true;
    foreach ($matches as $m) {
        if ($m['statut'] !== 'finished') {
            $all_finished = false;
            break;
        }
    }

    if (!$all_finished) return; // Pas encore tous les matchs terminés

    // Créer les matchs du round suivant
    if ($next_round === 2) { // Demi-finales
        $stmt = $pdo->prepare("INSERT INTO tournoi_matches 
            (tournoi_id, round, match_number, player1, player2, statut) 
            VALUES (?, 2, 1, ?, ?, 'pending')");
        $stmt->execute([$tournoi_id, $matches[0]['winner'], $matches[1]['winner']]);

        $stmt = $pdo->prepare("INSERT INTO tournoi_matches 
            (tournoi_id, round, match_number, player1, player2, statut) 
            VALUES (?, 2, 2, ?, ?, 'pending')");
        $stmt->execute([$tournoi_id, $matches[2]['winner'], $matches[3]['winner']]);
    } 
    elseif ($next_round === 3) { // Finale
        $stmt = $pdo->prepare("INSERT INTO tournoi_matches 
            (tournoi_id, round, match_number, player1, player2, statut) 
            VALUES (?, 3, 1, ?, ?, 'pending')");
        $stmt->execute([$tournoi_id, $matches[0]['winner'], $matches[1]['winner']]);
    }

    // Mettre à jour le round actuel du tournoi
    $pdo->prepare("UPDATE tournois SET round_actuel = ? WHERE id = ?")
         ->execute([$next_round, $tournoi_id]);
}

$validPages = ['accueil','classement','rdv','joueurs','touches','profil','admin','tournoi','login','register'];
$page = $_GET['page'] ?? 'accueil';
if (!in_array($page, $validPages, true)) $page = 'accueil';
$rdvTab = $_GET['tab'] ?? 'publier';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
                // ==================== CRÉER TOURNOI ====================
        if ($action === 'creer_tournoi') {
            if (!isAdmin()) throw new Exception('Action réservée à l’admin.');

            // Création du tournoi
            $stmt = $pdo->prepare("INSERT INTO tournois (nom) VALUES (?)");
            $stmt->execute(['Championnat Inter-Villes']);
            $tournoi_id = $pdo->lastInsertId();

            // Récupération des 8 meilleurs joueurs (non admin)
            $joueurs = getJoueurs($pdo, false);
            $top8 = array_slice($joueurs, 0, 8);
            $scrpt = $pdo->prepare("UPDATE joueurs SET 
                score = 0, 
                kills = 0, 
                deaths = 0, 
                matchs = 0 
                WHERE role != 'admin'");
            $scrpt->execute();

            if (count($top8) < 8) {
                throw new Exception('Pas assez de joueurs inscrits (minimum 8 requis).');
            }

            // Création des 4 matchs de quarts de finale
            $match_number = 1;
            for ($i = 0; $i < 8; $i += 2) {
                $stmt = $pdo->prepare("INSERT INTO tournoi_matches 
                    (tournoi_id, round, match_number, player1, player2, statut) 
                    VALUES (?, 1, ?, ?, ?, 'pending')");
                $stmt->execute([
                    $tournoi_id, 
                    $match_number++,
                    $top8[$i]['pseudo'],
                    $top8[$i+1]['pseudo']
                ]);
            }

            flash('success', 'Tournoi créé avec succès !');
            redirectTo('admin');
        }

        // ==================== TERMINER TOURNOI ====================
        if ($action === 'terminer_tournoi') {
            if (!isAdmin()) throw new Exception('Action réservée à l’admin.');
            
            $pdo->prepare("UPDATE tournois SET statut = 'terminé' WHERE statut = 'en_cours'")->execute();
            flash('success', 'Tournoi terminé avec succès.');
            redirectTo('admin');
        }

        // ==================== DÉCLARER GAGNANT ====================
        if ($action === 'declarer_gagnant') {
            if (!isAdmin()) throw new Exception('Action réservée à l’admin.');

            $match_id = (int)($_POST['match_id'] ?? 0);
            $winner   = trim($_POST['winner'] ?? '');

            if ($match_id <= 0 || empty($winner)) {
                throw new Exception('Données invalides.');
            }

            // Mise à jour du match
            $stmt = $pdo->prepare("UPDATE tournoi_matches 
                                  SET winner = ?, statut = 'finished' 
                                  WHERE id = ?");
            $stmt->execute([$winner, $match_id]);

            // Avancer automatiquement vers le prochain round
            avancerVainqueur($pdo, $match_id);

            flash('success', 'Gagnant enregistré ! Le match suivant a été créé.');
            redirectTo('tournoi');
        }

        if ($action === 'register') {
            $pseudo = trim($_POST['pseudo'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $password2 = $_POST['password_confirm'] ?? '';
            $ville = trim($_POST['ville'] ?? 'Marseille');
            $role = isAdmin() ? trim($_POST['role'] ?? 'joueur') : 'joueur';

            if ($pseudo === '' || $email === '' || $password === '') throw new Exception('Champs manquants.');
            if ($password !== $password2) throw new Exception('Les mots de passe ne correspondent pas.');
            if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $pseudo)) throw new Exception('Pseudo invalide.');
            if (!in_array($role, ['joueur','admin'], true)) $role = 'joueur';

             $stmt = $pdo->prepare("SELECT id FROM joueurs WHERE pseudo = ? OR email = ?");
            $stmt->execute([$pseudo, $email]);
            if ($stmt->fetch()) throw new Exception('Pseudo ou email déjà utilisé.');

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO joueurs (pseudo, email, password, ville, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$pseudo, $email, $hash, $ville, $role]);

            if (isAdmin()) {
                flash('success', 'Joueur ajouté avec succès.');
                redirectTo('admin');
            }

            $stmt = $pdo->prepare("SELECT id, pseudo, email, ville, role, score, kills, deaths, matchs, createAt, keybinds FROM joueurs WHERE pseudo = ?");
            $stmt->execute([$pseudo]);
            $_SESSION['user'] = $stmt->fetch(PDO::FETCH_ASSOC);
            flash('success', 'Compte créé avec succès.');
            redirectTo('profil');
        }

        if ($action === 'login') {
            $pseudo = trim($_POST['pseudo'] ?? '');
            $password = $_POST['password'] ?? '';
            $stmt = $pdo->prepare("SELECT * FROM joueurs WHERE pseudo = ?");
            $stmt->execute([$pseudo]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user || !password_verify($password, $user['password'])) throw new Exception('Pseudo ou mot de passe incorrect.');
            unset($user['password']);
            $_SESSION['user'] = $user;
            flash('success', 'Connexion réussie.');
            redirectTo('profil');
        }

        if ($action === 'logout') {
            $_SESSION = [];
            session_destroy();
            header('Location: index.php?page=accueil');
            exit;
        }

        if ($action === 'add_rdv') {
            if (!currentUser()) throw new Exception('Connecte-toi pour publier un rendez-vous.');
            $mode = trim($_POST['mode'] ?? '');
            $date = $_POST['date'] ?? '';
            $time = $_POST['time'] ?? '';
            $message = trim($_POST['message'] ?? '');
            if ($mode === '' || $date === '' || $time === '' || $message === '') throw new Exception('Complète tous les champs du rendez-vous.');
            $stmt = $pdo->prepare("INSERT INTO rendezvous (pseudo, ville, mode, date, time, message) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user']['pseudo'], $_SESSION['user']['ville'], $mode, $date, $time, $message]);
            flash('success', 'Rendez-vous publié.');
            redirectTo('rdv', '&tab=liste');
        }

        if ($action === 'delete_rdv') {
            if (!currentUser()) throw new Exception('Non connecté.');
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT pseudo FROM rendezvous WHERE id = ?");
            $stmt->execute([$id]);
            $rdv = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$rdv) throw new Exception('Rendez-vous introuvable.');
            if (!isAdmin() && $rdv['pseudo'] !== $_SESSION['user']['pseudo']) throw new Exception('Tu peux supprimer seulement tes rendez-vous.');
            $stmt = $pdo->prepare("DELETE FROM rendezvous WHERE id = ?");
            $stmt->execute([$id]);
            flash('success', 'Rendez-vous supprimé.');
            redirectTo('rdv', '&tab=liste');
        }

        if ($action === 'clear_rdv') {
            if (!isAdmin()) throw new Exception('Action réservée à l’admin.');
            $pdo->exec("DELETE FROM rendezvous");
            flash('success', 'Tous les rendez-vous ont été supprimés.');
            redirectTo('rdv', '&tab=liste');
        }

        if ($action === 'save_keys') {
            if (!currentUser()) throw new Exception('Connecte-toi pour sauvegarder tes touches.');
            $keybinds = [
                'avancer' => strtoupper(trim($_POST['avancer'] ?? 'Z')),
                'reculer' => strtoupper(trim($_POST['reculer'] ?? 'S')),
                'gauche' => strtoupper(trim($_POST['gauche'] ?? 'Q')),
                'droite' => strtoupper(trim($_POST['droite'] ?? 'D')),
            ];
            if (count($keybinds) !== count(array_unique($keybinds))) throw new Exception('Une touche ne peut pas être utilisée deux fois.');
            $stmt = $pdo->prepare("UPDATE joueurs SET keybinds = ? WHERE pseudo = ?");
            $stmt->execute([json_encode($keybinds, JSON_UNESCAPED_UNICODE), $_SESSION['user']['pseudo']]);
            refreshSession($pdo);
            flash('success', 'Touches sauvegardées.');
            redirectTo('touches');
        }

        if ($action === 'getCFG') 
        {

            if (!isset($_SESSION['user'])) {
                header("HTTP/1.1 403 Forbidden");
                exit;
            }

            $pseudo = $_SESSION['user']['pseudo'];

            $stmt = $pdo->prepare("SELECT keybinds FROM joueurs WHERE pseudo = ?");
            $stmt->execute([$pseudo]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $defaults = [
                'avancer' => 'Z',
                'reculer' => 'S',
                'gauche' => 'Q',
                'droite' => 'D',
                'sauter' => 'SPACE',
                'tirer' => 'MOUSE1',
                'viser' => 'MOUSE2'
            ];

            $keybinds = $defaults;

            if ($row && !empty($row['keybinds'])) {

                $decoded = json_decode($row['keybinds'], true);

                if (is_array($decoded)) {
                    $keybinds = array_merge($defaults, $decoded);
                }
            }

            header('Content-Type: text/plain; charset=utf-8');

            echo "unbindall\n\n";

            echo 'bind "' . $keybinds['avancer'] . "\" \"+forward\"\n";
            echo 'bind "' . $keybinds['reculer'] . "\" \"+back\"\n";
            echo 'bind "' . $keybinds['gauche'] . "\" \"+moveleft\"\n";
            echo 'bind "' . $keybinds['droite'] . "\" \"+moveright\"\n";
            echo 'bind "' . $keybinds['sauter'] . "\" \"+moveup\"\n\n";
            echo 'bind "' . $keybinds['tirer'] . "\" \"+attack\"\n";
            echo 'bind "' . $keybinds['viser'] . "\" \"+zoom\"\n";

            exit;
        }
        
        if ($action === 'login_linux')
         {
            // Vérification des paramètres
            if (empty($_POST['pseudo'])) {
                header("HTTP/1.1 400 Bad Request");
                echo "Pas de pseudo";
                exit;
            }

            $pseudo = trim($_POST['pseudo']);

            // Vérifier si le joueur existe
            $stmt = $pdo->prepare("SELECT pseudo FROM joueurs WHERE pseudo = ?");
            $stmt->execute([$pseudo]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Si le joueur n'existe pas, la requete est refusee
            if (!$user) {
                header("HTTP/1.1 400 Bad Request");
                echo "Joueur inexistant";
                exit;
            }

            // Création de la session
            $_SESSION['user'] = [
                'pseudo' => $pseudo,
                'login_time' => time()
            ];

            // Réponse simple pour le script shell
            header("Content-Type: text/plain; charset=utf-8");
            echo "OK";
            exit;
        }


        if ($action === 'lancer_partie') {
            if (!isAdmin()) throw new Exception('Action réservée à l’admin.');
            $stmt = $pdo->prepare("INSERT INTO parties (map, mode_jeu, nb_joueurs, temps, kills_max, statut) VALUES (?, ?, ?, ?, ?, 'En cours')");
            $stmt->execute([
                trim($_POST['adminMap'] ?? ''),
                trim($_POST['adminMode'] ?? ''),
                (int)($_POST['adminJoueurs'] ?? 0),
                (int)($_POST['adminTemps'] ?? 0),
                (int)($_POST['adminKills'] ?? 0),
            ]);
            $adminMap = trim($_POST['adminMap'] ?? '');
            $adminMode = trim($_POST['adminMode'] ?? '');
            $adminJoueurs = trim($_POST['adminJoueurs'] ?? '');
            $adminTemps = trim($_POST['adminTemps'] ?? '5');
            $adminKills = trim($_POST['adminKills'] ?? '5');

            $ch = curl_init('http://192.168.1.5:8000/start');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'adminMap' => $adminMap,
                'adminMode' => $adminMode,
                'adminTemps' => $adminTemps,
                'adminKills' => $adminKills,
                'adminJoueurs' => $adminJoueurs
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($response, true);

            if (isset($result['status']) && $result['status'] === 'success'){
                $stmt = $pdo->prepare("INSERT INTO parties (map, mode_jeu, nb_joueurs, temps, kills_max, statut) VALUES (?, ?, ?, ?, ?, 'En cours')");
                $stmt->execute([
                    trim($_POST['adminMap'] ?? ''),
                    trim($_POST['adminMode'] ?? ''),
                    (int)($_POST['adminJoueurs'] ?? 0),
                    (int)($_POST['adminTemps'] ?? 0),
                    (int)($_POST['adminKills'] ?? 0),
                ]);
                flash('success', 'Partie lancée.');
                redirectTo('admin');
            }
            else if (isset($result['status']) && $result['status'] === 'erreur')
                {
                    flash('error', 'Partie non lancée.');
                    redirectTo('admin');
                }
        }

        if ($action === 'delete_partie') {
            if (!isAdmin()) throw new Exception('Action réservée à l’admin.');
            $ch = curl_init('http://192.168.1.5:8000/stop');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($response, true);
            if (isset($result['status']) && $result['status'] === 'erreur'){
                flash('error', 'Partie non supprimée.');
                redirectTo('admin');
            }
            else {
                $stmt = $pdo->prepare("DELETE FROM parties WHERE id = ?");
                $stmt->execute([(int)($_POST['id'] ?? 0)]);
                foreach($result as $player){
                    $pseudo = $player[0];
                    $score = intval($player[1]);
                    if ($score != 0){
                    $scrpt = $pdo->prepare("UPDATE joueurs SET score = score + ?, kills = kills + ?, matchs = matchs + 1 WHERE pseudo = ? ");
                    $scrpt->execute([$score,$score,$pseudo]);
                    }
                    else {
                        $scrpt = $pdo->prepare("UPDATE joueurs SET matchs = matchs + 1 WHERE pseudo = ? ");
                        $scrpt->execute([$pseudo]);
                    }
                }
                flash('success', 'Partie supprimée.');
                redirectTo('admin');
            }
        }

        if ($action === 'edit_joueur') {
            if (!isAdmin()) throw new Exception('Action réservée à l’admin.');
            $pseudo = trim($_POST['pseudo'] ?? '');
            $ville = trim($_POST['ville'] ?? 'Marseille');
            $role = trim($_POST['role'] ?? 'joueur');
            $password = $_POST['password'] ?? '';
            if (!in_array($role, ['joueur','admin'], true)) $role = 'joueur';
            if ($password !== '') {
                $stmt = $pdo->prepare("UPDATE joueurs SET ville = ?, role = ?, password = ? WHERE pseudo = ?");
                $stmt->execute([$ville, $role, password_hash($password, PASSWORD_DEFAULT), $pseudo]);
            } else {
                $stmt = $pdo->prepare("UPDATE joueurs SET ville = ?, role = ? WHERE pseudo = ?");
                $stmt->execute([$ville, $role, $pseudo]);
            }
            if (currentUser() && $_SESSION['user']['pseudo'] === $pseudo) refreshSession($pdo);
            flash('success', 'Joueur modifié.');
            redirectTo('admin');
        }

        if ($action === 'delete_joueur') {
            if (!isAdmin()) throw new Exception('Action réservée à l’admin.');
            $pseudo = trim($_POST['pseudo'] ?? '');
            if (currentUser() && $pseudo === $_SESSION['user']['pseudo']) throw new Exception('Impossible de te supprimer toi-même.');
            $stmt = $pdo->prepare("DELETE FROM joueurs WHERE pseudo = ?");
            $stmt->execute([$pseudo]);
            flash('success', 'Joueur supprimé.');
            redirectTo('admin');
        }

    } catch (Throwable $e) {
        flash('error', $e->getMessage());
        redirectTo($page, $page === 'rdv' ? '&tab=' . urlencode($rdvTab) : '');
    }
}

$user = currentUser();
$joueursPublics = getJoueurs($pdo, false);
$joueursAll = getJoueurs($pdo, true);
$rdvs = getRDV($pdo);
$parties = getParties($pdo);
$keyDefaults = ['avancer' => 'Z', 'reculer' => 'S', 'gauche' => 'Q', 'droite' => 'D'];
$userKeys = $keyDefaults;
if ($user && !empty($user['keybinds'])) {
    $decoded = json_decode($user['keybinds'], true);
    if (is_array($decoded)) $userKeys = array_merge($keyDefaults, $decoded);
}
$keys = range('A', 'Z');
$villes = ['Paris','Dieppe','Rouen','Lille','Marseille'];
$modes = ['Free For All','Team Deathmatch','Capture The Flag','Duel','Autre'];
$maps = ['oa_dm3' => 'oa_dm3 — Duels', 'am_lavactf' => 'am_lavactf — CTF avec lave', 'oa_ctf1' => 'oa_ctf1 — CTF classique', 'oa_minia' => 'oa_minia — TDM compact'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpenArena - PHP</title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
    <ul>
        <li><a href="index.php?page=accueil">Accueil</a></li>
        <li><a href="index.php?page=classement">Classement</a></li>
        <li><a href="index.php?page=rdv&tab=publier">Rendez-vous</a></li>
        <li><a href="index.php?page=joueurs">Joueurs</a></li>
        <li><a href="index.php?page=touches">Touches</a></li>
        <?php if ($user): ?>
            <li class="nav-separator"></li>
            <li><a class="nav-user" href="index.php?page=profil">👤 <?= e($user['pseudo']) ?></a></li>
            <?php if (isAdmin()): ?>
                <li><a href="index.php?page=admin">Admin</a></li>
                <li><a href="index.php?page=tournoi">Tournoi</a></li>
            <?php endif; ?>
            <li>
                <form method="post" class="inline-form"><input type="hidden" name="action" value="logout"><button class="nav-button nav-logout">Déconnexion</button></form>
            </li>
        <?php else: ?>
            <li class="nav-separator"></li>
            <li><a class="nav-btn-login" href="index.php?page=login">Connexion</a></li>
            <li><a class="nav-btn-register" href="index.php?page=register">Inscription</a></li>
        <?php endif; ?>
    </ul>
</nav>

<main>
<?php showFlash(); ?>

<?php if ($page === 'accueil'): ?>
<section class="hero">
    <h1>🎮 OpenArena</h1>
    <p class="subtitle">Championnat inter-villes — FPS compétitif, rapide et 100% gratuit</p>
    <p class="ville-list">🗼 Paris • ⚓ Dieppe • 🏰 Rouen • 🏭 Lille • 🌊 Marseille</p>
    <div class="hero-btns">
        <?php if ($user): ?>
            <a class="btn-primary" href="index.php?page=rdv&tab=publier">📅 Planifier une partie</a>
            <a class="btn-secondary" href="index.php?page=profil">👤 Mon profil</a>
        <?php else: ?>
            <a class="btn-primary" href="index.php?page=register">🎮 S’inscrire</a>
            <a class="btn-outline" href="index.php?page=login">🔑 Se connecter</a>
        <?php endif; ?>
    </div>
</section>
<section>
    <h2>📈 En chiffres</h2>
    <div class="stat-grid">
        <div class="stat-card"><div class="stat-number orange"><?= count($joueursPublics) ?></div><div class="stat-label">Joueurs</div></div>
        <div class="stat-card"><div class="stat-number cyan"><?= count($rdvs) ?></div><div class="stat-label">Rendez-vous</div></div>
        <div class="stat-card"><div class="stat-number yellow">5</div><div class="stat-label">Villes</div></div>
        <div class="stat-card"><div class="stat-number blue">4</div><div class="stat-label">Modes</div></div>
    </div>
</section>
<section>
    <h2>🎮 Présentation</h2>
    <div class="card"><p>OpenArena est un FPS <strong>gratuit et open source</strong> basé sur Quake III Arena.</p></div>
    <img src="images/openarena1.jpg" alt="OpenArena">
</section>
<?php endif; ?>

<?php if ($page === 'login'): ?>
<section class="small-section">
    <h2>🔑 Connexion</h2>
    <div class="card">
        <form method="post">
            <input type="hidden" name="action" value="login">
            <div class="form-group"><label>Pseudo</label><input name="pseudo" required></div>
            <div class="form-group"><label>Mot de passe</label><input type="password" name="password" required></div>
            <button class="btn-primary full">Se connecter</button>
        </form>
        <p class="muted center">Pas de compte ? <a href="index.php?page=register">S’inscrire</a></p>
    </div>
</section>
<?php endif; ?>

<?php if ($page === 'register'): ?>
<section class="small-section">
    <h2>📝 Créer un compte</h2>
    <div class="card">
        <form method="post">
            <input type="hidden" name="action" value="register">
            <div class="form-group"><label>Pseudo</label><input name="pseudo" required minlength="3" maxlength="30" pattern="[a-zA-Z0-9_]+"></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
            <div class="form-group"><label>Mot de passe</label><input type="password" name="password" required minlength="6"></div>
            <div class="form-group"><label>Confirmer</label><input type="password" name="password_confirm" required minlength="6"></div>
            <div class="form-group"><label>Ville</label><select name="ville"><?php foreach($villes as $v): ?><option value="<?= e($v) ?>"><?= e($v) ?></option><?php endforeach; ?></select></div>
            <button class="btn-primary full">Créer le compte</button>
        </form>
    </div>
</section>
<?php endif; ?>

<?php if ($page === 'classement'): ?>
<section>
    <h2>🏆 Classement</h2>
    <table><thead><tr><th>#</th><th>Joueur</th><th>Ville</th><th>Kills</th><th>K/D</th><th>Score</th></tr></thead><tbody>
    <?php foreach($joueursPublics as $i => $j): $kills=(int)$j['kills']; $deaths=(int)$j['deaths']; $kd=$deaths>0?number_format($kills/$deaths,2):$kills; ?>
        <tr><td>#<?= $i+1 ?></td><td><?= e($j['pseudo']) ?></td><td><?= e($j['ville']) ?></td><td><?= $kills ?></td><td><?= $kd ?></td><td><?= (int)$j['score'] ?></td></tr>
    <?php endforeach; ?>
    </tbody></table>
</section>
<?php endif; ?>

<?php if ($page === 'joueurs'): ?>
<section>
    <h2>👥 Joueurs</h2>
    <table><thead><tr><th>Joueur</th><th>Ville</th><th>Score</th><th>Matchs</th><th>Inscrit</th></tr></thead><tbody>
    <?php foreach($joueursPublics as $j): ?>
        <tr><td><?= e($j['pseudo']) ?></td><td><?= e($j['ville']) ?></td><td><?= (int)$j['score'] ?></td><td><?= (int)$j['matchs'] ?></td><td><?= e(substr($j['createAt'],0,10)) ?></td></tr>
    <?php endforeach; ?>
    </tbody></table>
</section>
<?php endif; ?>

<?php if ($page === 'rdv'): ?>
<section>
    <h2>📅 Rendez-vous</h2>
    <p>Planifie une partie avec d’autres joueurs !</p>
    <div class="tabs">
        <a class="tab-btn <?= $rdvTab==='publier'?'active':'' ?>" href="index.php?page=rdv&tab=publier">📝 Publier</a>
        <a class="tab-btn <?= $rdvTab==='liste'?'active':'' ?>" href="index.php?page=rdv&tab=liste">📋 Liste</a>
    </div>

    <?php if ($rdvTab === 'publier'): ?>
        <?php if (!$user): ?><div class="alert alert-info">Connecte-toi pour publier.</div><?php else: ?>
        <div class="rdv-form">
            <form method="post">
                <input type="hidden" name="action" value="add_rdv">
                <div class="form-row"><div class="form-group"><label>Pseudo</label><input value="<?= e($user['pseudo']) ?>" readonly></div><div class="form-group"><label>Mode</label><select name="mode"><?php foreach($modes as $m): ?><option value="<?= e($m) ?>"><?= e($m) ?></option><?php endforeach; ?></select></div></div>
                <div class="form-row"><div class="form-group"><label>Date</label><input type="date" name="date" required></div><div class="form-group"><label>Heure</label><input type="time" name="time" required></div></div>
                <div class="form-group"><label>Message</label><textarea name="message" maxlength="300" required></textarea></div>
                <button class="btn-primary">Publier</button>
            </form>
        </div>
        <?php endif; ?>
    <?php else: ?>
        <?php if (isAdmin() && count($rdvs)): ?>
            <form method="post" class="inline-form margin-bottom"><input type="hidden" name="action" value="clear_rdv"><button class="btn-danger">🗑️ Supprimer tout</button></form>
        <?php endif; ?>
        <div class="counter"><?= count($rdvs) ?> rendez-vous</div>
        <div class="rdv-list">
        <?php if (!count($rdvs)): ?><div class="empty-state"><div class="icon">📭</div><p>Aucun rendez-vous.</p></div><?php endif; ?>
        <?php foreach($rdvs as $r): $canDelete = $user && (isAdmin() || $user['pseudo'] === $r['pseudo']); ?>
            <div class="rdv-item">
                <div class="rdv-header"><span class="rdv-pseudo">🎮 <?= e($r['pseudo']) ?></span><span class="rdv-date-time">📅 <?= e($r['date']) ?> à <?= e($r['time']) ?></span></div>
                <span class="rdv-mode">🎯 <?= e($r['mode']) ?></span><span class="rdv-mode">🏙️ <?= e($r['ville']) ?></span>
                <div class="rdv-message"><?= e($r['message']) ?></div>
                <?php if ($canDelete): ?><form method="post" class="inline-form rdv-actions"><input type="hidden" name="action" value="delete_rdv"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn-danger">Supprimer</button></form><?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php if ($page === 'touches'): ?>
<section>
    <h2>⌨️ Configuration des touches</h2>
    <?php if (!$user): ?><div class="alert alert-info">Connecte-toi pour configurer tes touches.</div><?php else: ?>
    <div class="card">
        <form method="post">
            <input type="hidden" name="action" value="save_keys">
            <div class="form-row">
                <?php foreach(['avancer'=>'⬆️ Avancer','reculer'=>'⬇️ Reculer','gauche'=>'⬅️ Gauche','droite'=>'➡️ Droite'] as $name=>$label): ?>
                <div class="form-group"><label><?= $label ?></label><select name="<?= $name ?>"><?php foreach($keys as $k): ?><option value="<?= $k ?>" <?= $userKeys[$name]===$k?'selected':'' ?>><?= $k ?></option><?php endforeach; ?></select></div>
                <?php endforeach; ?>
            </div>
            <button class="btn-primary">💾 Sauvegarder</button>
        </form>
    </div>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php if ($page === 'profil'): ?>
<section>
    <h2>👤 Profil</h2>
    <?php if (!$user): ?><div class="alert alert-info">Connecte-toi.</div><?php else: ?>
    <div class="card profile-header"><div class="profile-avatar"><?= e(strtoupper(substr($user['pseudo'],0,1))) ?></div><div class="profile-info"><h3><?= e($user['pseudo']) ?></h3><p><?= e($user['email']) ?></p><p>Ville : <?= e($user['ville']) ?></p><p>Rôle : <?= e($user['role']) ?></p><p>Touches : <?= e(json_encode($userKeys, JSON_UNESCAPED_UNICODE)) ?></p></div></div>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php if ($page === 'admin'): ?>
<section>
    <h2>⚙️ Panel Admin</h2>
    <?php if (!isAdmin()): ?><div class="alert alert-error">Accès réservé aux admins.</div><?php else: ?>
    <div class="card">
        <h3>🎮 Configurer une partie</h3>
        <form method="POST">
            <input type="hidden" name="action" value="lancer_partie">
            <div class="form-row">
                <div class="form-group">
                    <label>Map</label>
                    <select name="adminMap">
                        <?php 
                            foreach($maps as $value=>$label): 
                        ?>
                        <option value="<?= e($value) ?>">
                                <?= e($label) ?>
                        </option>
                        <?php 
                            endforeach; 
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Mode</label>
                    <select name="adminMode">
                        <?php 
                            foreach(array_slice($modes,0,4) as $m): 
                        ?>
                        <option value="<?= e($m) ?>"><?= e($m) ?>
                        </option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Joueurs max</label><input type="number" name="adminJoueurs" value="16" min="2" max="32">
                </div>
                <div class="form-group">
                    <label>Temps</label><input type="number" name="adminTemps" value="15" min="1" max="60">
                </div>
            </div>
            <div class="form-group">
                <label>Kills max</label><input type="number" name="adminKills" value="30" min="1" max="100">
            </div>
            <button class="btn-primary full">Lancer la partie</button>
        </form>
        <!-- ====================== GESTION TOURNOI ====================== -->
<div class="card">
    <h3>🏆 Gestion du Tournoi (8 Joueurs)</h3>
    
    <?php 
    // Récupération du tournoi en cours
    $stmt = $pdo->query("SELECT * FROM tournois WHERE statut = 'en_cours' ORDER BY id DESC LIMIT 1");
    $tournoi = $stmt->fetch(PDO::FETCH_ASSOC);
    ?>

    <?php if (!$tournoi): ?>
        <!-- Aucun tournoi en cours -->
        <form method="POST">
            <input type="hidden" name="action" value="creer_tournoi">
            <div class="form-row">
                <div class="form-group">
                    <label>Map</label>
                    <select name="adminMap">
                        <?php 
                            foreach($maps as $value=>$label): 
                        ?>
                        <option value="<?= e($value) ?>">
                                <?= e($label) ?>
                        </option>
                        <?php 
                            endforeach; 
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Mode</label>
                    <select name="adminMode">
                        <?php 
                            foreach(array_slice($modes,0,4) as $m): 
                        ?>
                        <option value="<?= e($m) ?>"><?= e($m) ?>
                        </option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Mode</label>
                    <select name="adminMode">
                        <?php 
                            foreach(array_slice($modes,0,4) as $m): 
                        ?>
                        <option value="<?= e($m) ?>"><?= e($m) ?>
                        </option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Mode</label>
                    <select name="adminMode">
                        <?php 
                            foreach(array_slice($modes,0,4) as $m): 
                        ?>
                        <option value="<?= e($m) ?>"><?= e($m) ?>
                        </option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Map</label>
                    <select name="adminMap">
                        <?php 
                            foreach($maps as $value=>$label): 
                        ?>
                        <option value="<?= e($value) ?>">
                                <?= e($label) ?>
                        </option>
                        <?php 
                            endforeach; 
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Mode</label>
                    <select name="adminMode">
                        <?php 
                            foreach(array_slice($modes,0,4) as $m): 
                        ?>
                        <option value="<?= e($m) ?>"><?= e($m) ?>
                        </option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Mode</label>
                    <select name="adminMode">
                        <?php 
                            foreach(array_slice($modes,0,4) as $m): 
                        ?>
                        <option value="<?= e($m) ?>"><?= e($m) ?>
                        </option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Mode</label>
                    <select name="adminMode">
                        <?php 
                            foreach(array_slice($modes,0,4) as $m): 
                        ?>
                        <option value="<?= e($m) ?>"><?= e($m) ?>
                        </option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn-primary" style="padding:12px 25px;">
                🏆 Créer un nouveau tournoi (8 joueurs)
            </button>
        </form>
    <?php else: ?>
        <!-- Tournoi en cours -->
        <p><strong>Tournoi actif :</strong> <?= e($tournoi['nom']) ?> 
           (Round <?= $tournoi['round_actuel'] ?>)</p>
        
        <a href="index.php?page=tournoi" class="btn-primary" style="margin-right:10px;">
            Voir la Grille du Tournoi
        </a>
        
        <!-- Bouton Terminer le Tournoi -->
        <form method="POST" class="inline-form" style="display:inline;" 
              onsubmit="return confirm('Terminer définitivement le tournoi ?')">
            <input type="hidden" name="action" value="terminer_tournoi">
            <button type="submit" class="btn-danger">
                Terminer le Tournoi
            </button>
        </form>
    <?php endif; ?>
</div>
    </div>

    <div class="card"><h3>📋 Parties</h3><table><thead><tr><th>Map</th><th>Mode</th><th>Joueurs</th><th>Temps</th><th>Kills</th><th>Statut</th><th>Action</th></tr></thead><tbody><?php foreach($parties as $p): ?><tr><td><?= e($p['map']) ?></td><td><?= e($p['mode']) ?></td><td><?= (int)$p['nbJoueurs'] ?></td><td><?= (int)$p['temps'] ?> min</td><td><?= (int)$p['kills'] ?></td><td><?= e($p['statut']) ?></td><td><form method="POST" class="inline-form"><input type="hidden" name="action" value="delete_partie"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn-danger">Supprimer</button></form></td></tr><?php endforeach; ?></tbody></table></div>

    <div class="card"><h3>👥 Gestion des joueurs</h3><details><summary class="btn-primary summary-btn">Ajouter un joueur</summary><form method="post" class="admin-form"><input type="hidden" name="action" value="register"><div class="form-row"><div class="form-group"><label>Pseudo</label><input name="pseudo" required></div><div class="form-group"><label>Email</label><input type="email" name="email" required></div></div><div class="form-row"><div class="form-group"><label>Mot de passe</label><input type="password" name="password" required></div><div class="form-group"><label>Confirmer</label><input type="password" name="password_confirm" required></div></div><div class="form-row"><div class="form-group"><label>Ville</label><select name="ville"><?php foreach($villes as $v): ?><option value="<?= e($v) ?>"><?= e($v) ?></option><?php endforeach; ?></select></div><div class="form-group"><label>Rôle</label><select name="role"><option value="joueur">Joueur</option><option value="admin">Admin</option></select></div></div><button class="btn-primary">Ajouter</button></form></details><table><thead><tr><th>Pseudo</th><th>Email</th><th>Ville</th><th>Rôle</th><th>Touches</th><th>Actions</th></tr></thead><tbody><?php foreach($joueursAll as $j): ?><tr><form method="post"><input type="hidden" name="action" value="edit_joueur"><input type="hidden" name="pseudo" value="<?= e($j['pseudo']) ?>"><td><?= e($j['pseudo']) ?></td><td><?= e($j['email']) ?></td><td><select name="ville"><?php foreach($villes as $v): ?><option value="<?= e($v) ?>" <?= $j['ville']===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select></td><td><select name="role"><option value="joueur" <?= $j['role']==='joueur'?'selected':'' ?>>Joueur</option><option value="admin" <?= $j['role']==='admin'?'selected':'' ?>>Admin</option></select></td><td class="small-text"><?= e($j['keybinds']) ?></td><td><input type="password" name="password" placeholder="Nouveau MDP"><button class="btn-edit">Modifier</button></form><?php if (!$user || $user['pseudo'] !== $j['pseudo']): ?><form method="post" class="inline-form"><input type="hidden" name="action" value="delete_joueur"><input type="hidden" name="pseudo" value="<?= e($j['pseudo']) ?>"><button class="btn-danger">Supprimer</button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php if ($page === 'tournoi'): ?>
<section>
    <h2>🏆 Tournoi Championnat Inter-Villes - Round <?= $tournoi['round_actuel'] ?? 1 ?></h2>

    <?php
    $stmt = $pdo->query("SELECT * FROM tournois WHERE statut = 'en_cours' ORDER BY id DESC LIMIT 1");
    $tournoi = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tournoi) {
        echo "<div class='alert alert-info'>Aucun tournoi en cours.</div>";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM tournoi_matches WHERE tournoi_id = ? ORDER BY round, match_number");
        $stmt->execute([$tournoi['id']]);
        $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <div class="bracket-container">
        
        <?php for($r=1; $r<=3; $r++): ?>
        <div class="round">
            <h3><?= $r===1 ? 'Quarts de Finale' : ($r===2 ? 'Demi-finales' : '🎖️ FINALE') ?></h3>
            
            <?php 
            $roundMatches = array_filter($matches, function($m) use ($r) {
                return $m['round'] == $r;
            });
            foreach($roundMatches as $m): 
            ?>
                <div class="match-box <?= ($m['statut'] === 'finished') ? 'finished' : '' ?>">
                    <div class="player"><?= e($m['player1'] ?? '---') ?></div>
                    <div class="vs">VS</div>
                    <div class="player"><?= e($m['player2'] ?? '---') ?></div>
                    
                    <?php if ($m['statut'] === 'finished'): ?>
                        <div class="winner">✓ Gagnant : <strong><?= e($m['winner']) ?></strong></div>
                    <?php else: ?>
                        <form method="POST" class="winner-form">
                            <input type="hidden" name="action" value="declarer_gagnant">
                            <input type="hidden" name="match_id" value="<?= $m['id'] ?>">
                            <select name="winner" required>
                                <option value="">→ Choisir le gagnant</option>
                                <option value="<?= e($m['player1']) ?>"><?= e($m['player1']) ?></option>
                                <option value="<?= e($m['player2']) ?>"><?= e($m['player2']) ?></option>
                            </select>
                            <button type="submit" class="btn-primary small">Valider Gagnant</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endfor; ?>
    </div>

    <div style="text-align:center; margin-top:40px;">
        <a href="index.php?page=admin" class="btn-primary">← Retour Panel Admin</a>
    </div>

    <?php } ?>
</section>
<?php endif; ?>

</main>

<footer><p>🎮 OpenArena Championship — Version PHP sans JavaScript</p></footer>
</body>
</html>
