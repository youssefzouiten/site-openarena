<?php

function ad_connect_admin() {

    $ldap = ldap_connect("ldaps://192.168.50.30");

    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

    if (!$ldap) {
        return false;
    }

    $adminUser = "Administrator@openarena.local";
    $adminPass = "Group4_";

  if (!ldap_bind($ldap, $adminUser, $adminPass)) {
    die("Erreur bind admin : " . ldap_error($ldap));
}

    return $ldap;
}

function ad_login($pseudo, $password) {

    $ldap = ldap_connect("ldaps://192.168.50.30");

    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

    $login1 = $pseudo . "@openarena.local";
    $login2 = "OPENARENA\\" . $pseudo;

    if (@ldap_bind($ldap, $login1, $password)) {
        ldap_close($ldap);
        return true;
    }

    if (@ldap_bind($ldap, $login2, $password)) {
        ldap_close($ldap);
        return true;
    }

    ldap_close($ldap);
    return false;
}

function ad_create_user($pseudo, $email, $password) {

    $ldap = ad_connect_admin();

    if (!$ldap) {
        return false;
    }

    $dn = "CN=$pseudo,CN=Users,DC=openarena,DC=local";

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
        "userAccountControl" => "514"
    ];

  if (!ldap_add($ldap, $dn, $user)) {
    die("Erreur ldap_add : " . ldap_error($ldap));
}

    $quotedPassword = '"' . $password . '"';

    $unicodePassword = mb_convert_encoding(
        $quotedPassword,
        "UTF-16LE"
    );

   if (!ldap_mod_replace($ldap, $dn, [
    "unicodePwd" => $unicodePassword
])) {
    die("Erreur mot de passe AD : " . ldap_error($ldap));
}

    if (!@ldap_mod_replace($ldap, $dn, [
        "userAccountControl" => "512"
    ])) {
        ldap_close($ldap);
        return false;
    }

    ldap_close($ldap);

    return true;
}

?>