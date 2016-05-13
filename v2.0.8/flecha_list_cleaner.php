<?php
include('includes/functions.php');
include('includes/helpers/short.php');

//Check if user is logged in:
if(isset($_COOKIE['logged_in'])) $cookie = $_COOKIE['logged_in'];
else $cookie = '';

if($cookie==hash('sha512', get_app_info('userID').get_app_info('email').get_app_info('password').'PectGtma'))
	flecha_clean_lists();
else{
	//echo "$cookie != ".hash('sha512', get_app_info('userID').get_app_info('email').get_app_info('password').'PectGtma');
	$response['error'] = 'Not logged in!';
	echo json_encode($response);
	exit;
}

function flecha_clean_lists(){
	global $mysqli;
	$debug = false; //true debug ON false debug OFF

	if(!CheckIfInstalled($mysqli)){
		$response['error'] = 'Needed table is not there, and could not create it :(';
		echo json_encode($response);
		exit;	
	}
	
	$ownerid = get_app_info('userID');
	
	$response = array();
	$action = Clean($_POST['a']);
	$selection = Clean($_POST['s']);
	$date = Clean($_POST['lcdp']); //format yyyy-mm-dd 00:00:00
	$fromlistid = Clean($_POST['fromlist']);//array with list id's
	$tolistid = Clean($_POST['tolist']);
	
	$c = count($fromlistid);
	if($c < 1){
		$response['error'] = 'You need to select at least 1 list to apply the cleaning!';
		echo json_encode($response);
		exit;			
	}elseif($c == 1){
		//just 1 list
		$cllistid = $fromlistid[0];		
	}else{
		//multiple lists selected
		$cllistin = "(";
		foreach($fromlistid as $lid){
			$cllistin .= "'$lid',";
		}
		$cllistin = substr($cllistin,0,-1).")";
	}
	
	//get subsriber selections data.
	$sql = false;
	switch($selection){
		case "openedorclicked":
			if(isset($cllistid)){
				//1 list
				$sql = "SELECT subid FROM flecha_activity_log WHERE ownerid = '$ownerid' AND listid = '$cllistid' AND (last_openend < '$date' OR last_clicked < '$date')";
			}else{
				//multiple list
				$sql = "SELECT subid FROM flecha_activity_log WHERE ownerid = '$ownerid' AND listid IN $cllistin AND (last_openend < '$date' OR last_clicked < '$date')";					
			}
			$response['debug1'] = __LINE__." Sql: $sql";
			break;
		case "opened":
			if(isset($cllistid)){
				//1 list
				$sql = "SELECT subid FROM flecha_activity_log WHERE ownerid = '$ownerid' AND listid = '$cllistid' AND last_openend < '$date'";
			}else{
				//multiple list
				$sql = "SELECT subid FROM flecha_activity_log WHERE ownerid = '$ownerid' AND listid IN $cllistin AND last_openend < '$date'";					
			}
			$response['debug1'] = __LINE__." Sql: $sql";				
			break;					
		case "clicked":
			if(isset($cllistid)){
				//1 list
				$sql = "SELECT subid FROM flecha_activity_log WHERE ownerid = '$ownerid' AND listid = '$cllistid' AND last_clicked < '$date'";
			}else{
				//multiple list
				$sql = "SELECT subid FROM flecha_activity_log WHERE ownerid = '$ownerid' AND listid IN $cllistin AND last_clicked < '$date'";					
			}
			$response['debug1'] = __LINE__." Sql: $sql";				
			break;
		case "unconfirmed":
			//subscribers who are unconfirmed since date X
			//So subs that are unconfirmed longer then date X (date x and older!)
			$ctimestamp = strtotime($date);
			if(isset($cllistid)){
				//1 list
				$sql = "SELECT id as subid FROM subscribers WHERE userID = '$ownerid' AND list = '$cllistid' AND confirmed = '0' AND timestamp < $ctimestamp ";
			}else{
				//multiple list
				$sql = "SELECT id as subid FROM subscribers WHERE userID = '$ownerid' AND list IN $cllistin AND confirmed = '0' AND timestamp < $ctimestamp ";					
			}
			$response['debug1'] = __LINE__." Sql: $sql";									
			break;
		case "noactivity":
			//subs who are in subscribers and NOT in flecha_activity_log 
			//since date X (so date X and more recent)
			$ctimestamp = strtotime($date);
			$sql = "SELECT s.id as subid
					FROM subscribers AS s 
					LEFT JOIN flecha_activity_log AS fal 
					ON s.id = fal.subid AND s.userID = fal.ownerid AND s.list = fal.listid 
					WHERE fal.subid IS NULL AND timestamp > $ctimestamp
			";
			$response['debug1'] = __LINE__." Sql: $sql";
			break;
		default:
			$response['error'] = "Bad selection: $selection";				
	}	
	
	switch($action){
		case "move":
			//start do the moving:
			if($sql !== false){
				$r = mysqli_query($mysqli, $sql);
				$moveids = "";
				if ($r && mysqli_num_rows($r) > 0){
				    while($row = mysqli_fetch_array($r)){
						$moveids .= "'".$row['subid']."',";	
					}
				}
				
				if(is_numeric($tolistid)){ //jst to be sure
					if($moveids != ""){
						$moveids = substr($moveids,0,-1);					
						if(!$debug){
							$sql = "UPDATE subscribers SET list = '$tolistid' WHERE id IN ($moveids)";
							$r = mysqli_query($mysqli, $sql);
							if($r !== false){
								$aff = mysqli_affected_rows($mysqli);
								$response['success'] = "Move ran successfully, $aff subscribers have been moved to list id: $tolistid";
								$response['debug'] = "Sql: $sql";
							}else{
								$response['error'] = "Failed to move: $sql";						
							}						
						}else{
							$c = explode(",",$moveids);
							$response['success'] = "[DEBUG MODE] Moving # to be affecte ids: ".count($c)."<br />$sql";							
							$response['debug'] = __LINE__." Sql: $sql";
						}
					}else{
						$response['error'] = "Move ran successfull but there was nothing to move ($moveids)";	
						$response['debug'] = __LINE__." Sql: $sql";
					}					
				}else{
					$response['error'] = "Move to list is not selected";
				}
			}						
			break;
		case "del":		
			if($sql !== false){
				$r = mysqli_query($mysqli, $sql);
				$delsubids = "";
				if ($r && mysqli_num_rows($r) > 0){
				    while($row = mysqli_fetch_array($r)){
						$delsubids .= "'".$row['subid']."',";	
					}
				}
				
				if($delsubids != ""){
					$delsubids = substr($delsubids,0,-1);
					$sql = "DELETE FROM subscribers WHERE id IN ($delsubids)";
					if(!$debug){
						$r = mysqli_query($mysqli, $sql);
						if($r !== false){
							$aff = mysqli_affected_rows($mysqli);
							$response['success'] = "Clean ran successfully, $aff subscribers have been deleted";
							$response['debug'] = __LINE__." Sql: $sql";
						}else{
							$response['error'] = "Failed to delete: $sql";						
						}
					}else{
						$c = explode(",",$delsubids);
						$response['success'] = "[DEBUG MODE] # to be affecte ids: ".count($c)."<br />$sql";
						$response['debug'] = __LINE__." Sql: $sql";
					}						
				}else{
					$response['success'] = "Clean ran successfull but there was nothing to delete";
					$response['debug'] = __LINE__." Sql: $sql";
				}			
			}
			break;
		default:
			$response['error'] = "Bad action: $action";
	}

	echo json_encode($response);
	exit;
}

function Clean($post = false){
	if($post === false) return false;
	elseif(is_array($post)){
		$clean = array();
		foreach($post as $k => $v){
			$k = htmlspecialchars($k,ENT_QUOTES, 'utf-8');
			$v = htmlspecialchars($v,ENT_QUOTES, 'utf-8');
			$clean[$k]=$v;
		}
		$post = $clean;
	}else{
		$post = htmlspecialchars($post,ENT_QUOTES, 'utf-8');	
	}	
	return $post;
}
function CheckIfInstalled($mysqli){
	$q = "SELECT 1 FROM `flecha_activity_log` LIMIT 1";
	$r = mysqli_query($mysqli, $q);
	if($r !== false){
		//all is good, its installed
		return true;
	}else{
		//its not there, lets create the table!
		$sql = "
CREATE TABLE IF NOT EXISTS `flecha_activity_log` (
  id INT(11) NOT NULL AUTO_INCREMENT,
  subid INT(11) NOT NULL,
  ownerid INT(11) NOT NULL,
  listid INT(11) NOT NULL, 
  last_clicked datetime default NULL,
  last_opened datetime default NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subkey` (subid,ownerid,listid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS flecha_mods (
  id INT(11) NOT NULL AUTO_INCREMENT,
  modname VARCHAR(40) NOT NULL,
  install_date datetime,
  PRIMARY KEY (`id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO flecha_mods (modname,install_date) VALUES('flecha_list_cleaner', NOW());		
		";
		$t = mysqli_query($mysqli, $sql);
		if($t !== false){
			return true;
		}else{
			return false;
		}
	}	
}
?>