<?php
error_reporting(0);
set_time_limit(0);
$host = "192.168.43.16";
$port = 8081;
$null = NULL;
$leaderboard = null;
$soundboard = null;
$socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create
socket\n");
$result = socket_bind($socket, $host, $port) or die("Could not bind to
socket\n");
$result = socket_listen($socket) or die("Could not set up socket
listener\n");
//create & add listning socket to the list
$clients = array($socket);
$users = array();
echo "Waiting for connections... \n";
while(1)
{
	//manage multiple connections
	$changed = $clients;
	//returns the socket resources in $changed array
	socket_select($changed, $null, $null, 0, 10);
	if(in_array($socket, $changed)){
		$socket_new = socket_accept($socket); //accpet new socket
		$clients[] = $socket_new; //add socket to client array
		$header = socket_read($socket_new, 1024); //read data sent by the socket
		perform_handshaking($header, $socket_new, $host, $port); //perform websocket handshake

		socket_getpeername($socket_new, $ip); //get ip address of connected socket
		echo "_______________________________________________________\n";
		echo $ip." connected\n";
		echo "_______________________________________________________\n";
		//socket_write($socket_new,"Hi client",1024);
		//make room for new socket
		$found_socket = array_search($socket, $changed);
		unset($changed[$found_socket]);
	}

	foreach ($changed as $changed_socket) {

		//check for any incomming data
		while(socket_recv($changed_socket, $buf, 1024, 0) >= 1)
		{
			$received_text = unmask($buf); //unmask data
			$tst_msg = json_decode($received_text); //json decode
			$user_name = $tst_msg->name; //sender name
			$user_message = $tst_msg->message; //message text
			if(isset($tst_msg->type) && $tst_msg->type == "leaderboard"){
				if($leaderboard == null) {
					$leaderboard = $changed_socket;
					echo "Leaderboard connected!\n";
				} else{
					socket_close($changed_socket);
					echo "bogus leader closed\n";
				}
			}

			if(isset($tst_msg->type) && $tst_msg->type == "soundboard"){
				if($soundboard == null) {
					$soundboard = $changed_socket;
					echo "Soundboard connected!\n";
				} else{
					socket_close($changed_socket);
					echo "bogus soundboard closed\n";
				}
			}

			if($user_message != null && $user_message == "hello server"){
				if(in_array($user_name,$users)){
					$reply = mask(json_encode(array("name"=>$user_name, "message"=>"Team registered already")));
					@socket_write($changed_socket,$reply,1024);
					socket_close($changed_socket);
				} else {
					$users[] = $user_name;
				}
			}
			if($user_message != null && $user_name != null && $leaderboard!=null && $soundboard != null && $tst_msg->winner == null) {
				if($user_message == "hello server"){

				} else {
					echo "$user_name $user_message\n";
					$response = mask(json_encode(array('name' => $user_name, 'message' => $user_message,)));
					@socket_write($leaderboard, $response, 1024);
				}
			}
			if($tst_msg->winner != null){
				echo $tst_msg->name. " pressed first\n";
				$resp = mask(json_encode(array('name'=>$tst_msg->name, 'win'=> true)));
				/*foreach($clients as $cli) {
					if($cli != $leaderboard)
						@socket_write($cli, $resp, 1024);
				}*/
				@socket_write($soundboard, $resp, 1024);
			}
			break 2; //exist this loop
		}

		$buf = @socket_read($changed_socket, 1024, PHP_NORMAL_READ);
		if ($buf === false) { // check disconnected client
			// remove client for $clients array
			$found_socket = array_search($changed_socket, $clients);
			socket_getpeername($changed_socket, $ip);
			if($changed_socket == $leaderboard){
				echo "leaderboard disconnected!!\n";
				$leaderboard = null;
			}
			if($changed_socket == $soundboard){
				echo "soundboard disconnected!!\n";
				$soundboard = null;
			}
			unset($clients[$found_socket]);
			echo "$ip disconnected\n";
/*
			//notify all users about disconnected connection
			$response = mask(json_encode(array('type'=>'system', 'message'=>$ip.' disconnected')));
			send_message($response);*/
		}
	}
}
socket_close($socket);

function perform_handshaking($receved_header,$client_conn, $host, $port)
{
	$headers = array();
	$lines = preg_split("/\r\n/", $receved_header);
	foreach($lines as $line)
	{
		$line = chop($line);
		if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
		{
			$headers[$matches[1]] = $matches[2];
		}
	}

	$secKey = $headers['Sec-WebSocket-Key'];
	$secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
	//hand shaking header
	$upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
		"Upgrade: websocket\r\n" .
		"Connection: Upgrade\r\n" .
		"WebSocket-Origin: $host\r\n" .
		"WebSocket-Location: ws://$host:$port/demo/shout.php\r\n".
		"Sec-WebSocket-Accept:$secAccept\r\n\r\n";
	socket_write($client_conn,$upgrade,strlen($upgrade));
}

//Encode message for transfer to client.
function mask($text)
{
	$b1 = 0x80 | (0x1 & 0x0f);
	$length = strlen($text);

	if($length <= 125)
		$header = pack('CC', $b1, $length);
	elseif($length > 125 && $length < 65536)
		$header = pack('CCn', $b1, 126, $length);
	elseif($length >= 65536)
		$header = pack('CCNN', $b1, 127, $length);
	return $header.$text;
}

//Unmask incoming framed message
function unmask($text) {
	$length = ord($text[1]) & 127;
	if($length == 126) {
		$masks = substr($text, 4, 4);
		$data = substr($text, 8);
	}
	elseif($length == 127) {
		$masks = substr($text, 10, 4);
		$data = substr($text, 14);
	}
	else {
		$masks = substr($text, 2, 4);
		$data = substr($text, 6);
	}
	$text = "";
	for ($i = 0; $i < strlen($data); ++$i) {
		$text .= $data[$i] ^ $masks[$i%4];
	}
	return $text;
}


?>