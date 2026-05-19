<?php

$user = "Administrator@openarena.local";
$pass = "Group4_";

$ldapconn = ldap_connect("ldap://192.168.1.200");

ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

if (!$ldapconn) {
    die("Erreur connexion LDAP");
}

$bind = @ldap_bind($ldapconn, $user, $pass);

if ($bind) {
    echo "Connexion Active Directory OK";
} else {
    echo "Erreur login AD : " . ldap_error($ldapconn);
}

ldap_close($ldapconn);

?>