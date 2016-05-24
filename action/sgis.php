<?php
/**
 * DokuWiki Plugin etherpadlite (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Michael Braun <michael-dev@fami-braun.de>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'action.php';

class action_plugin_sgis_sgis extends DokuWiki_Action_Plugin {

    private $sgisGroup = "sgis";
    private $sgisUrl = "http://helfer.stura.tu-ilmenau.de/sgis/";

    public function register(Doku_Event_Handler &$controller) {
        $controller->register_hook('TEMPLATE_USERTOOLS_DISPLAY', 'BEFORE', $this, 'add_menu_item');
    }

    public function add_menu_item(Doku_Event $event, $param) {
        global $USERINFO, $lang;

        if ($USERINFO === NULL)
          return;

        $isSgis = in_array("sgis", $USERINFO['grps']);

        if (!$isSgis)
          return;

        $event->data['items']['sgis'] = "<li>".tpl_link($this->sgisUrl,$lang["btn_profile"].' (sGIS)','',true)."</li>";
    }
}

// vim:ts=4:sw=4:et:
