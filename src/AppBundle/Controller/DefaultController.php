<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    public function indexAction(Request $request)
    {
        // This is the primary page for the pc browser to hit
        $gameId = $request->query->get('gameId');
        if (!$gameId) {
            $gameId = uniqid();
        }
        return $this->render('AppBundle:Default:index.html.twig', ['gameId' => $gameId]);
    }

    public function clientConnectAction(Request $request)
    {
        // This is where the mobile client goes to get the page it uses
        $gameId = $request->query->get('gameId');
        return $this->render('AppBundle:Default:client.html.twig', ['gameId' => $gameId]);
    }

    public function threeAction(Request $request)
    {
        return $this->render('AppBundle:Default:three.html.twig');
    }
}
