<?php
namespace AppBundle/Services;

use Ratchet/MessageComponentInterface;

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

    public function onOpen($event)
    {
        var_dump($event);
    }

    public function onMessage($message)
    {
        echo $message;
    }

    public function onError($error)
    {
        var_dump($error);
    }

    public function onClose($event)
    {
        var_dump($event);
    }
}
