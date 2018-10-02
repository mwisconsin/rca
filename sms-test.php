<?php

// Get the PHP helper library from twilio.com/docs/php/install
require_once './vendor/autoload.php'; // Loads the library
use Twilio\Rest\Client;

$sid = 'AC982350305cc84a594983f6d7e1a4aea8';
$token = 'd0ae621eb7b9989a1646a994a3341e04';
$client = new Client($sid, $token);

$client->messages->create(
  '3195736866',
  array(
    'from' => '3193180343',
    'body' => "Hi! This is Rider's Club! Are you still available to drive tomorrow? Respond YES if you are. Thank you.",
  )
);
