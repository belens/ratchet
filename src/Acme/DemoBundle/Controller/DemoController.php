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
     * @Route("/socket/", name="_demo_socket")
     * @Template()
     */
    public function socketAction()
    {
       return array();
    }

    /**
     * @Route("/room/", name="_demo_room")
     * @Template()
     */
    public function roomAction()
    {
        /**
         * Redis pubsub
         */
        $request = $this->get('request');

        if ($request->request->get('channel')){
            $this->channel = $request->request->get('channel');
        }

        $pr = new PredisHelper();

        if ($request->isMethod('POST')) {

            if ($request->request->get('pub')) {
                $payload = $request->request->get('pub');

                $result = substr($this->channel, 0, strpos($this->channel, ':'));

                switch ($result) {
                    case 'chat':
                        $data = $payload;
                        break;
                    case 'data':
                        $data = array('message'=>$payload, 'song'=>'daft punk get lucky', 'image'=>'blabla.jpg');
                        $data = json_encode($data);
                        break;
                    case 'room':
                        break;
                }                

                $pr->publish($this->channel, $data);
                $pr->push($this->channel,$data);

                return new Response(sprintf('Published %s to %s', $payload, $this->channel));
            }
            
            return new Response("Need pub and channel", 400);  
        }

        echo $this->channel;
        $messages = $pr->get10LastMessagesFromChannel($this->channel);

        echo count($messages);

        return array('channel'=>$this->channel, 'messages'=>$messages);
    }


    /**
     * @Route("/room/{channel}", name="_demo_room_channel")
     * @Template("AcmeDemoBundle:Demo:room.html.twig")
     */
    public function roomWithChannelAction($channel)
    {
        $this->channel = $channel;
        $this->roomAction();
        return array('channel' => $channel);
    }
}
