function playSound() {
    document.querySelector('#sound').innerHTML= '<audio controls="controls"> <source src="buzz.mp3" hidden="true" type="audio/mp3" /> </audio>';
}

$(document).ready(function(){
    $('img').click(function() {
        $.ajax({url: 'pressed.php?sid='+window.sid, success: function(result, status){
            if(result === "first") {
                var audio = new Audio('buzz.mp3');
                audio.play();
            }
        }, async: true});
    });
});