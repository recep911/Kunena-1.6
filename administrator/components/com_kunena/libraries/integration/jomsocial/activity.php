<?php
/**
 * @version $Id$
 * Kunena Component
 * @package Kunena
 *
 * @Copyright (C) 2008 - 2011 Kunena Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.kunena.org
 *
 **/
//
// Dont allow direct linking
defined ( '_JEXEC' ) or die ( '' );

class KunenaActivityJomSocial extends KunenaActivity {
	protected $integration = null;

	public function __construct() {
		$this->integration = KunenaIntegration::getInstance ( 'jomsocial' );
		if (! $this->integration || ! $this->integration->isLoaded ())
			return;
		$this->priority = 40;
		$this->_config = KunenaFactory::getConfig ();
	}

	protected function getAccess($group) {
		// Activity access level: 0 = public, 20 = registered, 30 = friend, 40 = private
		if (KUNENA_JOOMLA_COMPAT == '1.5') {
			if ($message->parent->pub_access == 0) {
				// Public
				$act->access = 0;
			} elseif ($message->parent->pub_access == -1 || $message->parent->pub_access == 18) {
				// Registered
				$act->access = 20;
			} else {
				// Other groups (=private)
				$act->access = 40;
			}
		} else {
			if ($message->parent->pub_access == 1) {
				// Public
				$act->access = 0;
			} elseif ( $message->parent->pub_access == 2) {
				// Registered
				$act->access = 20;
			} else {
				// Other groups (=private)
				$act->access = 40;
			}
		}
	}

	public function onAfterPost($message) {
		CFactory::load ( 'libraries', 'userpoints' );
		CUserPoints::assignPoint ( 'com_kunena.thread.new' );

		// Check for permisions of the current category - activity only if public or registered
		if (! empty ( $message->parent )) {
			//activity stream  - new post
			require_once KPATH_SITE.'/lib/kunena.link.class.php';
			require_once KPATH_SITE.'/lib/kunena.smile.class.php';
			$JSPostLink = CKunenaLink::GetThreadPageURL ( 'view', $message->get ( 'catid' ), $message->get ( 'thread' ), 1 );

			$kunena_emoticons = smile::getEmoticons ( 0 ); // use colored emoticons for the activity stream

			$content = $message->get ( 'message' );

			// Strip content not allowed for guests
			$content = preg_replace ( '/\[hide\](.*?)\[\/hide\]/s', '', $content );
			$content = preg_replace ( '/\[confidential\](.*?)\[\/confidential\]/s', '', $content );
			$content = preg_replace ( '/\[spoiler\]/s', '[spoilerlight]', $content );
			$content = preg_replace ( '/\[\/spoiler\]/s', '[/spoilerlight]', $content );
			$content = preg_replace ( '/\[attachment(.*?)\](.*?)\[\/attachment\]/s', '', $content );
			$content = preg_replace ( '/\[code\](.*?)\[\/code]/s', '', $content );

			// limit activity stream output if limit is set
			if ($this->_config->activity_limit > 0){
				$content = JString::substr($content, 0, $this->_config->activity_limit);
			}

			$content = smile::smileReplace ( $content, 0, $this->_config->disemoticons, $kunena_emoticons );
			$content = nl2br ( $content );

			// Add readmore link
			$content .= '<br /><a href="'.
					CKunenaLink::GetMessageURL($message->get ( 'id' )).
					'" class="small profile-newsfeed-item-action">'.JText::_('COM_KUNENA_READMORE').'</a>';

			$act = new stdClass ();
			$act->cmd = 'wall.write';
			$act->actor = $message->get ( 'userid' );
			$act->target = 0; // no target
			$act->title = JText::_ ( '{actor} ' . JText::_ ( 'COM_KUNENA_JS_ACTIVITYSTREAM_CREATE_MSG1' ) . ' <a href="' . $JSPostLink . '">' . $message->get ( 'subject' ) . '</a> ' . JText::_ ( 'COM_KUNENA_JS_ACTIVITYSTREAM_CREATE_MSG2' ) );
			$act->content = $content;
			$act->app = 'kunena.post';
			$act->cid = $message->get ( 'thread' );
			$act->access = $this->getAccess($message->parent->pub_access);

			CFactory::load ( 'libraries', 'activities' );
			CActivityStream::add ( $act );
		}
	}

	public function onAfterReply($message) {
		CFactory::load ( 'libraries', 'userpoints' );
		CUserPoints::assignPoint ( 'com_kunena.thread.reply' );

		// Check for permisions of the current category - activity only if public or registered
		if (! empty ( $message->parent )) {
			//activity stream - reply post
			require_once KPATH_SITE.'/lib/kunena.link.class.php';
			require_once KPATH_SITE.'/lib/kunena.smile.class.php';
			$JSPostLink = CKunenaLink::GetThreadPageURL ( 'view', $message->get ( 'catid' ), $message->get ( 'thread' ), 1 );

			$kunena_emoticons = smile::getEmoticons ( 0 );

			$content = $message->get ( 'message' );

			// Strip content not allowed for guests
			$content = preg_replace ( '/\[hide\](.*?)\[\/hide\]/s', '', $content );
			$content = preg_replace ( '/\[confidential\](.*?)\[\/confidential\]/s', '', $content );
			$content = preg_replace ( '/\[spoiler\]/s', '[spoilerlight]', $content );
			$content = preg_replace ( '/\[\/spoiler\]/s', '[/spoilerlight]', $content );
			$content = preg_replace ( '/\[attachment(.*?)\](.*?)\[\/attachment\]/s', '', $content );
			$content = preg_replace ( '/\[code\](.*?)\[\/code]/s', '', $content );

			// limit activity stream output if limit is set
			if ($this->_config->activity_limit > 0){
				$content = JString::substr($content, 0, $this->_config->activity_limit);
			}

			$content = smile::smileReplace ( $content, 0, $this->_config->disemoticons, $kunena_emoticons );
			$content = nl2br ( $content );

			// Add readmore link
			$content .= '<br /><a href="'.
					CKunenaLink::GetMessageURL($message->get ( 'id' )).
					'" class="small profile-newsfeed-item-action">'.JText::_('COM_KUNENA_READMORE').'</a>';

			$act = new stdClass ();
			$act->cmd = 'wall.write';
			$act->actor = $message->get ( 'userid' );
			$act->target = 0; // no target
			$act->title = JText::_ ( '{single}{actor}{/single}{multiple}{actors}{/multiple} ' . JText::_ ( 'COM_KUNENA_JS_ACTIVITYSTREAM_REPLY_MSG1' ) . ' <a href="' . $JSPostLink . '">' . $message->get ( 'subject' ) . '</a> ' . JText::_ ( 'COM_KUNENA_JS_ACTIVITYSTREAM_REPLY_MSG2' ) );
			$act->content = $content;
			$act->app = 'kunena.post';
			$act->cid = $message->get ( 'thread' );
			$act->access = $this->getAccess($message->parent->pub_access);

			CFactory::load ( 'libraries', 'activities' );
			CActivityStream::add ( $act );
		}
	}

	public function onAfterThankyou($thankyoutargetid, $username , $message) {
		CFactory::load ( 'libraries', 'userpoints' );
		CUserPoints::assignPoint ( 'com_kunena.thread.thankyou', $thankyoutargetid );

		if (! empty ( $message->parent )) {
			//activity stream - reply post
			require_once KPATH_SITE.'/lib/kunena.link.class.php';
			require_once KPATH_SITE.'/lib/kunena.smile.class.php';
			$JSPostLink = CKunenaLink::GetThreadPageURL ( 'view', $message->get ( 'catid' ), $message->get ( 'thread' ), 1 );

			$act = new stdClass ();
			$act->cmd = 'wall.write';
			$act->actor = JFactory::getUser()->id;
			$act->target = $thankyoutargetid;
			$act->title = JText::_ ( '{single}{actor}{/single}{multiple}{actors}{/multiple} ' . JText::_( 'COM_KUNENA_JS_ACTIVITYSTREAM_THANKYOU' ).' <a href="' . $JSPostLink . '">' . $message->get ( 'subject' ) . '</a> ' . JText::_ ( 'COM_KUNENA_JS_ACTIVITYSTREAM_REPLY_MSG2' ) );
			$act->content = NULL;
			$act->app = 'kunena.thankyou';
			$act->cid = $thankyoutargetid;
			$act->access = $this->getAccess($message->parent->pub_access);

			CFactory::load ( 'libraries', 'activities' );
			CActivityStream::add ( $act );
		}
	}
}
