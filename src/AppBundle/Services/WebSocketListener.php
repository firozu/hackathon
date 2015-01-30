<?php

namespace AppBundle\Services;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\Guzzle\Http\Message\RequestFactory;

class WebSocketListener implements MessageComponentInterface {

    protected $connections = [];

    public function __construct() {
        $this->connections = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        //$this->connectionPool[] = $conn;
        //$this->connections->attach($conn);
        echo "A new client connected: " . $conn->remoteAddress . "\n";
    }

    private function handshake($from, $message)
    {
        $msg = RequestFactory::getInstance()->fromMessage($message);
        $key = $msg->getHeader('Sec-WebSocket-Key');
        $key .= '258EAFA5-E914-47DA-95CA-C5AB0DC85B11'; //magic
        $key = base64_encode(sha1($key, true));

        $upgrade = "HTTP/1.1 101 Switching Protocols\r\n" .
           "Upgrade: websocket\r\n" .
           "Connection: Upgrade\r\n" .
           "Sec-WebSocket-Accept: " . $key . "\r\n" .
           "\r\n";

        $from->send($upgrade);
    }

    public function onMessage(ConnectionInterface $from, $message)
    {
        if (!$this->connections->contains($from)) {
            $this->handshake($from, $message);
            $this->connections->attach($from);

            return;
        }
        echo $from->remoteAddress . " said " . $this->unmask($message) . "\n";

        foreach($this->connections as $connection) {
            if ($connection == $from) {
                continue;
            }
            $connection->send($this->mask($from->remoteAddress . ' said: ' . $message . "\n"));
        }
    }

    private function mask($payload, $type = 'text', $masked = true)
    {
        $frameHead = array();
        $frame = '';
        $payloadLength = strlen($payload);
        switch($type) {
            case 'text':
                // first byte indicates FIN, Text-Frame (10000001):
                $frameHead[0] = 129;
                break;
            case 'close':
                // first byte indicates FIN, Close Frame(10001000):
                $frameHead[0] = 136;
                break;
            case 'ping':
                // first byte indicates FIN, Ping frame (10001001):
                $frameHead[0] = 137;
                break;
            case 'pong':
                // first byte indicates FIN, Pong frame (10001010):
                $frameHead[0] = 138;
                break;
        }
        // set mask and payload length (using 1, 3 or 9 bytes)
        if($payloadLength > 65535) {
            $payloadLengthBin = str_split(sprintf('%064b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 255 : 127;
            for($i = 0; $i < 8; $i++) {
                $frameHead[$i+2] = bindec($payloadLengthBin[$i]);
            }
            // most significant bit MUST be 0 (close connection if frame too big)
            if($frameHead[2] > 127) {
                $this->close(1004);

                return false;
            }
        } elseif($payloadLength > 125) {
            $payloadLengthBin = str_split(sprintf('%016b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 254 : 126;
            $frameHead[2] = bindec($payloadLengthBin[0]);
            $frameHead[3] = bindec($payloadLengthBin[1]);
        } else {
            $frameHead[1] = ($masked === true) ? $payloadLength + 128 : $payloadLength;
        }
        // convert frame-head to string:
        foreach(array_keys($frameHead) as $i) {
            $frameHead[$i] = chr($frameHead[$i]);
        }
        if($masked === true) {
            // generate a random mask:
            $mask = array();
            for($i = 0; $i < 4; $i++) {
                $mask[$i] = chr(rand(0, 255));
            }
            $frameHead = array_merge($frameHead, $mask);
        }
        $frame = implode('', $frameHead);
        // append payload to frame:
        $framePayload = array();
        for($i = 0; $i < $payloadLength; $i++) {
            $frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
        }

        return $frame;
    }

    private function unmask($message)
    {
        $length = ord($message[1]) & 127;
        if($length == 126) {
            $masks = substr($message, 4, 4);
            $data = substr($message, 8);
        }
        elseif($length == 127) {
            $masks = substr($message, 10, 4);
            $data = substr($message, 14);
        }
        else {
            $masks = substr($message, 2, 4);
            $data = substr($message, 6);
        }
        $text = "";
        for ($i = 0; $i < strlen($data); ++$i) {
            $text .= $data[$i] ^ $masks[$i%4];
        }

        return $text;
    }

    public function onError(ConnectionInterface $conn, \Exception $error)
    {
        echo $conn->remoteAddress . ' errored ' . $error;
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->connections->detach($conn);
        echo "A Client disconnected: " . $conn->remoteAddress . "\n";
    }
}
