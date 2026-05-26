<?php

function ad_connect($user = null, $password = null) {

    $ldap = ldap_connect("ldaps://WIN-0BDJN902Q6O.openarena.local:636");

    if (!$ldap) {
        die("Connexion LDAP impossible");
    }

    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

    if ($user !== null && $password !== null) {

        if (!@ldap_bind($ldap, $user, $password)) {

            ldap_close($ldap);

            return false;
        }
    }

    return $ldap;
}

function password_valid($password) {

    // Minimum 8 caractères
    if (strlen($password) < 8) {
        return "Le mot de passe doit contenir au moins 8 caractères";
    }

    // Au moins une majuscule
    if (!preg_match('/[A-Z]/', $password)) {
        return "Le mot de passe doit contenir au moins une majuscule";
    }

    // Au moins une minuscule
    if (!preg_match('/[a-z]/', $password)) {
        return "Le mot de passe doit contenir au moins une minuscule";
    }

    // Au moins un chiffre
    if (!preg_match('/[0-9]/', $password)) {
        return "Le mot de passe doit contenir au moins un chiffre";
    }

    // Au moins un caractère spécial
    if (!preg_match('/[\W]/', $password)) {
        return "Le mot de passe doit contenir au moins un caractère spécial";
    }

    return true;
}

function ad_login($pseudo, $password) {

    $login1 = $pseudo . "@openarena.local";
    $login2 = "OPENARENA\\" . $pseudo;

    $ldap = ad_connect($login1, $password);

    if ($ldap) {

        ldap_close($ldap);

        return true;
    }

    $ldap = ad_connect($login2, $password);

    if ($ldap) {

        ldap_close($ldap);

        return true;
    }

    return false;
}

function ad_create_user($pseudo, $email, $password, $password2) {

    // Vérification confirmation mot de passe
    if ($password !== $password2) {

        return "Les mots de passe ne correspondent pas";
    }

    // Vérification politique mot de passe
    $check = password_valid($password);

    if ($check !== true) {

        return $check;
    }

    $adminUser = "Administrator@openarena.local";
    $adminPass = "Group4_";

    $ldap = ad_connect($adminUser, $adminPass);

    if (!$ldap) {

        return "Erreur connexion LDAP";
    }

    // Sécurise le pseudo LDAP
    $pseudoSafe = ldap_escape($pseudo, "", LDAP_ESCAPE_DN);

    $dn = "CN=$pseudoSafe,OU=Joueurs,DC=openarena,DC=local";

    $user = [

        "cn" => $pseudo,
        "sn" => $pseudo,
        "givenName" => $pseudo,
        "displayName" => $pseudo,

        "sAMAccountName" => $pseudo,

        "userPrincipalName" => $pseudo . "@openarena.local",

        "mail" => $email,

        "objectClass" => [
            "top",
            "person",
            "organizationalPerson",
            "user"
        ],

        // Compte désactivé temporairement
        "userAccountControl" => ["514"]
    ];

    // Création utilisateur
    $add = @ldap_add($ldap, $dn, $user);

    if (!$add) {

        $error = ldap_error($ldap);

        ldap_close($ldap);

        return "Erreur création utilisateur : " . $error;
    }

    // Encodage mot de passe AD
    $pwd = mb_convert_encoding(
        '"' . $password . '"',
        'UTF-16LE'
    );

    // Définition mot de passe
    $passSet = @ldap_mod_replace($ldap, $dn, [
        "unicodePwd" => [$pwd]
    ]);

    if (!$passSet) {

        $error = ldap_error($ldap);

        // Supprime le compte si le mot de passe échoue
        @ldap_delete($ldap, $dn);

        ldap_close($ldap);

        return "Le mot de passe ne respecte pas la politique de sécurité";
    }

    // Active le compte
    $enable = @ldap_mod_replace($ldap, $dn, [
        "userAccountControl" => ["512"]
    ]);

    if (!$enable) {

        $error = ldap_error($ldap);

        ldap_close($ldap);

        return "Erreur activation compte : " . $error;
    }

    ldap_close($ldap);

    return true;
}

?>
