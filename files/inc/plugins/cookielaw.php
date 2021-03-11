<?php
/**
 * Cookie Law 1.0.0

 * Copyright 2016 Matthew Rogowski

 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at

 ** http://www.apache.org/licenses/LICENSE-2.0

 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
**/

if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook('global_start', 'overagelaw_global_start');
$plugins->add_hook('global_intermediate', 'overagelaw_global_intermediate');
$plugins->add_hook('global_end', 'overagelaw_global_end');
$plugins->add_hook('misc_start', 'overagelaw_misc');
$plugins->add_hook('admin_load', 'overagelaw_clear_overages');

function overagelaw_info()
{
	return array(
		"name" => "Overage Law",
		"description" => "Give information and gain consent for overages to be set by the forum.",
		"website" => "https://github.com/MattRogowski/Overage-Law",
		"author" => "Matt Rogowski",
		"authorsite" => "https://matt.rogow.ski",
		"version" => "1.0.0",
		"compatibility" => "16*,18*",
		"codename" => "overagelaw"
	);
}

function overagelaw_activate()
{
	global $mybb, $db;
	
	overagelaw_deactivate();
	
	require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';
	
	$settings_group = array(
		"name" => "overagelaw",
		"title" => "Overage Law Settings",
		"description" => "Settings for the overage law plugin.",
		"disporder" => "28",
		"isdefault" => 0
	);
	$db->insert_query("settinggroups", $settings_group);
	$gid = $db->insert_id();
	
	$settings = array();
	$settings[] = array(
		"name" => "overagelaw_method",
		"title" => "Display Method",
		"description" => "How do you want the message to function?<br /><strong>Notify:</strong> A message will be displayed notifying users that overages are used, but no method of opting out.<br /><strong>Opt In/Out:</strong> Give people a choice on whether they want to accept the use of overages or not.",
		"optionscode" => "radio
notify=Notify
opt=Opt In/Out",
		"value" => "notify"
	);
	$i = 1;
	foreach($settings as $setting)
	{
		$insert = array(
			"name" => $db->escape_string($setting['name']),
			"title" => $db->escape_string($setting['title']),
			"description" => $db->escape_string($setting['description']),
			"optionscode" => $db->escape_string($setting['optionscode']),
			"value" => $db->escape_string($setting['value']),
			"disporder" => intval($i),
			"gid" => intval($gid),
		);
		$db->insert_query("settings", $insert);
		$i++;
	}
	
	rebuild_settings();
	
	find_replace_templatesets("header", "#".preg_quote('<div id="container">')."#i", '{$overagelaw}<div id="container">');
	if(substr($mybb->version, 0, 3) == '1.6')
	{
		find_replace_templatesets("footer", "#".preg_quote('{$lang->bottomlinks_syndication}</a>')."#i", '{$lang->bottomlinks_syndication}</a> | <a href="{$mybb->settings[\'bburl\']}/misc.php?action=overagelaw_info">{$lang->overagelaw_footer}</a>');

		$js_header = "document.observe(\"dom:loaded\", function() {
	\$('overages').on('click', '.overagelaw_disallow', function(Event) {
		if(!confirm('{\$lang->overagelaw_disallow_confirm}'))
		{
			Event.stop();
		}
	});
});";
		$js_info = "document.observe(\"dom:loaded\", function() {
	\$('container').on('click', '.overagelaw_disallow', function(Event) {
		if(!confirm('{\$lang->overagelaw_disallow_confirm}'))
		{
			Event.stop();
		}
	});
});";
	}
	elseif(substr($mybb->version, 0, 3) == '1.8')
	{
		find_replace_templatesets("footer", "#".preg_quote('{$lang->bottomlinks_syndication}</a></li>')."#i", '{$lang->bottomlinks_syndication}</a></li>'."\n\t\t\t\t".'<li><a href="{$mybb->settings[\'bburl\']}/misc.php?action=overagelaw_info">{$lang->overagelaw_footer}</a></li>');

		$js_header = "jQuery(document).ready(function() {
	jQuery('#overages .overagelaw_disallow').click(function() {
		if(!confirm('{\$lang->overagelaw_disallow_confirm}'))
		{
			return false;
		}
	});
});";
		$js_info = "jQuery(document).ready(function() {
	jQuery('#container .overagelaw_disallow').click(function() {
		if(!confirm('{\$lang->overagelaw_disallow_confirm}'))
		{
			return false;
		}
	});
});";
	}
	
	$templates = array();
	$templates[] = array(
		"title" => "overagelaw_info",
		"template" => "<html>
<head>
<title>{\$lang->overagelaw_info_title}</title>
{\$headerinclude}
<script type=\"text/javascript\">
".$js_info."
</script>
</head>
<body>
{\$header}
<form action=\"{\$mybb->settings['bburl']}/misc.php?action=overagelaw_change\" method=\"post\">
	<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
		<tr>		
			<td class=\"thead\" colspan=\"4\"><strong>{\$lang->overagelaw_header}</strong></td>
		</tr>
		<tr>		
			<td class=\"trow1\" colspan=\"4\">{\$lang->overagelaw_description}</td>
		</tr>
		<tr>		
			<td class=\"tcat\"><strong>{\$lang->overagelaw_info_overage_name}</strong></td>
			<td class=\"tcat\"><strong>{\$lang->overagelaw_info_overage_description}</strong></td>
			<td class=\"tcat\" align=\"center\"><strong>{\$lang->overagelaw_info_overages_set_logged_in}</strong></td>
			<td class=\"tcat\" align=\"center\"><strong>{\$lang->overagelaw_info_overages_set_guest}</strong></td>
		</tr>
		{\$overages_rows}
		<tr>		
			<td class=\"tfoot\" colspan=\"4\"><div style=\"text-align: center;\">{\$buttons}</div></td>
		</tr>
	</table>
</form>
{\$footer}
</body>
</html>"
	);
	$templates[] = array(
		"title" => "overagelaw_header",
		"template" => "<script type=\"text/javascript\">
".$js_header."
</script>
<div id=\"overages\" style=\"width: 100%; text-align: left; margin-bottom: 10px;\">
	<form action=\"{\$mybb->settings['bburl']}/misc.php?action=overagelaw_change\" method=\"post\">
		<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
			<tr>		
				<td class=\"thead\"><strong>{\$lang->overagelaw_header}</strong></td>
			</tr>
			<tr>		
				<td class=\"trow1\">{\$lang->overagelaw_description}<br /><br />{\$lang->overagelaw_description_setoverage}</td>
			</tr>
			<tr>		
				<td class=\"tfoot\"><div class=\"float_right\">{\$buttons}</div></td>
			</tr>
		</table>
	</form>
</div>"
	);
	$templates[] = array(
		"title" => "overagelaw_buttons_notify",
		"template" => "<input type=\"submit\" name=\"okay\" value=\"{\$lang->overagelaw_ok}\" />{\$more_info}<input type=\"hidden\" name=\"my_post_key\" value=\"{\$mybb->post_code}\" />"
	);
	$templates[] = array(
		"title" => "overagelaw_buttons_opt",
		"template" => "<input type=\"submit\" name=\"allow\" value=\"{\$lang->overagelaw_allow}\" /> <input type=\"submit\" name=\"disallow\" class=\"overagelaw_disallow\" value=\"{\$lang->overagelaw_disallow}\" />{\$more_info}<input type=\"hidden\" name=\"my_post_key\" value=\"{\$mybb->post_code}\" />"
	);
	$templates[] = array(
		"title" => "overagelaw_button_more_info",
		"template" => "<input type=\"submit\" name=\"more_info\" value=\"{\$lang->overagelaw_more_info}\" />"
	);
	$templates[] = array(
		"title" => "overagelaw_header_no_overages",
		"template" => "<div id=\"overages\" style=\"display: inline-block; text-align: left; margin-bottom: 10px; padding: 4px; font-size: 10px; border: 1px solid #000000;\">
	{\$lang->overagelaw_description_no_overages}
</div>"
	);
	
	foreach($templates as $template)
	{
		$insert = array(
			"title" => $db->escape_string($template['title']),
			"template" => $db->escape_string($template['template']),
			"sid" => "-1",
			"version" => "1800",
			"status" => "",
			"dateline" => TIME_NOW
		);
		
		$db->insert_query("templates", $insert);
	}
}

function overagelaw_deactivate()
{
	global $mybb, $db;
	
	require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';
	
	$db->delete_query("settinggroups", "name = 'overagelaw'");
	
	$settings = array(
		"overagelaw_method"
	);
	$settings = "'" . implode("','", $settings) . "'";
	$db->delete_query("settings", "name IN ({$settings})");
	
	rebuild_settings();
	
	find_replace_templatesets("header", "#".preg_quote('{$overagelaw}')."#i", '', 0);
	if(substr($mybb->version, 0, 3) == '1.6')
	{
		find_replace_templatesets("footer", "#".preg_quote(' | <a href="{$mybb->settings[\'bburl\']}/misc.php?action=overagelaw_info">{$lang->overagelaw_footer}</a>')."#i", '', 0);
	}
	elseif(substr($mybb->version, 0, 3) == '1.8')
	{
		find_replace_templatesets("footer", "#".preg_quote("\n\t\t\t\t".'<li><a href="{$mybb->settings[\'bburl\']}/misc.php?action=overagelaw_info">{$lang->overagelaw_footer}</a></li>')."#i", '', 0);
	}
	
	$db->delete_query("templates", "title IN ('overagelaw_info','overagelaw_header','overagelaw_buttons_notify','overagelaw_buttons_opt','overagelaw_button_more_info','overagelaw_header_no_overages')");
}

function overagelaw_global_start()
{

	global $templatelist;

	if(!isset($templatelist))
	{
		$templatelist = '';
	}

	$templatelist .= ', overagelaw_button_more_info, overagelaw_buttons_notify, overagelaw_header';
}

function overagelaw_global_intermediate()
{
	global $mybb, $lang, $templates, $theme, $overagelaw;
	
	$lang->load('overagelaw');

	if(!isset($mybb->overages['mybb']['allow_overages']))
	{
		if(substr($mybb->version, 0, 3) == '1.6')
		{
			// 1.6 compatibility - $theme not available in global_start, spoof default table settings
			$theme = array('borderwidth' => 1, 'tablespace' => 4);
		}

		eval("\$more_info = \"".$templates->get("overagelaw_button_more_info")."\";");
		eval("\$buttons = \"".$templates->get("overagelaw_buttons_".$mybb->settings['overagelaw_method'])."\";");
		eval("\$overagelaw = \"".$templates->get("overagelaw_header")."\";");
	}
	elseif(isset($mybb->overages['mybb']['allow_overages']) && $mybb->overages['mybb']['allow_overages'] == '0')
	{
		$lang->overagelaw_description_no_overages = $lang->sprintf($lang->overagelaw_description_no_overages, $mybb->settings['bburl']);
		eval("\$overagelaw = \"".$templates->get("overagelaw_header_no_overages")."\";");
	}
	
	overagelaw_clear_overages();
}

function overagelaw_global_end()
{
	 overagelaw_clear_overages();
}

function overagelaw_misc()
{
	global $mybb, $lang, $templates, $theme, $overagelaw_info, $header, $headerinclude, $footer;
	
	$lang->load('overagelaw');
	
	if($mybb->input['action'] == 'overagelaw_change')
	{
		if(isset($mybb->input['more_info']))
		{
			// hack to show no redirect
			$mybb->settings['redirects'] = 0;
			redirect('misc.php?action=overagelaw_info');
		}
		else
		{
			if(isset($mybb->input['disallow']))
			{
				overagelaw_clear_overages();
				my_setoverage('mybb[allow_overages]', '0');
			}
			else
			{
				my_setoverage('mybb[allow_overages]', '1');

				if($mybb->input['okay'])
				{
					$lang->overagelaw_redirect = '';
				}
			}
			redirect('index.php', $lang->overagelaw_redirect);
		}
	}
	elseif($mybb->input['action'] == 'overagelaw_info')
	{
		$overages_rows = '';
		$overages = overagelaw_get_overages();
		foreach($overages as $overage_name => $info)
		{
			if(isset($info['mod']) || isset($info['admin']))
			{
				$overage_user_type = '';
				if($info['mod'])
				{
					$overage_user_type = $lang->overagelaw_info_overages_set_mod;
				}
				elseif($info['admin'])
				{
					$overage_user_type = $lang->overagelaw_info_overages_set_admin;
				}
				
				$trow = alt_trow();
				$overage_description = 'overagelaw_overage_'.$overage_name.'_desc';
				$overages_rows .= '<tr>
					<td class="'.$trow.'">'.$overage_name.'</td>
					<td class="'.$trow.'">'.$lang->$overage_description.'</td>
					<td class="'.$trow.'" align="center">'.$overage_user_type.'</td>
					<td class="'.$trow.'" align="center">-</td>
				</tr>';
			}
			else
			{
				if(substr($mybb->version, 0, 3) == '1.6')
				{
					$ext = 'gif';
				}
				elseif(substr($mybb->version, 0, 3) == '1.8')
				{
					$ext = 'png';
				}
				$overage_member = $overage_guest = '';
				if($info['member'])
				{
					$overage_member = '<img src="'.$mybb->settings['bburl'].'/images/valid.'.$ext.'" alt="" title="" />';
				}
				else
				{
					$overage_member = '<img src="'.$mybb->settings['bburl'].'/images/invalid.'.$ext.'" alt="" title="" />';
				}
				if($info['guest'])
				{
					$overage_guest = '<img src="'.$mybb->settings['bburl'].'/images/valid.'.$ext.'" alt="" title="" />';
				}
				else
				{
					$overage_guest = '<img src="'.$mybb->settings['bburl'].'/images/invalid.'.$ext.'" alt="" title="" />';
				}
				$trow = alt_trow();
				$overage_description = 'overagelaw_overage_'.$overage_name.'_desc';
				$overages_rows .= '<tr>
					<td class="'.$trow.'">'.$overage_name.'</td>
					<td class="'.$trow.'">'.$lang->$overage_description.'</td>
					<td class="'.$trow.'" align="center">'.$overage_member.'</td>
					<td class="'.$trow.'" align="center">'.$overage_guest.'</td>
				</tr>';
			}
		}
		
		if($mybb->settings['overagelaw_method'] == 'opt')
		{
			eval("\$buttons = \"".$templates->get("overagelaw_buttons_".$mybb->settings['overagelaw_method'])."\";");
		}
		eval("\$overagelaw_info = \"".$templates->get("overagelaw_info")."\";");
		output_page($overagelaw_info);
	}
}

function overagelaw_clear_overages()
{
	global $mybb, $session;
	
	if(isset($mybb->overages['mybb']['allow_overages']) && $mybb->overages['mybb']['allow_overages'] == '0' && !defined('IN_ADMINCP'))
	{
		$overages = overagelaw_get_overages(true);
		foreach($overages as $overage_name => $info)
		{
			if($overage_name == 'mybb[allow_overages]')
			{
				continue;
			}
			my_unsetoverage($overage_name);
		}
		foreach($mybb->overages as $key => $val)
		{
			if(strpos($key, 'inlinemod_') !== false)
			{
				my_unsetoverage($key);
			}
		}
		unset($mybb->user);
		unset($mybb->session);
		$session->load_guest();
	}
}

function overagelaw_get_overages($all = false)
{
	global $mybb;
	
	$overages = array(
		'sid' => array(
			'member' => true,
			'guest' => true
		),
		'mybbuser' => array(
			'member' => true,
			'guest' => false
		),
		'mybb[lastvisit]' => array(
			'member' => false,
			'guest' => true
		),
		'mybb[lastactive]' => array(
			'member' => false,
			'guest' => true
		),
		'mybb[threadread]' => array(
			'member' => false,
			'guest' => true
		),
		'mybb[forumread]' => array(
			'member' => false,
			'guest' => true
		),
		'mybb[readallforums]' => array(
			'member' => false,
			'guest' => true
		),
		'mybb[announcements]' => array(
			'member' => true,
			'guest' => true
		),
		'mybb[referrer]' => array(
			'member' => false,
			'guest' => true
		),
		'forumpass' => array(
			'member' => true,
			'guest' => true
		),
		'mybblang' => array(
			'member' => false,
			'guest' => true
		),
		'mybbtheme' => array(
			'member' => false,
			'guest' => true
		),
		'collapsed' => array(
			'member' => true,
			'guest' => true
		),
		'coppauser' => array(
			'member' => false,
			'guest' => true
		),
		'coppadob' => array(
			'member' => false,
			'guest' => true
		),
		'loginattempts' => array(
			'member' => false,
			'guest' => true
		),
		'failedlogin' => array(
			'member' => false,
			'guest' => true
		),
		'fcollapse' => array(
			'member' => false,
			'guest' => true
		),
		'multiquote' => array(
			'member' => true,
			'guest' => true
		),
		'pollvotes' => array(
			'member' => true,
			'guest' => true
		),
		'mybbratethread' => array(
			'member' => false,
			'guest' => true
		),
		'mybb[allow_overages]' => array(
			'member' => true,
			'guest' => true
		)
	);
	
	if($all || is_moderator())
	{
		$overages['inlinemod_*'] = array(
			'mod' => true
		);
	}
	
	if($all || $mybb->usergroup['cancp'] == 1)
	{
		$overages['adminsid'] = array(
			'admin' => true
		);
		$overages['acploginattempts'] = array(
			'admin' => true
		);
		$overages['acpview'] = array(
			'admin' => true
		);
	}
	
	return $overages;
}