<?php
/**
 * Plugin Name: At Home Polls (Advanced Sidebox Edition)
 * Author: Tanweth
 * http://www.kerfufflealliance.com
 *
 * This is a slightly modified version of the polls.php from MyBB core for use with the above plugin. It modifies the redirect behavior.
 *
 * Original code is: Copyright 2010 MyBB Group, All Rights Reserved
 *
 * Website: http://mybb.com
 * License: http://mybb.com/about/license
 *
 * $Id$
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'polls.php');

$templatelist = "changeuserbox,loginbox,polls_editpoll_option,polls_editpoll,polls_showresults_resultbit,polls_showresults";
require_once "../../../global.php";
require_once MYBB_ROOT."inc/functions_post.php";
require_once MYBB_ROOT."inc/class_parser.php";
$parser = new postParser;

// Load global language phrases
$lang->load("polls");

if($mybb->user['uid'] != 0)
{
	eval("\$loginbox = \"".$templates->get("changeuserbox")."\";");
}
else
{
	eval("\$loginbox = \"".$templates->get("loginbox")."\";");
}

if($mybb->input['preview'] || $mybb->input['updateoptions'])
{
	$mybb->input['action'] = "editpoll";
}
if($mybb->input['action'] == "editpoll")
{
	$pid = intval($mybb->input['pid']);
	
	/* MODIFIED REDIRECT BEHAVIOR FOR AT HOME POLLS PLUGIN
	 * Changes the redirect on edit poll to the script/page where the edit poll link was clicked, rather than the thread where the poll originates. Actual redirect is further below.
	 */ 	
	$redirect_url = htmlspecialchars_uni($mybb->input['this_script']);
	
	if ($redirect_url == 'forumdisplay.php')
	{
		$redirect_url = $redirect_url . '?fid=' .(int) $mybb->input['fid'];
	}
	elseif ($redirect_url == 'showthread.php')
	{
		$redirect_url = $redirect_url . '?tid=' .(int) $mybb->input['tid'];
	}
	elseif ($redirect_url == 'member.php')
	{
		$redirect_url = 'index.php';
	}
	elseif (empty($redirect_url))
	{
		$redirect_url = get_thread_link($thread['tid']);
	}

	$plugins->run_hooks("polls_editpoll_start");

	$query = $db->simple_select("polls", "*", "pid='$pid'");
	$poll = $db->fetch_array($query);

	if(!$poll['pid'])
	{
		error($lang->error_invalidpoll);
	}

	$query = $db->simple_select("threads", "*", "poll='$pid'");
	$thread = $db->fetch_array($query);
	$tid = $thread['tid'];
	if(!$tid)
	{
		error($lang->error_invalidthread);
	}

	$fid = $thread['fid'];

	// Make navigation
	add_breadcrumb($lang->nav_editpoll);

	$forumpermissions = forum_permissions($fid);

	// Get forum info
	$forum = get_forum($fid);
	if(!$forum)
	{
		error($lang->error_invalidforum);
	}
	else
	{
		// Is our forum closed?
		if($forum['open'] == 0 && !is_moderator($fid, "caneditposts"))
		{
			// Doesn't look like it is
			error($lang->error_closedinvalidforum);
		}
	}

	if(!is_moderator($fid, "caneditposts"))
	{
		error_no_permission();
	}

	$polldate = my_date($mybb->settings['dateformat'], $poll['dateline']);
	if(!$mybb->input['preview'] && !$mybb->input['updateoptions'])
	{
		if($poll['closed'] == 1)
		{
			$postoptionschecked['closed'] = 'checked="checked"';
		}

		if($poll['multiple'] == 1)
		{
			$postoptionschecked['multiple'] = 'checked="checked"';
		}

		if($poll['public'] == 1)
		{
			$postoptionschecked['public'] = 'checked="checked"';
		}

		$optionsarray = explode("||~|~||", $poll['options']);
		$votesarray = explode("||~|~||", $poll['votes']);


		for($i = 1; $i <= $poll['numoptions']; ++$i)
		{
			$poll['totvotes'] = $poll['totvotes'] + $votesarray[$i-1];
		}

		$question = htmlspecialchars_uni($poll['question']);
		$numoptions = $poll['numoptions'];
		$optionbits = "";
		for($i = 0; $i < $numoptions; ++$i)
		{
			$counter = $i + 1;
			$option = $optionsarray[$i];
			$option = htmlspecialchars_uni($option);
			$optionvotes = intval($votesarray[$i]);

			if(!$optionvotes)
			{
				$optionvotes = 0;
			}

			eval("\$optionbits .= \"".$templates->get("polls_editpoll_option")."\";");
			$option = "";
			$optionvotes = "";
		}

		if(!$poll['timeout'])
		{
			$timeout = 0;
		}
		else
		{
			$timeout = $poll['timeout'];
		}
	}
	else
	{
		if($mybb->settings['maxpolloptions'] && $mybb->input['numoptions'] > $mybb->settings['maxpolloptions'])
		{
			$numoptions = $mybb->settings['maxpolloptions'];
		}
		elseif($mybb->input['numoptions'] < 2)
		{
			$numoptions = "2";
		}
		else
		{
			$numoptions = $mybb->input['numoptions'];
		}
		$question = htmlspecialchars_uni($mybb->input['question']);

		$postoptions = $mybb->input['postoptions'];
		if($postoptions['multiple'] == 1)
		{
			$postoptionschecked['multiple'] = 'checked="checked"';
		}

		if($postoptions['public'] == 1)
		{
			$postoptionschecked['public'] = 'checked="checked"';
		}

		if($postoptions['closed'] == 1)
		{
			$postoptionschecked['closed'] = 'checked="checked"';
		}

		$options = $mybb->input['options'];
		$votes = $mybb->input['votes'];
		$optionbits = '';
		for($i = 1; $i <= $numoptions; ++$i)
		{
			$counter = $i;
			$option = $options[$i];
			$option = htmlspecialchars_uni($option);
			$optionvotes = $votes[$i];

			if(!$optionvotes)
			{
				$optionvotes = 0;
			}

			eval("\$optionbits .= \"".$templates->get("polls_editpoll_option")."\";");
			$option = "";
		}

		if($mybb->input['timeout'] > 0)
		{
			$timeout = $mybb->input['timeout'];
		}
		else
		{
			$timeout = 0;
		}
	}

	$plugins->run_hooks("polls_editpoll_end");

	eval("\$editpoll = \"".$templates->get("asb_poll_edit")."\";");
	output_page($editpoll);
}

if($mybb->input['action'] == "do_editpoll" && $mybb->request_method == "post")
{
	// Verify incoming POST request
	verify_post_check($mybb->input['my_post_key']);

	$plugins->run_hooks("polls_do_editpoll_start");

	$query = $db->simple_select("polls", "*", "pid='".intval($mybb->input['pid'])."'");
	$poll = $db->fetch_array($query);

	if(!$poll['pid'])
	{
		error($lang->error_invalidpoll);
	}

	$query = $db->simple_select("threads", "*", "poll='".intval($mybb->input['pid'])."'");
	$thread = $db->fetch_array($query);
	if(!$thread['tid'])
	{
		error($lang->error_invalidthread);
	}

	$forumpermissions = forum_permissions($thread['fid']);

	// Get forum info
	$forum = get_forum($thread['fid']);
	if(!$forum)
	{
		error($lang->error_invalidforum);
	}
	else
	{
		// Is our forum closed?
		if($forum['open'] == 0 && !is_moderator($fid, "caneditposts"))
		{
			// Doesn't look like it is
			error($lang->error_closedinvalidforum);
		}
	}

	if(!is_moderator($thread['fid'], "caneditposts"))
	{
		error_no_permission();
	}

	if($mybb->settings['maxpolloptions'] && $mybb->input['numoptions'] > $mybb->settings['maxpolloptions'])
	{
		$numoptions = $mybb->settings['maxpolloptions'];
	}
	elseif(!$mybb->input['numoptions'])
	{
		$numoptions = 2;
	}
	else
	{
		$numoptions = $mybb->input['numoptions'];
	}

	$postoptions = $mybb->input['postoptions'];
	if($postoptions['multiple'] != '1')
	{
		$postoptions['multiple'] = 0;
	}

	if($postoptions['public'] != '1')
	{
		$postoptions['public'] = 0;
	}

	if($postoptions['closed'] != '1')
	{
		$postoptions['closed'] = 0;
	}
	$optioncount = "0";
	$options = $mybb->input['options'];

	for($i = 1; $i <= $numoptions; ++$i)
	{
		if(trim($options[$i]) != '')
		{
			$optioncount++;
		}

		if(my_strlen($options[$i]) > $mybb->settings['polloptionlimit'] && $mybb->settings['polloptionlimit'] != 0)
		{
			$lengtherror = 1;
			break;
		}
	}

	if($lengtherror)
	{
		error($lang->error_polloptiontoolong);
	}

	if(trim($mybb->input['question']) == '' || $optioncount < 2)
	{
		error($lang->error_noquestionoptions);
	}

	$optionslist = '';
	$voteslist = '';
	$numvotes = '';
	$votes = $mybb->input['votes'];
	for($i = 1; $i <= $numoptions; ++$i)
	{
		if(trim($options[$i]) != '')
		{
			if($optionslist != '')
			{
				$optionslist .= "||~|~||";
				$voteslist .= "||~|~||";
			}

			$optionslist .= trim($options[$i]);
			if(intval($votes[$i]) <= 0)
			{
				$votes[$i] = "0";
			}
			$voteslist .= $votes[$i];
			$numvotes = $numvotes + $votes[$i];
		}
	}

	if($mybb->input['timeout'] > 0)
	{
		$timeout = intval($mybb->input['timeout']);
	}
	else
	{
		$timeout = 0;
	}

	$updatedpoll = array(
		"question" => $db->escape_string($mybb->input['question']),
		"options" => $db->escape_string($optionslist),
		"votes" => $db->escape_string($voteslist),
		"numoptions" => intval($optioncount),
		"numvotes" => $numvotes,
		"timeout" => $timeout,
		"closed" => $postoptions['closed'],
		"multiple" => $postoptions['multiple'],
		"public" => $postoptions['public']
	);

	$plugins->run_hooks("polls_do_editpoll_process");

	$db->update_query("polls", $updatedpoll, "pid='".intval($mybb->input['pid'])."'");

	$plugins->run_hooks("polls_do_editpoll_end");

	$modlogdata['fid'] = $thread['fid'];
	$modlogdata['tid'] = $thread['tid'];
	log_moderator_action($modlogdata, $lang->poll_edited);

	/* MODIFIED REDIRECT BEHAVIOR FOR AT HOME POLLS PLUGIN
	 * Changes the redirect on edit poll to the script/page where the edit poll link was clicked, rather than the thread where the poll originates.
	 */ 	
	redirect($mybb->input['redirect_url'], $lang->redirect_unvoted);
}

if($mybb->input['action'] == "showresults")
{
	$query = $db->simple_select("polls", "*", "pid='".intval($mybb->input['pid'])."'");
	$poll = $db->fetch_array($query);

	if(!$poll['pid'])
	{
		error($lang->error_invalidpoll);
	}

	$tid = $poll['tid'];
	$query = $db->simple_select("threads", "*", "tid='$tid'");
	$thread = $db->fetch_array($query);
	if(!$thread['tid'])
	{
		error($lang->error_invalidthread);
	}

	$fid = $thread['fid'];

	// Get forum info
	$forum = get_forum($fid);
	if(!$forum)
	{
		error($lang->error_invalidforum);
	}

	$forumpermissions = forum_permissions($forum['fid']);

	$plugins->run_hooks("polls_showresults_start");

	if($forumpermissions['canviewthreads'] == 0 || $forumpermissions['canview'] == 0 || ($forumpermissions['canonlyviewownthreads'] != 0 && $thread['uid'] != $mybb->user['uid']))
	{
		error_no_permission();
	}

	// Make navigation
	build_forum_breadcrumb($fid);
	add_breadcrumb(htmlspecialchars_uni($thread['subject']), get_thread_link($thread['tid']));
	add_breadcrumb($lang->nav_pollresults);

	$voters = array();

	// Calculate votes
	$query = $db->query("
		SELECT v.*, u.username
		FROM ".TABLE_PREFIX."pollvotes v
		LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=v.uid)
		WHERE v.pid='{$poll['pid']}'
		ORDER BY u.username
	");
	while($voter = $db->fetch_array($query))
	{
		// Mark for current user's vote
		if($mybb->user['uid'] == $voter['uid'] && $mybb->user['uid'])
		{
			$votedfor[$voter['voteoption']] = 1;
		}

		// Count number of guests and users without a username (assumes they've been deleted)
		if($voter['uid'] == 0 || $voter['username'] == '')
		{
			// Add one to the number of voters for guests
			++$guest_voters[$voter['voteoption']];
		}
		else
		{
			$voters[$voter['voteoption']][$voter['uid']] = $voter['username'];
		}
	}

	$optionsarray = explode("||~|~||", $poll['options']);
	$votesarray = explode("||~|~||", $poll['votes']);
	for($i = 1; $i <= $poll['numoptions']; ++$i)
	{
		$poll['totvotes'] = $poll['totvotes'] + $votesarray[$i-1];
	}

	$polloptions = '';
	for($i = 1; $i <= $poll['numoptions']; ++$i)
	{
		$parser_options = array(
			"allow_html" => $forum['allowhtml'],
			"allow_mycode" => $forum['allowmycode'],
			"allow_smilies" => $forum['allowsmilies'],
			"allow_imgcode" => $forum['allowimgcode'],
			"allow_videocode" => $forum['allowvideocode'],
			"filter_badwords" => 1
		);
		$option = $parser->parse_message($optionsarray[$i-1], $parser_options);

		$votes = $votesarray[$i-1];
		$number = $i;
		// Make the mark for current user's voted option
		if($votedfor[$number])
		{
			$optionbg = 'trow2';
			$votestar = '*';
		}
		else
		{
			$optionbg = 'trow1';
			$votestar = '';
		}

		if($votes == '0')
		{
			$percent = '0';
		}
		else
		{
			$percent = number_format($votes / $poll['totvotes'] * 100, 2);
		}

		$imagewidth = round($percent/3) * 5;
		$comma = '';
		$guest_comma = '';
		$userlist = '';
		$guest_count = 0;
		if($poll['public'] == 1 || is_moderator($fid))
		{
			if(is_array($voters[$number]))
			{
				foreach($voters[$number] as $uid => $username)
				{
					$userlist .= $comma.build_profile_link($username, $uid);
					$comma = $guest_comma = $lang->comma;
				}
			}

			if($guest_voters[$number] > 0)
			{
				if($guest_voters[$number] == 1)
				{
					$userlist .= $guest_comma.$lang->guest_count;
				}
				else
				{
					$userlist .= $guest_comma.$lang->sprintf($lang->guest_count_multiple, $guest_voters[$number]);
				}
			}
		}
		eval("\$polloptions .= \"".$templates->get("polls_showresults_resultbit")."\";");
	}

	if($poll['totvotes'])
	{
		$totpercent = '100%';
	}
	else
	{
		$totpercent = '0%';
	}

	$plugins->run_hooks("polls_showresults_end");

	$poll['question'] = htmlspecialchars_uni($poll['question']);
	eval("\$showresults = \"".$templates->get("polls_showresults")."\";");
	output_page($showresults);
}
if($mybb->input['action'] == "vote" && $mybb->request_method == "post")
{
	// Verify incoming POST request
	verify_post_check($mybb->input['my_post_key']);

	$query = $db->simple_select("polls", "*", "pid='".intval($mybb->input['pid'])."'");
	$poll = $db->fetch_array($query);
	$poll['timeout'] = $poll['timeout']*60*60*24;

	$plugins->run_hooks("polls_vote_start");

	if(!$poll['pid'])
	{
		error($lang->error_invalidpoll);
	}

	$query = $db->simple_select("threads", "*", "poll='".$poll['pid']."'");
	$thread = $db->fetch_array($query);

	if(!$thread['tid'] || $thread['visible'] == 0)
	{
		error($lang->error_invalidthread);
	}

	$fid = $thread['fid'];
	$forumpermissions = forum_permissions($fid);
	if($forumpermissions['canvotepolls'] == 0)
	{
		error_no_permission();
	}

	// Get forum info
	$forum = get_forum($fid);
	if(!$forum)
	{
		error($lang->error_invalidforum);
	}
	else
	{
		// Is our forum closed?
		if ($forum['open'] == 0)
		{
			// Doesn't look like it is
			error($lang->error_closedinvalidforum);
		}
	}

	$expiretime = $poll['dateline'] + $poll['timeout'];
	$now = TIME_NOW;
	if($poll['closed'] == 1 || $thread['closed'] == 1 || ($expiretime < $now && $poll['timeout']))
	{
		error($lang->error_pollclosed);
	}

	if(!isset($mybb->input['option']))
	{
		error($lang->error_nopolloptions);
	}

	// Check if the user has voted before...
	if($mybb->user['uid'])
	{
		$query = $db->simple_select("pollvotes", "*", "uid='".$mybb->user['uid']."' AND pid='".$poll['pid']."'");
		$votecheck = $db->fetch_array($query);
	}

	if($votecheck['vid'] || (isset($mybb->cookies['pollvotes'][$poll['pid']]) && $mybb->cookies['pollvotes'][$poll['pid']] !== ""))
	{
		error($lang->error_alreadyvoted);
	}
	elseif(!$mybb->user['uid'])
	{
		// Give a cookie to guests to inhibit revotes
		if(is_array($mybb->input['option']))
		{
			// We have multiple options here...
			$votes_cookie = implode(',', array_keys($mybb->input['option']));
		}
		else
		{
			$votes_cookie = $mybb->input['option'];
		}

		my_setcookie("pollvotes[{$poll['pid']}]", $votes_cookie);
	}

	$votesql = '';
	$now = TIME_NOW;
	$votesarray = explode("||~|~||", $poll['votes']);
	$option = $mybb->input['option'];
	$numvotes = (int)$poll['numvotes'];
	if($poll['multiple'] == 1)
	{
		if(is_array($option))
		{
			foreach($option as $voteoption => $vote)
			{
				if($vote == 1 && isset($votesarray[$voteoption-1]))
				{
					if($votesql)
					{
						$votesql .= ",";
					}
					$votesql .= "('".$poll['pid']."','".$mybb->user['uid']."','".$db->escape_string($voteoption)."','$now')";
					$votesarray[$voteoption-1]++;
					$numvotes = $numvotes+1;
				}
			}
		}
	}
	else
	{
		if(is_array($option) || !isset($votesarray[$option-1]))
		{
			error($lang->error_nopolloptions);
		}
		$votesql = "('".$poll['pid']."','".$mybb->user['uid']."','".$db->escape_string($option)."','$now')";
		$votesarray[$option-1]++;
		$numvotes = $numvotes+1;
	}

	if(!$votesql)
	{
		error($lang->error_nopolloptions);
	}

	$db->write_query("
		INSERT INTO
		".TABLE_PREFIX."pollvotes (pid,uid,voteoption,dateline)
		VALUES $votesql
	");
	$voteslist = '';
	for($i = 1; $i <= $poll['numoptions']; ++$i)
	{
		if($i > 1)
		{
			$voteslist .= "||~|~||";
		}
		$voteslist .= $votesarray[$i-1];
	}
	$updatedpoll = array(
		"votes" => $db->escape_string($voteslist),
		"numvotes" => intval($numvotes),
	);

	$plugins->run_hooks("polls_vote_process");

	$db->update_query("polls", $updatedpoll, "pid='".$poll['pid']."'");

	$plugins->run_hooks("polls_vote_end");

	/* MODIFIED REDIRECT BEHAVIOR FOR AT HOME POLLS PLUGIN
	 * Changes the redirect on voting to the script/page where the vote button was clicked, rather than the thread where the poll originates.
	 */
	$redirect_url = $_SERVER['HTTP_REFERER'];
	
	if (empty($redirect_url))
	{
		$redirect_url = get_thread_link($thread['tid']);
	}
	 
	redirect($redirect_url, $lang->redirect_votethanks);
}

if($mybb->input['action'] == "do_undovote")
{
	verify_post_check($mybb->input['my_post_key']);

	$plugins->run_hooks("polls_do_undovote_start");
	if($mybb->usergroup['canundovotes'] != 1)
	{
		error_no_permission();
	}

	$query = $db->simple_select("polls", "*", "pid='".intval($mybb->input['pid'])."'");
	$poll = $db->fetch_array($query);
	$poll['numvotes'] = (int)$poll['numvotes'];

	if(!$poll['pid'])
	{
		error($lang->error_invalidpoll);
	}

	// We do not have $forum_cache available here since no forums permissions are checked in undo vote
	// Get thread ID and then get forum info
	$query = $db->simple_select("threads", "*", "tid='".intval($poll['tid'])."'");
	$thread = $db->fetch_array($query);
	if(!$thread['tid'] || $thread['visible'] == 0)
	{
		error($lang->error_invalidthread);
	}

	$fid = $thread['fid'];

	// Get forum info
	$forum = get_forum($fid);
	if(!$forum)
	{
		error($lang->error_invalidforum);
	}
	else
	{
		// Is our forum closed?
		if ($forum['open'] == 0)
		{
			// Doesn't look like it is
			error($lang->error_closedinvalidforum);
		}
	}

	$poll['timeout'] = $poll['timeout']*60*60*24;


	$expiretime = $poll['dateline'] + $poll['timeout'];
	if($poll['closed'] == 1 || $thread['closed'] == 1 || ($expiretime < TIME_NOW && $poll['timeout']))
	{
		error($lang->error_pollclosed);
	}

	// Check if the user has voted before...
	$vote_options = array();
	if($mybb->user['uid'])
	{
		$query = $db->simple_select("pollvotes", "vid,voteoption", "uid='".$mybb->user['uid']."' AND pid='".$poll['pid']."'");
		while($voteoption = $db->fetch_array($query))
		{
			$vote_options[$voteoption['vid']] = $voteoption['voteoption'];
		}
	}
	else
	{
		// for Guests, we simply see if they've got the cookie
		$vote_options = explode(',', $mybb->cookies['pollvotes'][$poll['pid']]);
	}
	$votecheck = !empty($vote_options);

	if(!$votecheck)
	{
		error($lang->error_notvoted);
	}
	else if(!$mybb->user['uid'])
	{
		// clear cookie for Guests
		my_setcookie("pollvotes[{$poll['pid']}]", "");
	}

	// Note, this is not thread safe!
	$votesarray = explode("||~|~||", $poll['votes']);
	if(count($votesarray) > $poll['numoptions'])
	{
		$votesarray = array_slice(0, $poll['numoptions']);
	}

	if($poll['multiple'] == 1)
	{
		foreach($vote_options as $vote)
		{
			if(isset($votesarray[$vote-1]))
			{
				--$votesarray[$vote-1];
				--$poll['numvotes'];
			}
		}
	}
	else
	{
		$voteoption = reset($vote_options);
		if(isset($votesarray[$voteoption-1]))
		{
			--$votesarray[$voteoption-1];
			--$poll['numvotes'];
		}
	}

	// check if anything < 0 - possible if Guest vote undoing is allowed (generally Guest unvoting should be disabled >_>)
	if($poll['numvotes'] < 0)
	{
		$poll['numvotes'] = 0;
	}

	foreach($votesarray as $i => $votes)
	{
		if($votes < 0)
		{
			$votesarray[$i] = 0;
		}
	}

	$voteslist = implode("||~|~||", $votesarray);
	$updatedpoll = array(
		"votes" => $db->escape_string($voteslist),
		"numvotes" => intval($poll['numvotes']),
	);

	$plugins->run_hooks("polls_do_undovote_process");

	$db->delete_query("pollvotes", "uid='".$mybb->user['uid']."' AND pid='".$poll['pid']."'");
	$db->update_query("polls", $updatedpoll, "pid='".$poll['pid']."'");

	$plugins->run_hooks("polls_do_undovote_end");
	
	/* MODIFIED REDIRECT BEHAVIOR FOR AT HOME POLLS PLUGIN
	 * Changes the redirect on undo vote to the script/page where the edit poll link was clicked, rather than the thread where the poll originates.
	 */
	$redirect_url = htmlspecialchars_uni($mybb->input['this_script']);
	
	if ($redirect_url == 'forumdisplay.php')
	{
		$redirect_url = $redirect_url . '?fid=' .(int) $mybb->input['fid'];
	}
	elseif ($redirect_url == 'showthread.php')
	{
		$redirect_url = $redirect_url . '?tid=' .(int) $mybb->input['tid'];
	}
	elseif ($redirect_url == 'member.php')
	{
		$redirect_url = 'index.php';
	}
	elseif (empty($redirect_url))
	{
		$redirect_url = get_thread_link($thread['tid']);
	}
	
	redirect($redirect_url, $lang->redirect_unvoted);
}
?>