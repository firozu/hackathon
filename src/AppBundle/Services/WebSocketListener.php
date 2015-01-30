<?php

namespace AppBundle\Services;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\Guzzle\Http\Message\RequestFactory;

class WebSocketListener implements MessageComponentInterface {

    protected $connections;

    public function __construct() {
        $this->connections = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $obj = new \StdClass();
        $obj->gameId = null;
        $obj->conn = $conn;
        $obj->mobile = null;
        $this->connections->attach($obj);
        echo "A new client connected: " . $conn->remoteAddress . "\n";
    }

    public function onMessage(ConnectionInterface $from, $message)
    {
        echo $from->remoteAddress . " said " . $message . "\n";

        foreach($this->connections as $splConn) {
            if ($splConn->conn == $from) {
                $splObj = $splConn;
                break;
            }
        }

        $params = json_decode($message, true);

        if (isset($params['game'])) {
            //attach to a game;
            $splObj->gameId = $params['game'];
            $splObj->mobile = $params['mobile'];
        } else {
            $gameId = $splObj->gameId;
            $this->connections->rewind();
            foreach($this->connections as $socket) {
                if ($socket->gameId !== $gameId) {
                    continue;
                }
                if ($socket == $splObj) {
                    continue;
                }
                if ($socket->mobile) {
                    continue;
                }

                echo 'sent to : ' . $socket->conn->remoteAddress . "\n";

                $socket->conn->send(json_encode($params));
            }
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $error)
    {
        echo $conn->remoteAddress . ' errored ' . $error . "\n";
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->connections->detach($conn);
        echo "A Client disconnected: " . $conn->remoteAddress . "\n";
    }
}
