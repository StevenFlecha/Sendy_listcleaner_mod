<?php 
	include('includes/config.php');
	//--------------------------------------------------------------//
	function dbConnect() { //Connect to database
	//--------------------------------------------------------------//
	    // Access global variables
	    global $mysqli;
	    global $dbHost;
	    global $dbUser;
	    global $dbPass;
	    global $dbName;
	    global $dbPort;
	    
	    // Attempt to connect to database server
	    if(isset($dbPort)) $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
	    else $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
	
	    // If connection failed...
	    if ($mysqli->connect_error) {
	        fail();
	    }
	    
	    global $charset; mysqli_set_charset($mysqli, isset($charset) ? $charset : "utf8");
	    
	    return $mysqli;
	}
	//--------------------------------------------------------------//
	function fail() { //Database connection fails
	//--------------------------------------------------------------//
	    print 'Database error';
	    exit;
	}
	// connect to database
	dbConnect();
?>
<?php 
	include('includes/helpers/geo/geoip.inc');
	include('includes/helpers/short.php');
	
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	//----------------------------------------------------------------//
	
	//get variable
	$i = mysqli_real_escape_string($mysqli, $_GET['i']);
	$i_array = explode('/', $i);
	$campaign_id = short($i_array[0], true);
	$userID = short($i_array[1], true);
	
	if(array_key_exists(2, $i_array)) $ares = $i_array[2];
	else $ares = '';
	
	//MOD EDIT BEGIN
	if(count($i_array)==3 && $i_array[2]=='a'){
		//its an autoresponder email
		//damn its a autoresponder mail
		$q = 'SELECT l.userID,l.id FROM lists AS l
			LEFT JOIN ares ON ares.list = l.id 
			LEFT JOIN ares_emails AS ar ON ar.ares_id = ares.id 
			WHERE ar.id = '.$campaign_id;
		$r = mysqli_query($mysqli, $q);	
		$row = mysqli_fetch_array($r);
		$ownerid = $row['userID'];
		$listids = $row['id'];					
	}else{
		//its a campaign email
		$q = 'SELECT userID,to_send_lists FROM campaigns WHERE id = '.$campaign_id;
		$r = mysqli_query($mysqli, $q);
		$row = mysqli_fetch_array($r);
		$ownerid = $row['userID'];
		$listids = $row['to_send_lists'];	
	}

	if(strpos($listids,',') !== false){
		//multiple list - again nicely stored in sendy db /sarcasm
		$listsidsarr = explode(',',$listids);
		foreach($listsidsarr as $lid){
			//don't like putting queries in a loop but in this case...			
			$sql = "SELECT id FROM flecha_activity_log WHERE subid = ".$userID." AND ownerid = ".$ownerid." AND listid = ".$lid;
			$r = mysqli_query($mysqli, $q);
			if($r !== false){
				//means entry for this users-owner-list combi already exists so just update
				$row = mysqli_fetch_array($r);
				$q = "UPDATE flecha_activity_log SET last_clicked = NOW(), last_opened = NOW() WHERE id = ".$row['id'];
				mysqli_query($mysqli, $q);
			}else{
				//new sub entry in the log
				$q = 'INSERT INTO flecha_activity_log(subid,ownerid,listid,last_clicked,last_opened) VALUES('.$userID.','.$ownerid.','.$lid.',NOW(), NOW())';
				mysqli_query($mysqli, $q);
			}			
		}		
	}else{
		//just one list, one number
		$sql = "SELECT id FROM flecha_activity_log WHERE subid = ".$userID." AND ownerid = ".$ownerid." AND listid = ".$listids;
		$r = mysqli_query($mysqli, $q);
		if($r !== false){
			//means entry for this users already exists so just update
			$row = mysqli_fetch_array($r);
			$q = "UPDATE flecha_activity_log SET last_clicked = NOW(), last_opened = NOW() WHERE id = ".$row['id'];
			mysqli_query($mysqli, $q);
		}else{
			//new sub entry in the log
			$q = 'INSERT INTO flecha_activity_log(subid,ownerid,listid,last_clicked,last_opened) VALUES('.$userID.','.$ownerid.','.$listids.',NOW(), NOW())';
			mysqli_query($mysqli, $q);
		}		
	}
	//MOD EDIT END	
	
	
	//get user's client
	$useragent = $_SERVER['HTTP_USER_AGENT'];
	//get user's ip address & country code
	if (getenv("HTTP_CLIENT_IP")) {
		$ip = getenv("HTTP_CLIENT_IP");
	} elseif (getenv("HTTP_X_FORWARDED_FOR")) {
		$ip = getenv("HTTP_X_FORWARDED_FOR");
	} else {
		$ip = getenv("REMOTE_ADDR");
	}
	$ip_array = explode(',', $ip);
	if(array_key_exists(1, $ip_array)) $ip = trim($ip_array[0]);
	
	$gi = geoip_open("includes/helpers/geo/GeoIP.dat",GEOIP_STANDARD);
	$country = geoip_country_code_by_addr($gi, $ip);
	geoip_close($gi);
	$time = time();
	
	//if this is an autoresponder email,
	$val = '';
	if(count($i_array)==3 && $i_array[2]=='a')
		$q = 'SELECT opens FROM ares_emails WHERE id = '.$campaign_id;
	else
		$q = 'SELECT opens FROM campaigns WHERE id = '.$campaign_id;
	$r = mysqli_query($mysqli, $q);
	if ($r && mysqli_num_rows($r) > 0)
	{
	    while($row = mysqli_fetch_array($r))
	    {
			$opens = $row['opens'];
			
			if($opens=='')
				$val = $userID.':'.$country;
			else
			{
				$opens .= ','.$userID.':'.$country;
				$val = $opens;
			}
	    }  
	}
	
	//Set open
	//if this is an autoresponder email,
	if(count($i_array)==3 && $i_array[2]=='a')
		$q = 'UPDATE ares_emails SET opens = "'.$val.'" WHERE id = '.$campaign_id;
	else
		$q = 'UPDATE campaigns SET opens = "'.$val.'" WHERE id = '.$campaign_id;
	$r = mysqli_query($mysqli, $q);
	if ($r){}
	
	//Just in case this user is set to bounced because Amazon can't deliver it the first time.
	//If user opens the newsletter, it means user did not bounce, so we set bounced to 0
	$q = 'SELECT email FROM subscribers WHERE id = '.$userID.' AND bounced = 1';
	$r = mysqli_query($mysqli, $q);
	if ($r && mysqli_num_rows($r) > 0)
	{
	    while($row = mysqli_fetch_array($r))
	    {
			$email = stripslashes($row['email']);
			
			$q = 'UPDATE subscribers SET bounced = 0, timestamp = '.$time.' WHERE email = "'.$email.'" AND last_campaign = '.$campaign_id;
			$r = mysqli_query($mysqli, $q);
			if ($r){}
	    }  
	}
	
	//----------------------------------------------------------------//
	header("Location: ".APP_PATH."/img/to.png");
	return;
?>