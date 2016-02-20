<html>
    <head>
        <meta charset="UTF-8">
        <title>LeaderBoard</title>
        <link rel="stylesheet" href="style.css"/>
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <script src="jquery.min.js"></script>
        <script type="text/javascript">
            var ranks = [];
            function notify(data){
                if (!("Notification" in window)) {
                    alert("This browser does not support desktop notification");
                }
                var options = {
                    icon: 'buzzer.png',
                    body: 'pressed first'
                };
                switch(Notification.permission){
                    case "granted":
                        var n = new Notification(data.name, options);
                        break;
                    case "denied":
                        break;
                    default:
                        Notification.requestPermission(function (permission) {
                            // If the user accepts, let's create a notification
                            if (permission === "granted") {
                                var notification = new Notification(data.name, options);
                            }
                        });
                }
            }
            $(document).ready(function(){
                if (!("Notification" in window)) {
                    alert("This browser does not support desktop notification");
                }
                Notification.requestPermission();
                if("WebSocket" in window){
                    var ws = new WebSocket("ws://192.168.1.104:8081/socket_chat/server.php");
                    ws.onopen = function () {
                        var cred = {
                            type: "leaderboard"
                        };
                        ws.send(JSON.stringify(cred));
                        console.log("Leaderboard is ready...");
                    };
                    ws.onmessage = function (e) {
                        var data = JSON.parse(e.data);
                        var done = false;
                        ranks.forEach(function(element){
                            if(element.name == data.name)
                                done = true;
                        });
                        if(!done){
                            $('table').append("<tr><td>"+(ranks.length+1)+"</td><td>"+ data.name+"</td></tr>");
                            ranks.push(data);
                            console.log(ranks);
                            if(ranks.length == 1){
                                data.winner = true;
                                ws.send(JSON.stringify(data));
                                notify(data);
                            }
                        }

                    };
                    $('#reset').click(function (){
                        ranks = [];

                        $('table').empty();
                        $('table').append("<tr><th>Sr. No.</th><th>Team name</th></tr>");

                    });
                    ws.onclose = function() {
                      console.log("Connection closed...")
                    };
                }
            });

        </script>
    </head>
    <body>
        <table border="1">
            <tr><th>Sr. No.</th><th>Team name</th></tr>
        </table>
        <p id="reset">Reset</p>
    </body>
</html>