<?php	
	class ArctroBase{
		//MySQL Link
		public $link;
		
		//Setup function
		function __construct($link){
			$this->link = $link;
		}
		
		//Get POST results, if not set, get GET results
		function get_post($name){
			if(isset($_POST[$name])){
				return $_POST[$name];
			}else{
				return $_GET[$name];
			}
		}
		
		//GET POST Secured
		function safe_get_post($name){
			return string_safe(get_post($name));
		}
		
		//Secure String
		function string_safe($string){
			return mysqli_real_escape_string($this->link, strip_tags($string));
		}
		
		function mysqli_results($query, $exclude=[]){
			$query_exec = mysqli_query($this->link, $query);
			$return = array("return"=>[], "insert_id"=>mysqli_insert_id($this->link));
			
			while($row=mysqli_fetch_assoc($query_exec)){
				for($i=0;$i<count($exclude);$i++){
					$row[$exclude[$i]]="";
				}
				$return["return"][] = $row;
			}
			
			return $return;
		}
		
		function filter_spam($string){
			return $string;
		}
		
		//Check if all keys have a value assigned
		function keys_set($keys, $array){
			for($i = 0; $i < count($keys); $i++){
				if(!isset($array[$keys[$i]])){
					return false;
				}
			}
			return true;
		}
		
		function gen_uuid() {
		    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        		// 32 bits for "time_low"
        		mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
		
		        // 16 bits for "time_mid"
        		mt_rand( 0, 0xffff ),

        		// 16 bits for "time_hi_and_version",
       		 	// four most significant bits holds version number 4
        		mt_rand( 0, 0x0fff ) | 0x4000,

        		// 16 bits, 8 bits for "clk_seq_hi_res",
        		// 8 bits for "clk_seq_low",
        		// two most significant bits holds zero and one for variant DCE1.1
        		mt_rand( 0, 0x3fff ) | 0x8000,

        		// 48 bits for "node"
        		mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    		);
		}
	}
?>