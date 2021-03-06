<?php

namespace AppBundle\Controller;

use BaseBundle\Entity\User;
use FOS\UserBundle\Form\Type\ProfileFormType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        if (($this->container->get('security.authorization_checker')->isGranted('ROLE_USER')))
            return $this->redirectToRoute('member_profile');
        if (($this->container->get('security.authorization_checker')->isGranted('ROLE_BUSINESS')))
            return $this->redirectToRoute('business_home');
        if (($this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN')))
            return $this->redirectToRoute('admin_home');
        return $this->render('base.html.twig');
    }
}
