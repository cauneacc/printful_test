<?php
session_start();
define('LOGGING_LEVEL',LOG_DEBUG); // dev
//shows and error page when an error happens
register_shutdown_function('shutdownFunction');

require_once(__DIR__.'/protected/libraries/db.php');
require_once(__DIR__.'/protected/libraries/templates.php');
$db=new Db();
$template=new Templates();

class Controller{
	function __construct(&$db,&$template){
		$this->db=$db;
		$this->template=$template;
	}
	
	/*
	 * Checks if the received value may be interpreted as a integer with 
	 * a length between 1 and 11 characters 
	 * 
	 * @param mixed $value
	 * @return boolean
	 */
	private function checkInt(&$value){
		return strlen($value)>0 and strlen($value)<12 and ctype_digit($value);
	}
	/*
	 * Checks if the received value is an array containing values that may be 
	 * interpreted as a integer with a length between 1 and 11 characters 
	 * 
	 * @param mixed $value
	 * @return boolean
	 */
	private function checkArrayOfInts(&$value){
		if(is_array($value)){
			foreach($value as $row){
				if((strlen($row)>0 and strlen($row)<12 and ctype_digit($row))==false){
					return false;
				}
			}
			return true;
		}else{
			return false;
		}
	}
	
	/*
	 * Checks if the received parameter is a string between 1 and 255 characters
	 * and the characters are printable
	 * 
	 * @param mixed $value
	 * @return boolean
	 * 
	 */
	private function checkString(&$value){
		return strlen($value)>0 and strlen($value)<256 and ctype_print($value);
	}
	
	/*
	 * Routes the request
	 */
	public function route(){
		if(empty($_GET) && empty($_POST)){//index page
			$this->displayIndexPage();
			/*
			 * First route after the user submits the initial form.
			 * Checks to see if the user sent the chosen test and username
			 * and if they are allowed. If there is a problem with the data
			 * sent by the user, it displays the index page.
			 * If everything is ok, it displays the form with the first question
			 * of the test.
			 */
		}elseif(isset($_GET['takeTest']) && $_GET['takeTest']=='1'){
			$errorMessages=[];
			if(!$this->checkInt($_POST['test'])){
				$errorMessages[]='You must choose a test';
			}
			if(!$this->checkString($_POST['username'])){
				$errorMessages[]='You must use a username containing numbers and letter of a minimum length of 1 character, and a maximum length of 255 characters';
			}
			if(count($errorMessages)>0){
				$this->displayIndexPage($errorMessages);
			}else{

				$this->displayFirstQuestion($_POST['username'],$_POST['test']);
			}	
		/*
		 * Displays the form for the remaining questions, or the result
		 * Checks if the user started the test, or just arrived at this page
		 */
		}elseif(isset($_GET['answerQuestion']) && $_GET['answerQuestion']=='1'){
			if(isset($_SESSION['userId']) && isset($_SESSION['testId']) && isset($_SESSION['username'])){
				$this->template->isAjaxRequest=!empty($_GET['ajax']);
				if($this->checkInt($_POST['questionId']) && $this->checkArrayOfInts($_POST['answers'])){
					$this->displayQuestionOrResults($_POST['answers'],$_POST['questionId']);
				}else{
					$this->displayFirstQuestion($_SESSION['username'],$_SESSION['testId']);
				}
			}else{
				header('Location: /', true, 302);
			}
		}else{
			trigger_error("Unknown route", E_USER_ERROR);
		}
	}
	
	/*
	 * Displays index page
	 * 
	 * @param array $messages error messages for the choose test form, 
	 * array('first error message','second error message')
	 * @return void
	 */
	function displayIndexPage($messages=array()){
		$parameters['tests']=$this->db->getAllTests();
		$parameters['messages']=$messages;
		$this->template->addPiece(array($this->template,'createIndexPage'),$parameters);
	}
	
	/*
	 * Creates the user or gets the user id by username.
	 * Chooses and displays the first question and sets user information 
	 * in the session.
	 * 
	 * @param string $username
	 * @param int $testId
	 * @return void 
	 */
	function displayFirstQuestion(&$username,&$testId){
		$userId=$this->db->getOrCreateUser($username);
		$question=$this->db->getUnansweredQuestionAndAnswersForTest($testId,$userId);
		if($question['question']!=false){

			$_SESSION['userId']=$userId;
			$_SESSION['username']=$username;
			$_SESSION['testId']=$testId;
			$this->template->addPiece(array($this->template,'addJavascript'));
			$question['percentOfUnansweredQuestionsInTest']=$this->db->getPercentOfUnansweredQuestionsInTest($testId,$userId);
			$this->template->addPiece(array($this->template,'createQuestionPage'),$question);
		}else{
			throw new Exception('No questions left');
		}
	}
	
	/*
	 * Saves in the database the answers chosen by user. If no answers are
	 * sent by the user, it just displays an unanswered question.
	 * If there are no more unanswered questions, it displays the result
	 * of the test and destroys the session.
	 * 
	 * @param array $answers array(integer id, another integer id)
	 * @param int $questionId 
	 * @return void
	 */
	function displayQuestionOrResults(&$answers,&$questionId){
		if($this->checkArrayOfInts($_POST['answers']) ){
			$this->db->markAnswersForTest($answers,$questionId,$_SESSION['testId'],$_SESSION['userId']);
		}
		$question=$this->db->getUnansweredQuestionAndAnswersForTest($_SESSION['testId'],$_SESSION['userId']);
		if(empty($question['question'])){
			$testResult=$this->db->getResultsForTest($_SESSION['testId'],$_SESSION['userId']);
			$parameters=['username'=>$_SESSION['username'],
			'numberOfQuestionsAnswerredCorrectly'=>$testResult['numberOfQuestionsAnswerredCorrectly'],
			'numberOfQuestions'=>$testResult['numberOfQuestions']];
			$this->template->addPiece(array($this->template,'createResultsPage'),$parameters);
			session_destroy();
		}else{
			//do not add javascript if the request is made with ajax
			if($this->template->isAjaxRequest==false){
				$this->template->addPiece(array($this->template,'addJavascript'));
			}
			$question['percentOfUnansweredQuestionsInTest']=$this->db->getPercentOfUnansweredQuestionsInTest($_SESSION['testId'],$_SESSION['userId']);
			$this->template->addPiece(array($this->template,'createQuestionPage'),$question);
		}
		
	}
	
	/*
	 * Outputs the page using the Template object
	 * 
	 * @return void
	 */
	function __destruct(){
		$this->template->outputPage();
	}
}
$a=new Controller($db,$template);
$a->route();


function shutdownFunction(){
	$isError = false;
	$error = error_get_last();
	if($error){
		switch($error['type']){
			case E_ERROR:
			case E_CORE_ERROR:
			case E_COMPILE_ERROR:
			case E_USER_ERROR:
				$isError = true;
				break;
		}
	}
	
	if($isError){//display an error page with the Template object
		global $template;
		$template->displayErrorPage();
		$template->outputPage();
	}
}
