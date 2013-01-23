<?php

/**
 * ownCloud - user_sgis
 *
 * @author Michael Braun
 * @copyright 2013 Michael Braun <michael-dev (at) fami-braun (dot) de>
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
$params = array('sgis_url', 'sgis_key');

OCP\Util::addscript('user_sgis', 'settings');

if ($_POST) {
    foreach($params as $param){
        if(isset($_POST[$param]))
        {
            OCP\Config::setAppValue('user_sgis', $param, $_POST[$param]);
        }
        elseif($param == 'strip_domain')
        {
            OCP\Config::setAppValue('user_sgis', $param, 0);
        }
    }
}

// fill template
$tmpl = new OCP\Template( 'user_sgis', 'settings');
$tmpl->assign( 'sgis_url', OCP\Config::getAppValue('user_sgis', 'sgis_url', 'https://example.org/sgis/rpclogin.php'));
$tmpl->assign( 'sgis_key', OCP\Config::getAppValue('user_sgis', 'sgis_key', ''));

return $tmpl->fetchPage();
