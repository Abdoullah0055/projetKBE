<?php
function validate_email($email) {
    // Validation syntaxique
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    // Validation du domaine (DNS)
    $domain = substr(strrchr($email, "@"), 1);
    return checkdnsrr($domain, "MX");
}

function normalize_email($email) {
    return strtolower(trim($email));
}