<?php
session_start();

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $pseudo = trim($_POST["pseudo"] ?? "");
    $password = $_POST["password"] ?? "";

    $ldap = ldap_connect("ldap://192.168.50.30");

    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

    $userLogin = $pseudo . "@openarena.local";

    if (@ldap_bind($ldap, $userLogin, $password)) {

        $_SESSION["user"] = [
            "pseudo" => $pseudo,
            "role" => $pseudo === "Administrator"
                ? "admin"
                : "joueur"
        ];

        $message =
            "Connexion réussie avec Active Directory : "
            . htmlspecialchars($pseudo);

    } else {

        $message =
            "Pseudo ou mot de passe incorrect dans Active Directory";
    }

    ldap_close($ldap);
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Connexion Active Directory</title>
</head>

<body>

<h1>Connexion Active Directory</h1>

<form method="POST">

    <label>Pseudo AD</label><br>
    <input type="text" name="pseudo" required><br><br>

    <label>Mot de passe AD</label><br>
    <input type="password" name="password" required><br><br>

    <button type="submit">
        Se connecter
    </button>

</form>

<p><?= $message ?></p>

</body>
</html>