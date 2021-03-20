<?php
/**
 * This file is part of Hide Thread Content plugin for MyBB.
 * Copyright (C) Sunil Baral, Vintagedaddyo
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
 
//disallow unauthorize access

if(!defined("IN_MYBB")) {
	die("You are not authorized to view this");
}

//Hooks

$plugins->add_hook('postbit', 'hidetillreply_hide');
$plugins->add_hook('parse_quoted_message', 'hidetillreply_quote');
$plugins->add_hook('newreply_threadreview_post','hidetillreply_newreply');
$plugins->add_hook('search_results_post', 'hidetillreply_search');
$plugins->add_hook('portal_announcement', 'hidetillreply_portal');
$plugins->add_hook('printthread_post', 'hidetillreply_printthread');
$plugins->add_hook('archive_thread_post', 'hidetillreply_archive');


//Plugin Information

function hidetillreply_info()
{
	global $db, $mybb, $settings, $lang;

	$lang->load("hidetillreply");

	return array(
		'name' => $lang->hidetillreply_name,
		'author' => $lang->hidetillreply_author,
		'website' => $lang->hidetillreply_website,
		'authorsite' => $lang->hidetillreply_website,		
		'description' => $lang->hidetillreply_description,
		'version' => '1.1',
		'compatibility' => '18*',
		'guid' => ''
	);
}

//Activate Plugin

function hidetillreply_activate()
{
	global $db, $mybb, $settings, $lang;

	$lang->load("hidetillreply");

	//Admin CP Settings

	$hidetillreply_group = array(
		'gid' => intval($gid),
		'name' => 'hidetillreply',
		'title' => $lang->hidetillreply_settinggroup_title,
		'description' => $lang->hidetillreply_settinggroup_description,
		'disporder' => '1',
		'isdefault' =>  '0'
	);

	$db->insert_query('settinggroups',$hidetillreply_group);

	$gid = $db->insert_id();

	//Enable or Disable

	$hidetillreply_enable = array(
		'sid' => '0',
		'name' => 'hidetillreply_enable',
		'title' => $lang->hidetillreply_setting_1_title,
		'description' => $lang->hidetillreply_setting_1_description,
		'optionscode' => 'yesno',
		'value' => '1',
		'disporder' => '1',
		'gid' => intval($gid)
	);

	//Allowed User Group

	$hidetillreply_allowed_group = array(
		'sid' => '0',
		'name' => 'hidetillreply_allowed_group',
		'title' => $lang->hidetillreply_setting_2_title,
		'description' => $lang->hidetillreply_setting_2_description,
		'optionscode' => 'groupselect',
		'value' => '3,4,6',
		'disporder' => '1',
		'gid' => intval($gid)
	);

	$db->insert_query('settings',$hidetillreply_enable);
	$db->insert_query('settings',$hidetillreply_allowed_group);

	rebuild_settings();
}

//Deactivate Plugin

function hidetillreply_deactivate()
{
	global $db, $mybb, $settings;

	$db->query("DELETE from ".TABLE_PREFIX."settinggroups WHERE name IN ('hidetillreply')");
	$db->query("DELETE from ".TABLE_PREFIX."settings WHERE name IN ('hidetillreply_enable')");
	$db->query("DELETE from ".TABLE_PREFIX."settings WHERE name IN ('hidetillreply_allowed_group')");

	rebuild_settings();
}


//Hide on Postbit

function hidetillreply_hide(&$post)
{
	global $db, $mybb, $settings, $thread, $mainpostpid, $lang;

	$lang->load("hidetillreply");

	$mainpostpid = (int)$thread['firstpost'];

	//If Plugin is enabled

    if($settings['hidetillreply_enable'] == 1) {

		//Just run this for first post in thread not all

		if((int)$post['pid']===$mainpostpid) {
			if(!$mybb->user['uid'] || is_member($settings['hidetillreply_allowed_group'],$post)) {
				$usergroup = $mybb->user['usergroup'];
				//If user is guest
				if($usergroup==1) {
					$post['message'] = "<div class='red_alert'><b>{$lang->hidetillreply_login_unlock}</b></div>";
				} else {
					$maintid = (int)$thread['tid'];
					$mainuid = (int)$mybb->user['uid'];
					$query = $db->simple_select("posts","*","tid='$maintid' AND uid='$mainuid'");
					$rows = $db->num_rows($query);

					//If user has replied to the thread return else hide

					if($rows>0) {
					} else {
						$post['message'] = "<div class='red_alert'><b>{$lang->hidetillreply_reply_unlock}</b></div>";			
					}
				}
			}
		}
	}

}

//Hide while multiquote

function hidetillreply_quote(&$quoted_post)
{
	global $db, $mybb, $post, $settings, $lang;

	$lang->load("hidetillreply");

    if($settings['hidetillreply_enable'] == 1) {
		$mainposttid = (int)$quoted_post['tid'];
		$query = $db->simple_select("threads","*","tid='$mainposttid'");
		$row = $db->fetch_array($query);
		$mainpostpid = (int)$row['firstpost'];

		//Just run this for first post in thread not all

		if((int)$quoted_post['pid']===$mainpostpid) {
			if(!$mybb->user['uid'] || is_member($settings['hidetillreply_allowed_group'],$post)) {
				$usergroup = $mybb->user['usergroup'];

				//If user is guest

				if($usergroup==1) {
					$quoted_post['message'] = "{$lang->hidetillreply_login_unlock}";
				} else {
					$maintid = (int)$post['tid'];
					$mainuid = (int)$mybb->user['uid'];
					$query = $db->simple_select("posts","*","tid='$maintid' AND uid='$mainuid'");
					$rows = $db->num_rows($query);

					//If user has replied to the thread return else hide

					if($rows>0) {
					} else {
						$quoted_post['message'] = "{$lang->hidetillreply_reply_unlock}";			
					}
				}
			}
		}
	}
}


//Hide on New Reply

function hidetillreply_newreply()
{
	global $db, $mybb, $post, $thread, $settings, $lang;

	$lang->load("hidetillreply");

	$mainpostpid = (int)$thread['firstpost'];

    if($settings['hidetillreply_enable'] == 1) {

		//Just run this for first post in thread not all

		if((int)$post['pid']===$mainpostpid) {
			if(!$mybb->user['uid'] || is_member($settings['hidetillreply_allowed_group'],$post)) {
				$usergroup = $mybb->user['usergroup'];

				//If user is guest

				if($usergroup==1) {
					$post['message'] = "[b]{$lang->hidetillreply_login_unlock}[/b]";
				} else {
					$maintid = (int)$thread['tid'];
					$mainuid = (int)$mybb->user['uid'];
					$query = $db->simple_select("posts","*","tid='$maintid' AND uid='$mainuid'");
					$rows = $db->num_rows($query);

					//If user has replied to the thread return else hide

					if($rows>0) {
					} else {
						$post['message'] = "[b]{$lang->hidetillreply_reply_unlock}[/b]";			
					}
				}
			}
		}
	}
}


//Hide on post search

function hidetillreply_search()
{
	global $db, $mybb, $post, $settings, $prev, $lang;

	$lang->load("hidetillreply");

	if($settings['hidetillreply_enable'] == 1) {
		$mainposttid = (int)$post['tid'];
		$query = $db->simple_select("threads","*","tid='$mainposttid'");
		$row = $db->fetch_array($query);
		$mainpostpid = (int)$row['firstpost'];

		//Just run this for first post in thread not all

		if((int)$post['pid']===$mainpostpid) {
			if(!$mybb->user['uid'] || is_member($settings['hidetillreply_allowed_group'],$post)) {
				$usergroup = $mybb->user['usergroup'];

				//If user is guest

				if($usergroup==1) {
					$prev = "{$lang->hidetillreply_login_unlock}";
				} else {
					$maintid = (int)$post['tid'];
					$mainuid = (int)$mybb->user['uid'];
					$query = $db->simple_select("posts","*","tid='$maintid' AND uid='$mainuid'");
					$rows = $db->num_rows($query);

					//If user has replied to the thread return else hide

					if($rows>0) {
					} else {
						$prev = "{$lang->hidetillreply_reply_unlock}";			
					}
				}
			}
		}
	}

}


//Hide on portal page

function hidetillreply_portal()
{
	global $db, $mybb, $settings, $announcement, $lang;

	$lang->load("hidetillreply");

	$mainpostpid = (int)$announcement['firstpost'];

    if($settings['hidetillreply_enable'] == 1) {

		//Just run this for first post in thread not all

		if((int)$announcement['pid']===$mainpostpid) {
			if(!$mybb->user['uid'] || is_member($settings['hidetillreply_allowed_group'],$announcement)) {
				$usergroup = $mybb->user['usergroup'];

				//If user is guest

				if($usergroup==1) {
					$announcement['message'] = "[b]{$lang->hidetillreply_login_unlock}[/b]";
				} else {
					$maintid = (int)$announcement['tid'];
					$mainuid = (int)$mybb->user['uid'];
					$query = $db->simple_select("posts","*","tid='$maintid' AND uid='$mainuid'");
					$rows = $db->num_rows($query);

					//If user has replied to the thread return else hide

					if($rows>0) {
					} else {
						$announcement['message'] = "[b]{$lang->hidetillreply_reply_unlock}[/b]";	
					}
				}
			}
		}
	}
}


//Hide on Printthread page

function hidetillreply_printthread()
{
	global $db, $mybb, $settings, $postrow, $thread, $lang;

	$lang->load("hidetillreply");

	$mainpostpid = (int)$thread['firstpost'];

    if($settings['hidetillreply_enable'] == 1) {

		//Just run this for first post in thread not all

		if((int)$postrow['pid']===$mainpostpid) {
			if(!$mybb->user['uid'] || is_member($settings['hidetillreply_allowed_group'],$postrow)) {
				$usergroup = $mybb->user['usergroup'];

				//If user is guest

				if($usergroup==1) {
					$postrow['message'] = "<b><b>{$lang->hidetillreply_login_unlock}</b>";
				} else {
					$maintid = (int)$thread['tid'];
					$mainuid = (int)$mybb->user['uid'];
					$query = $db->simple_select("posts","*","tid='$maintid' AND uid='$mainuid'");
					$rows = $db->num_rows($query);

					//If user has replied to the thread return else hide

					if($rows>0) {
					} else {
						$postrow['message'] = "<b>{$lang->hidetillreply_reply_unlock}</b>";	
					}
				}
			}
		}
	}

}

//Hide on archive view

function hidetillreply_archive()
{
	global $db, $mybb, $settings, $thread, $post, $lang;

	$lang->load("hidetillreply");

	$mainpostpid = (int)$thread['firstpost'];

    if($settings['hidetillreply_enable'] == 1) {

		//Just run this for first post in thread not all

		if((int)$post['pid']===$mainpostpid) {
			if(!$mybb->user['uid'] || is_member($settings['hidetillreply_allowed_group'],$post)) {
				$usergroup = $mybb->user['usergroup'];

				//If user is guest

				if($usergroup==1) {
					$post['message'] = "<b>{$lang->hidetillreply_login_unlock}</b>";
				} else {
					$maintid = (int)$thread['tid'];
					$mainuid = (int)$mybb->user['uid'];
					$query = $db->simple_select("posts","*","tid='$maintid' AND uid='$mainuid'");
					$rows = $db->num_rows($query);

					//If user has replied to the thread return else hide
					
					if($rows>0) {
					} else {
						$post['message'] = "<b>{$lang->hidetillreply_reply_unlock}</b>";			
					}
				}
			}
		}
	}
}
