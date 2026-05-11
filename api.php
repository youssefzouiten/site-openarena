<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function jsonResponse($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {

    if ($action === 'login') {
        $pseudo = trim($_POST['pseudo'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM joueurs WHERE pseudo = ?");
        $stmt->execute([$pseudo]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            $_SESSION['user'] = $user;
            jsonResponse(['success' => true, 'user' => $user]);
        }

        jsonResponse(['success' => false, 'message' => 'Pseudo ou mot de passe incorrect']);
    }

    elseif ($action === 'getSession') {
        jsonResponse(['success' => true, 'user' => $_SESSION['user'] ?? null]);
    }

    elseif ($action === 'logout') {
        $_SESSION = [];
        session_destroy();
        jsonResponse(['success' => true]);
    }

    elseif ($action === 'getKeybinds') {
        if (!isset($_SESSION['user'])) {
            jsonResponse(['success' => false, 'message' => 'Non connecté']);
        }

        $pseudo = $_SESSION['user']['pseudo'];

        $stmt = $pdo->prepare("SELECT keybinds FROM joueurs WHERE pseudo = ?");
        $stmt->execute([$pseudo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $defaults = [
            'avancer' => 'Z',
            'reculer' => 'S',
            'gauche' => 'Q',
            'droite' => 'D'
        ];

        $keybinds = $defaults;

        if ($row && !empty($row['keybinds'])) {
            $decoded = json_decode($row['keybinds'], true);
            if (is_array($decoded)) {
                $keybinds = array_merge($defaults, $decoded);
            }
        }

        jsonResponse(['success' => true, 'keybinds' => $keybinds]);
    }

    elseif ($action === 'saveKeybinds') {
        if (!isset($_SESSION['user'])) {
            jsonResponse(['success' => false, 'message' => 'Non connecté']);
        }

        $pseudo = $_SESSION['user']['pseudo'];

        $keybinds = [
            'avancer' => strtoupper(trim($_POST['avancer'] ?? 'Z')),
            'reculer' => strtoupper(trim($_POST['reculer'] ?? 'S')),
            'gauche' => strtoupper(trim($_POST['gauche'] ?? 'Q')),
            'droite' => strtoupper(trim($_POST['droite'] ?? 'D'))
        ];

        if (count($keybinds) !== count(array_unique($keybinds))) {
            jsonResponse([
                'success' => false,
                'message' => 'Une touche ne peut pas être utilisée deux fois'
            ]);
        }

        $stmt = $pdo->prepare("UPDATE joueurs SET keybinds = ? WHERE pseudo = ?");
        $stmt->execute([json_encode($keybinds, JSON_UNESCAPED_UNICODE), $pseudo]);

        jsonResponse([
            'success' => true,
            'message' => 'Touches sauvegardées',
            'keybinds' => $keybinds
        ]);
    }

    elseif ($action === 'register') {
        $pseudo = trim($_POST['pseudo'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $ville = trim($_POST['ville'] ?? 'Marseille');
        $role = trim($_POST['role'] ?? 'joueur');

        if ($role === 'admin' && (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin')) {
            $role = 'joueur';
        }

        if ($pseudo === '' || $email === '' || $password === '') {
            jsonResponse(['success' => false, 'message' => 'Champs manquants']);
        }

        $stmt = $pdo->prepare("SELECT id FROM joueurs WHERE pseudo = ? OR email = ?");
        $stmt->execute([$pseudo, $email]);

        if ($stmt->fetch()) {
            jsonResponse(['success' => false, 'message' => 'Pseudo ou email déjà utilisé']);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO joueurs (pseudo, email, password, ville, role)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$pseudo, $email, $hash, $ville, $role]);

        $user = [
            'pseudo' => $pseudo,
            'email' => $email,
            'ville' => $ville,
            'role' => $role
        ];

        if (!isset($_SESSION['user'])) {
            $_SESSION['user'] = $user;
        }

        jsonResponse(['success' => true, 'user' => $user]);
    }

    elseif ($action === 'getJoueurs') {
        $stmt = $pdo->query("
            SELECT id, pseudo, email, ville, role, score, kills, deaths, matchs, createAt, keybinds
            FROM joueurs
            ORDER BY score DESC, pseudo ASC
        ");

        jsonResponse(['success' => true, 'joueurs' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    elseif ($action === 'getRDV') {
        $stmt = $pdo->query("SELECT * FROM rendezvous ORDER BY createAt DESC");
        jsonResponse(['success' => true, 'rdvs' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    elseif ($action === 'addRDV') {
        if (!isset($_SESSION['user'])) {
            jsonResponse(['success' => false, 'message' => 'Non connecté']);
        }

        $pseudo = $_SESSION['user']['pseudo'];
        $ville = $_SESSION['user']['ville'];
        $mode = trim($_POST['mode'] ?? '');
        $date = $_POST['date'] ?? '';
        $time = $_POST['time'] ?? '';
        $message = trim($_POST['message'] ?? '');

        if ($mode === '' || $date === '' || $time === '' || $message === '') {
            jsonResponse(['success' => false, 'message' => 'Champs RDV manquants']);
        }

        $stmt = $pdo->prepare("
            INSERT INTO rendezvous (pseudo, ville, mode, date, time, message)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$pseudo, $ville, $mode, $date, $time, $message]);

        jsonResponse(['success' => true]);
    }

    elseif ($action === 'deleteRDV') {
        if (!isset($_SESSION['user'])) {
            jsonResponse(['success' => false, 'message' => 'Non connecté']);
        }

        $id = $_POST['id'] ?? null;
        $user = $_SESSION['user'];

        $stmt = $pdo->prepare("SELECT pseudo FROM rendezvous WHERE id = ?");
        $stmt->execute([$id]);
        $rdv = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$rdv) {
            jsonResponse(['success' => false, 'message' => 'RDV introuvable']);
        }

        if ($rdv['pseudo'] !== $user['pseudo'] && $user['role'] !== 'admin') {
            jsonResponse(['success' => false, 'message' => 'Non autorisé']);
        }

        $stmt = $pdo->prepare("DELETE FROM rendezvous WHERE id = ?");
        $stmt->execute([$id]);

        jsonResponse(['success' => true]);
    }

    elseif ($action === 'clearAllRDV') {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            jsonResponse(['success' => false, 'message' => 'Non autorisé']);
        }

        $pdo->exec("DELETE FROM rendezvous");
        jsonResponse(['success' => true]);
    }

    elseif ($action === 'lancerPartie') {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            jsonResponse(['success' => false, 'message' => 'Non autorisé']);
        }

        $map = trim($_POST['map'] ?? '');
        $mode = trim($_POST['mode'] ?? '');
        $nbJoueurs = (int)($_POST['nbJoueurs'] ?? 0);
        $temps = (int)($_POST['temps'] ?? 0);
        $kills = (int)($_POST['kills'] ?? 0);

        $stmt = $pdo->prepare("
            INSERT INTO parties (map, mode_jeu, nb_joueurs, temps, kills_max, statut)
            VALUES (?, ?, ?, ?, ?, 'En cours')
        ");
        $stmt->execute([$map, $mode, $nbJoueurs, $temps, $kills]);

        jsonResponse(['success' => true, 'message' => 'Partie lancée !']);
    }

    elseif ($action === 'getParties') {
        $stmt = $pdo->query("
            SELECT id, map, mode_jeu AS mode, nb_joueurs AS nbJoueurs, temps, kills_max AS kills, statut, createdAt
            FROM parties
            ORDER BY createdAt DESC
            LIMIT 20
        ");

        jsonResponse(['success' => true, 'parties' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    elseif ($action === 'deletePartie') {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            jsonResponse(['success' => false, 'message' => 'Non autorisé']);
        }

        $id = $_POST['id'] ?? null;
        $stmt = $pdo->prepare("DELETE FROM parties WHERE id = ?");
        $stmt->execute([$id]);

        jsonResponse(['success' => true]);
    }

    elseif ($action === 'deleteJoueur') {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            jsonResponse(['success' => false, 'message' => 'Non autorisé']);
        }

        $pseudo = trim($_POST['pseudo'] ?? '');

        if ($pseudo === ($_SESSION['user']['pseudo'] ?? '')) {
            jsonResponse(['success' => false, 'message' => 'Impossible de se supprimer soi-même']);
        }

        $stmt = $pdo->prepare("DELETE FROM joueurs WHERE pseudo = ?");
        $stmt->execute([$pseudo]);

        jsonResponse(['success' => true]);
    }

    elseif ($action === 'editJoueur') {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            jsonResponse(['success' => false, 'message' => 'Non autorisé']);
        }

        $pseudo = trim($_POST['pseudo'] ?? '');
        $ville = trim($_POST['ville'] ?? '');
        $role = trim($_POST['role'] ?? 'joueur');
        $password = $_POST['password'] ?? '';

        if ($pseudo === '' || $ville === '') {
            jsonResponse(['success' => false, 'message' => 'Données invalides']);
        }

        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE joueurs SET ville = ?, role = ?, password = ? WHERE pseudo = ?");
            $stmt->execute([$ville, $role, $hash, $pseudo]);
        } else {
            $stmt = $pdo->prepare("UPDATE joueurs SET ville = ?, role = ? WHERE pseudo = ?");
            $stmt->execute([$ville, $role, $pseudo]);
        }

        jsonResponse(['success' => true]);
    }

    elseif ($action === 'getKeybinds') {
        if (!isset($_SESSION['user'])) {
            jsonResponse(['success' => false, 'message' => 'Non connecté']);
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

        jsonResponse(['success' => true, 'keybinds' => $keybinds]);
    }

    elseif ($action === 'saveKeybinds') {
        if (!isset($_SESSION['user'])) {
            jsonResponse(['success' => false, 'message' => 'Non connecté']);
        }

        $pseudo = $_SESSION['user']['pseudo'];

        $keybinds = [
            'avancer' => strtoupper(trim($_POST['avancer'] ?? 'Z')),
            'reculer' => strtoupper(trim($_POST['reculer'] ?? 'S')),
            'gauche' => strtoupper(trim($_POST['gauche'] ?? 'Q')),
            'droite' => strtoupper(trim($_POST['droite'] ?? 'D')),
            'sauter' => strtoupper(trim($_POST['sauter'] ?? 'SPACE')),
            'tirer' => strtoupper(trim($_POST['tirer'] ?? 'MOUSE1')),
            'viser' => strtoupper(trim($_POST['viser'] ?? 'MOUSE2'))
        ];

        $stmt = $pdo->prepare("UPDATE joueurs SET keybinds = ? WHERE pseudo = ?");
        $stmt->execute([json_encode($keybinds, JSON_UNESCAPED_UNICODE), $pseudo]);

        jsonResponse(['success' => true, 'message' => 'Touches sauvegardées', 'keybinds' => $keybinds]);
    }

    else {
        jsonResponse(['success' => false, 'message' => 'Action inconnue']);
    }

} catch (Throwable $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Erreur serveur',
        'debug' => $e->getMessage()
    ]);
}
?>
