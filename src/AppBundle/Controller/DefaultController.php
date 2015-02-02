<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    public function indexAction()
    {
        // This is the primary page for the pc browser to hit
        $gameId = uniqid();
        return $this->render('AppBundle:Default:index.html.twig', ['gameId' => $gameId]);
    }

    public function gameListAction()
    {
        return $this->render('AppBundle:Default:gamelist.html.twig');
    }

    public function clientConnectAction(Request $request)
    {
        // This is where the mobile client goes to get the page it uses
        $gameId = $request->query->get('gameId');
        $playerId = $request->query->get('playerId');
        return $this->render('AppBundle:Default:client.html.twig',[
            'gameId' => $gameId,
            'playerId' => $playerId
        ]);
    }
}
