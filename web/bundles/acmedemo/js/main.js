
jQuery(document).ready(function($) {
var sess;
var KEY_RETURN = 13;
var roomList = ['getBetter','congrats'];
var defaultRooms = ['getBetter'];
var rooms = [];
var channels = [];
var debug = false;
var username = '';
var channelTypes = ['data','chat','frontdesk'];
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
	var routeRoom = $('#routeChannel').val();
	
	
	if( $.inArray(routeRoom, defaultRooms) == -1 && routeRoom != '' ){
		for (var i = 0; i < channelTypes.length; i++) {
			subscribe_to(routeRoom, channelTypes[i]);
		};
		add_room(routeRoom);
   }

	$.each(defaultRooms, function (i, room) {
		for (var i = 0; i < channelTypes.length; i++) {
			subscribe_to(room, channelTypes[i]);
		};
		add_room(room);
		publish($('#username').val(), 'frontdesk');
	});

	$.each(roomList, function (i, room) {
		$('ul.room-list').append('<li> <a href="#" class="text-info" >' + room + ' </a></li>');
	});	

}

subscribe_to = function (room, channelType) {
	var chan = channelType + '::' + room;

	if (!add_channels(chan)){
		return false;
	}


	console.log(chan);
	sess.subscribe(chan, function (channel, event) {
 		console.log("ab: channel: " + channel + " event: " + event);
 		add_response(event, channelType);
 		notify("Message: " + event, 'info');
 	});

 	console.log("ab: subscribed to: " + chan);
 	notify("Subscribed to channel " + chan, 'success');
 	return true;
}

unsubscribe = function(channel) {
	console.log('unsubscribed');
	remove_channel(channel)
	sess.unsubscribe(channel)
	console.log("ab: unsubscribed from: " + channel)
	notify('Unsubscribed from channel ' + channel, 'warning')
}

publish = function(message,channelType) {
	$.post($('#room-post').val(), {"pub": message, "channel":get_channel(channelType)}, function (data) {
		console.log("pubsub: ajax response: " + data);
	});
}

publish_leave_room = function(message,channelType) {
	alert('hi');
	$.post($('#room-post').val(), {"del": message, "channel":get_channel(channelType)}, function (data) {
		console.log("del: ajax response: " + data);
	});
}

get_room = function () {
	return $('select.rooms').val();
}

add_room = function (room) {
	if (rooms.indexOf(room) != -1) {
		return false;
	}
	rooms.push(room);
	$('select.rooms').append('<option>' + room + '</option>');
	var messages = $('#chatMessages').val();
	var obj = $.parseJSON(messages);
	for (var j = 0; j < obj.length; j++){
		target = '#chat';
		$(target).val(function (i, val) {
			//Messages is a collection of JSON messages, hence the double parse.
			return val + $.parseJSON(obj[j]).message + "\n" ;
	});	
	}

	var dataMessages = $('#dataMessages').val();
	//console.log(dataMessages);
	var obj = $.parseJSON(dataMessages);
	for (var j = 0; j < obj.length; j++){
		target = '#response';
		$(target).val(function (i, val) {
			//Messages is a collection of JSON messages, hence the double parse.
			return val + $.parseJSON(obj[j]).message + "\n" ;
	});}
	return channels;
}
remove_room = function (room) {
	$('select.rooms option').filter(function() { return $.text([this]) === room; }).remove();
}

add_channels = function (channel) {
	if (channels.indexOf(channel) != -1) {
		return false;
	}
	channels.push(channel);
	$('ul.channels').append('<li>' + channel + '</li>');
	return channels;
}

remove_channel = function (channel) {
	i = channels.indexOf(channel);
	if (i == -1) {
		return false
	}
	channels.splice(i, 1);
	$('ul.channels li').filter(function() {return $.text([this]) === channel;}).remove();
	
	return channels;
}


get_channel = function (channelType) {
	return channelType + '::' + $('select.rooms').val();
}

notify = function (message, type) {
	n = $('#notify')
	n.stop().text(message).css({opacity: 1}).removeClass().addClass('input-block-level alert alert-' + type)
	n.delay(1000).fadeTo(2000, 0.3)
}

add_response = function (text, channelType) {
	var obj = $.parseJSON(text);
	var target = '';
	switch (channelType) {
		case 'data': 
			target = '#response';
			$(target).val(function (i, val) {
				return obj.message + "\n" + val;
			});				
			break;
		case 'chat':
			target = '#chat';
			$(target).val(function (i, val) {
				return obj.message + "\n" + val;
			});			
			break;
		case 'frontdesk':
			target = '.subscribers-total';
			console.log(obj);
			if (obj.total != null) {
				$(target).html(function (i, val) {
					return obj.total ;
				});	
			}else if (obj.subscriber != null){	
				$('.subscribers').append('<li>' + obj.subscriber + '</li>');
			}			
			break;			
		default:
			target = '#response'
			break;
	}


}
//when the subscriber leaves room, unsubscribe
$(window).on('beforeunload', function(e) {
	//alert('dsqf');
	/*$.post($('#room-post').val(), {"del": 'Sander', "channel":get_channel('frontdesk')}, function (data) {
		return("del: ajax response: " + data);
	});*/
	var username = $('#username').val();
	var data = {"del": username, "channel":get_channel('frontdesk')};
	$.post($('#room-post').val(), {"del": username, "channel":get_channel('frontdesk')}, function (data) {
		console.log("pubsub: ajax response: " + data);
	});


});

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

	var username = $('#username').val();
	$.post($('#room-post').val(), {"del": username, "channel":get_channel('frontdesk')}, function (data) {
		console.log("pubsub: ajax response: " + data);
	});

	$.each(defaultRooms, function (i, room) {
		for (var i = 0; i < channelTypes.length; i++) {
			unsubscribe(get_channel(channelTypes[i]));
		};
		remove_room(get_room());
	});	


	
});

// publish via ajax on server side
$('#pub').keypress(function (e) {
	if (e.which == KEY_RETURN) {
		publish(this.value, 'data');
		$(this).val('');
		e.preventDefault();
	}
});
$('#talk').keypress(function (e) {
	if (e.which == KEY_RETURN) {
		publish($('#username').val() + ': ' + this.value, 'chat');
		$(this).val('');
	}
});

$('.room-list').on( 'click', 'a',function (e) {
	$(e.target).toggleClass('text-info text-success').parents().siblings().find('a').removeClass('text-success');
	//TODO: switch rooms

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