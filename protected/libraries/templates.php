<?php
/*
 * Class handling outputting the html.
 * It uses a queue which contains the order in which it's methods will be
 * called to create the html for a page. 
 * For example: 
 * $template=new Templates();
 * $parameters=array('title'=>'title of the page');
 * $template->addPiece(array($template,'createIndexPage'),$parameters)
 * $template->addPiece(array($template,'addJavascript'))
 * $template->outputPage()
 * Ads to the execution queue the methods createHeader(), createIndexPage()
 * with the parameters $parameters, addJavascript() and createFooter(). All 
 * these methods will be called in this order, aiming to output the complete
 * html for a page.
 * The methods createHeader() and createFooter() are added automatically
 * to the queue, unless the $isAjaxRequest variable is set to true
 */
class Templates{
	protected $pieces=array();
	public $isAjaxRequest=false;
	

	/*
	 * Adds method and it's parameter if it exists to execution queue
	 * 
	 * @param callable $method
	 * @param mixed @parameters
	 * 
	 * return @void
	 */
	public function addPiece($method,&$parameters=null){
		$this->pieces[]=array('method'=>$method,'parameters'=>$parameters);
	}
	
	/*
	 * Removes all elements from execution queue. Used when an error happens.
	 */
	public function removeAllPieces(){
		$this->pieces=[];
	}
	
	public function outputPage(){
		if($this->isAjaxRequest==false){
			$this->createHeader();
		}
		foreach($this->pieces as &$piece){
			if (!empty($piece['parameters'])) {
				$piece['method']($piece['parameters']);
			}else{
				$piece['method']();
			}
		}
		if($this->isAjaxRequest==false){
			$this->createFooter();
		}
	}
/*
 * Displays javascript used for sending the question form through ajax.
 * The target url of  the ajax request is hardcoded. The response to the
 * ajax request is expected to be a part of a html file containing and element 
 * with the id "container". The content of the element is inserted in the 
 * current page, in the element with the id "container", allowing for the 
 * content of question form to be changed without reloading the page.
 * The javascript function also checks that at least one answer is selected
 * from the available answers.
 */
	public function addJavascript(){
		?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script type="text/javascript">
function submitForm() {
	$('form').submit(function(event) {
		var formData = {
			'answers': $('input:checkbox[name="answers[]"]:checked').map(function() {
				return $(this).val();
			}).get(),
			'questionId':$('input[name=questionId]').val()
		};
		
		if (formData['answers'].length > 0) {
			$.ajax({
				type        : 'POST', 
				url         : '?answerQuestion=1&ajax=1', 
				data        : formData,
				dataType    : 'html',
				encode          : true
			})
			.done(function(data) {
				console.log(data);
				$('div#container').html($(data).filter('#container').html());
				submitForm()
			});
		}else{
			alert('You must select an answer.');
		}
        event.preventDefault();
    });
}
$(document).ready(submitForm);
</script>
<?php
	}
	/*
	 * Outputs part of the index page
	 * 
	 * @param array $parameters array('tests'=>array(array('id'=>integer id,'name'=>'test name')),
	 * 'message'=>array('error message 1','error message 2')) 
	 */
	public function createIndexPage(&$parameters){
?><div class="formContainer">
	<div class="title">
		<h1>Technical Task</h1>
	</div>
	<div>
		<?php
		foreach($parameters['messages'] as $message){
			?><p style="color:red"><?php echo $message ?></p><?php
		}
		?>
		<form action="?takeTest=1" method="post">
			<input type="text" placeholder="Enter your name" name="username" class="username" required>
			<br>
			<select name="test" class="testSelect" required>
				<option disabled="disabled" selected="selected">Choose test</option>
				<?php
				foreach($parameters['tests'] as $test){
					?><option value="<?php echo $test['id']?>"><?php echo $test['name']?></option><?php
				}
				?>
			</select>
			<div class="buttonContainer">
				<input type="submit" value="Start" style="align:center">
			</div>
		</form>
	</div>
</div>
<?php
	}
	
	/*
	 * Outputs part of the question page
	 * 
	 * @param array $question array('question'=>array('id'=>integer id,'text'=>'question text'),
	 * 'availableAnswers'=>array(array('id'=>integer id,'text'=>'answer text')),
	 * 'percentOfUnansweredQuestionsInTest'=>float between 0.0 and 100.0
	 * )
	 */
	public function createQuestionPage(&$question){
		?>
		<div class="formContainer" id="container">
			<div class="titleContainer">
				<h1><?php echo $question['question']['text']?></h1>
			</div>
			<div>
				<form method="post" action="?answerQuestion=1">

<div>
	<?php
	foreach($question['availableAnswers'] as $key=>&$availableAnswer){
	?>
<div class="jbjocrb <?php 
		if ($key%2==0 ){
			?>floatLeft<?php 
		} else {
			?>floatRight<?php 
		}?> ">
	<input type="checkbox" value="<?php echo $availableAnswer['id'] ?>" name="answers[]" id="a<?php echo $key ?>">
	<label for="a<?php echo $key ?>"><?php echo $availableAnswer['text'] ?></label>
</div>
	<?php
		if($key%2==1){
			?><br style="clear:both"><?php
		}
	}
	?>

<br style="clear:both">
<div class="progressBar">
  <span style="width: <?php echo $question['percentOfUnansweredQuestionsInTest']?>%"></span>
</div>
<br style="clear:both">
					<div class="title">
						<input type="hidden" value="<?php echo $question['question']['id']?>" name="questionId">
						<input type="submit" value="Next">
					</div>
					
				</form>
			</div>
		</div>
		<?php
	}	
	
	/*
	 * Outputs part of the page with the test result
	 * 
	 * @param array $parameters array('username'=>'user username',
	 * 'numberOfQuestionsAnswerredCorrectly'=>integer,
	 * 'numberOfQuestions'=>integer
	 * ) 
	 */
	public function createResultsPage(&$parameters){
?><div class="formContainer" id="container">
	<div class="title">
		<h1>Thanks, <?php echo $parameters['username'] ?></h1>
	</div>
	<div>You've responded correctly to <?php echo $parameters['numberOfQuestionsAnswerredCorrectly'] ?> out of <?php echo $parameters['numberOfQuestions'] ?> questions.</div>
</div>
<?php
	}
	
	public function errorPage(){
		?>An error occured.<?php
	}
	
	public function displayErrorPage(){
		$this->removeAllPieces();
		$this->addPiece(array($this,'errorPage'));

	}
	public function createHeader(){
		?><html>
	<head>
	<link rel="stylesheet" href="/static/main.css">
	</head>
	<body><?php 
	}
	
	public function createFooter(){
		?></body>
</html>
<?php 
	}

}
