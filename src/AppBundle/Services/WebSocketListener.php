<?php

namespace AppBundle\Services;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class WebSocketListener implements MessageComponentInterface {
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * Constructor
     *
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function __construct($em)
    {
        $this->em = $em;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        var_dump($conn);
    }

    public function onMessage(ConnectionInterface $from, $message)
    {
        echo $message;
    }

    public function onError(ConnectionInterface $conn, \Exception $error)
    {
        var_dump($error);
    }

    public function onClose(ConnectionInterface $conn)
    {
        var_dump($conn);
    }
}
