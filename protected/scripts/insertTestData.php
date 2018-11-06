<?php
/*
 * script that creates test data for the application
 * ex: php insertTestData.php
 */

require_once(__DIR__.'/../libraries/db.php');
$db=new Db();
function runQuery($sql){
	global $db;
	echo $sql.PHP_EOL;
	$db->pdo->query($sql);

}
$sql='delete from test_question_lookup';
runQuery($sql);
$sql='delete from question_answer_lookup';
runQuery($sql);
$sql='delete from questions';
runQuery($sql);
$sql='delete from tests';
runQuery($sql);
$sql='delete from available_answers';
runQuery($sql);

$questionId=1;
$answerId=1;
for ($testId=1;$testId<6;$testId++){
	$sql='insert into tests (id,name) values ('.$testId.',\'test'.$testId.'\')';
	runQuery($sql);
	for ($questionCounter=1;$questionCounter<15;$questionCounter++){
		$sql='insert into questions (id, text) values ('.$questionId.',\'test'.$testId.' question'.$questionCounter.'\')';
		runQuery($sql);
		$sql='insert into test_question_lookup (test_id, question_id) values ('.$testId.','.$questionId.')';
		runQuery($sql);
		for ($answerCounter=1;$answerCounter<rand(4,8);$answerCounter++){
			$sql='insert into available_answers (id, text) values ('.$answerId.',\'answer'.$answerId.'\')';
			runQuery($sql);
			$sql='insert into question_answer_lookup (question_id, answer_id, correct_answer) values ('.$questionId.','.$answerId.','.rand(0,1).')';
			runQuery($sql);
			$answerId=$answerId+1;
		}
		$questionId=$questionId+1;
	}
}
