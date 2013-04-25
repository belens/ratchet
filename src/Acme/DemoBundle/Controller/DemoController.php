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
        private $pr;
        private $channel;
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

        //Default channel you're subscribed on TODO: make it dynamic based on url/first in list
        if (empty($this->channel)){
        $this->channel = 'card:getBetter';
    }
        if ($request->request->get('channel')){
            $this->channel = $request->request->get('channel');
        }
        $pr = new PredisHelper();
        if ($request->isMethod('POST')) {

            if ($request->request->get('pub')) {
                $payload = $request->request->get('pub');

                
                if(strpos($this->channel,'chat') !== false){
                    $data = $payload;
                } else {
                    $data = array('message'=>$payload, 'song'=>'daft punk get lucky', 'image'=>'blabla.jpg');
                    $data = json_encode($data);
                }

                $pr->publish($this->channel, $data);
                $pr->push($this->channel,$data);

                return new Response(sprintf('Published %s to %s', $payload, $this->channel));
            }
            
            return new Response("Need pub and channel", 400);  
        }

        echo $this->channel;
        $messages = $pr->getAllMessagesFromChannel($this->channel);

        echo count($messages);

        return array('channel'=>$this->channel, 'messages'=>$messages);
    }


    /**
     * @Route("/pubsub/{channel}", name="_demo_pubsub_channel")
     * @Template("AcmeDemoBundle:Demo:pubsub.html.twig")
     */
    public function pubsubWithChannelAction($channel)
    {
        $this->channel = $channel;
        $this->pubsubAction();
        return array('channel' => $channel);
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
