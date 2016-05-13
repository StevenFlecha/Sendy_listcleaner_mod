<?php include('includes/header.php');?>
<?php include('includes/login/auth.php');?>
<?php include('includes/list/main.php');?>
<?php include('includes/helpers/short.php');?>
<?php
	if(get_app_info('is_sub_user')) 
	{
		if(get_app_info('app')!=get_app_info('restricted_to_app'))
		{
			echo '<script type="text/javascript">window.location="'.addslashes(get_app_info('path')).'/list?i='.get_app_info('restricted_to_app').'"</script>';
			exit;
		}
	}
?>
<link href="<?php echo get_app_info('path');?>/js/tablesorter/theme.default.min.css" rel="stylesheet">
<script type="text/javascript" src="<?php echo get_app_info('path');?>/js/tablesorter/jquery.tablesorter.min.js"></script>
<script type="text/javascript" src="<?php echo get_app_info('path');?>/js/tablesorter/jquery.tablesorter.widgets.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $('table').tablesorter({
            widgets        : ['saveSort'],
            usNumberFormat : true,
            sortReset      : true,
            sortRestart    : true,
            headers: { 0: { sorter: false}, 5: {sorter: false}, 6: {sorter: false} }    
        });
    });
</script>
<div class="row-fluid">
    <div class="span2">
        <?php include('includes/sidebar.php');?>
    </div> 
    <div class="span10">
    	<div>
	    	<p class="lead"><?php echo get_app_data('app_name');?></p>
    	</div>
    	<h2><?php echo _('Subscriber lists');?></h2><br/>
    	
    	<div style="clear:both;">
	    	<button class="btn" onclick="window.location='<?php echo get_app_info('path');?>/new-list?i=<?php echo get_app_info('app');?>'"><i class="icon-plus-sign"></i> <?php echo _('Add a new list');?></button>    
	    	
	    	<!-- MOD BEGIN //-->
	    	<button class="btn" id="listcleaner" data-toggle="modal" data-target="#listcleanermodal"><i class="icon-eraser"></i> <?php echo _('List Cleaner');?></button>
	    	<!-- MOD END //-->
	    	
	    	<form class="form-search" action="<?php echo get_app_info('path');?>/search-all-lists" method="GET" style="float:right;">
	    		<input type="hidden" name="i" value="<?php echo get_app_info('app');?>">
				<input type="text" class="input-medium search-query" name="s" style="width: 200px;">
				<button type="submit" class="btn"><i class="icon-search"></i> <?php echo _('Search all lists');?></button>
			</form>
		</div>
		
		<br/>
    	
	    <table class="table table-striped responsive">
		  <thead>
		    <tr>
		      <th><?php echo _('ID');?></th>
		      <th><?php echo _('List');?></th>
		      <th><?php echo _('Active');?></th>
		      <th><?php echo _('Unsubscribed');?></th>
		      <th><?php echo _('Bounced');?></th>
		      <th><?php echo _('Edit');?></th>
		      <th><?php echo _('Delete');?></th>
		    </tr>
		  </thead>
		  <tbody>
		  	
		  	<!-- Auto select encrypted listID -->
		  	<script type="text/javascript">
		  		$(document).ready(function() {
					$(".encrypted-list-id").mouseover(function(){
						$(this).selectText();
					});
				});
			</script>
		  	
		  	<?php 
			  	$q = 'SELECT id, name FROM lists WHERE app = '.get_app_info('app').' AND userID = '.get_app_info('main_userID').' ORDER BY name ASC';
			  	$r = mysqli_query($mysqli, $q);
			  	if ($r && mysqli_num_rows($r) > 0)
			  	{
			  	    while($row = mysqli_fetch_array($r))
			  	    {
			  			$id = $row['id'];
			  			$name = stripslashes($row['name']);
			  			$subscribers_count = get_subscribers_count($id);
			  			$unsubscribers_count = get_unsubscribers_count($id);
			  			$bounces_count = get_bounced_count($id);
			  			if(strlen(short($id))>5) $listid = substr(short($id), 0, 5).'..';
			  			else $listid = short($id);
			  				
			  			echo '
			  			
			  			<tr id="'.$id.'">
			  			  <td><span class="label" id="list'.$id.'">'.$listid.'</span><span class="label encrypted-list-id" id="list'.$id.'-encrypted" style="display:none;">'.short($id).'</span></td>
					      <td><a href="'.get_app_info('path').'/subscribers?i='.get_app_info('app').'&l='.$id.'" title="">'.$name.'</a></td>
					      <td id="progress'.$id.'">'.$subscribers_count.'</td>
					      <td><span class="label">'.get_unsubscribers_percentage($subscribers_count, $unsubscribers_count).'%</span> '.$unsubscribers_count.' '._('users').'</td>
					      <td><span class="label">'.get_bounced_percentage($bounces_count, $subscribers_count).'%</span> '.$bounces_count.' '._('users').'</td>
					      <td><a href="edit-list?i='.get_app_info('app').'&l='.$id.'" title=""><i class="icon icon-pencil"></i></a></td>
					      <td><a href="javascript:void(0)" title="'._('Delete').' '.$name.'?" id="delete-btn-'.$id.'" class="delete-list"><i class="icon icon-trash"></i></a></td>
					      <script type="text/javascript">
					    	$("#delete-btn-'.$id.'").click(function(e){
							e.preventDefault(); 
							c = confirm("'._('All subscribers, custom fields and autoresponders in this list will also be permanently deleted. Confirm delete').' '.$name.'?");
							if(c)
							{
								$.post("includes/list/delete.php", { list_id: '.$id.' },
								  function(data) {
								      if(data)
								      {
								      	$("#'.$id.'").fadeOut();
								      }
								      else
								      {
								      	alert("'._('Sorry, unable to delete. Please try again later!').'");
								      }
								  }
								);
							}
							});
							$("#list'.$id.'").mouseover(function(){
								$("#list'.$id.'-encrypted").show();
								$(this).hide();
							});
							$("#list'.$id.'-encrypted").mouseout(function(){
								$(this).hide();
								$("#list'.$id.'").show();
							});
							</script>
					    </tr>
						
			  			';
			  	    }  
			  	}
			  	else
			  	{
				  	echo '
				  		<tr>
				  			<td>'._('No list yet.').' <a href="'.get_app_info('path').'/new-list?i='.get_app_info('app').'" title="">'._('Add one').'</a>!</td>
				  			<td></td>
				  			<td></td>
				  			<td></td>
				  			<td></td>
				  			<td></td>
				  			<td></td>
				  		</tr>
				  	';
			  	}
		  	?>
		    
		  </tbody>
		</table>		
    </div>   
</div>

<!-- MOD BEGIN -->
<script type="text/javascript" src="<?php echo get_app_info('path');?>/js/pikaday.min.js"></script>
<div class="modal fade" id="listcleanermodal" tabindex="-1" role="dialog" aria-labelledby="listcleanermodal">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
      		<div class="modal-header">
        		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        		<h4 class="modal-title" id="myModalLabel">Sendy List Cleaner (<a href="http://www.flechamobile.com" target="_blank">By FlechaMobile Inc.</a>)</h4>
      		</div>
      		<div class="modal-body center-block">
      			<select id="action">
     				<option value="move">Move</option>
      				<option value="del">Delete</option>
			 	</select>
      			 subscribers who 
      			<select id="selection">
			 		<option value="openedorclicked">have not opened OR clicked an email</option>
     				<option value="opened">have not opened an email</option>
      				<option value="clicked">have not clicked an email</option>
			 		<option value="unconfirmed">are unconfirmed</option>
			 		<option value="noactivity">have no recorded activity at all</option>
			 	</select> 
			 	<br />Since <input type="text" id="lcdp" value="<?php echo date("m/d/Y", strtotime("-6 months"));?>" />  
      			from the following list(s): 
			  	<br /><select id="fromlist" multiple>
			  	 	<?php
			  		$q = 'SELECT id, name FROM lists WHERE app = '.get_app_info('app').' AND userID = '.get_app_info('main_userID').' ORDER BY name ASC';
			  		$r = mysqli_query($mysqli, $q);	$list = array();			  	
				  	if($r && mysqli_num_rows($r) > 0){
				  	    while($row = mysqli_fetch_array($r)){			      	
			  				$id = $row['id'];
			  				$name = stripslashes($row['name']);	
			  				$list[$id] = $name;		      			
			      			echo "<option value=\"".$id."\">$name</option>";
			      		}
			      	}
			      	?>
			  	</select>  
			  	<div class="lcmoveto">
			  		<br />And move them to:
					<select id="tolist">
			      	<?php
				  	if (count($list) > 0){
				  	    foreach($list as $id => $lname){      			
			      			echo "<option value=\"$id\">$lname</option>";
			      		}
			      	}
			      	?>
			  	</select>
			  	</div> 
			  	<br /><br /><small>Do not run a list clean while sending email campaigns!</small>			  		
				<div class="noactwarn">
					<?php
					$q = "SELECT install_date FROM flecha_mods WHERE modname = 'flecha_list_cleaner'";
			  		$r = mysqli_query($mysqli, $q);
			  		$row = mysqli_fetch_array($r);			  	
					?>
					<br />
					<p style="color: #FF0000; font-size: 120%;">
						!!CAUTION!!: activity per sub is only properly recorded AFTER you installed the FlechaMobile List Cleaner Sendy MOD! 
						We strongly recommend no to clean using this option -before- the install date: <strong><?PHP echo $row['install_date'];?></strong> 
						otherwise you might be deleting / moving many unwanted subscribers!
					</p>
				</div>
			  	<?php
			  	//echo "<pre>";
			  	//print_r($_SESSION);
			  	//echo "</pre>";
			  	?>   			
      		</div>
      		<div class="modal-footer">
        		<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        		<button type="button" id="cleannow" class="btn btn-primary">Clean Now!</button>
      		</div>
    	</div>
  	</div>
</div>
<script type="text/javascript">
	$(document).ready(function(){
		$("head").append("<link id='lcdpcss' href='<?php echo get_app_info('path');?>/css/flechalc.css' type='text/css' rel='stylesheet' />");
		
		var picker = new Pikaday({ field: document.getElementById('lcdp') });
		
		$('#action').on('change', function() {
			if(this.value == "del"){
				$('.lcmoveto').hide();	
			}else{
				$('.lcmoveto').show();
			}
		});
		$('#selection').on('change', function() {
			console.log('go..');
			if(this.value == "noactivity"){
				$('.noactwarn').show();	
			}else{
				$('.noactwarn').hide();
			}
		});		
		
		$('#cleannow').click( function(){
			var a = $('#action').val();
			var s = $('#selection').val();
			var lcdp = picker.getDate()
			var fromlist = $('#fromlist').val();
			var tolist = $('#tolist').val();
			
			var yyyy = lcdp.getFullYear();
   			var mm = lcdp.getMonth() < 9 ? "0" + (lcdp.getMonth() + 1) : (lcdp.getMonth() + 1); // getMonth() is zero-based
   			var dd  = lcdp.getDate() < 10 ? "0" + lcdp.getDate() : lcdp.getDate();
   			var lcdpstr =  yyyy+"-"+mm+"-"+dd+" 00:00:00";			
			
			console.log(a);
			console.log(s);
			console.log(lcdp);
			console.log(lcdpstr);
			console.log(fromlist);
			console.log(tolist);
			
			pdata = {a: a, s: s, lcdp: lcdpstr, fromlist: fromlist, tolist: tolist	};
			$.ajax({
				type: "post",
			    url: "/flecha_list_cleaner",
			    cache: false,
			    data: pdata,
			    dataType: 'json',
			    success: function(dat){
					console.log(dat);
					if(dat.error){
						alert('Error processing the cleaning: '+dat.error);	
					}else{
						alert('Succesfully cleaned: '+dat.success);	
					}
					$('#listcleanermodal').modal('hide')
			    },
			    error: function (request, status, error) {
					console.log('Ajax error');
				    alert('Error processing the cleaning: '+error);
				}
			}); 			
		});
	});	
</script>
<!-- MOD END -->

<?php include('includes/footer.php');?>
