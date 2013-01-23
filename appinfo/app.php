<?php

/**
* ownCloud - user_sgis
*
* @author Andreas Böhler
* @copyright 2012 Andreas Böhler <andreas (at) aboehler (dot) at>
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

if (OCP\App::isEnabled('user_sgis')) {

	require_once('apps/user_sgis/user_sgis.php');
	OC_App::registerAdmin('user_sgis','settings');

	OC::$CLASSPATH['OC_USER_SGIS_Hooks'] = 'apps/user_sgis/lib/hooks.php';
	OCP\Util::connectHook('OC_User', 'post_login', 'OC_USER_SGIS_Hooks', 'post_login');

#	// register user backend
	OC_User::clearBackends();
	OC_User::useBackend( "SGIS" );

}
