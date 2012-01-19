
var redis = require('redis').createClient();
 
redis.on('error', function (err) {
	console.log('Redis error: ' + err);
});

var PORT = 1337
var io = require('socket.io').listen(PORT);

console.log('listening on http://localhost:' + PORT + '/ ...');

var MAX_LEN = 4096;

var names = [];
var msg_history = [];

redis.lrange("wordchat.history", "0", "-1", function (err, replies) {
	replies.forEach(function (reply) {
		msg_history.unshift(JSON.parse(reply));
	});
});

io.sockets.on('connection', function (socket) {
	socket.emit('history', { msg: msg_history });

	socket.on('join', function (message) {
		socket.set('name', message.name, function () {
			names.push(message.name);
			io.sockets.emit('names', {names: names});
		});
	});

	socket.on('name change', function (message) {
		if (names.indexOf(message.newname) == -1) {
			socket.set('name', message.newname, function () {
				var oldidx;
				if ((oldidx = names.indexOf(message.oldname)) != -1) {
					names.splice(oldidx, 1);
				}
				names.push(message.newname);
				io.sockets.emit('names', {names: names});
				socket.emit('name change ok', message);
			});
		}
		else {
			socket.emit('name taken');
		}
	});

	socket.on('disconnect', function () {
		socket.get('name', function (err, name) {
			var oldidx;
			if ((oldidx = names.indexOf(name)) != -1) {
				names.splice(oldidx, 1);
			}
			io.sockets.emit('names', {names: names});
		});
	});

	socket.on('msg', function (message) {
		if (message.msg && message.msg.length <= MAX_LEN) {
			redis.lpush('wordchat.history', JSON.stringify(message), function () {
				redis.ltrim('wordchat.history', '0', '200');
			});
			msg_history.push(message);
			msg_history = msg_history.slice(-200);
			io.sockets.emit('chat', message);
		}
		else {
			socket.emit('stfu');
		}
	});
});

