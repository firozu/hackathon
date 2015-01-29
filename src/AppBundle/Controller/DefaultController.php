<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    public function indexAction()
    {
        // This is the primary page for the pc browser to hit
        return $this->render('AppBundle:Default:index.html.twig');
    }

    public function clientConnectAction(Request $request)
    {
        // This is where the mobile client goes to get the page it uses
        $gameId = $request->query->get('gameId');
        return $this->render('AppBundle:Default:client.html.twig', ['gameId' => $gameId]);
    }
}
