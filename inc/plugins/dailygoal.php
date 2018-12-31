<?php

if(!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.");
}

$plugins->add_hook("index_start", "dailygoal_index_start");

function dailygoal_info()
{
    return array(
        "name"          => 'Today Goal',
        "description"   => 'Set a goal to reach daily',
        "website"       => "https://www.developement.design/",
        "author"        => "AmazOuz",
        "authorsite"    => "https://www.developement.design/",
        "version"       => "1.0",
        "guid"          => "dailygoal",
        "compatibility" => "18*"
    );
}

function dailygoal_install()
{
	global $db;
	$setting_group = array(
		'name'			=> 'dailygoal',
		'title'			=> 'Today Goal Settings',
		'description'	=> 'Settings for today goal plugin.',
		'disporder'		=> '1'
	);
    $db->insert_query('settinggroups', $setting_group);
	$gid = $db->insert_id();

	$dailygoal_type = array(
		'name'			=> 'dailygoal_type',
		'title'			=> 'Goal Type',
		'description'	=> 'Select a goal type.',
		'optionscode'	=> 'select
posts=New Posts
threads=New Threads
users=New Users
active=Active Users', 
		'value'			=> 'posts', 
		'disporder'		=> '4', 
		'gid'			=> intval($gid)
	);
	$db->insert_query('settings', $dailygoal_type);

    $dailygoal_value = array(
		'name'			=> 'dailygoal_value',
		'title'			=> 'Goal Value',
		'description'	=> 'Set a value for the goal. For example : 20',
		'optionscode'	=> 'text', 
		'value'			=> '20', 
		'disporder'		=> '2', 
		'gid'			=> intval($gid)
	);
	$db->insert_query('settings', $dailygoal_value);
	rebuild_settings();
}

function dailygoal_is_installed()
{
    global $mybb;
    if (isset($mybb->settings['dailygoal_value']))
    {
        return true;
    }
    else
    {
        return false;
    }
}

function dailygoal_uninstall()
{
	global $db;
	$db->write_query("DELETE FROM ".TABLE_PREFIX."settings WHERE name IN ('dailygoal_value','dailygoal_type')");

	$db->write_query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name = 'dailygoal'");
	rebuild_settings();
}

function dailygoal_activate()
{
    global $db;
    $template = '
    <table border="0" cellspacing="0" cellpadding="5" class="tborder" style="width: 24%; float: right;">
    <tr>
    <td class="thead" colspan="5">
    <strong><a href="#">Daily Goal</a></strong>
    </td>
    </tr>
    
    <tr>
    <td class="trow1" colspan="5">{$goal_title}</td>
    </tr>
    
    <tr>
    <td class="trow1" width="50%">Progress:</td>
    <td class="trow1" width="50%">{$goal_progress}/{$goal}</td>
    </tr>

    <tr>
    <td class="trow1" width="50%">Status:</td>
    <td class="trow1" width="50%">{$goal_result}</td>
    </tr>
    </table>';

    $insert_template = array(
    	'title'    => 'dailygoal',
    	'template' => $db->escape_string($template),
    	'sid'      => '-1',
    	'version'  => '',
    	'dateline' => TIME_NOW
    );

    $db->insert_query('templates', $insert_template);

    require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
    find_replace_templatesets("index", '#'.preg_quote('{$forums}').'#', '{$forums}{$dailygoal}');
    find_replace_templatesets("index", '#'.preg_quote('{$forums}').'#', '<div style="width: 75%; float: left;">{$forums}</div>');
}

function dailygoal_deactivate()
{
    global $db;
	$db->delete_query('templates', 'title = \'dailygoal\''); 
    require MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets("index", '#'.preg_quote('{$dailygoal}').'#', '');
	find_replace_templatesets("index", '#'.preg_quote('<div style="width: 75%; float: left;">{$forums}</div>').'#', '{$forums}');
}

function dailygoal_index_start() {
    
   global $mybb, $db, $goal_result, $goal_progress, $goal_title, $goal, $templates, $dailygoal;

   $dailygoal_type = $mybb->settings['dailygoal_type'];
   $goal = $mybb->settings['dailygoal_value'];
    
   $timecut = TIME_NOW - 86400;

   $query = $db->simple_select("posts", "COUNT(*) AS newposts", "dateline > '$timecut' AND visible='1'");
   $newposts = my_number_format($db->fetch_field($query, "newposts"));

   $query = $db->simple_select("threads", "COUNT(*) AS newthreads", "dateline > '$timecut' AND visible='1' AND closed NOT LIKE 'moved|%'");
   $newthreads = my_number_format($db->fetch_field($query, "newthreads"));

   $query = $db->simple_select("users", "COUNT(uid) AS newusers", "regdate > '$timecut'");
   $newusers = my_number_format($db->fetch_field($query, "newusers"));

   $query = $db->simple_select("users", "COUNT(uid) AS activeusers", "lastvisit > '$timecut'");
   $activeusers = my_number_format($db->fetch_field($query, "activeusers"));
    
    if ($dailygoal_type == "posts")
    {
        $goal_progress = $newposts;
        $goal_title = "Get $goal new posts";  
        if ($newposts >= $goal) {
            $goal_result = '<span style="color: rgb(80, 252, 16);">Reached</span>';
        }
        else
        {
            $goal_result = '<span style="color: #f33;">Not Reached</span>';
        }
    }
    if ($dailygoal_type == "threads")
    {
        $goal_progress = $newthreads;
        $goal_title = "Get $goal new threads";  
        if ($newthreads >= $goal) {
            $goal_result = '<span style="color: rgb(80, 252, 16);">Reached</span>';
        }
        else
        {
            $goal_result = '<span style="color: #f33;">Not Reached</span>';
        }
    }
    if ($dailygoal_type == "users")
    {
        $goal_progress = $newusers;
        $goal_title = "Get $goal new members";  
        if ($newusers >= $goal) {
            $goal_result = '<span style="color: rgb(80, 252, 16);">Reached</span>';
        }
        else
        {
            $goal_result = '<span style="color: #f33;">Not Reached</span>';
        }
    }
    if ($dailygoal_type == "active")
    {
        $goal_progress = $activeusers;
        $goal_title = "Get $goal active users";  
        if ($activeusers >= $goal) {
            $goal_result = '<span style="color: rgb(80, 252, 16);">Reached</span>';
        }
        else
        {
            $goal_result = '<span style="color: #f33;">Not Reached</span>';
        }
    }
    
    eval('$dailygoal = "' . $templates->get('dailygoal') . '";');
}
?>
