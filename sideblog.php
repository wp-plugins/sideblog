<?php
/* 
Plugin Name: Sideblog Wordpress Plugin
Plugin URI: http://katesgasis.com/2005/10/24/sideblog/
Description: A simple aside plugin. <br/>Licensed under the <a href="http://www.fsf.org/licensing/licenses/gpl.txt">GPL</a>
Version: 3.8
Author: Kates Gasis
Author URI: http://katesgasis.com
*/


$sb_defaultformat = "<li>%content% - %permalink%</li>";
$sb_defaultposts = 10;

function sideblog_where($query) {
	global $parent_file, $wpdb;
	$sideblog_options = get_option('sideblog_options');
	
	if((isset($parent_file)||!empty($parent_file))){
		return $query;
	}
	
	if(is_feed()){
		if(isset($sideblog_options['excludefromfeeds']) && !empty($sideblog_options['excludefromfeeds'])){
			$query .= " AND $wpdb->post2cat.category_id NOT IN (" . implode(",", $sideblog_options['excludefromfeeds']) . ") ";
		}		
	} else {
		if(!is_category() && !is_single()){
			if(isset($sideblog_options['setaside']) && !empty($sideblog_options['setaside'])){
				$query .= " AND $wpdb->post2cat.category_id NOT IN (" . implode(",", $sideblog_options['setaside']) . ") ";
			}
		}
	}
	return $query;

}

function sideblog_join($query) {
	global $wpdb;
	$sideblog_options = get_option('sideblog_options');
	
	if((isset($parent_file)||!empty($parent_file))){
		return $query;
	}
	
	if(!is_category()){
		if(strstr($query,"$wpdb->post2cat")===FALSE){
			$query .= " LEFT JOIN $wpdb->post2cat ON (  $wpdb->posts.ID  = $wpdb->post2cat.post_id) ";
		}
	} 
	return $query;
}

function sideblog_recent_entries($args) {
	global $wpdb;
	$sideblog_options = get_option('sideblog_options');
	if(isset($sideblog_options['setaside']) && !empty($sideblog_options['setaside'])){
		$setasides = implode(",",$sideblog_options['setaside']);
	}
	extract($args);
	$title = __('Recent Posts');
	if(strstr($query,"$wpdb->post2cat")===FALSE && isset($setasides)){
		$rows = $wpdb->get_results("SELECT DISTINCT $wpdb->posts.* FROM $wpdb->posts LEFT JOIN $wpdb->post2cat ON($wpdb->posts.ID=$wpdb->post2cat.post_id) WHERE $wpdb->post2cat.category_id NOT IN ($setasides) ORDER BY $wpdb->posts.post_date DESC LIMIT 10");
	}
	if ($rows) :
?>
		<?php echo $before_widget; ?>
			<?php echo $before_title . $title . $after_title; ?>
			<ul>
			<?php  foreach($rows as $row): ?>
			<li><a href="<?php echo get_permalink($row->ID); ?>"><?php if ($row->post_title) echo $row->post_title; else echo $row->ID; ?> </a></li>
			<?php endforeach; ?>
			</ul>
		<?php echo $after_widget; ?>
<?php
	endif;
}

function sideblog_distinct($query) {
	global $wpdb, $parent_file;
	
	if((isset($parent_file)||!empty($parent_file))){
		return " DISTINCT ";
	}
	
	if((!isset($query)||empty($query))){
		 $query = " DISTINCT ";
		 return $query;
	}
}

function sideblog_orderby($query) {
	return $query;
}

function sideblog($asidecategory=''){
	global $wpdb, $sb_defaultformat,$sb_defaultposts;
	$limit = 5;
	$sideblog_options = get_option('sideblog_options');
	
	if(!isset($asidecategory) || empty($asidecategory)) {
		echo "Aside category not selected. Please provide a category slug if you're using non-dynamic sidebar.";
		return;
	}

	if(!$asidecategory){
		$asidecount = count($sideblog_options['setaside']);
		if($asidecount < 1){
			echo "No aside category selected. Please select an aside category in Options &raquo; Sideblog Panel.";
			return;
		}
		$asideid = '';
		if(isset($sideblog_options['setaside']) && !empty($sideblog_options['setaside'])){
			foreach($sideblog_options['setaside'] as $aside){
				if($asideid!=''){
					break;
				}
				$asideid = $aside;
			}
		}
	} else {
		$asideid = $wpdb->get_var("SELECT cat_ID FROM " . $wpdb->categories . " WHERE category_nicename='" . $asidecategory . "'");
		if(isset($sideblog_options['setaside']) && !empty($sideblog_options['setaside'])){
			if(!in_array($asideid,$sideblog_options['setaside'])){
				echo "Aside category not selected.";
				return;
			}
		} else {
			echo "Aside category not selected.";
			return;
		}
	}
	$asidecategory = $asideid;
	$limit = $sideblog_options['numentries'][$asideid];
	if(!$limit){
		$limit = $sb_defaultposts;
	}

	$displayformat = stripslashes($sideblog_options['displayformat'][$asideid]);
	if(!$displayformat){
		$displayformat = $sb_defaultformat;
	}
	
	$now = current_time('mysql');
	$sideblog_contents = $wpdb->get_results("SELECT $wpdb->posts.ID, $wpdb->posts.post_title, $wpdb->posts.post_content, $wpdb->posts.post_date FROM $wpdb->posts, $wpdb->post2cat WHERE $wpdb->posts.ID = $wpdb->post2cat.post_id AND $wpdb->post2cat.category_id = $asidecategory AND $wpdb->posts.post_status ='publish' AND $wpdb->posts.post_type = 'post' AND $wpdb->posts.post_password ='' AND $wpdb->posts.post_date < '" . $now . "' ORDER BY $wpdb->posts.post_date DESC LIMIT " . $limit);
	
	$patterns[] = "%title%";
	$patterns[] = "%content%";
	$patterns[] = "%permalink%";
	$patterns[] = "%title_url%";
	$patterns[] = "%postdate%";
	$patterns[] = "%postdate_url%";
	$patterns[] = "%excerpt%";

	preg_match("/\%excerpt\_\d+\%/",$displayformat,$matches);
	$patterns[] = $matches[0];
	preg_match("/\d+/",$matches[0],$excerptcut);
	
	if($sideblog_contents){
		foreach($sideblog_contents as $sideblog_content){			
			$permalink = get_permalink($sideblog_content->ID);
			
			$excerpt = sideblog_excerpt($sideblog_content->post_content,15);
			$excerpt2 = sideblog_excerpt($sideblog_content->post_content,$excerptcut[0]);

			$replacements[] = $sideblog_content->post_title;
			$replacements[] = $sideblog_content->post_content;
			$replacements[] = "<a href=\"" . $permalink . "\">#</a>";
			$replacements[] = "<a href=\"" . $permalink . "\" title=\"" . $sideblog_content->post_title . "\">" . $sideblog_content->post_title . "</a>";
			$replacements[] = $sideblog_content->post_date;
			$replacements[] = "<a href=\"" . $permalink . "\">" . $sideblog_content->post_date . "</a>";
			$replacements[] = $excerpt;
			$replacements[] = $excerpt2;
			
			$output = str_replace($patterns,$replacements,$displayformat);
			
			if(preg_match_all("/\%(\w)\%/",$output,$matches)){
				foreach($matches[1] as $match){
					$output = str_replace("%" . $match . "%",date($match,strtotime($sideblog_content->post_date)),$output);
				}
			}
		
			if(preg_match_all("/\%url\%([^\%]*)\%url\%/",$output,$matches)){
				foreach($matches[1] as $match){
					$output = str_replace("%url%" . $match . "%url%","<a href=\"" . $permalink . "\">" . $match . "</a>",$output);
				}
			}
			unset($matches);
			if(function_exists('Markdown')){
				$output =  Markdown($output);
			}
			echo $output;
			unset($replacements);
		}
	}
}

function sideblog_option_page(){
	global $wpdb, $sb_defaultformat, $sb_defaultposts;
	if(isset($_POST['op'])){
		update_option('sideblog_options',$_POST['sideblog_options']);
		echo "<div id=\"message\" class=\"updated fade\"><p>Sideblog Options Updated</p></div>\n";

	}
	$sideblog_options = get_option('sideblog_options');
	
	$rows = $wpdb->get_results("SELECT cat_ID as id, cat_name as name, category_nicename as slug FROM " . $wpdb->categories . " ORDER BY cat_name");
	
	$catlist = "";
	if($rows) {
		$alt = true;
		foreach($rows as $row) {
			if($alt) {
				$class="class='alternate'";
				$alt = false;
			}else{
				$class="class=''";
				$alt = true;
			}

			$excludefromfeeds = "";
			if(isset($sideblog_options['excludefromfeeds'][$row->id])){
				$excludefromfeeds = "checked='checked'";
			}

			$setaside = "";
			if(isset($sideblog_options['setaside'][$row->id])){
				$setaside = "checked='checked'";
			}
	
			$numentries = "";
			$postno = isset($sideblog_options['numentries'][$row->id]) ? $sideblog_options['numentries'][$row->id] : '';
			if(trim($postno)==''){
				for($i=1;$i<=$sb_defaultposts;$i++){
					if($postno == $i){
						$numentries .= "<option value=\"" . $i . "\" selected='true' >" . $i . "</option>\n";
					} else {
						$numentries .= "<option value=\"" . $i . "\">" . $i . "</option>\n";
					}
				}
			} else {
				for($i=1;$i<=$sb_defaultposts;$i++){
					if($postno == $i){
						$numentries .= "<option value=\"" . $i . "\" selected='true' >" . $i . "</option>\n";
					} else {
						$numentries .= "<option value=\"" . $i . "\">" . $i . "</option>\n";
					}
				}
			}

			$displayformat = isset($sideblog_options['displayformat'][$row->id]) ? $sideblog_options['displayformat'][$row->id]: '' ;
			if(trim($displayformat)==''){
				$displayformat = $sb_defaultformat;
			}

			$displayformat = htmlspecialchars(stripslashes($displayformat));

			$catlist .= "<tr " . $class . ">\n<td align='center'><input type=\"checkbox\" name=\"sideblog_options[setaside][$row->id]\" value=\"$row->id\" " . $setaside . "/></td>\n";
			$catlist .= "<td>" . $row->name . "</td>\n";
			$catlist .= "<td>" . $row->slug . "</td>\n";
			$catlist .= "<td align='center'><input type=\"text\" name=\"sideblog_options[displayformat][" . $row->id . "]\" value=\"" . $displayformat . "\" style=\"width:90%;\"/></td>\n";
			$catlist .= "<td align='center'><select name=\"sideblog_options[numentries][" . $row->id . "]\">" . $numentries . "</select></td>";
			$catlist .= "<td align='center'><input type=\"checkbox\" name=\"sideblog_options[excludefromfeeds][" . $row->id . "]\" value=\"" . $row->id . "\" " . $excludefromfeeds . "/></td>\n</tr>\n";
		}
	}
	
	echo '
		<div class="wrap">
			<h2>' . __('Sideblog','sideblog') . '</h2>
			<form name="sideblog_options" method="POST">
				<input type="hidden" name="sideblog_options_update" value="update" />
				<fieldset class="options">
					<table width="100%" cellpadding="10px">
						<tr>
							<th width="8%">Select Categories
							</th>
							<th width="15%">Category Name
							</th>
							<th width="15%">Category Slug
							</th>
							<th>Display Format
							</th>	
							<th width="8%">Number of Entries
							</th>
							<th width="8%">Exclude from Feeds
							</th>
						</tr>
						' . $catlist . '
					</table>
				</fieldset>
				<p class="submit"><input type="submit" value="Update Sideblog Options"/></p>
				<input type="hidden" name="op" value="update"/>
				<legend>Display Format Tags</legend>
					<ul>
					<li>%title%</li>
					<li>%title_url%</li>
					<li>%content%</li>
					<li>%permalink%</li>
					<li>%postdate%</li>
					<li>%postdate_url%</li>
					<li>%excerpt%</li>
					<li>%excerpt_&lt;length&gt;% - e.g. %excerpt_200% (will cut after 200 words)</li>
					<li><a href="http://www.php.net/date">PHP Date Format</a> - e.g. %m%/%d%/%Y% - 08-11-2006</li>
					</ul>
			</form>
		</div>';
}


function sideblog_add_option_page() {
	add_options_page('Sideblog','Sideblog',9,basename(__FILE__),'sideblog_option_page');
}

function sideblog_install(){
	add_option('sideblog_options');
	//add_option('widget_sideblog');
}

function sideblog_uninstall(){
	delete_option('sideblog_options');
	delete_option('widget_sideblog');

}

function widget_sideblogwidget($args,$number=1){
	global $registered_widgets;
	extract($args);
	$options = get_option('widget_sideblog');
	$title = $options[$number]['title'];
	$category = $options[$number]['category'];

	if(empty($title)){
		$title = '&nbsp;';
	}
	echo $before_widget . $before_title . $title . $after_title . "<ul>";
	sideblog($category);
	echo "</ul>" . $after_widget;
}

function widget_sideblogwidget_control($number){
	global $wpdb;
	$sideblog_options = get_option('sideblog_options');
	$options = $newoptions = get_option('widget_sideblog');
	if ( !is_array($options) )
		$options = $newoptions = array();
	$newoptions['number'] = count($sideblog_options['setaside']);
	if(isset($_POST["sideblog-submit-$number"])) {
		$newoptions[$number]['title'] = strip_tags(stripslashes($_POST["sideblog-title-$number"]));
		$newoptions[$number]['category'] = $_POST["sideblog-category-$number"];
	}
	if($options != $newoptions) {
		$options = $newoptions;
		update_option('widget_sideblog', $options);
	}
	//$title = htmlspecialchars($options[$number]['title'], ENT_QUOTES);
	
	$title = attribute_escape($options[$number]['title']);
	
	$rows = $wpdb->get_results("SELECT cat_ID as id, cat_name as name, category_nicename as slug FROM " . $wpdb->categories . " ORDER BY cat_name");

	$catlist = "";
	if($rows){
		foreach($rows as $row){
			if(isset($sideblog_options['setaside'][$row->id])){
				if($options[$number]['category']==$row->slug){ 
					$catlist .= "<option value=\"" . $row->slug . "\" selected=\"selected\">" . $row->name . "</option>";
				} else {
					$catlist .= "<option value=\"" . $row->slug . "\">" . $row->name . "</option>";
				}
			}
		}
	}

?>
	<input style="width: 250px;" id="sideblog-title-<?php echo "$number"; ?>" name="sideblog-title-<?php echo "$number"; ?>" type="text" value="<?php echo $title; ?>" />
	<select name="sideblog-category-<?php echo $number; ?>"><?php echo $catlist; ?></select>	
	<input type="hidden" id="sideblog-submit-<?php echo $number; ?>" name="sideblog-submit-<?php echo $number; ?>" value="<?php echo $number; ?>" />

<?php

}

function sideblog_widget_init(){
	global $registered_widgets;
	if(function_exists('register_sidebar_widget')){
		$sideblog_options = get_option('sideblog_options');
		if($sideblog_options['setaside']){
			$number = count($sideblog_options['setaside']);
			$class = array('classname' => 'widget_sideblog');
			for($i=1;$i<=$number;$i++){
				$id = "sideblog-$id";
				$name = sprintf(__('Sideblog %s'),$i);
				if(function_exists('wp_register_sidebar_widget')){
					wp_register_sidebar_widget($id, $name,'widget_sideblogwidget',$class, $i);
					wp_register_widget_control($id, $name,'widget_sideblogwidget_control', array('width'=>300,'height'=>200),$i);
				} else {
					register_sidebar_widget($name, 'widget_sideblogwidget', $i);
					register_widget_control($name, 'widget_sideblogwidget_control', 300,200, $i);
				}
			}
		}
		register_sidebar_widget('SB Recent Posts','sideblog_recent_entries');
	}
}

//A modified the_content_rss function
function sideblog_excerpt($content,$cut = 0, $encode_html = 0) {

	if ($cut && !$encode_html) {
		$encode_html = 2;
	}
	if ($encode_html == 1) {
		$content = wp_specialchars($content);
		$cut = 0;
	} elseif ($encode_html == 0) {
		$content = make_url_footnote($content);
	} elseif ($encode_html == 2) {
		$content = strip_tags($content);
	}
	if ($cut) {
		$blah = explode(' ', $content);
		if (count($blah) > $cut) {
			$k = $cut;
			$use_dotdotdot = 1;
		} else {
			$k = count($blah);
			$use_dotdotdot = 0;
		}
		for ($i=0; $i<$k; $i++) {
			$excerpt .= $blah[$i].' ';
		}
		$excerpt .= ($use_dotdotdot) ? '...' : '';
		$content = $excerpt;
	}
	$content = str_replace(']]>', ']]&gt;', $content);
	return $content;
}

add_filter('posts_where','sideblog_where');
add_filter('posts_join','sideblog_join');
add_filter('posts_distinct','sideblog_distinct');
add_filter('posts_orderby','sideblog_orderby');
add_action('admin_menu','sideblog_add_option_page');
register_activation_hook(__FILE__,'sideblog_install');
register_deactivation_hook(__FILE__,'sideblog_uninstall');
add_action('plugins_loaded','sideblog_widget_init');

?>
