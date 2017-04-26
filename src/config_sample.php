<?php

/*
 * Copy this file to congig.php and setup config data
 *
 */

function consoleLog($var) {
    if(is_array($var) || is_object($var)) {
        var_dump($var);
    } else {
        echo $var . "\r\n";
    }
}

$glConfig = array();
$glConfig['creds'] = array();
$glConfig['creds']['user'] = ''; // user id
$glConfig['creds']['pass'] = ''; // password

// for now works with curl and guzzle, both sync and async
// $glConfig['apiClient'] = "curl";
$glConfig['apiClient'] = "guzzle";

// use for guzzle async calls
// $glConfig['guzzleAsync'] = true;

$glConfig['httpProtocol'] = ""; // "http" or "https";
$glConfig['httpHost'] = ""; // "api.host-domain.com"
$glConfig['httpPath'] = ""; // "/path/to/service/url"

$glConfig['xmlns'] = ""; // "https://www.api-server.com/apiRequest/";

?>
