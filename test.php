<?php

require_once './jsonRPCClient.php';

$url = 'https://web.okeya.heteml.local/api/order/';

$sendData = array(
    'version'  => '1.1',
    'method'  => 'validate',
    'params'  => array(
        'account'    => '10021901',
        'sub_domain' => '10021901',
        'password'   => '10011901',
    )
);

$client   = new jsonRPCClient($url);
$response = $client->validate($sendData);
var_dump($response);
exit;

