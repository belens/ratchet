<?php

namespace Acme;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;

class Pusher implements WampServerInterface {
    
    /**
     * A lookup of all the channels clients have subscribed to
     **/
    public $subscribedChannels = array();

    protected $redis;

    public function timedCallback() {
        if (array_key_exists('debug', $this->subscribedChannels)) {
            $channel = $this->subscribedChannels['debug'];
            $channel->broadcast('Unix timestamp is ' . time());
        }
    }

    public function init($connection) {
        // link Predis/Async connection up with Redis
        $this->redis = $connection;
        $this->log("Plugged into Redis, now listening for incoming messages...");
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->log("onOpen ({$conn->WAMP->sessionId})");
    }

    public function onClose(ConnectionInterface $conn) {
        $this->log("onClose ({$conn->WAMP->sessionId})");
    }
    public function onSubscribe(ConnectionInterface $conn, $channel) {
        $this->log("onSubscribe");
        $this->log("session id {$conn->WAMP->sessionId}");
        $this->log("channel: $channel {$channel->count()}");


        
        
        // When a visitor subscribes to a channel link the Channel object in a lookup array
        if (!array_key_exists($channel->getId(), $this->subscribedChannels)) {
            $this->subscribedChannels[$channel->getId()] = $channel;
            $pubsubContext = $this->redis->pubsub($channel->getId(), array($this, 'pubsub'));
            $this->log("subscribed to channel $channel");
        }

        //update subscriber count on frontdesk channel
        $channelArr =  explode("::", $channel);
        if ($channelArr[0] == "frontdesk"){
            $payload = $channel->count();
            echo $payload;
           $json = json_encode(array('channel'=>$channel,'total'=>$payload));
           $channel->broadcast($json);            
        }

    }
    
    public function onUnSubscribe(ConnectionInterface $conn, $channel) {
        $this->log("onUnSubscribe");
        $this->log("topic: $channel {$channel->count()}");
    }

    /**
     * @param string
     */
    public function pubsub($event, $pubsub) {
        $this->log("pubsub");

        $this->log("kind: $event->kind channel: $event->channel payload: $event->payload");

        if (!array_key_exists($event->channel, $this->subscribedChannels)) {
            $this->log("no subscribers, no broadcast");
            return;
        }
        $channel = $this->subscribedChannels[$event->channel];


        $this->log("$event->channel: $event->payload {$channel->count()}");
        $channel->broadcast("$event->payload");

        // quit if we get the message from redis
        if (strtolower(trim($event->payload)) === 'quit') {
            $this->log("quitting...");
            $pubsub->quit();
        }
    }

    public function onCall(ConnectionInterface $conn, $id, $channel, array $params) {
        // In this application if clients send data it's because the user hacked around in console
        $this->log("onCall");
        $conn->callError($id, $channel, 'You are not allowed to make calls')->close();
    }

    public function onPublish(ConnectionInterface $conn, $channel, $event, array $exclude, array $eligible) {
        //only used with websockets, not Redis
        $this->log("onPublish");
        $channel->broadcast("$channel: $event");
    }

    /**
     * echo the message and also broadcast to channel 'debug'
     */
    public function log($value)
    {
        $message = sprintf("Pusher: %s", $value);
        echo "$message\n";
        if (array_key_exists('debug', $this->subscribedChannels)) {
            $channel = $this->subscribedChannels['debug'];
            $channel->broadcast($message);
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $this->log("onError");
    }
}
function strstr_after($haystack, $needle, $case_insensitive = false) {
    $strpos = ($case_insensitive) ? 'stripos' : 'strpos';
    $pos = $strpos($haystack, $needle);
    if (is_int($pos)) {
        return substr($haystack, $pos + strlen($needle));
    }
    // Most likely false or null
    return $pos;
}