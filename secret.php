<?php
	ini_set('session.cookie_domain', '' );
	ini_set('session.use_cookies', '' );
  	ini_set('session.use_only_cookies', '' );
  	ini_set('session.cookie_httponly', '' );
  	ini_set('session.use_trans_sid', '' );
  	ini_set('session.cache_limiter', '' );
  	ini_set('session.hash_function', '' );
  	
  	/*error_reporting(-1);
	ini_set('display_errors', 'On');*/

	$mysql_host = "";
	$mysql_database = "";
	$mysql_user = "";
	$mysql_password = "";

	//conection
	$link=mysqli_connect($mysql_host,$mysql_user,$mysql_password,$mysql_database) or die("Error " . mysqli_error($link));
?>