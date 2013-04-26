
jQuery(document).ready(function($) {
var sess;
var KEY_RETURN = 13;
var channels = [];
var defaultChannels = ['card:getBetter','card:congrats'];
var debug = false;


// connect to WAMP server
console.log(ab);
ab.connect("ws://local.ratchet.com:8081",
  	// WAMP session was established
  	function (session) {
        // things to do once the session has been established
 		console.log("ab: session connected")
 		sess = session
 		on_connect()
  	},

  	// WAMP session is gone
  	function (code, reason) {
    	// things to do once the session fails
    	notify(reason, 'error');
    	console.log("ab: session gone code " + code + " reason " + reason)
  	}
);

on_connect = function() {
	// initialise default channels
	console.log("ab: subscribing to default channels");

	// if you go to /pubsub/banana, this will check if there's a default banana channel, if there is not, it is added to the dropdown and it becomes the current active channel.
	var channel = $('#routeChannel').val();
	if( $.inArray(channel, defaultChannels) == -1 ){
      subscribe_to(channel);
		add_channel(channel);
   }
	$.each(defaultChannels, function (i, el) {
		subscribe_to(el);
		add_channel(el);
	});
}

subscribe_to = function (chan) {
	if (!add_channel(chan)) {
		return false;
	}

	sess.subscribe(chan, function (channel, event) {
 		console.log("ab: channel: " + channel + " event: " + event);
 		add_response(event);
 		notify("Message: " + event, 'info');
 	});

 	console.log("ab: subscribed to: " + chan);
 	notify("Subscribed to channel " + chan, 'success');
 	return true;
}

unsubscribe = function(channel) {
	remove_channel(channel)
	sess.unsubscribe(channel)
	console.log("ab: unsubscribed from: " + channel)
	notify('Unsubscribed from channel ' + channel, 'warning')
}

redis_publish = function(message) {
	$.post($('#room-post').val(), {"pub": message, "channel":get_channel()}, function (data) {
		console.log("pubsub: ajax response: " + data + "TEST");
	});
}
redis_publish_chat = function(message) {
	$.post($('#room-post').val(), {"pub": message, "channel":get_channel_chat()}, function (data) {
		console.log("pubsub: ajax response: " + data);
	});
}

add_channel = function (channel) {
	if (channels.indexOf(channel) != -1) {
		return false;
	}
	channels.push(channel);
	$('ul.channels').append('<li>' + channel + '</li>');
	$('select.channels').append('<option>' + channel + '</option>');
	return channels;
}

remove_channel = function (channel) {
	i = channels.indexOf(channel)
	if (i == -1) {
		return false
	}
	channels.splice(i, 1)
	$('ul.channels li').filter(function() { return $.text([this]) === channel; }).remove();
	$('select.channels option').filter(function() { return $.text([this]) === channel; }).remove();
	return channels
}

get_channel = function () {
	return $('select.channels').val();
}
get_channel_chat = function (){
	return 'chat:' + $('select.channels').val().split(':')[1];
}

notify = function (message, type) {
	n = $('#notify')
	n.stop().text(message).css({opacity: 1}).removeClass().addClass('input-block-level alert alert-' + type)
	n.delay(1000).fadeTo(2000, 0.3)
}

add_response = function (text, target) {
	console.log("--" + target);
	if (!target) {
		target = '#response'
	}

	$(target).val(function (i, val) {
		return text + "\n" + val;
	});
}


// subscribe to a channel
$('#sub').keypress(function (e) {
	if (e.which == KEY_RETURN) {
		if (subscribe_to(this.value)) {
			$(this).val('');
		}
	}
});

// unsubscribe to channel
$('#unsub').click(function () {
	channel = get_channel()
	unsubscribe(channel)
});

// publish via ajax on server side
$('#redispub').keypress(function (e) {
	if (e.which == KEY_RETURN) {
		redis_publish(this.value);
		$(this).val('');
	}
});
$('#talk').keypress(function (e) {
	if (e.which == KEY_RETURN) {
		redis_publish_chat(this.value);
		$(this).val('');
	}
});

$('select.channels').change(function(){
	var chatChannel = get_channel_chat();
	sess.subscribe(chatChannel, function (channel, event) {
		add_response(event, '#chat');
	});
	console.log("ab: subscribed to "+ chatChannel  + " channel");
	notify("Subscribed to chat channel", 'success');	
});

// debug stream
$('#debug').click(function () {
	if (!debug) {
		sess.subscribe('debug', function (channel, event) {
	 		add_response(event, '#debug-output');
	 	});
	 	console.log("ab: subscribed to debug channel");
	 	notify("Subscribed to debug channel", 'success');
	 	$(this).removeClass('btn-primary').addClass('btn-danger').text('Disable')
	 	debug = true
	} else {
		sess.unsubscribe('debug')
		notify('Unsubscribed from debug channel', 'warning')
		$(this).removeClass('btn-danger').addClass('btn-primary').text('Enable')
		debug = false
	}
});
	
});