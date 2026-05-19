<?php

$ldap = ldap_connect("ldap://192.168.50.30");

ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

$user = "Administrator@openarena.local";
$pass = "Group4_";

if (@ldap_bind($ldap, $user, $pass)) {

    echo "CONNEXION AD OK";

} else {

    echo "ERREUR AD : " . ldap_error($ldap);
}

?>