<?php
namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Http\HttpServer;

class SocketCommand extends ContainerAwareCommand
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
        $server = IoServer::factory(
            new HttpServer(new WsServer($sockListener)),
            8085
        );
        $output->writeln('Socket Server running on port 8085');
        try {
            $server->run();
        } catch (\Exception $e) {
            echo $e;
        }
    }
}
