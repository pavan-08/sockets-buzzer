<html>
<head>
    <meta charset="UTF-8">
    <title>Soundboard</title>
    <style>
        body {
            background-color: red;
        }
    </style>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <script src="jquery.min.js"></script>
    <script type="text/javascript">
        var ranks = [];
        $(document).ready(function(){
            if (!("Notification" in window)) {
                alert("This browser does not support desktop notification");
            }
            Notification.requestPermission();
            if("WebSocket" in window){
                var ws = new WebSocket("ws://192.168.43.16:8081/socket_chat/server.php");
                ws.onopen = function () {
                    var cred = {
                        type: "soundboard"
                    };
                    ws.send(JSON.stringify(cred));
                    console.log("Soundboard is ready...");
                };
                ws.onmessage = function (evt) {
                    var received_msg = JSON.parse(evt.data);
                    console.log("Message is received...");
                    console.log(received_msg);
                    if( received_msg.win != undefined && received_msg.win === true){
                        var audio = new Audio('buzz.mp3');
                        audio.play();
                    }
                };
                ws.onclose = function() {
                    console.log("Connection closed...")
                };
            }
        });

    </script>
</head>
<body>

</body>
</html>