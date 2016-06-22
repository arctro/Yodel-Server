<?php
	require('base.php');

	class Yodel{
		//MySQL Link
		public $link;
		//ArctroBase
		public $base;
		
		public $radius = 20;
		public $limit = 25;
		
		//Setup function
		function __construct($link){
			$this->link = $link;
			$this->base = new ArctroBase($link);
		}
		
		//Post a message
		function post_message($title, $message, $lat, $lng, $session_id=""){
			if($session_id!=""){
				$session_data = $this->base->mysqli_results("SELECT * FROM `sessions` WHERE `session_id` LIKE '". $session_id ."'");
				$user_id = $session_data['return'][0]['user_id'];
			}else{
				$user_id = 0;
			}
			
			$message = $this->base->filter_spam($message);
			
			$result = $this->base->mysqli_results("INSERT INTO `yodel`.`posts` (`id`, `user_id`, `title`, `content`, `lat`, `lng`, `post_date`) VALUES (NULL, '". $user_id ."', '". $title ."', '". $message ."', '". $lat ."', '". $lng ."', CURRENT_TIMESTAMP);");
			return $result;
		}
		
		//Make sure user can be where they are
		//If going over 100kph app will assume false
		function validate_location($lat, $lng, $old_lat, $old_long, $time){
			$radius = 6371000;
			$lat_rad = deg2rad($lat);
			$lng_rad = deg2rad($lng);
			$lat_dif = deg2rad($old_lat - $lat);
			$lng_dif = deg2rad($old_lng - $lng);
			
			$a = sin($lat_dif/2) * sin($lat_dif/2) + cos($lat_rad) * cos($lng_rad) * sin($lng_dif/2) * sin($lng_dif/2);
			$c = 2 * atan2(sqrt(a), sqrt(1-a));
			
			//Meters
			$distance = $radius * $c;
			
			//If distance is over 1000 meters
			if($distance/($time*90) > 1000){
				return false;
			}
			return true;
		}
		
		//Vote on post
		function vote($id, $value){
			$sql = "";
			
			if($value > 0){
				$sql = "UPDATE `yodel`.`posts` SET `ups` = `ups` + '1' WHERE `posts`.`id` = " . $id;
			}else{
				$sql = "UPDATE `yodel`.`posts` SET `downs` = `downs` + '1' WHERE `posts`.`id` = " . $id;
			}
			
			$result = $this->base->mysqli_results($sql)['return'];
			return $result;
		}
				
		//Get messages in area
		function get_messages($lat, $lng, $radius, $offset=0){
			$latRight = $lat + ($radius * 0.00904371733);
			$latLeft = $lat - ($radius * 0.00904371733);
			
			$lngTop = $lng - ((1/(111.320 * cos($lat))) * $radius);
			$lngBottom = $lng + ((1/(111.320 * cos($lat))) * $radius);
			
			if($lngBottom > $lngTop){
				$temp = $lngTop;
				$lngTop = $lngBottom;
				$lngBottom = $temp;
			}
			
			$offset = intval($offset);
		
			$result = $this->base->mysqli_results("SELECT * FROM `posts` WHERE `lat` > '".$latLeft."' AND `lat` < '".$latRight."' AND `lng` > '".$lngBottom."' AND `lng` < '".$lngTop."' ORDER BY `post_date` DESC LIMIT ". $this->limit ." OFFSET " . $offset)['return'];
			return $result;
		}
		
		function get_messages_hot($lat, $lng, $radius, $offset=0){
			$latRight = $lat + ($radius * 0.00904371733);
			$latLeft = $lat - ($radius * 0.00904371733);
			
			$lngTop = $lng - ((1/(111.320 * cos($lat))) * $radius);
			$lngBottom = $lng + ((1/(111.320 * cos($lat))) * $radius);
			
			if($lngBottom > $lngTop){
				$temp = $lngTop;
				$lngTop = $lngBottom;
				$lngBottom = $temp;
			}
			
			$offset = intval($offset);
		
			$result = $this->base->mysqli_results("SELECT * FROM `hot` WHERE `lat` > '".$latLeft."' AND `lat` < '".$latRight."' AND `lng` > '".$lngBottom."' AND `lng` < '".$lngTop."' LIMIT ". $this->limit ." OFFSET " . $offset)['return'];
			return $result;
		}
		
		//Get single message
		function get_message($id){
			$result = $this->base->mysqli_results("SELECT * FROM `posts` WHERE `id` = '". $id ."'")['return'][0];
			return $result;
		}
		
		//Post a comment
		function post_comment($post_id, $user, $parent_id, $content){
			$session = $this->get_session($user);
			$user_id = $session['user_id'];
			
			$content = $this->base->filter_spam($content);
			
			$result = $this->base->mysqli_results("INSERT INTO `yodel`.`comments` (`id`, `user_id`, `post_id`, `parent_id`, `content`, `date_posted`) VALUES (NULL, '". $user_id ."', '". $post_id ."', '". $parent_id ."', '". $content ."', CURRENT_TIMESTAMP)");
			return $result;
		}
		
		//Get comments
		function get_comments($post_id, $parent_id=0, $offset=0){
			$offset = intval($offset);
			
			$result = $this->base->mysqli_results("SELECT * FROM `comments` WHERE `post_id` = '". $post_id ."' AND `parent_id` = '". $parent_id ."' ORDER BY `date_posted` DESC LIMIT ". $this->limit ." OFFSET " . $offset)['return'];
			return $result;
		}
		
		function new_session($key, $user_id=0){
			$key_data = $this->get_auth($key);
			
			if(!isset($key_data['id'])){
				return false;
			}
			
			$uuid = $this->base->gen_uuid();
			$expire = date("Y-m-d H:i:s", strtotime('+24 hour'));
			
			$sql = "INSERT INTO `yodel`.`sessions` (`id`, `user_id`, `key_id`, `session_id`, `expire`) VALUES (NULL, '". $user_id ."', '". $key_data['id'] ."', '". $uuid ."', '". $expire ."')";
			$result = $this->base->mysqli_results($sql);
			
			return array("session_id"=>$uuid, "expire"=>strtotime('+24 hour'));
		}
		
		function invalidate_session($session_id){
			$sql = "UPDATE `yodel`.`sessions` SET `expire` = '2015-08-30 14:46:37' WHERE `sessions`.`session_id` LIKE '". $session_id ."'";
			$result = $this->base->mysqli_results($sql);
		}
		
		function get_session($session_id){
			$sql = "SELECT * FROM `sessions` WHERE `session_id` LIKE '". $session_id ."'";
			$result = $this->base->mysqli_results($sql);
			
			return $result;
		}
		
		function login_user($username, $password, $key){
			$sql = "SELECT * FROM `users` WHERE `email` LIKE '". $username ."'";
			$result = $this->base->mysqli_results($sql)['return'][0];
			
			$hashed_password = hash('sha256', $password . $result['salt']);
			
			if($hashed_password == $result['password']){
				return $this->new_session($key, $result['id']);
			}else{
				return array();
			}
		}
		
		function logout_user($session_id){
			$this->invalidate_session($session_id);
		}
		
		function signup_user($key, $email, $password, $phone=0){
			$salt = $this->base->gen_uuid();
			$hashed_password = hash('sha256', $password . $salt);
			
			$sql = "INSERT INTO `yodel`.`users` (`id`, `phone_number`, `email`, `password`, `salt`, `date_joined`, `vertified`) VALUES (NULL, '". $phone ."', '". $email ."', '". $hashed_password ."', '". $salt ."', CURRENT_TIMESTAMP, '0')";
			$result = $this->base->mysqli_results($sql)['insert_id'];
			
			$session = $this->new_session($key, $result);
			return $session;
		}
		
		function get_auth($key){
			$sql = "SELECT * FROM `auth_keys` WHERE `key` LIKE '". $key ."'";
			$result = $this->base->mysqli_results($sql);
			return $result['return'][0];
		}
	}
	
	/*
	Permissions:
	u = User permissions
	s = Signup permissions
	p = Post permissions
	e = Edit permissions
	d = Delete permissions
	a = Post Advertisement permissions
	k = Create Key permissions
	*/
	
	class YodelAPI{
		//MySQL Link
		public $link = null;
		//Yodel
		public $yodel = null;
		//ArctroBase
		public $base = null;
		
		//Setup function
		function __construct($link){
			$this->link = $link;
			$this->yodel = new Yodel($link);
			$this->base = new ArctroBase($link);
		}
		
		function handle_api($input){
			$request = $input['request'];
			$auth_key = $input['key'];
			
			$auth = $this->yodel->get_auth($auth_key);
			$permissions = json_decode($auth['permissions'], true);
			$enabled = $auth['enabled'];
			
			if($request == "POST_MESSAGE"){
				if($permissions['p'] == 1 && $enabled == 1){
					if(!$this->base->keys_set(array("title", "message", "lat", "lng"), $input)){
						return array("error"=>"Title, Message, Lat or Lng not entered");
					}
					return $this->yodel->post_message($input['title'],$input['message'],$input['lat'],$input['lng'],$input['session']);
				}
				return array("error"=>"Auth key invalid or not right permission");
			}
			if($request == "GET_MESSAGES"){
				if(!$this->base->keys_set(array("lat", "lng"), $input)){
					return array("error"=>"Lat or Lng not entered");
				}
				if($input['s']=='h'){
					return $this->yodel->get_messages_hot($input['lat'], $input['lng'], $this->yodel->radius, $input['offset']);
				}
				return $this->yodel->get_messages($input['lat'], $input['lng'], $this->yodel->radius, $input['offset']);
			}
			if($request == "GET_MESSAGE"){
				if(!$this->base->keys_set(array("id"), $input)){
					return array("error"=>"ID not entered");
				}
				return $this->yodel->get_message($input['id']);
			}
			if($request == "UPVOTE"){
				if($permissions['p'] == 1 && $enabled == 1){
					if(!$this->base->keys_set(array("id"), $input)){
						return array("error"=>"ID not entered");
					}
					return $this->yodel->vote($input['id'], 1);
				}
				return array("error"=>"Auth key invalid or not right permission");
			}
			if($request == "DOWNVOTE"){
				if($permissions['p'] == 1 && $enabled == 1){
					if(!$this->base->keys_set(array("id"), $input)){
						return array("error"=>"ID not entered");
					}
					return $this->yodel->vote($input['id'], -1);
				}
				return array("error"=>"Auth key invalid or not right permission");
			}
			if($request == "SIGNUP"){
				if($permissions['s'] == 1 && $enabled == 1){
					if(!$this->base->keys_set(array("email", "password"), $input)){
						return array("error"=>"Email or Password not entered");
					}
					return $this->yodel->signup_user($auth_key, $input['email'], $input['password'], $input['phone']);
				}
				return array("error"=>"Auth key invalid or not right permission");
			}
			if($request == "LOGIN"){
				if($permissions['u'] == 1 && $enabled == 1){
					if(!$this->base->keys_set(array("username", "password"), $input)){
						return array("error"=>"Username or Password not entered");
					}
					return $this->yodel->login_user($input['username'], $input['password'], $auth_key);
				}
				return array("error"=>"Auth key invalid or not right permission");
			}
			if($request == "LOGOUT"){
				if($permissions['u'] == 1 && $enabled == 1){
					if(!$this->base->keys_set(array("session"), $input)){
						return array("error"=>"Session not entered");
					}
					$this->yodel->logout_user($input['session']);
					return array();
				}
				return array("error"=>"Auth key invalid or not right permission");
			}
			if($request == "POST_COMMENT"){
				if($permissions['p'] == 1 && $auth['enabled'] == 1){
					if(!$this->base->keys_set(array("post", "parent", "content"), $input)){
						return array("error"=>"Post, Parent or Content not entered");
					}
					return $this->yodel->post_comment($input['post'], $input['session'], $input['parent'], $input['content']);
				}
				return array("error"=>"Auth key invalid or not right permission");
			}
			if($request == "GET_COMMENTS"){
				if(!$this->base->keys_set(array("post_id"), $input)){
					return array("error"=>"Post_id not entered");
				}
				return $this->yodel->get_comments($input['post_id'], $input['parent_id'], $input['offset']);
			}
			return array("error"=>"Request Invalid");
		}
	}
?>
