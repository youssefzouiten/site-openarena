<?php

function ad_login($pseudo, $password) {

    $ldap = ldap_connect("ldap://192.168.50.30");

    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

    $login = $pseudo . "@openarena.local";

    $bind = @ldap_bind($ldap, $login, $password);

    if ($bind) {
        ldap_close($ldap);
        return true;
    }

    ldap_close($ldap);
    return false;
}

function ad_create_user($pseudo, $email) {

    $adminUser = "Administrator@openarena.local";
    $adminPass = "Group4_";

    $ldap = ldap_connect("ldap://192.168.50.30");

    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

    $bind = @ldap_bind($ldap, $adminUser, $adminPass);

    if (!$bind) {
        return false;
    }

    $dn = "CN=$pseudo,CN=Users,DC=openarena,DC=local";

    $user = [
        "cn" => $pseudo,
        "sn" => $pseudo,
        "givenName" => $pseudo,
        "displayName" => $pseudo,
        "sAMAccountName" => $pseudo,
        "userPrincipalName" => $email,
        "mail" => $email,
        "objectClass" => [
            "top",
            "person",
            "organizationalPerson",
            "user"
        ],
        "userAccountControl" => "544"
    ];

    $add = ldap_add($ldap, $dn, $user);

    ldap_close($ldap);

    return $add;
}

?>