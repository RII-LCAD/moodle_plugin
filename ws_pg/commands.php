<?php
function hello($name){
	return "Hello $name\n";
}
//function user_files($array, $field1, $value1,$field2,$value2,$url,$token)
function user_files($array, $field1, $value1,$field2,$value2){
	$user_submitions = array();
	foreach ($array as $key => $obj){
		$item = get_object_vars($obj);
   		if ( $item[$field1] === $value1 and $item[$field2] === $value2){
   			//$item['url'] = $url."/webservice/pluginfile.php/".$item['contextid']."/assignsubmission_file/submission_files/".$item['itemid']."/".$item['filename']."?forcedownload=1&token=".$token;
		    $aux= array('index' => $key,'item' => $item );
		    array_push($user_submitions, $aux);
		}
   	}
   	return $user_submitions;
}







//function result_query($dominioMoodle,$tokenMoodle){
function result_query($PGSQL){

$sql = "SELECT mdl_course.id AS course,mdl_course_modules.id AS instanceid, mdl_grade_grades.userid,COALESCE(files_user.contextid, 0) AS contextid, COALESCE(files_user.itemid, 0) AS itemid,COALESCE(files_user.filename, '') AS filename, mdl_grade_grades.rawgrademin, mdl_grade_grades.rawgrademax, mdl_grade_grades.id AS id_grade_grades,mdl_course_modules.idnumber AS  idnumber, COALESCE( (SELECT mdl_grade_grades_professor.finalGRADE FROM mdl_grade_grades AS mdl_grade_grades_professor, mdl_user WHERE mdl_grade_grades_professor.ID = mdl_grade_grades.id AND mdl_grade_grades.usermodified = mdl_user.id AND mdl_user.username <> 'soap'),-1) AS notaProfessor, mdl_course.shortname AS course_name, mdl_assignsubmission_onlinetext.onlinetext AS resposta, mdl_course.shortname AS course_name, mdl_assignsubmission_onlinetext.onlinetext AS resposta FROM mdl_course INNER JOIN mdl_course_modules ON mdl_course_modules.course = mdl_course.id INNER JOIN mdl_assign ON mdl_course.id = mdl_assign.course AND mdl_course_modules.instance = mdl_assign.id INNER JOIN mdl_assign_submission ON mdl_assign.id = mdl_assign_submission.assignment LEFT JOIN mdl_assignsubmission_onlinetext ON mdl_assign_submission.id = mdl_assignsubmission_onlinetext.submission LEFT JOIN mdl_modules ON mdl_modules.id = mdl_course_modules.module INNER JOIN mdl_grade_items ON mdl_assign.id = mdl_grade_items.iteminstance AND mdl_modules.name = mdl_grade_items.itemmodule INNER JOIN mdl_grade_grades ON mdl_grade_items.id = mdl_grade_grades.itemid AND mdl_assign_submission.userid = mdl_grade_grades.userid LEFT JOIN (SELECT mdl_files.userid, mdl_files.contextid, mdl_files.itemid, mdl_files.filename,mdl_context.instanceid FROM mdl_context INNER JOIN mdl_files ON mdl_files.contextid = mdl_context.id AND mdl_files.component = 'assignsubmission_file' AND mdl_files.filesize > 0) AS files_user ON files_user.userid = mdl_grade_grades.userid AND files_user.instanceid = mdl_course_modules.id WHERE not(mdl_course_modules.idnumber IS NULL OR mdl_course_modules.idnumber = '') ORDER BY 1, 2, 3";


$sqlFeed = "SELECT * FROM mdl_assignfeedback_comments";
$sqlNames = "SELECT mdl_user.id, mdl_user.firstname, mdl_user.lastname FROM mdl_user";

$pgconn = pg_connect("host=".$PGSQL['servidor']." dbname=".$PGSQL['banco']." user=".$PGSQL['usuario']." password=".$PGSQL['senha']) or die("connection failed");
//$MySQLi = new MySQLi($MySQL['servidor'], $MySQL['usuario'], $MySQL['senha'], $MySQL['banco']);


// verificacao Mysql:  se ocorreu um erro e exibe a mensagem de erro
//if (mysqli_connect_errno())
//    trigger_error(mysqli_connect_error(), E_USER_ERROR);


// Fim do codigo de configuracao DB

// Executa a consulta OU mostra uma mensagem de erro
//$MySQLi->set_charset("utf8");
$result_data = pg_query($pgconn, $sql) or die("Erro na pesquisa");

//$result_data = $MySQLi->query($sql) OR trigger_error($MySQLi->error, E_USER_ERROR);
$result_names = pg_query($pgconn, $sqlNames) or die("Erro na pesquisa");

//$result_names =  $MySQLi->query($sqlNames) OR trigger_error($MySQLi->error, E_USER_ERROR);
$result_feedbacks = pg_query($pgconn, $sqlFeed) or die("Erro na pesquisa");
//$result_feedbacks = $MySQLi->query($sqlFeed) OR trigger_error($MySQLi->error, E_USER_ERROR);



$count = 0;
if($result_data){
	// Cycle through results
	while ($anexo = pg_fetch_object($result_data,$count)){
		$anexos[] = $anexo;
		$count++;
	}
	// Free result set
	pg_free_result($result_data);
}

$count = 0;
if($result_names){
    while ($name = pg_fetch_object($result_names,$count)){
        $names[] = $name;
	$count++;
    }
    // Free result set
    pg_free_result($result_names);
}
$count = 0;
if($result_feedbacks){
    while ($feedback = pg_fetch_object($result_feedbacks,$count)){
        $feedbacks[] = $feedback;
	$count++;
    }
    // Free result set
    pg_free_result($result_feedbacks);
}


header('Content-type: application/json');
//$MySQLi->close();
pg_close($pgconn);

//return json_encode(array('anexos' => $anexos, 'feedbacks' => $feedbacks));
return json_encode(array('anexos' => $anexos,'users' => $names,'feedbacks' => $feedbacks, 'sql' => $sql));
//return "OK";



}



function atualizaQuestoes($sql,$questoesJson)
{
	$input = json_decode($questoesJson);
 	// para cada questao

	for ($i = 0; $i < count($input); $i++)
	{
		//$input[$i]->id_grade_grades;
		atualizaQuestao($sql,$input[$i]->id_grade_grades, $input[$i]->nota, 0, $input[$i]->feedback);
		//array_push($aux,$out);
	}

	//se tudo correr bem retorna true, caso contrario uma lista de erros, que o chamador registrará no log;
	return "OK";
}

function atualizaQuestao ($PGSQL,$id, $nota, $professor, $feedback)
{


	$pgconn = pg_connect("host=".$PGSQL['servidor']." dbname=".$PGSQL['banco']." user=".$PGSQL['usuario']." password=".$PGSQL['senha']) or die("connection failed");


	//echo "ID: $id\nNota: $nota\nFeedback: $feedback\n";
	if ($nota >= 0){
		if(is_null($feedback)){
			echo "$id sem feedback\n";
			
			$query = "UPDATE mdl_grade_grades SET RAWGRADE = '".pg_escape_string($nota)."',finalgrade = '".pg_escape_string($nota)."',timemodified =  (select extract(epoch from now())),usermodified = (select id from mdl_user where username = 'wsmoodle') WHERE ID = '".pg_escape_string($id)."' AND (usermodified not in (SELECT mdl_role_assignments.userid FROM mdl_role_assignments, mdl_context, mdl_course, mdl_grade_items WHERE mdl_context.contextlevel = 50				AND mdl_role_assignments.contextid = mdl_context.id AND mdl_context.instanceid = mdl_course.id AND mdl_course.id = mdl_grade_items.courseid AND mdl_role_assignments.roleid in (3,4) AND mdl_grade_items.id = mdl_grade_grades.itemid) OR rawgrade is null)";
		}else{
			echo "$id com feedback\n";
			
			$query = "UPDATE mdl_grade_grades SET RAWGRADE = '".pg_escape_string($nota)."' ,finalgrade = '".pg_escape_string($nota)."' ,feedback = '".pg_escape_string($feedback)."' ,feedbackformat = 1,timemodified =  (select extract(epoch from now())) ,usermodified = (select id from mdl_user where username = 'wsmoodle') WHERE ID = '".pg_escape_string($id)."' AND (usermodified not in (SELECT mdl_role_assignments.userid FROM mdl_role_assignments, mdl_context, mdl_course, mdl_grade_items WHERE mdl_context.contextlevel = 50 AND mdl_role_assignments.contextid = mdl_context.id AND mdl_context.instanceid = mdl_course.id AND mdl_course.id = mdl_grade_items.courseid AND mdl_role_assignments.roleid in (3,4) AND mdl_grade_items.id = mdl_grade_grades.itemid) OR rawgrade is null)";
		}


	}else{
		if(!is_null($feedback)){
			echo "$id Feedback sem nota\n";
	
        	$query = "UPDATE mdl_grade_grades SET feedback = '".pg_escape_string($feedback)."',
			feedbackformat = 1,
			timemodified =  (select extract(epoch from now())),
        	usermodified = (select id from mdl_user where username = 'wsmoodle')WHERE ID = '".pg_escape_string($id)."'              AND (usermodified not in (SELECT mdl_role_assignments.userid FROM mdl_role_assignments, mdl_context, mdl_course, mdl_grade_items WHERE mdl_context.contextlevel = 50 AND mdl_role_assignments.contextid = mdl_context.id AND mdl_context.instanceid = mdl_course.id  AND mdl_course.id = mdl_grade_items.courseid AND mdl_role_assignments.roleid in (3,4) AND mdl_grade_items.id = mdl_grade_grades.itemid)  OR rawgrade is null)";
		}

	}	
	
	
	if(pg_query($pgconn, $query)){
		
		
		//Fazer um select na tabela mdl_assign_grades, se existir registro, fazer um update, senão fazer um insert
		$sql = "SELECT mdl_assign_grades.id FROM mdl_grade_grades, mdl_grade_items, mdl_assign_grades where mdl_grade_grades.id = '".pg_escape_string($id)."' and mdl_grade_grades.itemid = mdl_grade_items.id and mdl_grade_items.iteminstance = mdl_assign_grades.assignment and mdl_grade_grades.userid = mdl_assign_grades.userid 	and mdl_grade_items.itemmodule = 'assign'";

		

		// Executa a consulta OU mostra uma mensagem de erro		
		$resultado = pg_query($pgconn, $sql);		

		$i = 0;
		if($resultado){
			// Cycle through results
			while ($anexo = pg_fetch_row($resultado,$i)){
				$Count[] = $anexo;
				$i++;
			}
			// Free result set
			pg_free_result($resultado);
		}
		
		
  		
		pg_free_result($resultado);
		

		$id_assign_grades = $Count[0][0];
		
		
		// Se existir registro, fazer update
		if($id_assign_grades){
			echo "Existe registro!!!\n";
		
			//$dados = $resultado->fetch_assoc();
			if ($nota >= 0){
				$query = "UPDATE mdl_assign_grades SET timemodified =  (select extract(epoch from now()))
				,grader = (select id from mdl_user where username = 'wsmoodle')
				,grade = '".pg_escape_string($nota)."'
				WHERE ID = ".$id_assign_grades;
			}else{
				$query = "UPDATE mdl_assign_grades SET timemodified = (select extract(epoch from now()))
				,grader = (select id from mdl_user where username = 'wsmoodle')
				WHERE ID = ".$id_assign_grades;
			}
			
			//$MySQLi->query($query);
			pg_query($pgconn, $query) or die("Erro na pesquisa");

	
			$query = "UPDATE mdl_assignfeedback_comments SET commenttext = '".pg_escape_string($feedback)."'WHERE grade = ".$id_assign_grades;

			pg_query($pgconn, $query) or die("Erro no update!!!\n");

		}else{ 	// Se não existir registro, fazer insert


			echo "Não existe registro!!\n";
			//echo "Nota: $nota\n";
			if ($nota >= 0){
			//	echo "Nota maior que 0!!\n";
			//	echo "ID: $id\n";
				$query = "INSERT INTO mdl_assign_grades (assignment, userid, timecreated, timemodified, grader, grade, attemptnumber) VALUES ( (SELECT mdl_grade_items.iteminstance FROM  mdl_grade_grades, mdl_grade_items where mdl_grade_grades.id = '".pg_escape_string($id)."' and mdl_grade_grades.itemid = mdl_grade_items.id and mdl_grade_items.itemmodule = 'assign'), (SELECT userid FROM mdl_grade_grades WHERE ID = '".pg_escape_string($id)."'),(select extract(epoch from now())), (select extract(epoch from now())), (select id from mdl_user where username = 'wsmoodle'), '".pg_escape_string($nota)."', 0)";
			}else{
				//echo "Sem nota!!!\n";
				$query = "INSERT INTO mdl_assign_grades (assignment, userid, timecreated, timemodified, grader, attemptnumber) VALUES (	(SELECT mdl_grade_items.iteminstance FROM  mdl_grade_grades, mdl_grade_items where mdl_grade_grades.id = '".pg_escape_string($id)."'and mdl_grade_grades.itemid = mdl_grade_items.id and mdl_grade_items.itemmodule = 'assign'), (SELECT userid FROM mdl_grade_grades WHERE ID = '".pg_escape_string($id)."'), (select extract(epoch from now())), (select extract(epoch from now())), 		(select id from mdl_user where username = 'wsmoodle'), 0)";
			}

			
			//echo "$query;\n";
			pg_query($pgconn, $query) or die("Erro no insert!\n");

			// Cria registro para feedback - inicio
			$sql = "SELECT mdl_assign_grades.id	FROM mdl_grade_grades, mdl_grade_items, mdl_assign_grades where mdl_grade_grades.id = '".pg_escape_string($id)."' and mdl_grade_grades.itemid = mdl_grade_items.id 	and mdl_grade_items.iteminstance = mdl_assign_grades.assignment and mdl_grade_grades.userid = mdl_assign_grades.userid and mdl_grade_items.itemmodule = 'assign'";

		
			$resultado = pg_query($pgconn, $sql) or die("Erro na pesquisa");
			
			$i=0;
			while ($row=pg_fetch_row($resultado,$i)){
  				for($j=0; $j < count($row); $j++){
     					$Count[]= $row[$j];

				}
  			}

			pg_free_result($resultado);
			$id_assign_grades = $Count[0];			


			$query = "INSERT INTO mdl_assignfeedback_comments (commenttext, assignment, grade, commentformat) VALUES ('".pg_escape_string($feedback)."', (SELECT mdl_grade_items.iteminstance FROM  mdl_grade_grades, mdl_grade_items where mdl_grade_grades.id = '".pg_escape_string($id)."' and mdl_grade_grades.itemid = mdl_grade_items.id and mdl_grade_items.itemmodule = 'assign'),".$id_assign_grades.",1)";

			$resultado = pg_query($pgconn, $query) or die("Erro na pesquisa");

			// Cria registro para feedback - fim
		}

		return "Nota Atualizada.";
	}else{
		return "Database Error: Unable to update record.";
	}
}
?>
