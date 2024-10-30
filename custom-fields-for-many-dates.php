<?php
/*
Plugin Name: Custom Fields for Many Dates
Description: This plugin will allow a user to add/edit/delete dates and date titles for posts as custom meta values.
Version: 1.0
Author: Chris J. Sanders
Author URI: http://www.cjsand.com
License: GNLv3

   This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

//  1) Initiate the plugin...setup the database, etc

// if a user tries to activate the plugin, this will be invoked
register_activation_hook(__FILE__,'ManyDates_install');

// after the hook is invoked, it will seek this function
function ManyDates_install () {
	
	// add a post to store the date type dropdown as meta values (a & b)
	// a) Create post object
	  	$manyDates_Post = array(
	     'post_title' => 'CustomFieldsForManyDates',
	     'post_content' => 'This post is used to store meta values for the plugin.',
	     'post_status' => 'publish',
	     'post_author' => 1,
	     'post_category' => array(8,39),
	     'post_type' => 'pluginMeta'
	  	);

	// b) Insert the post into the database
  		wp_insert_post( $manyDates_Post );
  		
  	global $wpdb;
  	
  	// get the post's ID
  	$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = 'CustomFieldsForManyDates'", $page_title ));
        
	// add the first default drop-down option as meta
	add_post_meta($post_id, 'dateTypeDropDownValue', 'Default Date Type'); 

}

// What to do when the plugin is uninstalled
register_deactivation_hook(__FILE__,'ManyDates_uninstall');

// after the deactivation hook is invoked, it will seek this function
function ManyDates_uninstall () {
	
	global $wpdb;
	
	// delete the post	
	$wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_title = 'CustomFieldsForManyDates'", $page_title );
	
	// delete all the post's meta
	$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = 'dateTypeDropDownValue'", $page_title );
}

//  2) Let the user change the drop down menu items

// first, create the hook
add_action('admin_menu', 'ManyDates_menu');


// next, the function that will be called when the hook is invoked
function ManyDates_menu() {
	
// who is allowed to view the menu
$allowed_group = 'manage_options';

// add the top-level menu
add_menu_page(__('Many Dates Menu Admin','ManyDates'), __('Many Dates Menu Admin','ManyDates'),$allowed_group,'ManyDates','ManyDates_menu_page_display');

}

function ManyDates_menu_page_display()
{
	//must check that the user has the required capability 
    if (!current_user_can('manage_options'))
    {
      wp_die( __('You do not have sufficient permissions to access this page.') );
    }

    global $wpdb;
    
    // if the user edited the drop-down list
    if( isset($_POST[ 'addToDropDownList' ])) 
    {
        // Read their posted value
        $newDropDownItem = $_POST[ 'newDropDownItem' ];
        
        // get the post's ID
  		$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = 'CustomFieldsForManyDates'", $page_title ));
       	
       	// add the new drop down value
		add_post_meta($post_id, 'dateTypeDropDownValue', $newDropDownItem);  

        // Put an settings updated message on the screen
        echo "<div class='updated'><p><strong>";
        echo _e("New Drop Down Option '".$newDropDownItem."' Saved.", 'ManyDates');
        echo "</strong></p></div>";
	}
	
	// if the user edited the drop-down list
    if( isset($_POST[ 'deleteFromDropDownList' ])) 
    {
        // Read the posted value to delete
        $deleteDropDownItem = $_POST[ 'deleteValue' ];
        
        // get the post's ID
  		$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = 'CustomFieldsForManyDates'", $page_title ));
       	
       	// delete the posted drop down value
		delete_post_meta($post_id, 'dateTypeDropDownValue', $deleteDropDownItem);  

        // Put an settings updated message on the screen
        echo "<div class='updated'><p><strong>";
        echo _e("Drop Down Option '".$deleteDropDownItem."' Deleted.", 'ManyDates');
        echo "</strong></p></div>";
	}
	
	    // Now display the settings editing screen
	
	    echo '<div class="wrap">';
	
	    // header
	
	    echo "<h2>" . __( 'Custom Fields for Many Dates Admin', 'ManyDates' ) . "</h2>";
	
	    // settings form
	    
	    ?>
	<div align="center">
		<p style='font-weight:bold'>
			Drop-Down Options:
		</p>
		
		<?php
		global $wpdb;
  	
	  	// get the post ID for the plugin
	  	$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = 'CustomFieldsForManyDates'", $page_title ));
		
		
		$meta_values = get_post_meta($post_id, 'dateTypeDropDownValue', false);
		
		echo "<table>";
		foreach ($meta_values as $dropDownValue)
	{
			// output the value
			echo "<tr>";
				echo "<td>Option: ".$dropDownValue."</td>";
				?>
				<td>
					<form action="" method="POST" name="enterNewitem">
						<input type="hidden" name="deleteValue" value="<?php echo $dropDownValue;?>">
						<input type="submit" name="deleteFromDropDownList" class="button-primary" value="<?php esc_attr_e('Delete this Value') ?>" />
					</form>
				</td>
				<?php
			echo "</tr>";
	} // end loop
	?>
	</table>
	
	<p>
				
		<form action="" method="POST" name="enterNewitem">
			<input type="text" name="newDropDownItem" />
			<br>
			<input type="submit" name="addToDropDownList" class="button-primary" value="<?php esc_attr_e('Add New Drop-Down Value') ?>" />
		</form>
	
	</p>
		
	</div>
	
<?php
}

//  3) Pull the data into a custom fields box for each post and let the user select the appropriate records

/* Define the custom box */
add_action('add_meta_boxes', 'ManyDates_add_box');

/* Adds a box to the main column on the Post and Page edit screens */
function ManyDates_add_box() {
    add_meta_box( 'ManyDates_sectionid', __( 'Custom Fields for Many Dates', 'ManyDates_textdomain' ), 
                'ManyDates_inner_custom_box', 'post' );
}

/* Prints the box content */
function ManyDates_inner_custom_box() {

	//declare global wpdb
	global $wpdb;
	
	// declare global post
	global $post;
  
  	// get the post ID for the plugin (the plugin uses a post's meta to store the dropdown options)
	$plugin_post_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = 'CustomFieldsForManyDates'", $page_title ));
		
	// get the actual values for the dropdown from postmeta
	$meta_values = get_post_meta($plugin_post_id, 'dateTypeDropDownValue');
	
	// get the values of pre-existing date rows
	$existingDates = $wpdb->get_results("SELECT meta_value FROM ".$wpdb->prefix."postmeta WHERE (post_ID='".$post->ID."') AND ".
		"(meta_key LIKE 'DateInput%') ORDER BY meta_id");
		
	// get the values of pre-existing date title rows	
	$existingDateTitle = $wpdb->get_results("SELECT meta_value FROM ".$wpdb->prefix."postmeta WHERE (post_ID='".$post->ID."') AND ".
		"(meta_key LIKE 'DateTitleInput%') ORDER BY meta_id");
		
	//declare a 3d array that will combine the data and date title rows for sorting
	$existingData;	
		
	// declare a counter
	$count = 0;
	
	// Only go through the array combination and sorting if the date array isn't empty
	if (!(empty($existingDates)))
	{
		// combine the meta values into a single array
		foreach ($existingDates as $existingDate)
		{
			$existingData[$count][1]=$existingDate->meta_value;
			$existingData[$count][2]=$existingDateTitle[$count]->meta_value;
			
			$count += 1;
		}		
	
		// capture the number of rows that were moved into the 3d array in the previous loop in $rows
		$rows = $count;
		
		/*  *** the following is just for testing values ***
		while ($count<$rows)
		{
			echo "Row ".$count." Date: ".$existingData[$count][1];
			echo "Row ".$count." Date Title: ".$existingData[$count][2];
			echo "<br>";
			
			$count += 1;
		}*/
	
		// $pass will be used to track the "passes" through the array as the bubble sort works
		$pass = 1;
		// reset count
		$count = 0;
	
		// sort the array using a bubble sort
		do
		{
			$currentRowTime = strtotime($existingData[$count][1]);
			$nextRowTime = strtotime($existingData[$count+1][1]);
			
			if ($currentRowTime > $nextRowTime)
			{
				$oldDate = $existingData[$count][1];
				$oldDateTitle = $existingData[$count][2];
				
				$existingData[$count][1] = $existingData[$count+1][1];
				$existingData[$count][2] = $existingData[$count+1][2];
				
				$existingData[$count+1][1] = $oldDate;
				$existingData[$count+1][2] = $oldDateTitle;
			}
			
			$count += 1;
			
			if (($count==($rows-($pass))))
			{
				$count = 0;
				$pass += 1;
			};
			
			if ($rows-($pass)==0)
				$count = -1;
			
		}while (!($count==-1));
		
		echo "<br><br>";
		
		$count = 0;
		
		/*  *** the following is just for testing values ***
		while ($count<$rows)
		{
			echo "Row ".$count." Date: ".$existingData[$count][1];
			echo "Row ".$count." Date Title: ".$existingData[$count][2];
			echo "<br>";
			
			$count += 1;
		}*/
	}//end if
		?>
	
	<form>
	
	<table name="customFieldsDateTable" id="customFieldsDateTable">
		<tr>
			<td>
				Date Title:
			</td>
			<td>
				Date:
			</td>
		</tr>
		<?php
		
		?>
		<?php
		if (!(empty($existingDates)))
		{
			
			// loop through the values and add them to the window
			$count = 0;
			while ($count < $rows)
			{	
			?>
				<tr>
					<td>
						<select name="dateTitle[]" id="dateTitle[]">
							<?php
							echo "<option selected='selected' value='".$existingData[$count][2]."'>".$existingData[$count][2]."</option>";
							foreach ($meta_values AS $dropDownValue)
							{
								echo "<option value='".$dropDownValue."'>".$dropDownValue."</option>";
							};
							?>
						</select>
					</td>
					<td>
						<input class="date" name="date[]" type="text" value="<?php echo $existingData[$count][1];?>">
					</td>
				</tr>
				<?php
				
				$count += 1;
			}
			
				echo "<script type='text/javascript'>\n";
					echo "update_Field_Number(".$count.");\n";
				echo "</script>";
		}
		else
		{
		?>
			<tr>
				<td>
					<select name="dateTitle[]" id="dateTitle[]">
						<?php
						foreach ($meta_values AS $dropDownValue)
						{
							echo "<option value='".$dropDownValue."'>".$dropDownValue."</option>";
						};
						?>
					</select>
				</td>
				<td>
					<input class="date" name="date[]" type="text" >
				</td>
			</tr>
		<?php
		}
		?>
	</table>
	
	<p>
		<a href="javascript:add_Row()">Add another row...</a>
	</p>
	
	</form>
				
<?php	
	echo wp_nonce_field('check_nonce','ManyDates_nonce'); 
}

/* Do something with the data entered */
add_action('save_post', 'ManyDates_save_postdata');

/* When the post is saved, saves our custom data */
function ManyDates_save_postdata( $post_id ) 
{
	
	// check to make sure the call isn't for an autosave
	if ((wp_is_post_revision( $post_id )) || (wp_is_post_autosave( $post_id )))
	{
  	return $post_id;
	};
  	
	  // verify this came from the our screen and with proper authorization,
  // because save_post can be triggered at other times
  if ( !wp_verify_nonce( $_POST['hCustomFields_nonce'], 'check_nonce' )) {
    return $post_id;
  };

  // verify if this is an auto save routine. If it is our form has not been submitted, so we dont want
  // to do anything
  if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
    return $post_id;

  
  // Check permissions
  if ( 'page' == $_POST['post_type'] ) {
    if ( !current_user_can( 'edit_page', $post_id ) )
      return $post_id;
  } else {
    if ( !current_user_can( 'edit_post', $post_id ) )
      return $post_id;
  };

  // OK, we're authenticated: we need to find and save the data
  
  	// so you can access the dB
	global $wpdb; 
	
	// get the date values array
	$dateInput = $_POST['date'];
	// get the date titles array
	$dateTitleInput = $_POST['dateTitle'];

	// get the value of the previously selected parent element (parent if CustomH_Parent is null)
	$metaArray = $wpdb->get_results("SELECT meta_id FROM ".$wpdb->prefix."postmeta WHERE (post_ID='".$post_id."') AND ".
		"((meta_key LIKE 'DateInput%') OR (meta_key LIKE 'DateTitleInput%'))");
	
	// check to see if previous dates existed in postmeta. If empty, just insert the values.
	if (!(empty($metaArray)))
	{
		// delete the old many date entries
	   	$wpdb->query("DELETE FROM ".$wpdb->prefix."postmeta WHERE (post_ID='".$post_id."') AND ((meta_key LIKE 'DateInput%') OR ".
	   		"(meta_key LIKE 'DateTitleInput%'))");
	}	
	
	// start a count
	$count = 0;	
	
	foreach ($dateInput as $date)
	{
		if (!($date==""))
		{
			$wpdb->query("INSERT INTO ".$wpdb->prefix."postmeta (meta_key,meta_value,post_id) ".
			"VALUES ('DateInput".$count."','".$date."','".$post_id."')");

			$wpdb->query("INSERT INTO ".$wpdb->prefix."postmeta (meta_key,meta_value,post_id) ".
			"VALUES ('DateTitleInput".$count."','".$dateTitleInput[$count]."','".$post_id."')");
		}
				
		$count += 1;
	}	
		
   return $mydata;
}

/* Code below adds the required data libraries to the head for the date picker */

// add the date_picker_head code to the head section
add_action('admin_head', 'ManyDates_date_picker_head');	

function ManyDates_date_picker_head() {
?>

	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js"></script>

	<link type="text/css" rel="stylesheet" href="<?php echo get_bloginfo('wpurl');?>/wp-content/plugins/custom-fields-for-many-dates/datepick/jquery.datepick.css"/>
	<script type="text/javascript" src="<?php echo get_bloginfo('wpurl');?>/wp-content/plugins/custom-fields-for-many-dates/datepick/jquery.datepick.js"></script>
	
	<script>
	$(function() {
			$(".date").datepick({dateFormat: 'MM d, yyyy'});
		});
	</script>

<?php
}

// add the my_action_javascript code to the head section
add_action('admin_head', 'ManyDates_my_action_javascript');

function ManyDates_my_action_javascript() {
?>
<script type="text/javascript" >

fieldsInUse=1;

function update_Field_Number(fields){
	fieldsInUse = fieldsInUse + fields;
};

function add_Row(){
jQuery(document).ready(function($) {
	
	if (fieldsInUse<11)
	{
	
		var data = {
			action: 'ManyDates_my_special_action',
		};
	
		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.post(ajaxurl, data, function(response) 
		{
			$("#customFieldsDateTable").last().append(response);
		});
		
		fieldsInUse= fieldsInUse + 1;
	}
	else
	{
		alert("Sorry, but you can only add 10 fields per post.");
	};
});
};
</script>
<?php
}

// if my_special_action is called via javascript, direct to the my_action_callback php function
add_action('wp_ajax_ManyDates_my_special_action', 'ManyDates_my_action_callback');

function ManyDates_my_action_callback() {
	//declare global wpdb
	global $wpdb;
	
    $newRow.="<tr>";
		$newRow.="<td>";
			$newRow.="<select name='dateTitle[]' id='dateTitle[]'>";
				  
			  	// get the post ID for the plugin
				$plugin_post_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = 'CustomFieldsForManyDates'", $page_title ));
				
				// get the drop down meta values	
				$meta_values = get_post_meta($plugin_post_id, 'dateTypeDropDownValue');
				
				// loop through the meta values
				foreach ($meta_values AS $dropDownValue)
				{
					$newRow.="<option value='".$dropDownValue."'>".$dropDownValue."</option>";
				};
				
			$newRow.="</select>";
		$newRow.="</td>";
		$newRow.="<td>";
			$newRow.="<input class='date' name='date[]' type='text'/>";
			$newRow.="<script>";
				$newRow.="$(function() {";
					$newRow.="$('.date').datepick({dateFormat: 'MM d, yyyy'});";
				$newRow.="});";
			$newRow.="</script>";
		$newRow.="</td>";
	$newRow.="</tr>";
	
	echo $newRow;

	die();
}//end function
?>