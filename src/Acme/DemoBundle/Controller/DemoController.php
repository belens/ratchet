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
    private $currentUsers;
    private $chatMessages;
    private $chatMessagesJSON;
    private $dataMessages;
    private $dataMessagesJSON;

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

        $this->channel = 'getBetter';
        if ($request->request->get('channel')){
            $this->channel = $request->request->get('channel');
        }

        $pr = new PredisHelper();

        if ($request->isMethod('POST')) {
            if ($request->request->get('pub')) {
                $payload = $request->request->get('pub');
                $result = substr($this->channel, 0, strpos($this->channel, '::'));

                switch ($result) {
                    case 'chat':
                        $data = array('channel'=>$this->channel, 'message'=>$payload);
                        $data = json_encode($data);
                        break;
                    case 'data':
                        $data = array('channel'=>$this->channel,'message'=>$payload);
                        $data = json_encode($data);
                        break;
                    case 'frontdesk':
                        if(is_int($payload)) {
                            $data = array('channel'=>$this->channel,'total'=>$payload);
                        } else {
                            $data = array('channel'=>$this->channel,'subscriber'=>$payload);
                        }                
                        $data = json_encode($data);
                        break;
                }                

                $pr->publish($this->channel, $data);
                $pr->push($this->channel,$data);

                return new Response(sprintf('Published %s to %s', $payload, $this->channel));
            }
            if ($request->request->get('del')) {
                $payload = $request->request->get('del');

                /*$result = substr($this->channel, 0, strpos($this->channel, '::'));

                switch ($result) {
                    case 'frontdesk':
                        if(is_int($payload)) {
                            $data = array('channel'=>$this->channel,'total'=>$payload);
                        } else {*/
                            $data = array('channel'=>$this->channel,'subscriber'=>$payload);
                        /*}                
                        */$data = json_encode($data);/*
                        break;
                }          */      

                //$pr->publish($this->channel, $data);
                $pr->del($this->channel,$data);
                /*$list = array();
                $list[] = "DELETE";
                $list = array_merge($list, $pr->getAllMessagesFromChannel($this->channel));*/
                $list = $pr->getAllMessagesFromChannel($this->channel);

                var_dump(json_encode($list));

                $pr->publish($this->channel, json_encode($list));

                return new Response(sprintf('Deleted %s to %s', $payload, $this->channel));
            }            
            
            return new Response("Need pub and channel", 400);  
        }
        $currentUsersJSONArray = $pr->getAllMessagesFromChannel('frontdesk::'.$this->channel);
       // echo $currentUsers;
        /*if (count($currentUsersArray) > 0){
            var_dump(json_decode($currentUsersArray[0])->subscriber);
        }*/
        $currentUsers = array();
        foreach($currentUsersJSONArray as $currentUser){
            $currentUsers[] = json_decode($currentUser)->subscriber;
        }     
        //echo $this->channel;
        $chatMessages = $pr->get10LastMessagesFromChannel('chat::'.$this->channel);
        //echo $messages;
        $chatMessagesJSON = json_encode($chatMessages);
        //echo $messagesJSON;
        $dataMessages = $pr->get10LastMessagesFromChannel('data::'.$this->channel);
        //echo $dataMessages;
        $dataMessagesJSON = json_encode($dataMessages);
        //echo $dataMessagesJSON;
        return array('channel'=>$this->channel, 'chatMessages'=>$chatMessagesJSON, 'dataMessages'=>$dataMessagesJSON, 'currentUsers'=>$currentUsers);
    }


    /**
     * @Route("/room/{channel}", name="_demo_room_channel")
     * @Template("AcmeDemoBundle:Demo:room.html.twig")
     */
    public function roomWithChannelAction($channel)
    {
        $this->channel = $channel;
        $this->roomAction();
        return array('channel'=>$this->channel, 'chatMessages'=>$chatMessagesJSON, 'dataMessages'=>$dataMessagesJSON, 'currentUsers'=>$currentUsers);
    }
}
