<?php

namespace EventBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class EventController extends Controller
{
    /**
     * @Route("/", name="events")
     */
    public function eventsAction()
    {
        return $this->render('EventBundle:Event:events.html.twig', array(
            // ...
        ));
    }

}