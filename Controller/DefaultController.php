<?php

namespace XBsystem\WirecardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('XBsystemWirecardBundle:Default:index.html.twig', array());
    }
}
