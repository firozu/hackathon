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
        $obj->playerId = null;
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
            // A client is attempting to attach to a game
            $this->attachToGame($splObj, $params);
        } else if (isset($params['getList'])) {
            // They are asking for a list of waiting games
            $this->sendWaiting($from);
        } elseif (isset($params['p2u'])) {
            // Player 2 console update;
            $this->sendSlaveUpdate($splObj, $params);
        } else {
            // Send gyros to master
            $this->sendMasterUpdate($splObj, $params);
        }
    }

    private function sendSlaveUpdate($splObj, $params)
    {
        foreach ($this->connections as $splConn) {
            if ($splObj->gameId != $splConn->gameId) {
                continue;
            }
            if ($splConn->mobile) {
                continue;
            }
            $splConn->conn->send(json_encode($params));
        }
    }

    private function sendWaiting($from)
    {
        $gameIds = [];
        foreach ($this->connections as $splConn) {
            if (!$splConn->gameId) {
                continue;
            }
            if (!$splConn->mobile) {
                continue; // skip mobiles;
            }
            $cgame = $splConn->gameId;
            if (isset($gameIds[$cgame])) {
                ++$gameIds[$cgame];
            } else {
                $gameIds[$cgame] = 1;
            }
        }
        $sendAble = [];
        foreach ($gameIds as $gameId => $count) {
            if ($count == 1) {
                $sendAble[] = $gameId;
            }
        }
        $from->send(json_encode(['games' => $sendAble]));
    }

    private function sendMasterUpdate($splObj, $params)
    {
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
            if ($socket->playerId != 1) {
                continue;
            }
            $params['p'] = $splObj['playerId'];

            $socket->conn->send(json_encode($params));

            return;
        }
    }

    private function attachToGame($splObj, $params)
    {
        //attach to a game;
        $splObj->gameId = $params['game'];
        $splObj->mobile = $params['mobile'];
        if(isset($params['playerId'])) {
            $splObj->playerId = $params['playerId'];
        } else {
            $splObj->playerId = 1;
        }

        if (!$splObj->mobile && $splObj->playerId == 1) {
            $this->sendWaitingUpdate();
        }
    }

    //I know this duplicates the other function, dont care
    private function sendWaitingUpdate()
    {
        $sendList = [];
        $gameList = [];
        foreach($this->connections as $client) {
            if ($client->mobile) {
                continue;
            }
            if (!$client->gameId) {
                $sendList[] = $client->conn;
                continue;
            }
            if (isset($gameList[$client->gameId])) {
                ++$gameList[$client->gameId];
            } else {
                $gameList[$client->gameId] = 1;
            }
        }

        $toSend = [];
        foreach($gameList as $gameId => $count) {
            if ($count == 1) {
                $toSend[] = $gameId;
            }
        }

        foreach($sendList as $conn) {
            $conn->send(json_encode(['games' => $toSend]));
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
