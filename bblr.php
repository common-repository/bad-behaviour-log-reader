<?php
/*
Plugin Name: Bad Behavior Log Reader
Version: 1.3
Plugin URI: http://www.misthaven.org.uk/blog/projects/bblogreader/
Description: Basic log viewer for Bad Behavior in WordPress
Author: David McFarlane.
Author URI: http://www.misthaven.org.uk/blog/
*/

/*

Note (dm): The other guys got there before me on this, I beautified the output and fixed some syntax.  Removed the chunker function from the output because I don't think it's needed with the paging and DIVs. Added option page to choose numbers of rows to display per page. 
Any rights that were established remain as was. Any other bits are licensed under a Creative Commons Attribution-Noncommercial-Share Alike 3.0 License (see http://creativecommons.org/licenses/by-nc-sa/3.0/ for full text). 

Changed - 
The keyinfo function remains the same.
I changed most of the rest of the bits to alter the presentation of the plugin.
Inlcuded options and pagination, etc.

Wordpress options stored - dm_bblr_numrows (Number of rows to show),dm_bblr_showblocksonly (Show only blocks, not warnings)


	Changelog : 

V1.0 	Initial Release
v1.1	Moved main plugin page from Options into Management in menu structure
	Added link from activity box.
v1.3    Show block logs only option
	Allow log entries in local time (thanks to Craig's comment)

-----------------------
----- Older notes -----
-----------------------

URL for the version that was adapted - http://jonathanmurray.com/wordpress/2006/07/08/wordpress-plugins/#more-893

Note: (jm) Simon Elvery wrote the basic plugin, which worked great
on older versions of BB. I found this plugin after installing
BB 2.0 for wordpress, and discovered a number of changes to the
database that made this plugin unusable. Since I wanted the
functionality, and found no replacements, I have taken it upon
myself to update the plugin for my own use.



Changed:
- Name of default database (wp_bad_behavior) vice (wp_bad_behavior_log)

- Updated field names (*in view):
 id (sort order, not needed to view)
 ip (the source of the 'visitor')*
 date (date / time)*
 request_method (GET)
 request_uri (where they were headed)*
 server_protocol (HTTP)
 http_headers (all the ugly info)* (chunked)
 user_agent (who they identified themselves as)*
 request_entity (blank?)
 key (this is the explanation key, not yet documented)*

- Added Chunker, a small function to break up long strings so they don't screw up your layout (as much)

The original concept and credit for the plugin remains with Simon Elvery.
All rights to the original work remain the property of Simon Elvery.
I'm just a guy trying to learn about PHP, MySQL, and WordPress!
- Jonathan Murray
 
---------------------------
----- End older notes -----
---------------------------
 */

define('BBLR_VERSION', '1.3');

// For compatibility with WP 2.0

if (!function_exists('wp_die')) {
	function wp_die($msg) {
		die($msg);
	}
}

function bblr_keyInfo($key) {
$keyInfo_data = array(
'00000000' => 'Suspicious, logged but not denied',
'17566707' => 'An invalid request was received from your browser. This may be caused by a malfunctioning proxy server or browser privacy software.',
'17f4e8c8' => 'You do not have permission to access this server.',
'21f11d3f' => 'An invalid request was received. You claimed to be a mobile Web device, but you do not actually appear to be a mobile Web device.',
'2b90f772' => 'You do not have permission to access this server. If you are using the Opera browser, then Opera must appear in your user agent.',
'408d7e72' => 'You do not have permission to access this server. Before trying again, run anti-virus and anti-spyware software and remove any viruses and spyware from your computer.',
'41feed15' => 'An invalid request was received. This may be caused by a malfunctioning proxy server. Bypass the proxy server and connect directly, or contact your proxy server administrator.',
'45b35e30' => 'An invalid request was received from your browser. This may be caused by a malfunctioning proxy server or browser privacy software.',
'57796684' => 'You do not have permission to access this server. Before trying again, run anti-virus and anti-spyware software and remove any viruses and spyware from your computer.',
'582ec5e4' => 'An invalid request was received. If you are using a proxy server, bypass the proxy server or contact your proxy server administrator. This may also be caused by a bug in the Opera web browser.',
'69920ee5' => 'An invalid request was received from your browser. This may be caused by a malfunctioning proxy server or browser privacy software.',
'799165c2' => 'You do not have permission to access this server.',
'7a06532b' => 'An invalid request was received from your browser. This may be caused by a malfunctioning proxy server or browser privacy software.',
'7ad04a8a' => 'The automated program you are using is not permitted to access this server. Please use a different program or a standard Web browser.',
'7d12528e' => 'You do not have permission to access this server.',
'939a6fbb' => 'The proxy server you are using is not permitted to access this server. Please bypass the proxy server, or contact your proxy server administrator.',
'9c9e4979' => 'The proxy server you are using is not permitted to access this server. Please bypass the proxy server, or contact your proxy server administrator.',
'a0105122' => 'Expectation failed. Please retry your request.',
'a1084bad' => 'You do not have permission to access this server.',
'a52f0448' => 'An invalid request was received. This may be caused by a malfunctioning proxy server or browser privacy software. If you are using a proxy server, bypass the proxy server or contact your proxy server administrator.',
'b40c8ddc' => 'You do not have permission to access this server. Before trying again, close your browser, run anti-virus and anti-spyware software and remove any viruses and spyware from your computer.',
'b7830251' => 'Your proxy server sent an invalid request. Please contact the proxy server administrator to have this problem fixed.',
'b9cc1d86' => 'The proxy server you are using is not permitted to access this server. Please bypass the proxy server, or contact your proxy server administrator.',
'c1fa729b' => 'You do not have permission to access this server. Before trying again, run anti-virus and anti-spyware software and remove any viruses and spyware from your computer.',
'd60b87c7' => 'You do not have permission to access this server. Before trying again, please remove any viruses or spyware from your computer.',
'dfd9b1ad' => 'You do not have permission to access this server.',
'e4de0453' => 'An invalid request was received. You claimed to be a major search engine, but you do not appear to actually be a major search engine.',
'e87553e1' => 'You do not have permission to access this server.',
'f0dcb3fd' => 'You do not have permission to access this server. Before trying again, run anti-virus and anti-spyware software and remove any viruses and spyware from your computer.',
'f1182195' => 'An invalid request was received. You claimed to be a major search engine, but you do not appear to actually be a major search engine.',
'f9f2b8b9' => 'You do not have permission to access this server. This may be caused by a malfunctioning proxy server or browser privacy software.',
);

if (array_key_exists($key, $keyInfo_data)) return $keyInfo_data[$key];
return array('00000000');
}


function bblr_display(){
	// Shows the actual log rows.
	// Edited v1.0 DM for presentation and paging.

	global $wpdb, $table_prefix;
	$dm_bblr_show_num = bblr_check_rows();
	$whereclause = bblr_get_whereclause();

	// Grab the number of rows in the table;
	$dm_bblr_count_rows = $wpdb->get_var("SELECT COUNT(*) FROM ".$table_prefix."bad_behavior ".$whereclause.";");
	if ($dm_bblr_count_rows > 0) { 
		// Any rows to process

		if ('POST' == $_SERVER['REQUEST_METHOD']) {
			// Possibly need to move to next page of results
			$dm_bblr_pageno = attribute_escape($_POST[pagenotoview]);
			$dm_bblr_offset = 0;
			if (is_numeric($dm_bblr_pageno)) {
				if ($dm_bblr_pageno > 1) {
					$dm_bblr_offset = ($dm_bblr_show_num * ($dm_bblr_pageno - 1));
				} else {$dm_bblr_pageno = 1; }
			}
		} else {
			// Must be viewing first page as no button pressed.
			// echo "MBVFP";
			$dm_bblr_offset = 0;
			$dm_bblr_pageno = 1;
		}


		if ($log = $wpdb->get_results('SELECT * FROM '.$table_prefix.'bad_behavior '.$whereclause.' ORDER BY id DESC LIMIT '.$dm_bblr_offset.','.$dm_bblr_show_num.';')): $alternate = '';
//		echo 'SELECT * FROM '.$table_prefix.'bad_behavior ORDER BY id DESC LIMIT '.$dm_bblr_offset.','.$dm_bblr_show_num.';';

		echo "<div class=\"wrap\"><h2>Displaying BB Log Rows</h2>";
		foreach ($log as $entry) : 
			$alternate = ($alternate == 'Bisque') ? 'Aquamarine' : 'Bisque'; 
			echo "<div style=\"background : $alternate;\">";
			echo "<span style=\"font-weight : bold;\">Client IP:</span>&nbsp;{$entry->ip}&nbsp;";
			echo "<span style=\"font-weight : bold;\">Date:</span>&nbsp;".strftime("%Y-%m-%d %H:%M:%S", strtotime($entry->date." GMT")). "&nbsp;"; 
			echo "<br /><span style=\"font-weight : bold;\">Request URI:</span>&nbsp;";
			echo $entry->request_uri;
			echo "<br /><span style=\"font-weight : bold;\">Headers:</span>&nbsp;";
			echo $entry->http_headers;
			if (!empty($entry->user_agent)) {
				echo "<br /><span style=\"font-weight : bold;\">User-Agent:</span>&nbsp;";
				echo $entry->user_agent;
			}
			echo "<br /><span style=\"font-weight : bold;\">Request Result (key):</span>&nbsp;";
			echo bblr_keyInfo($entry->key);
			echo '</div>';
			echo '<br />';

		endforeach;
		else:
			echo '<p>There are no entries in the log matching the range specified.</p>';
		endif;
		echo '<br /><hr />';
		echo '<p>Currently showing a maximum of '.$dm_bblr_show_num.' records per page - ';
		echo 'records '.($dm_bblr_offset + 1).' to ';  
		// We add values here because humans do not like to think of record number 0
		echo ($dm_bblr_offset+$dm_bblr_show_num > $dm_bblr_count_rows ) ?  $dm_bblr_count_rows : $dm_bblr_offset+$dm_bblr_show_num; 
		//echo $dm_bblr_offset+$dm_bblr_show_num;
		echo " of a total of ".$dm_bblr_count_rows." records</p><hr />";
		// paginate buttons if needed
		if ($dm_bblr_offset+$dm_bblr_show_num < $dm_bblr_count_rows || $dm_bblr_pageno > 1) {
			// Show buttons
			echo "<div style=\"text-align : center;\">";
			if ($dm_bblr_pageno > 1) {
				// show back button
				//echo '<p>Previous results';
?>
				<form action="" method="POST" id="bblr-move-prev-results">
					<input type="hidden" name="pagenotoview" value="<?php echo $dm_bblr_pageno - 1;?>" />
					<input type="submit" name="Submit" value="&laquo;Previous Page" />
				</form>
<?php			}// else {
			 //	echo '<p>';
//			}
			if ($dm_bblr_offset+$dm_bblr_show_num < $dm_bblr_count_rows) {
				// show next button
				//echo 'Next results</p>';
?>
				<form action="" method="POST" id="bblr-move-next-results">
					<input type="hidden" name="pagenotoview" value="<?php echo $dm_bblr_pageno + 1;?>" />
					<input type="submit" name="Submit" value="Next Page &raquo;" />
				</form>
<?php
			} //else {
			//	echo '&nbsp;</p>';
//			}
			echo "</div>";
		}
		echo "</div>";
	} else {
		// no rows
		echo "<div class=\"wrap\"><h2>Displaying BB Log Rows</h2>";
		echo "<p>There are currently no rows in the BB Log table.</p>";
		echo "</div>";
	}

 
} //end function bblr_display


// The rest is new for version 0.4 and above

function dm_bblr_install(){
	add_option('dm_bblr_numrows','20','Number of rows to return on each page in BB log viewer','no');
	add_option('dm_bblr_showblocksonly','0','If this is 1 then only show blocked rows not suspicious rows','no');
}

function dm_bblr_uninstall(){
	delete_option('dm_bblr_numrows');
	delete_option('dm_bblr_showblocksonly');
}

function bblr_check_showblocks(){
// get the last setting for show blocked rows only, or default to 0 (show everything)
	$dm_bblr_showblocksonly = get_option('dm_bblr_showblocksonly');
	if (empty($dm_bblr_showblocksonly)) {
		$dm_bblr_showblocksonly = 0;
		update_option('dm_bblr_showblocksonly','0');
	}
	return $dm_bblr_showblocksonly;
}
function bblr_set_showblocks($dm_bblr_sb_howmany){
	if (is_numeric($dm_bblr_sb_howmany)) {
		update_option('dm_bblr_showblocksonly',$dm_bblr_sb_howmany);
	} else {
		update_option('dm_bblr_showblocksonly','0');
	}
	return $dm_bblr_sb_howmany;
}

function bblr_get_whereclause(){
// Depending on show blocks value, generate a where clause
	$wherecl = get_option('dm_bblr_showblocksonly');
	if (empty($wherecl)) {$wherecl = " ";} elseif ($wherecl == '1') {$wherecl = ' WHERE `key` != "00000000" ';} else {$wherecl = " ";}
	return $wherecl;
}

function bblr_check_rows(){
// get the last set number of rows to return per page, or default to 20
	$dm_bblr_numrows = get_option('dm_bblr_numrows');
	if (empty($dm_bblr_numrows)) {
		$dm_bblr_numrows = 20;
		update_option('dm_bblr_numrows','20');
	}
	return $dm_bblr_numrows;
}
function bblr_set_rows($dm_bblr_howmany){
	if (is_numeric($dm_bblr_howmany)) {
		if ($dm_bblr_howmany > 0) {
			update_option('dm_bblr_numrows',$dm_bblr_howmany);
		} else {
			update_option('dm_bblr_numrows','20');
			$dm_bblr_howmany = 20;
		}
	} else {
		update_option('dm_bblr_numrows','20');
		$dm_bblr_howmany = 20;
	}
	return $dm_bblr_howmany;
}

	/* add  menus */

	function bblr_add_config_page()
	{
		add_submenu_page('options-general.php',__('BB Log Reader Options'),__('BBLR options'),8,'BBLR_confmanager',bblr_config_page);
	}

	function bblr_add_menu(){
		add_management_page('BB Log Reader', 'BB Log Reader', 8, __FILE__, 'bblr_display');
	}


	/* ====== config_page ====== */
	
	/*
	 * Loads in the configuration page.
	 */
	
	function bblr_config_page()
	{
		if ('POST' == $_SERVER['REQUEST_METHOD']) {
			$dm_bblr_numrows = bblr_set_rows(attribute_escape($_POST[numperpage]));
			if (attribute_escape($_POST[showblock]) == 'showblock') {
				$dm_bblr_showblocksonly = 1;
			} else {
				$dm_bblr_showblocksonly = 0;
			}
			$dm_bblr_showblocksonly = bblr_set_showblocks($dm_bblr_showblocksonly);
			echo '<div id="bblr-config-saved" class="updated fade-ffff00"">';
			echo '<p><strong>';
			_e('Options saved.');
			echo '</strong></p></div>';
		} else {
			$dm_bblr_numrows = bblr_check_rows();
			$dm_bblr_showblocksonly = bblr_check_showblocks();
		}?>
		<div class="wrap">
			<h2>Displaying Log Rows</h2>

			<form action="" method="POST" id="bblr-display-rows-conf">
		
			<fieldset class="options">
			<legend style="padding : 0px;">
				<h3>Show how many rows per page:</h3>
			</legend>
			<label for="numperpage" style="margin-left : 9px;">Show this many log rows:</label>
			<input id="numperpage" name="numperpage" size="6" value="<?php echo $dm_bblr_numrows;?>" />
			<legend style="padding : 0px;">
				<h3>Show blocked entries only (ignore warnings)</h3>
			</legend>
			<label for="showblock" style="margin-left : 9px;">Tick to only view log entries showing blocks:</label>
			<input type="checkbox" name="showblock" <?php if ($dm_bblr_showblocksonly == 1) {echo 'checked="checked"';} ?> value="showblock" />
			</fieldset>

			<p class="submit">
			<input type="submit" name="Submit" value="Update Options &raquo;" />
			</p>

		<p style="text-align:center">
			BB Log Reader Version <?php echo BBLR_VERSION; ?> - 
			Copyright 2007 <a href="http://www.misthaven.org.uk/blog/">David McFarlane</a>
			-
			<a href="http://www.misthaven.org.uk/blog/projects/bblogview">Help and FAQ</a>
		</p>
	</form>
</div>
<?php

	}

function dm_bblr_activitybox() {
	echo '<h3>'.__('Bad Behaviour Log Reader').'</h3>';
	echo '<p><a href="'.clean_url("edit.php?page=bblr.php").'" title="View Bad Behaviour Logs">View the bad behaviour logs</a>.</p>';
}

add_action('activity_box_end', 'dm_bblr_activitybox');



add_action('admin_menu', 'bblr_add_menu');
add_action('admin_menu', 'bblr_add_config_page');

add_action('activate_bblr.php', 'dm_bblr_install');
add_action('deactivate_bblr.php','dm_bblr_uninstall');
?>
