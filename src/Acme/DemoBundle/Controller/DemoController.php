<?php

namespace Acme\DemoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

use Acme\DemoBundle\Form\ContactType;

// these import the "@Route" and "@Template" annotations
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DemoController extends Controller
{
    /**
     * @Route("/", name="_demo")
     * @Template()
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * @Route("/hello/{name}", name="_demo_hello")
     * @Template()
     */
    public function helloAction($name)
    {
        return array('name' => $name);
    }

    /**
     * @Route("/socket/", name="_demo_socket")
     * @Template()
     */
    public function socketAction()
    {
       return array();
    }

    /**
     * @Route("/pubsub/", name="_demo_pubsub")
     * @Template()
     */
    public function pubsubAction()
    {
        /**
         * Redis pubsub
         */

        $request = $this->get('request');
        if ($request->isMethod('POST')) {

            if ($request->request->get('pub') && $request->request->get('channel')) {
                $channel = $request->request->get('channel');
                $payload = $request->request->get('pub');

                $pr = new PredisHelper();
                $pr->publish($channel, $payload);
                
                return new Response(sprintf('Published %s to %s', $payload, $channel));
            }
            
            return new Response("Need pub and channel", 400);  
        }




       return array();
    }

    /**
     * @Route("/contact", name="_demo_contact")
     * @Template()
     */
    public function contactAction()
    {
        $form = $this->get('form.factory')->create(new ContactType());

        $request = $this->get('request');
        if ($request->isMethod('POST')) {
            $form->bind($request);
            if ($form->isValid()) {
                $mailer = $this->get('mailer');
                // .. setup a message and send it
                // http://symfony.com/doc/current/cookbook/email.html

                $this->get('session')->getFlashBag()->set('notice', 'Message sent!');

                return new RedirectResponse($this->generateUrl('_demo'));
            }
        }

        return array('form' => $form->createView());
    }
}
