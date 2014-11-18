<?php
require_once('lib/yunbi-client.php');

try {
  $client = new YunbiClient(array(
                  'access_key' => 'Your access key',
                  'secret_key' => 'Your secret key'
            ));
  //var_dump($client->get('/api/v2/markets.json');
  //var_dump($client->get('/api/v2/members/me.json'));
  //var_dump($client->post('/api/v2/order/delete.json',['id' => 1]));
}
catch (Exception $e) {
  die($e);
}