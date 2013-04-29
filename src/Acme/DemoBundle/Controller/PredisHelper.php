<?php
namespace Acme\DemoBundle\Controller;

use Predis;

class PredisHelper {
	
	private $redis;

	public function __construct() {
		$this->redis = new Predis\Client('tcp://127.0.0.1:6379');
	}

	public function publish($channel, $payload) {
		$this->redis->publish($channel, $payload);
	}

	public function push($channel,$payload){
		$this->redis->lpush($channel, $payload);
	}

	public function getAllMessagesFromChannel($channel){
		$messages = $this->redis->lrange($channel,0, -1);
		return $messages;
	}

	public function get10LastMessagesFromChannel($channel){
		$channel = "chat::".$channel;
		$messages = $this->redis->lrange($channel,0,9);
		return $messages;
	}
}