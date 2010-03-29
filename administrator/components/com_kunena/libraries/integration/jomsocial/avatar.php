<?php
/**
 * @version $Id$
 * Kunena Component
 * @package Kunena
 *
 * @Copyright (C) 2008 - 2010 Kunena Team All rights reserved
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.kunena.com
 *
 **/
//
// Dont allow direct linking
defined( '_JEXEC' ) or die('');

kimport('integration.profile');

return;

class KunenaAvatarJomSocial extends KunenaAvatar
{
	protected $integration = null;

	public function __construct() {
		$this->integration = KunenaIntegration::getInstance ('jomsocial');
		if (! $this->integration || ! $this->integration->isLoaded())
			return;
		$this->priority = 50;
	}

	public function getEditURL()
	{
		return CRoute::_('index.php?option=com_community&view=profile&task=uploadAvatar');
	}

	public function getURL($user, $size='thumb')
	{
		$user = KunenaFactory::getUser($user);
		// Get CUser object
		$user =& CFactory::getUser($user->userid);
		if ($size=='thumb')	$avatar = $user->getThumbAvatar();
		else $avatar = $user->getAvatar();
		return $avatar;
	}
}
