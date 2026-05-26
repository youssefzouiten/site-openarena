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

            echo "LDAP BIND ERROR : " . ldap_error($ldap);

            ldap_close($ldap);

            return false;
        }
    }

    return $ldap;
}

function ad_login($pseudo, $password) {

    $login1 = $pseudo . "@openarena.local";
    $login2 = "OPENARENA\\" . $pseudo;

    /*
    echo "<pre>";
    echo "TEST LOGIN 1 : $login1\n";
    */

    $ldap = ad_connect($login1, $password);

    if ($ldap) {

        /*
        echo "LOGIN 1 OK";
        */

        ldap_close($ldap);

        return true;
    }

    /*
    echo "LOGIN 1 FAILED\n";
    echo "TEST LOGIN 2 : $login2\n";
    */

    $ldap = ad_connect($login2, $password);

    if ($ldap) {

        /*
        echo "LOGIN 2 OK";
        */

        ldap_close($ldap);

        return true;
    }

    /*
    echo "LOGIN 2 FAILED\n";
    */

    return false;
}

function ad_create_user($pseudo, $email, $password) {

    $adminUser = "Administrator@openarena.local";
    $adminPass = "Group4_";

    $ldap = ad_connect($adminUser, $adminPass);

    if (!$ldap) {
        return false;
    }

    // Sécurise le pseudo dans le DN
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

        echo "LDAP ADD ERROR : " . ldap_error($ldap);

        ldap_close($ldap);

        return false;
    }

    // Mot de passe AD
    $pwd = mb_convert_encoding(
        '"' . $password . '"',
        'UTF-16LE'
    );

    // Définition du mot de passe
    $passSet = @ldap_mod_replace($ldap, $dn, [
        "unicodePwd" => [$pwd]
    ]);

    if (!$passSet) {

        echo "PASSWORD ERROR : " . ldap_error($ldap);

        ldap_close($ldap);

        return false;
    }

    // Activation du compte
    $enable = @ldap_mod_replace($ldap, $dn, [
        "userAccountControl" => ["512"]
    ]);

    if (!$enable) {

        echo "ENABLE ACCOUNT ERROR : " . ldap_error($ldap);

        ldap_close($ldap);

        return false;
    }

    ldap_close($ldap);

    return true;
}

?>
