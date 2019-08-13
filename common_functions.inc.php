<?php
//filters the input data for SQL injection sanitization
function filter($in)
{
	$out = trim($in); // Kills needless whitespace
    $out = strip_tags($out); // Kills html tags

    // if magic quotes is not enabled addslashes to protect from sql injection
    if (!get_magic_quotes_gpc()) 
    {
        $out = addslashes($out);
    }
    $out= htmlentities($out, ENT_QUOTES, "UTF-8");
    return $out;
}
#this function is used to retreive form data, after it has been submitted
#additional checks are to be applied
function GetFormData($field_name, $data_type)
{
	#$field_name	->	name of the form field	
	#$data_type		->	0 for POST, 1 for GET, 2 for REQUEST
	
	#returns the form data after filtering
	#move the filter function inside this function to save processing time
	
	if($data_type==0)
		$sFormData = filter(isset($_POST[$field_name])?$_POST[$field_name]:'');
	elseif($data_type==1)
		$sFormData = filter(isset($_GET[$field_name])?$_GET[$field_name]:'');
	elseif($data_type==2)
		$sFormData = filter(isset($_REQUEST[$field_name])?$_REQUEST[$field_name]:'');
		
	return $sFormData;
	
}
# function for redirecting to any specific url.
function redirect()
{
	$json_response				= array();
	$json_response['result'] 	= -10;//Error code for login
	$json_response['message'] 	= "You are not currently logged in";
	$json_response['data'] 		= "Auth failed";
	echo json_encode($json_response, JSON_UNESCAPED_SLASHES);
	die();		
}

#Return the difference between two time stamps
function datediff($start_date, $end_date, &$difference, &$diff_secs=0)
{
	#parse date into arrays
	$i_sdate = strtotime($start_date);
	$f_sdate = strtotime($end_date);
	
	#difference in seconds
	$d_s = $f_sdate - $i_sdate;
	$diff_secs = $d_s;
	
	$d_m = floor($d_s/60);
	$d_s = $d_s - ($d_m * 60);
	
	$d_h = floor($d_m/60);
	$d_m = $d_m - ($d_h * 60);
	
	$d_d = floor($d_h/24);
	$d_h = $d_h - ($d_d * 24);
	
	$difference['seconds'] = $d_s;
	$difference['minutes'] = $d_m;
	$difference['hours'] = $d_h;
	$difference['days'] = $d_d;
}

# Adds some interval to a date,the interval should always be in seconds
# Can take negative values too, so one can subtract date/time too
function dateadd($start_date, $interval)
{	
	#parse date into arrays
	$sdate = date_parse($start_date);
	
	#unix timestamp
	$i_sdate = mktime($sdate['hour'],$sdate['minute'],$sdate['second'],$sdate['month'],$sdate['day'],$sdate['year']);
	
	#add the interval, 
	$f_sdate = $i_sdate + $interval;
	
	$final_date = date('Y-m-d H:i:s',$f_sdate);	
	return $final_date;	
}
  
# determine direction in english notation
function get_direction($bearing)
{
	if(($bearing >= 0)&&($bearing <= 22.5))
  		$direction = "N";
  	elseif(($bearing > 22.5)&&($bearing <= 67.5))
  		$direction = "NE";
  	elseif(($bearing > 67.5)&&($bearing <= 112.5))
	  	$direction = "E";
  	elseif(($bearing > 112.5)&&($bearing <= 157.5))
  		$direction = "SE";
  	elseif(($bearing > 157.5)&&($bearing <= 202.5))
  		$direction = "S";
  	elseif(($bearing > 202.5)&&($bearing <= 247.5))
  		$direction = "SW";
  	elseif(($bearing > 247.5)&&($bearing <= 292.5))
  		$direction = "W";
  	elseif(($bearing > 292.5)&&($bearing <= 337.5))
  		$direction = "NW";
  	elseif(($bearing > 337.5)&&($bearing <= 360))
  		$direction = "N";
  	else
		$direction = "N";
		
  	return $direction;
}	

# format date time according to user selected format
function format_date_display($input_date)
{
	$time_stamp = strtotime($input_date);
	$final_date = date($GLOBALS['USER_DATE_FORMAT'], $time_stamp);
	return $final_date;
}

// Validates date as per comments in http://php.net/manual/en/function.checkdate.php
function validateDate($date, $format = 'Y-m-d H:i:s')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}

function validateTime($time)
{
	return preg_match('/^([0-1][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/', trim($time));
}

//Generate random string
function generate_random_string($length = 10, $letters = 'qwertyuiopasdfghjklzxcvbnm')
{
	$s = '';
	$lettersLength = strlen($letters)-1;

	for($i = 0 ; $i < $length ; $i++)
	{
		$s .= $letters[rand(0,$lettersLength)];
	}
	return $s;
} 

//general function to create hashes
function create_hash_value($string_to_hash, $salt='')
{
	try
	{
		if($salt!='')
		{
			$string_to_hash = substr($salt,0,round(strlen($salt)/2)) . $string_to_hash . substr($salt,round(strlen($salt)/2));
		}
		$temp_data=hash('sha512', $string_to_hash);
		return $temp_data;
	}
	catch (Exception $e)
	{
		# Handle error
		$message = ShowErrorMessages($e->getMessage(),$e->getCode(),$e->getFile(),$e->getLine(),$e->getTraceAsString());
		return 9999;
	}	
}

// generate json function
function generate_json($result=0, $message="", $data=array(), $api_version="1")
{
	$json_response['result'] = $result;
	$json_response['version'] = $api_version;
	$json_response['message'] = $message;
	$json_response['data'] = $data;
	echo json_encode($json_response, 64);
	die();
}

//++++++++++++++++++++++++++++++++++++++
// CLI only funcitons below this line
//++++++++++++++++++++++++++++++++++++++

// This function searches the command line parameter for parameter values. 
// The parameter can be in any order now.
// Just pass the parameter name, "--" and "=" are automatically added
// DEPRECATED FUNCTION - Replaced by the function below which creates an array of command line parameters
function search_param($param_name, $param_array, $default_value='-1')
{
	//only work in CLI mode
	if(PHP_SAPI=='cli')
	{
		for($iCounter=1;$iCounter<count($param_array);$iCounter++)
		{
			$param_pos = strpos($param_array[$iCounter],$param_name . "=");
			if($param_pos==2)
			{
				$param_value = str_replace("--$param_name=", "", $param_array[$iCounter]);
				return  $param_value;
			}
			elseif($param_pos==1)
			{
				$param_value = str_replace("-$param_name=", "", $param_array[$iCounter]);
				return  $param_value;
			}
		}

		//if it doesn't matches anything, set to default value
		return $default_value;
	}
}

// This functions returns an array of all command line arguments passed in a key=>value pair.
function cmd_line_args()
{
	global $argc;
	global $argv;
	// Only work if it's running in CLI mode
	if(PHP_SAPI=='cli')
	{
		// First argument is always the file name
		$cmd_array['script_name'] = $argv[0];

		// Looop through all the command line argumnets
		for($arg_counter=1;$arg_counter<$argc;$arg_counter++)
		{
			$argument_array = explode("=",str_replace("--","",$argv[$arg_counter]));
			$cmd_array[$argument_array[0]] = $argument_array[1]; 
		}
		return $cmd_array;
	}
}

//This function is used to show the debug messages if the debug mode is on
function debug_show($message, $force_show=0,$message_type=0)
{
	//do not work if -1 has been passed
	if($force_show==-1)
		return 0;

	//only work in CLI mode
	if(PHP_SAPI=='cli')
	{
		global $debug_mode;

		if(($debug_mode==1) || ($force_show==1))
		{
			// Either the edbug mode is on, or force show is on
			echo $message . "\n";
			return 0;
		}
		elseif($force_show==-1)
		{
			//if the message is not be displayed in any case, simply return 0
			return 0;
		}
	}
}


//this function acts as a wrapper to echo coloured text on terminals
function echo_colored($message, $fg_color=37, $bg_color=0)
{
	//only work in CLI mode
	if(PHP_SAPI=='cli')
	{
		/*
		0	Reset all attributes
		1	Bright
		2	Dim
		4	Underscore	
		5	Blink
		7	Reverse
		8	Hidden

			Foreground Colours
		30	Black
		31	Red
		32	Green
		33	Yellow
		34	Blue
		35	Magenta
		36	Cyan
		37	White

			Background Colours
		40	Black
		41	Red
		42	Green
		43	Yellow
		44	Blue
		45	Magenta
		46	Cyan
		47	White
		*/

		echo "\033[" . $bg_color . ";" . $fg_color . "m" . $message . "\033[40;37m";
	}
}

function no_to_words($no)
{   
	$words = array('0'=> '' ,'1'=> 'one' ,'2'=> 'two' ,'3' => 'three','4' => 'four','5' => 'five','6' => 'six','7' => 'seven','8' => 'eight','9' => 'nine','10' => 'ten','11' => 'eleven','12' => 'twelve','13' => 'thirteen','14' => 'fouteen','15' => 'fifteen','16' => 'sixteen','17' => 'seventeen','18' => 'eighteen','19' => 'nineteen','20' => 'twenty','30' => 'thirty','40' => 'fourty','50' => 'fifty','60' => 'sixty','70' => 'seventy','80' => 'eighty','90' => 'ninty','100' => 'hundred','1000' => 'thousand','100000' => 'lakh','10000000' => 'crore');
    if($no == 0)
        return ' ';
	else if($no < 0)
        return ' --- ';
    else 
	{		
		$novalue='';
		$highno=$no;
		$remainno=0;
		$value=100;
		$value1=1000;       
		while($no>=100)    
		{
			if(($value <= $no) &&($no  < $value1))    
			{
				$novalue=$words["$value"];
				$highno = (int)($no/$value);
				$remainno = $no % $value;
				break;
			}
                $value= $value1;
                $value1 = $value * 100;
		}       
		if(array_key_exists("$highno",$words))
			return $words["$highno"]." ".$novalue." ".no_to_words($remainno);
		else 
		{
			$unit=$highno%10;
			$ten =(int)($highno/10)*10;            
			
			return $words["$ten"]." ".$words["$unit"]." ".$novalue." ".no_to_words($remainno);
		}
	}
}


// file exclusive locking mechanism starts here 

// function to create lock file
function lock_file($file_name='',$restrict_root=0)
{
	global $fp_lock;
	global $dbConn;
	
	// not to be run as root
	if(posix_geteuid() == 0 && $restrict_root == 0)
	{
		echo_colored("Can not run as root user. \n",31,47);
		$dbConn->close();
		die;
	}

	$processUser = posix_getpwuid(posix_geteuid());
	// Check if another instance is already running. If yes, then exit	

	$current_file_name = $file_name;
	if($current_file_name == '')
		$current_file_name = str_replace(".php", "", basename($_SERVER["SCRIPT_NAME"]));

	$fp_lock = fopen("/tmp/" . $processUser['name'] . "." . $current_file_name . ".lock", "c");
	if (!flock($fp_lock, LOCK_EX | LOCK_NB)) 
	{
		echo_colored("Another instance already running.\n",31,47);
		$dbConn->close();
		die();
	}
}

// function to delete locked file
function unlock_file($file_name='')
{
	global $fp_lock;
	global $dbConn;
	$processUser = posix_getpwuid(posix_geteuid());
	// Check if another instance is already running. If yes, then exit
	
	$current_file_name = $file_name;
	if($current_file_name == '')
		$current_file_name = str_replace(".php", "", basename($_SERVER["SCRIPT_NAME"]));

	flock($fp_lock, LOCK_UN);																		// release the file lock
	fclose($fp_lock);																				// close the file pointer
	unlink("/tmp/" . $processUser['name'] . "." . $current_file_name . ".lock");					// delete the file
	$dbConn->close();
}
// file exclusive locking mechanism ends here

// function to add actions happened in modules like login and table/row level changes
function action_log_add($select_table_name, $select_condition, $log_user_id, $log_table)
{
	global $dbConn;
	$user_ip	= get_ip_address();
	$dbConn->FetchAllData("select row_to_json(t) as row_to_json from (select * from $select_table_name where $select_condition) t", $arLogArray, $iILRows);
	$json_data = $arLogArray[0]['row_to_json'];
	$dbConn->ExecuteQuery("insert into tab_logs(log_user_id,log_data,log_table,user_ip) values ($log_user_id,'{$json_data}','{$log_table}','$user_ip')",$iLGRows);
}

// get current request ip address
function get_ip_address()
{
	if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != '') {
		$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		$ip_address = $_SERVER['REMOTE_ADDR'];
	}	
	if(PHP_SAPI=='cli')
	{
		$ip_address = '127.0.0.1';		
	}
	if($ip_address == '::1')
		$ip_address = '127.0.0.1';
	
	return $ip_address;	
}
?>