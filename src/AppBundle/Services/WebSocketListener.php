<?php

namespace AppBundle\Services;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class WebSocketListener implements MessageComponentInterface {

    protected $connections = [];

    public function __construct() {
        $this->connections = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        //$this->connectionPool[] = $conn;
        $this->connections->attach($conn);
        echo "A new client connected: " . $conn->remoteAddress . "\n";
    }

    public function onMessage(ConnectionInterface $from, $message)
    {
        echo $from->remoteAddress . ' said: ' . $message . "\n";

        $this->connections->rewind();

        foreach($this->connections as $connection) {
            if ($connection == $from) {
                continue;
            }
            $connection->send($from->remoteAddress . ' said: ' . $message . "\n");
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $error)
    {
        echo $conn->remoteAddress . ' errored ' . $error->getMessage();
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->connections->detach($conn);
        echo "A Client disconnected: " . $conn->remoteAddress . "\n";
    }
}
