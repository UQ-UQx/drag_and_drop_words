<?php require_once('inc/header.php'); ?>
<script src="www/js/jquery-simulate.min.js"></script>
<script type='text/javascript' src="https://tools.ceit.uq.edu.au/audioplayer/load.js"></script>

</head>
<body>
<?php
	$onlycorrect = false;
	$audioanswer_url = '';
	$instructions = '';
	
	$lti->requirevalid();
	$text = "<p>You need to add text=blah into your ^LTI settings^.</p>";
	if(isset($_POST['custom_text'])) {
		$text = $_POST['custom_text'];
	}
	if(isset($_POST['custom_onlycorrect'])) {
		$onlycorrect = $_POST['custom_onlycorrect'];
	}
	
	if(isset($_POST['custom_audioanswer'])) {
		$audioanswer_url = $_POST['custom_audioanswer'];
	}
	
	if(isset($_POST['custom_instructions'])) {
		$instructions = $_POST['custom_instructions'];
	}


	$answer_text = '';
	$entry = '';
	$vars = array();
	$falsevars = array();
	$currentvar = '';
	$invar = false;
	$count = 1;
	for($i=0; $i<strlen($text); $i++) {
		if($text[$i] != '^') {
			if($invar) {
				$currentvar .= $text[$i];
			} else {
				$entry .= $text[$i];
			}
			$answer_text .= $text[$i];
		} else {
			if($invar) {
				$vars[] = array('tag'=>$count-1,'text'=>$currentvar);
				$currentvar = '';
				$invar = false;
				$answer_text .= '</span>';
			} else {
				$invar = true;
				$entry .= "<span id='drop_".$count."' data-tag='".$count."' class='droppable'></span>";
				$count += 1;
				$answer_text .= '<span>';
			}
		}
	}
	shuffle($vars);
	
	
	//ankith - false options
	$falseoptionsText = "";
	$falseoptions = array();
	if(isset($_POST['custom_falseoptions'])){
		$falseoptionsText = $_POST['custom_falseoptions'];
		if(strlen($falseoptionsText) > 0){
			$falseoptions = explode(',', $falseoptionsText);
		}
	}
	
	
?>


<script>
var secret_answer = "<?php echo $answer_text; ?>";



var prepopulated = false;
var positions = {
<?php	
for($i=1; $i<=sizeOf($vars); $i++) {
	echo '_'.$i.':0,';
}
?>
};
<?php	
$select = $db->select( 'responses', '*', array( 'user_id' => $lti->user_id(), 'lti_id' => $lti->lti_id() ));
while ( $row = $select->fetch() ) {
	echo "positions = ".$row->positions.";";
	echo "prepopulated = true;";
	break;
}
?>
$(function() {
	$( ".draggable" ).draggable({
		revert: function(event, ui) {
			$(this).data("uiDraggable").originalPosition = {
                top : 0,
                left : 0
            };
            // return boolean
            return !event;
		}
	});
	$( ".droppable" ).droppable({
		drop: function( event, ui ) {
			$(this).css({'border-color':'green'});
			var xdiff = parseInt(ui.draggable.css('left'),10) - (ui.draggable.offset().left - $(this).offset().left);
			var ydiff = parseInt(ui.draggable.css('top'),10) - (ui.draggable.offset().top - $(this).offset().top) + 1;
			ui.draggable.css('left',xdiff);
			ui.draggable.css('top',ydiff);
			positions['_'+$(this).data('tag')] = ui.draggable.data('tag');
			if(!prepopulated) {
				save();
			}
		},
		out: function(event, ui) {
			$(this).css({'border-color':'#369'});
			if(!prepopulated) {
				positions['_'+$(this).data('tag')] = 0;
				save();
			}
		},
		accept: function(el) {
			<?php if($onlycorrect) { ?>
			if($(el).data('tag') != $(this).data('tag')) {
				return false;
			}
			<?php } ?>
			return true
		}
	});
});
$('document').ready(function() {
	

	addTouch(".drags");
	var largestWidth = 10;
	$('.draggable').each(function() {
		if($(this).width() > largestWidth) {
			largestWidth = $(this).width();
		}
	});
	largestWidth = largestWidth+50;
	$('.draggable').css({'width':largestWidth});
	$('.droppable').css({'width':largestWidth});
	if(prepopulated) {
		for(var pos in positions) {
			if(positions[""+pos] != 0) {
				autodrag("#drag_"+positions[""+pos],"#drop"+pos);
			} else {
				console.log("BAD POSITION");
				console.log(positions);
			}
		}
		prepopulated = false;
	}
	//Ankith - enables check and show answer buttons to recognise touchevents as they get overridden by the drag and drop touch change
	$(".check-button").on({ 'touchend' : function(){ 
		check();
	}});
	$(".answer-button").on({ 'touchend' : function(){ 
		showAnswers();
	}});
	var audioanswer_url = "<?php echo $audioanswer_url?>";

	if(audioanswer_url != ''){
		$(".audioplayer").css('display','block');
		
	}
	
	var instructions = "<?php echo $instructions ?>";
	console.log(instructions);
	
	if(instructions != ''){
		$(".instructions").css('display','block');
	}
	
});
function autodrag(drag,drop) {
	var draggable = $(drag);
	var droppable = $(drop);
	
	var droppableOffset = droppable.offset();
	var draggableOffset = draggable.offset();
	var dx = droppableOffset.left - draggableOffset.left;
	var dy = droppableOffset.top - draggableOffset.top;
	
	draggable.simulate( "drag", {
		dx: dx,
	    dy: dy
	});
}
function check() {
	var correct = true;
	for(var position in positions) {
		if(position != '_'+positions[position]) {
			correct = false;
		}
	}
	save();
	if(correct) {
		$('#result').html('<p class="correct"><i class="fa fa-check"></i></p>');
	} else {
		$('#result').html('<p class="incorrect"><i class="fa fa-close"></i></p>');
	}
}
function save() {
	var data = {'positions':positions,'user_id':'<?php echo $lti->user_id(); ?>','lti_id':'<?php echo $lti->lti_id(); ?>'};
	$.ajax({
	type: "POST",
	  url: "save.php",
	  data: data,
	  success: function() {
		  
	  }
	});
}
function showAnswers() {
	if($('.answer-button').text() == 'Show Answer') {
		$('#textAnswer').html(secret_answer);
		$('#answer').css({'display':'block'});
		$('.answer-button').text('Hide Answer');
	} else {
		$('#textAnswer').html("");
		$('#answer').css({'display':'none'});
		$('.answer-button').text('Show Answer');		
	}
}
//Ankith - Allows Touch to be recognised
function addTouch(touchContainer){
     $(touchContainer).children().bind('touchstart touchmove touchend touchcancel', function(){
        var touches = event.changedTouches,    first = touches[0],    type = ""; 
        switch(event.type){    
          case "touchstart": type = "mousedown"; 
        break;    
          case "touchmove":  type="mousemove"; 
        break;            
          case "touchend":   type="mouseup"; 
        break;    
          default: return;
        }

        var simulatedEvent = document.createEvent("MouseEvent");
        simulatedEvent.initMouseEvent(type, true, true, window, 1,
                          first.screenX, first.screenY,
                          first.clientX, first.clientY, false,
                          false, false, false, 0/*left*/, null);
        first.target.dispatchEvent(simulatedEvent);
        event.preventDefault();
      });
}
</script>

<div class='instructions'><?php echo $instructions ?><hr></div>

<div class='content'><?php echo $entry; ?></div>
<div class='drags'>
	<?php
	foreach($vars as $var) {
	?>
	<div class='draggable' id="drag_<?php echo $var['tag']; ?>" data-tag='<?php echo $var['tag']; ?>'><?php echo $var['text']; ?></div>
	<?php
	}
	?>
	<?php
	foreach($falseoptions as $false) {
	?>
	<div class='draggable falseDrag' ><?php echo $false; ?></div>
	<?php
	}
	?>
	<div style='clear:left'></div>
</div>
<p>&nbsp;</p>
<div id='result'></div>
<div id='answer'>
	<p class="heading">CORRECT ANSWER</p>
	<div id="answeraudio" class="audioplayer" data-audiofile="<?php  echo $audioanswer_url ?>" data-hidetimeline="false" data-showsubtitles="false" ></div>
	<div id='textAnswer'></div>
</div>
<p>
<a href='javascript:check();' class='btn btn-default check-button'>Check</a> <a href='javascript:showAnswers();' class='btn btn-default answer-button'>Show Answer</a>
</p>

<style>
	#answer p.heading {
		color:#aaa;
		font-size:0.9em;
		font-style: normal;
	}
	div#result .correct {
		color:green;
		font-size:220%;
	}
	div#result .incorrect {
		color:red;
		font-size:220%;
	}
	div#answer {
		color:#333;
		font-size:120%;
		box-shadow: inset 0 0 0 1px #eee;
		margin:20px 0;
		border:1px solid #ddd;
		padding:9px 15px 20px;
		border-radius:3px;
		display:none;
	}
	div#answer span {
		font-weight: bold;
	}
	div.content {
		border:1px solid #ccc;
		padding:10px;
		line-height:3em;
	}
	div.content span.droppable {
		width:200px;
		border:1px dotted #369;
		display:inline-block;
		border-radius:3px;
		height:30px;
		vertical-align:middle;
	}
	div.draggable {
		border:1px solid #666;
		padding:3px 5px;
		float:left;
		background:#eee;
		cursor: hand;
		cursor: move;
		text-align: center;
		border-radius:3px;
		outline: 1px solid transparent;
	}
	div.drags div {
		margin:10px;
	}
	div.drags {
		margin-top:20px;
		border:1px solid #333;
	}
	div.audioplayer{
		display:none;
	}
	div.instructions{
		display:none;
	}
</style>


</body>
</html>