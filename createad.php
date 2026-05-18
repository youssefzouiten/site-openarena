<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

$adminUser = "Administrator@openarena.local";
$adminPass = "Group4_";
$ldapServer = "ldap://192.168.50.30";

$pseudo = "joueurtest";
$email = "joueurtest@openarena.local";

$ldap = ldap_connect($ldapServer);

ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

if (!$ldap) {
    die("Erreur connexion LDAP");
}

$bind = ldap_bind($ldap, $adminUser, $adminPass);

if (!$bind) {
    die("Erreur authentification admin AD : " . ldap_error($ldap));
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
    "objectClass" => ["top", "person", "organizationalPerson", "user"],
    "userAccountControl" => "544"
];

$add = ldap_add($ldap, $dn, $user);

if ($add) {
    echo "Utilisateur créé dans Active Directory : " . $pseudo;
} else {
    echo "Erreur création utilisateur : " . ldap_error($ldap);
}

ldap_close($ldap);

?>