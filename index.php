<!DOCTYPE html>
<html>
<head>
<title>wordchat</title>

<link href="/templates/wordsauce/favicon.ico" rel="shortcut icon" type="image/x-icon" />
<link rel="stylesheet" href="wordsauce.css" type="text/css" />
<style type="text/css">
body {
  background: none repeat scroll 0 0 #133B19;
  margin: 0;
  padding: 0;
  position: relative;
}
#messages {
	border: 1px solid #111;
	border-radius: 10px 10px 10px 10px;
	overflow: auto;
	padding: 5px 10px;
	width: 600px;
	height: 450px;
	background: rgba(0,0,0,.67);
}
#msg { width: 500px; }
#msg, #name {
	border-radius: 10px;
	padding: 10px;
	background: #000;
	color: #00ff00;
	border: none;
	letter-spacing: .67px;
	font-size: 115%;
}
#send { 
	float: right;
	margin: 0;
	padding: 15px;
	background: #020;
	border: none;
	border-radius: 10px;
	width: 80px;
	color: #FFF;
	text-transform: uppercase;	
} 
#send:hover, #send:focus { background: #004200; }
#msg-wrap { float: left; margin-top: -125px; position: relative; }
#users {
	border: none;
	border-radius: 10px 10px 10px 10px;
	color: #C0C0C0;
	float: left;
	margin: -66px -20px;
	text-align: center;
	width: 200px;
	height: 460px;
	background: rgba(0,0,0,.5);
}
#users h2 { 
	border-bottom: none;
	font-size: 16px;
	font-weight: bold;
	margin: 0;
	padding: 10px;
	-webkit-border-top-right-radius: 10px;
	-moz-border-radius-topright: 10px;
	border-top-right-radius: 10px;
	-webkit-border-top-left-radius: 10px;
	-moz-border-radius-topleft: 10px;
	border-top-left-radius: 10px;
	background: #001100;
}
#names {
	height: 370px;
	line-height: 22px;
	overflow: auto;
	padding: 5px;
	color: rgba(255,255,255,.45);
}
.controls {
	padding: 10px 5px;
}
.yourname {
	margin: 0 5px;
}
.err {
	color: red;
	display: none;
	margin: 0 0 0 10px;
}
.uname { font-weight: bold; margin-right: 5px; font-size: 90%; }
.uname:hover { color: #FFF; }

.dice { background: url(images/icon-dice.png) 0 0 no-repeat; width: 16px; height: 15px; display: inline-block; }

.dice2 { background-position: -16px 0; }
.dice3 { background-position: -32px 0; }
.dice4 { background-position: -48px 0; }
.dice5 { background-position: -64px 0; }
.dice6 { background-position: -80px 0; }

</style>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js" type="text/javascript"></script>
<script src="http://ec2-204-236-244-195.compute-1.amazonaws.com/socket.io/socket.io.js" type="text/javascript"></script>
<script type="text/javascript">
jQuery(function ($) {
		
	var myname = '';

	var socket = io.connect('http://ec2-204-236-244-195.compute-1.amazonaws.com/'); 

	socket.on('connect', function() {
		myname = $('#name').val();
		socket.emit('join', { name: myname });
	});

	socket.on('history', function (message) {
		var msgs = message.msg;
		for (var i = 0; i < msgs.length; i++) {
			display_msg(msgs[i]);
		}
		$('#messages').prop('scrollTop', $('#messages').prop('scrollHeight'));
	});

	socket.on('names', function (message) {
		var names = message.names;
		var namelist = $('#names');
		namelist.html('');
		$.each(names, function (idx, name) {
			if (!name) return;
			namelist.append(name + '<br/>');
		});
	});

	$('#name').change(function () {
		socket.emit('name change', { oldname: myname, newname: $('#name').val() });
	});

	socket.on('name change ok', function (message) {
		myname = message.newname;
	});
	socket.on('name taken', function (message) {
		$('#name').val(myname);
		// flash error
		flash_err('Name already taken!');
	});

	socket.on('chat', function (message) {
		display_msg(message);
		$('#messages').stop().animate({ scrollTop: $('#messages').prop('scrollHeight') });
	});
	socket.on('stfu', function () { 
		flash_err('Max Length Exceeded!');
	});

	var send_msg = function () {
		var message = $('#msg').val();
		if (message.length == 0) return;
		socket.emit('msg', { msg: message, name: myname });
		$('#msg').val('');
	};

	$('#send').click(send_msg);
	$(document).keypress(function (e) {
		if (e.keyCode == 13) {
			send_msg();
		}
	});

	var linkify_msg = function (msg) {
		var re = /(^|\s)(((ftp|https?):\/\/)?[\w-]+(\.[\w-]+)+\.?(:\d+)?(\/\S*)?)/ig;
		
		var match = msg.match(re);
		if (match) {
			for (var i = 0; i < match.length; i++) {
				url = match[i].trim();
				if (/^(ftp|https?)/.test(url) == false) {
					url = "http://" + url;
				}
				msg = msg.replace(match[i], '<a href="'+url+'">'+match[i]+'</a>');
			}
		}
		
		return msg;
	};
	
	var process_msg = function (msg) {
		msg = msg.replace(/batduck/g, '<img src="images/icon-batduck.png" />');
		msg = msg.replace(/sitting\sduck/g, '<img src="images/icon-duckwalk.png" />');
		
		msg = msg.replace(/rolldice/g, '<a class="dice dice' + Math.ceil(Math.random()*6) + '" /></a>');
		return linkify_msg(msg);
	};
	
	var display_msg = function (msg) {
		$('#messages').append($('<strong class="uname">'+msg.name+':</strong> '+process_msg(msg.msg)+'<br />'));
	};

	var flash_err = (function () {
		var tid = 0;
		return function (err) {
			$('.err').css('display','none').text(err).fadeIn();
			if (tid) clearTimeout(tid);
			tid = setTimeout(function () {
				$('.err').fadeOut();
			}, 2500);
		};
	})();
	
	$('#ws-header').click(function() {
    	$(this).toggleClass('front');
    	return false;
	});
});
</script>
<meta name="viewport" content="width=device-width, initial-scale=0.75">
</head>

<body>
	<div id="ws-page">
		<div id="ws-wrapper">
			<div class="ws-left">
				<div id="ws-content">
					<div id="ws-top">
	                	<a class="logo" href="index.php">wordsauce: cross-contaminated &amp; multi-dimensional</a>
	                </div>
	            	<div id="ws-header" class="front">
		            </div>
		            <div id="component">
						<div id="msg-wrap">
							<div id="messages">
							</div>
							<div class="controls">
								<input type="text" id="msg" tabindex="1" /> <input id="send" type="submit" value="send" tabindex="2" /> 
							</div>
							<div class="controls">
								<label class="yourname" for="name">Your Name: <input id="name" value="anonymous" tabindex="3" /></label>
								<span class="err"></span>
							</div>
						</div>
						<div class="clr"></div>
					</div>
				</div>
			</div>
			<div class="ws-right">
				<div id="users">
					<h2>Users</h2>
					<div id="names">
					</div>
				</div>
				<div class="kitty">
					<img width="113" alt="kitty cat" src="/images/kitty.png" />
				</div>
			</div>
			<div class="clr"></div>
		</div>
		<div class="clr"></div>
	</div>
</body>
</html>
