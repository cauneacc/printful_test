<?php
/*
 * Class handling the interaction with the database
 * 
 * To create a database connection it uses information from the config/db.php file
 */
class Db{
	public $pdo;
	function __construct(){
		require(__DIR__.'/../config/db.php');
		$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
		$options = [
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
		];
		try {
			 $this->pdo = new PDO($dsn, $user, $pass, $options);
		} catch (\PDOException $e) {
			 throw new \PDOException($e->getMessage(), (int)$e->getCode());
		}
	}
	
	function getAllTests(){
		return $this->pdo->query('SELECT * FROM tests');
	}
	

/*
 * Returns an unanswered question and the available answers for a given test id and user id
 * 
 * 
 * @return array array('question'=>array('id'=>integer id,'text'=>'the text of the question'),
 * 'availableAnswers'=>array('id'=>integer id,'text'=>'the text of the answer')) or 
 * array('question'=>false,'availableAnswers'=>false) if there are no unanswered questions
 */
	function getUnansweredQuestionAndAnswersForTest($testId,$userId){
		$sql='SELECT q.id, q.text
			FROM questions AS q 
			INNER JOIN test_question_lookup AS tq 
			ON (q.id=tq.question_id)
			WHERE tq.test_id=:testId AND q.id NOT IN (
				SELECT question_id 
				FROM user_answer_lookup 
				WHERE user_id=:userId 
				AND test_id=:testId2) 
				LIMIT 1;';
		$stmt=$this->pdo->prepare($sql);
		$stmt->execute([':testId' => $testId,':userId' => $userId,':testId2' => $testId]);
		$question = $stmt->fetch();

		$sql='SELECT aa.*
			FROM question_answer_lookup AS qa
			INNER JOIN available_answers AS aa
			ON (aa.id=qa.answer_id)
			WHERE qa.question_id=:questionId;
			';
		$stmt=$this->pdo->prepare($sql);
		$stmt->execute([':questionId' => $question['id']]);
		$availableAnswers= $stmt->fetchAll();
		return array('question'=>$question,'availableAnswers'=>$availableAnswers);
	}
	
	function markAnswersForTest($answers,$questionId,$testId,$userId){
		$result=false;
		if(is_array($answers)){
			foreach($answers as $answerId){
				$sql='INSERT INTO user_answer_lookup (user_id, answer_id, question_id, test_id) 
				VALUES (:userId, :answerId, :questionId, :testId);';
				$stmt=$this->pdo->prepare($sql);
				$result=$stmt->execute([':userId'=>$userId,':answerId'=>$answerId,':questionId' => $questionId,':testId'=>$testId]);
				if($result==false){
					break;
				}
			}
		}
		return $result;
	}

/*
 * Creates a user and returns it's user id if the user does not exist or
 * it returns the id of user, if the user exists;
 * 
 * @param string $username
 * @return int user id
 */	
	function getOrCreateUser($username){
		$sql='SELECT id
			FROM users 
			WHERE username=:username';
		$stmt=$this->pdo->prepare($sql);
		$stmt->execute([':username' => $username]);
		$aux=$stmt->fetch();
		if (empty($aux)){
			$sql='INSERT INTO users (username) 
				VALUES (:username);';
			$stmt=$this->pdo->prepare($sql);
			$stmt->execute([':username' => $username]);
			$sql='SELECT id
				FROM users 
				WHERE username=:username';
			$stmt=$this->pdo->prepare($sql);
			$stmt->execute([':username' => $username]);
			$aux=$stmt->fetch();

		}
		return $aux['id'];
	}
/*
 * Returns the number of questions answered correctly and the number of questions
 * for a given test id and user id.
 * A question is considered to have been answered correctly, if the user chose all
 * the correct answers and did not choose any incorrect answer 
 * 
 * @return array array('numberOfQuestionsAnswerredCorrectly'=>integer,
		'numberOfQuestions'=>integer)
 */
	function getResultsForTest($testId,$userId){
		$sql='SELECT qa.question_id,qa.answer_id 
		FROM tests AS t
		INNER JOIN test_question_lookup AS tq
		ON (t.id=tq.test_id)
		INNER JOIN question_answer_lookup AS qa
		ON (tq.question_id=qa.question_id)
		WHERE qa.correct_answer=1
		AND test_id=:testId';
		$stmt=$this->pdo->prepare($sql);
		$stmt->execute([':testId' => $testId]);
		$correctAnswers=$stmt->fetchAll();
		$auxCorrectAnswers=array();
		foreach($correctAnswers as $cAnswer){
			if(isset($auxCorrectAnswers[$cAnswer['question_id']])){
				$auxCorrectAnswers[$cAnswer['question_id']]=[$cAnswer['answer_id']];
			}else{
				$auxCorrectAnswers[$cAnswer['question_id']][]=$cAnswer['answer_id'];
			}
		}

		$sql='SELECT question_id,answer_id 
			FROM user_answer_lookup
			WHERE user_id=:userId
			AND test_id=:testId';
		$stmt=$this->pdo->prepare($sql);
		$stmt->execute([':testId' => $testId,':userId'=>$userId]);
		$userAnswers=$stmt->fetchAll();
		$auxUserAnswers=[];
		foreach($userAnswers as $uAnswer){
			if(isset($auxUserAnswers[$uAnswer['question_id']])){
				$auxUserAnswers[$uAnswer['question_id']]=[$uAnswer['answer_id']];
			}else{
				$auxUserAnswers[$uAnswer['question_id']][]=$uAnswer['answer_id'];
			}
		}
		$numberOfQuestionsAnsweredWrong=0;
		foreach($auxCorrectAnswers as $questionId=>&$value){
			if(isset($auxUserAnswers[$questionId])){
				if(count(array_merge(array_diff($auxUserAnswers[$questionId], $value), array_diff($value, $auxUserAnswers[$questionId])))>0){
					$numberOfQuestionsAnsweredWrong=$numberOfQuestionsAnsweredWrong+1;	
				}
			}else{
				$numberOfQuestionsAnsweredWrong=$numberOfQuestionsAnsweredWrong+1;
			}
		}
		$numberOfQuestionsAnswerredCorrectly=count($auxCorrectAnswers)-$numberOfQuestionsAnsweredWrong;
		return array('numberOfQuestionsAnswerredCorrectly'=>$numberOfQuestionsAnswerredCorrectly,
		'numberOfQuestions'=>count($auxCorrectAnswers));
	}
/*
 * Returns the percent of questions that have an answer from all available
 * questions, for a given user id and test id
 * 
 * @return float between 0.0 and 100.0
 */
	function getPercentOfUnansweredQuestionsInTest($testId,$userId){
		$sql='SELECT (
				(SELECT count(*) 
					FROM (
						SELECT count(*) 
						FROM user_answer_lookup 
						WHERE user_id=:userId 
						AND test_id=:testId 
						GROUP BY question_id) AS aux
				)/(
				SELECT count(*) 
				FROM test_question_lookup 
				WHERE test_id=:testId))*100 AS r;';
		$stmt=$this->pdo->prepare($sql);
		$stmt->execute([':testId' => $testId,':userId'=>$userId]);
		return $stmt->fetchColumn();
		
	}
}























