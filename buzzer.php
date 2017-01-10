<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Buzzer</title>
    <link rel="stylesheet" href="style.css"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <script src="jquery.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            console.log(window.name);
            if ("WebSocket" in window) {
                console.log("WebSocket is supported by your Browser!");

                // Let us open a web socket
                var ws = new WebSocket("ws://"+window.location.hostname+":8081/buzzer/server.php");

                ws.onopen = function () {
                    // Web Socket is connected, send data using send()
                    var user = {
                        name: window.name,
                        message: "hello server"
                    };
                    ws.send(JSON.stringify(user));
                    console.log("Message is sent...");
                };
                $('img').click(function() {
                    var mymessage = "pressed";
                    var myname = window.name;
                    var msg = {
                        message: mymessage,
                        name: myname
                    };
                    ws.send(JSON.stringify(msg));
                });
                ws.onmessage = function (evt) {
                    var received_msg = JSON.parse(evt.data);
                    console.log("Message is received...");
                    console.log(received_msg);
                    if( received_msg.win != undefined && received_msg.win === true && received_msg.name == window.name){
                        var audio = new Audio('buzz.mp3');
                        audio.play();
                    }
                    if(received_msg.message != null && received_msg.message == "Team registered already"){
                        alert(received_msg.message);
                        window.location.assign('index.html');
                    }
                };

                ws.onclose = function () {
                    // websocket is closed.
                    console.log("Connection is closed...");
                };
            }

            else {
                // The browser doesn't support WebSocket
                console.log("WebSocket NOT supported by your Browser!");
            }
            });
    </script>
</head>
<body><!--
<div id="sse">
    <a href="javascript:WebSocketTest()">Run WebSocket</a>
</div>-->
<img src="buzzer.png" alt="Hit Here" />
<span id="sound"></span>

</body>
</html>