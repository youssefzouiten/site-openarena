<?php

function ad_login($pseudo, $password) {

    $ldap = ldap_connect("ldap://192.168.1.200");

    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

    $login1 = $pseudo . "@openarena.local";
    $login2 = "OPENARENA\\" . $pseudo;

    echo "<pre>";
    echo "TEST LOGIN 1 : $login1\n";

    if (ldap_bind($ldap, $login1, $password)) {
        echo "LOGIN 1 OK";
        ldap_close($ldap);
        return true;
    }

    echo "LOGIN 1 FAILED\n";
    echo "TEST LOGIN 2 : $login2\n";

    if (@ldap_bind($ldap, $login2, $password)) {
        echo "LOGIN 2 OK";
        ldap_close($ldap);
        return true;
    }

    echo "LOGIN 2 FAILED\n";

    echo "LDAP ERROR : " . ldap_error($ldap);

    ldap_close($ldap);

    return false;
}

function ad_create_user($pseudo, $email) {

    $adminUser = "Administrator@openarena.local";
    $adminPass = "Group4_";

    $ldap = ldap_connect("ldap://192.168.1.200");

    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

    $bind = @ldap_bind($ldap, $adminUser, $adminPass);

    if (!$bind) {
        return false;
    }

    $dn = "CN=$pseudo,OU=Joueurs,DC=openarena,DC=local";

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

function ad_delete_user($pseudo) {
    $adminUser = "Administrator@openarena.local";
    $adminPass = "Group4_";

    $ldap = ldap_connect("ldap://192.168.1.200");

    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

    $bind = @ldap_bind($ldap, $adminUser, $adminPass);
    if (!$bind) {
        return false;
    }

    $dn = "CN=$pseudo,OU=Joueurs,DC=openarena,DC=local";

    // suppression
    $delete = ldap_delete($ldap, $dn);
    ldap_close($ldap);
    return $delete;
}

?>