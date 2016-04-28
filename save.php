<?php
	ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);
// 	print_r($_POST);
	require_once('config.php');	
	require_once('lib/db.php');
	$db = new Db( 'mysql', $config['db']['hostname'], $config['db']['dbname'], $config['db']['username'], $config['db']['password'] );
	
	$select = $db->select( 'responses', '*', array( 'user_id' => $_POST['user_id'], 'lti_id' => $_POST['lti_id'] ));
		
	$exists = false;
	while ( $row = $select->fetch() ) {
		$exists = true;
		$db->update( 'responses', 'positions', json_encode($_POST['positions']), $row->id );
	}
	if(!$exists) {
		$db->create( 'responses', array( 'user_id' => $_POST['user_id'], 'lti_id' => $_POST['lti_id'] ,'positions' => json_encode($_POST['positions']) ) );
	}
	return '{"status":"true"}';
?>