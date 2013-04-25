<?php

use Acme\Pusher;

require __DIR__ . '/../vendor/autoload.php';

$loop   = React\EventLoop\Factory::create();
$pusher = new Pusher();

//Unix timestamp
$loop->addPeriodicTimer(10, array($pusher, 'timedCallback'));

$connection = new Predis\Async\Client('tcp://127.0.0.1:6379', $loop);
$connection->connect(array($pusher, 'init'));

// Set up our WebSocket server for clients wanting real-time updates
$webSock = new React\Socket\Server($loop);
$webSock->listen(8081, '0.0.0.0');
$webServer = new Ratchet\Server\IoServer(
    new Ratchet\WebSocket\WsServer(
        new Ratchet\Wamp\WampServer(
            $pusher
        )
    ),
    $webSock
);

echo "Pusher starting...\n";
$loop->run();