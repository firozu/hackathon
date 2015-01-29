<?php
namespace AppBundle/Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Ratchet\Server\IoServer;

class ServerCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('socket:run')
            ->setDescription('Start the Websocket Server');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sockListener = $this->getContainer()->get('app_socket');
        $server = IoServer::factory($sockListener, 8080);
        $server->run();
    }
}
