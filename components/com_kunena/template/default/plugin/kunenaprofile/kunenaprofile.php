<?php
/**
* @version $Id$
* Kunena Component
* @package Kunena
*
* @Copyright (C) 2008 - 2009 Kunena Team All rights reserved
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @link http://www.kunena.com
*
* Based on FireBoard Component
* @Copyright (C) 2006 - 2007 Best Of Joomla All rights reserved
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @link http://www.bestofjoomla.com
**/

defined( '_JEXEC' ) or die('Restricted access');

$app =& JFactory::getApplication();
$kunena_acl = &JFactory::getACL();
$kunenaConfig =& CKunenaConfig::getInstance();

if ($kunenaConfig->kunena_profile != 'kunena') {
    $userid = JRequest::getVar('userid', null);
	$kunenaProfile =& CKunenaProfile::getInstance();
    $url = $kunenaProfile->getProfileURL($userid);
	header("HTTP/1.1 307 Temporary Redirect");
	header("Location: " . htmlspecialchars_decode($url));
	$app->close();
}

$document=& JFactory::getDocument();
$document->setTitle(_KUNENA_USERPROFILE_PROFILE . ' - ' . stripslashes($kunenaConfig->board_title));

if ($kunena_my->id) //registered only
{
    require_once(KUNENA_PATH_LIB .DS. 'kunena.authentication.php');
    require_once(KUNENA_PATH_LIB .DS. 'kunena.statsbar.php');

    $task = JRequest::getVar('task', 'showprf');

    switch ($task)
    {
        case "showprf":
            $userid = JRequest::getVar('userid', null);

            $page = 0;
            showprf((int)$userid, $page);
            break;
    }
}
else {
    echo '<h3>' . _COM_A_REGISTERED_ONLY . '</h3>';
}

function showprf($userid, $page)
{
    $kunenaConfig =& CKunenaConfig::getInstance();
    $kunena_acl = &JFactory::getACL();
    $kunena_my = &JFactory::getUser();
    $kunena_db = &JFactory::getDBO();
    // ERROR: mixed global $kunenaIcons
    global $kunenaIcons;

    //Get userinfo needed later on, this limits the amount of queries
    unset($userinfo);
    $kunena_db->setQuery("SELECT a.*, b.* FROM #__kunena_users AS a LEFT JOIN #__users AS b ON b.id=a.userid WHERE a.userid='{$userid}'");

    $userinfo = $kunena_db->loadObject();
    check_dberror('Unable to get user profile info.');

    if (!$userinfo) {
	$kunena_db->setQuery("SELECT * FROM #__users WHERE id='{$userid}'");
	$userinfo = $kunena_db->loadObject();
	check_dberror('Unable to get user profile info.');

	if (!$userinfo) {
		echo '<h3>' . _KUNENA_PROFILE_NO_USER . '</h3>';
		return;
	} else {
		// Check moderator status (admin is moderator)
		$aro_group = $kunena_acl->getAroGroup($userid);
		$is_admin = (strtolower($aro_group->name) == 'super administrator' || strtolower($aro_group->name) == 'administrator');

		// there's no profile; set userid and moderator status.
		$kunena_db->setQuery("INSERT INTO #__kunena_users (userid,moderator) VALUES ('$userid','$is_admin')");
		$kunena_db->query();
		check_dberror('Unable to create user profile.');

		$kunena_db->setQuery("SELECT a.*, b.* FROM #__kunena_users AS a LEFT JOIN #__users AS b ON b.id=a.userid WHERE a.userid='{$userid}'");

		$userinfo = $kunena_db->loadObject();
		check_dberror('Unable to get user profile info.');

		// TODO: For future use
		// echo '<h3>' . _KUNENA_PROFILE_NOT_FOUND . '</h3>';
		// return;
	}

    }

	// User Hits
	$kunena_db->setQuery('UPDATE #__kunena_users SET uhits=uhits+1 WHERE userid='.$userid);
	$kunena_db->query() or trigger_dberror("Unable to update user hits.");

	// get userprofile hits
	$msg_userhits = $userinfo->uhits;

    //get the username:
    $kunena_username = "";

    if ($kunenaConfig->username) {
        $kunena_queryName = "username";
    }
    else {
        $kunena_queryName = "name";
    }

    $kunena_username = $userinfo->{$kunena_queryName};

    $lists["userid"] = $userid;

	$msg_username = $kunena_username;
    // $msg_username = ($fmessage->email != "" && $kunena_my->id > 0 && $kunenaConfig->showemail == '1') ? "<a href=\"mailto:" . $fmessage->email . "\">" . $kunena_username . "</a>" : $kunena_username;

    if ($kunenaConfig->allowavatar)
    {
        $Avatarname = $userinfo->username;

		$kunenaProfile = CKunenaProfile::getInstance();
		$msg_avatar = '<span class="kunena_avatar">' . $kunenaProfile->showAvatar($userid, '', 0) . '</span>';
    }

    if ($kunenaConfig->showuserstats)
    {
        //user type determination
        $ugid = $userinfo->gid;
        $uIsMod = 0;
        $uIsAdm = 0;

        if ($ugid > 0) { //only get the groupname from the ACL if we're sure there is one
            $agrp = strtolower($kunena_acl->get_group_name($ugid, 'ARO'));
        }

        if ($ugid == 0) {
            $msg_usertype = _VIEW_VISITOR;
        }
        else
        {
            if (strtolower($agrp) == "administrator" || strtolower($agrp) == "superadministrator" || strtolower($agrp) == "super administrator")
            {
                $msg_usertype = _VIEW_ADMIN;
                $uIsAdm = 1;
            }
            elseif ($uIsMod) {
                $msg_usertype = _VIEW_MODERATOR;
            }
            else {
                $msg_usertype = _VIEW_USER;
            }
        }

        //done usertype determination, phew...

        //Get the max# of posts for any one user
        $kunena_db->setQuery("SELECT MAX(posts) FROM #__kunena_users");
        $maxPosts = $kunena_db->loadResult();

        //# of post for this user and ranking

        $numPosts = (int)$userinfo->posts;

							//ranking
							if ($kunenaConfig->showranking)
							{

								if ($userinfo->rank != '0')
								{
												//special rank
												$kunena_db->setQuery("SELECT * FROM #__kunena_ranks WHERE rank_id='{$userinfo->rank}'");
												$getRank = $kunena_db->loadObjectList();
													check_dberror("Unable to load ranks.");
												$rank=$getRank[0];
												$rText = $rank->rank_title;
												$rImg = KUNENA_URLRANKSPATH . $rank->rank_image;
									}
									if ($userinfo->rank == '0')
									{
											//post count rank
												$kunena_db->setQuery("SELECT * FROM #__kunena_ranks WHERE ((rank_min <= '{$numPosts}') AND (rank_special = '0')) ORDER BY rank_min DESC", 0, 1);
												$getRank = $kunena_db->loadObjectList();
													check_dberror("Unable to load ranks.");
												$rank=$getRank[0];
												$rText = $rank->rank_title;
												$rImg = KUNENA_URLRANKSPATH . $rank->rank_image;
									}

									if ($uIsMod)
									{
													$rText = _RANK_MODERATOR;
													$rImg = KUNENA_URLRANKSPATH . 'rankmod.gif';
									}

									if ($uIsAdm)
									{
													$rText = _RANK_ADMINISTRATOR;
													$rImg = KUNENA_URLRANKSPATH . 'rankadmin.gif';
									}

									if ($kunenaConfig->rankimages) {
													$msg_userrankimg = '<img src="' . $rImg . '" alt="" />';
									}

								$msg_userrank = $rText;

            $useGraph = 0; //initialization

            if (!$kunenaConfig->poststats)
            {
                $msg_posts = '<div class="viewcover">' .
                             "<strong>" . _POSTS . " $numPosts" . "</strong>" .
                             "</div>";
                $useGraph = 0;
            }
            else
            {
                $myGraph = new phpGraph;
                //$myGraph->SetGraphTitle(_POSTS);
                $myGraph->AddValue(_POSTS, $numPosts);
                $myGraph->SetRowSortMode(0);
                $myGraph->SetBarImg(KUNENA_URLGRAPHPATH . "col" . $kunenaConfig->statscolor . "m.png");
                $myGraph->SetBarImg2(KUNENA_URLEMOTIONSPATH . "graph.gif");
                $myGraph->SetMaxVal($maxPosts);
                $myGraph->SetShowCountsMode(2);
                $myGraph->SetBarWidth(4); //height of the bar
                $myGraph->SetBorderColor("#333333");
                $myGraph->SetBarBorderWidth(0);
                $myGraph->SetGraphWidth(120); //should match column width in the <TD> above -5 pixels
                //$myGraph->BarGraphHoriz();
                $useGraph = 1;
            }
        }
    }

    //karma points and buttons
    if ($kunenaConfig->showkarma && $userid != '0')
    {
        $karmaPoints = $userinfo->karma;
        $karmaPoints = (int)$karmaPoints;
        $msg_karma = "<strong>" . _KARMA . ":</strong> $karmaPoints";

		$msg_karmaminus = '';
		$msg_karmaplus = '';
        if ($kunena_my->id != '0' && $kunena_my->id != $userid)
        {
            $msg_karmaminus .= "<a href=\"" . JRoute::_(KUNENA_LIVEURLREL . '&amp;func=karma&amp;do=decrease&amp;userid=' . $userid) . "\"><img src=\"";

            if (isset($kunenaIcons['karmaminus'])) {
                $msg_karmaminus .= KUNENA_URLICONSPATH . $kunenaIcons['karmaminus'];
            }
            else {
                $msg_karmaminus .= KUNENA_URLEMOTIONSPATH . "karmaminus.gif";
            }

            $msg_karmaminus .= "\" alt=\"Karma-\" border=\"0\" title=\"" . _KARMA_SMITE . "\" align=\"middle\" /></a>";
            $msg_karmaplus .= "<a href=\"" . JRoute::_(KUNENA_LIVEURLREL . '&amp;func=karma&amp;do=increase&amp;userid=' . $userid) . "\"><img src=\"";

            if (isset($kunenaIcons['karmaplus'])) {
                $msg_karmaplus .= KUNENA_URLICONSPATH . $kunenaIcons['karmaplus'];
            }
            else {
                $msg_karmaplus .= KUNENA_URLEMOTIONSPATH . "karmaplus.gif";
            }

            $msg_karmaplus .= "\" alt=\"Karma+\" border=\"0\" title=\"" . _KARMA_APPLAUD . "\" align=\"middle\" /></a>";
        }
    }

    /*let's see if we should use uddeIM integration */

    if ($kunenaConfig->pm_component == "uddeim" && $userid && $kunena_my->id)
    {

        //we should offer the user a PMS link
        //first get the username of the user to contact
        $PMSName = $userinfo->username;
        $msg_pms = "<a href=\"" . JRoute::_('index.php?option=com_uddeim&amp;task=new&recip=' . $userid) . "\"><img src=\"";

        if ($kunenaIcons['pms']) {
            $msg_pms .= KUNENA_URLICONSPATH . $kunenaIcons['pms'];
        }
        else {
            $msg_pms .= KUNENA_URLEMOTIONSPATH . "sendpm.gif";
        }

        $msg_pms .= "\" alt=\"" . _VIEW_PMS . "\" border=\"0\" title=\"" . _VIEW_PMS . "\" /></a>";
    }

    /*let's see if we should use myPMS2 integration */
    if ($kunenaConfig->pm_component == "pms" && $userid && $kunena_my->id)
    {
        //we should offer the user a PMS link
        //first get the username of the user to contact
        $PMSName = $userinfo->username;
        $msg_pms = "<a href=\"" . JRoute::_('index.php?option=com_pms&amp;page=new&amp;id=' . $PMSName . '&title=' . $fmessage->subject) . "\"><img src=\"";

        if ($kunenaIcons['pms']) {
            $msg_pms .= KUNENA_URLICONSPATH . $kunenaIcons['pms'];
        }
        else {
            $msg_pms .= KUNENA_URLEMOTIONSPATH . "sendpm.gif";
        }

        $msg_pms .= "\" alt=\"" . _VIEW_PMS . "\" border=\"0\" title=\"" . _VIEW_PMS . "\" /></a>";
    }

    // online - ofline status

    if ($userid > 0)
    {
        $sql = "SELECT COUNT(userid) FROM #__session WHERE userid='{$userid}'";

        $kunena_db->setQuery($sql);

        $isonline = $kunena_db->loadResult();

        if ($isonline && $userinfo->showOnline ==1 ) {
            $msg_online = isset($kunenaIcons['onlineicon'])
                ? '<img src="' . KUNENA_URLICONSPATH . $kunenaIcons['onlineicon'] . '" border="0" alt="' . _MODLIST_ONLINE . '" />' : '  <img src="' . KUNENA_URLEMOTIONSPATH . 'onlineicon.gif" border="0"  alt="' . _MODLIST_ONLINE . '" />';
        }
        else {
            $msg_online = isset($kunenaIcons['offlineicon'])
                ? '<img src="' . KUNENA_URLICONSPATH . $kunenaIcons['offlineicon'] . '" border="0" alt="' . _MODLIST_OFFLINE . '" />' : '  <img src="' . KUNENA_URLEMOTIONSPATH . 'offlineicon.gif" border="0"  alt="' . _MODLIST_OFFLINE . '" />';
        }
    }

    $jr_username = $userinfo->name;

    // (JJ) JOOMLA STYLE CHECK
    if ($kunenaConfig->joomlastyle < 1) {
        $boardclass = "kunena_";
    }
?>

    <table class="kunena_profile_cover" width = "100%" border = "0" cellspacing = "0" cellpadding = "0">
        <tr>
            <td class = "<?php echo $boardclass; ?>profile-left" align="center" valign="top" width="25%">
            <!-- Kunena Profile -->
                <?php
                if (file_exists(KUNENA_ABSTMPLTPATH . '/plugin/kunenaprofile/userinfos.php')) {
                    include(KUNENA_ABSTMPLTPATH . '/plugin/kunenaprofile/userinfos.php');
                }
                else {
                    include(KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'plugin/kunenaprofile/userinfos.php');
                }
                ?>

            <!-- /Kunena Profile -->
            </td>

            <td class = "<?php echo $boardclass; ?>profile-right" valign="top" width="74%">
            <!-- User Messages -->



            <?php

                if (file_exists(KUNENA_ABSTMPLTPATH . '/plugin/kunenaprofile/summary.php')) {
                    include(KUNENA_ABSTMPLTPATH . '/plugin/kunenaprofile/summary.php');
                }
                else {
                    include(KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'plugin/kunenaprofile/summary.php');
                }
                ?>

                <?php
                if (file_exists(KUNENA_ABSTMPLTPATH . '/plugin/kunenaprofile/forummsg.php')) {
                    include(KUNENA_ABSTMPLTPATH . '/plugin/kunenaprofile/forummsg.php');
                }
                else {
                    include(KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'plugin/kunenaprofile/forummsg.php');
                }
                ?>
            </td>
        </tr>
    </table>

    <?php
/*    end of function        */
}
?>
<!-- -->

<!-- Begin: Forum Jump -->
<div class="<?php echo $boardclass; ?>_bt_cvr1">
<div class="<?php echo $boardclass; ?>_bt_cvr2">
<div class="<?php echo $boardclass; ?>_bt_cvr3">
<div class="<?php echo $boardclass; ?>_bt_cvr4">
<div class="<?php echo $boardclass; ?>_bt_cvr5">
<table class = "kunena_blocktable" id="kunena_bottomarea"   border = "0" cellspacing = "0" cellpadding = "0" width="100%">
    <thead>
        <tr>
            <th class = "th-right">
                <?php
                //(JJ) FINISH: CAT LIST BOTTOM
                if ($kunenaConfig->enableforumjump)
                    require_once(KUNENA_PATH_LIB .DS. 'kunena.forumjump.php');
                ?>
            </th>
        </tr>
    </thead>
	<tbody><tr><td></td></tr></tbody>
</table>
</div>
</div>
</div>
</div>
</div>
<!-- Finish: Forum Jump -->