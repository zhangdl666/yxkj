<?php
$redisChat = new \Redis();
$redisChat->connect('127.0.0.1');
var_dump($redisChat->keys('*'));
//$redisChat->expire('')
var_dump($redisChat->hGetAll('2018-02-02/16_76_047863016A46'));