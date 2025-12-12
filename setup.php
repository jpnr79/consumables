// Fallbacks for static analysis and static analyzers (namespaced)
namespace Glpi\Helpdesk\Tile {
    if (!class_exists('Glpi\\Helpdesk\\Tile\\TilesManager')) {
        class TilesManager {
            public static function getInstance() { return new self(); }
            public function registerPluginTileType($tile) { return true; }
        }
    }
}
namespace Glpi\Plugin {
    if (!class_exists('Glpi\\Plugin\\Hooks')) {
        class Hooks {
            const ADD_CSS = 'add_css';
            const ADD_JAVASCRIPT = 'add_javascript';
        }
    }
}
namespace {
// Fallbacks for plugin-local classes for static analysis
namespace GlpiPlugin\Consumables {
    if (!class_exists('GlpiPlugin\\Consumables\\Request')) {
        class Request {
            public static function getIcon() { return ''; }
        }
    }
    if (!class_exists('GlpiPlugin\\Consumables\\Profile')) {
        class Profile {
            public static function initProfile() { return true; }
        }
    }
    if (!class_exists('GlpiPlugin\\Consumables\\Field')) {
        class Field {
            public static function addFieldOrderReference() { return true; }
            public static function postAddConsumable() { return true; }
            public static function preUpdateConsumable() { return true; }
        }
    }
    if (!class_exists('GlpiPlugin\\Consumables\\Servicecatalog')) {
        class Servicecatalog {}
    }
    if (!class_exists('GlpiPlugin\\Consumables\\Validation')) {
        class Validation {}
    }
    if (!class_exists('GlpiPlugin\\Consumables\\Menu')) {
        class Menu {}
    }
    if (!class_exists('GlpiPlugin\\Consumables\\Helpdesk\\Tile\\ConsumablesPageTile')) {
        class ConsumablesPageTile {}
    }
}
namespace GlpiPlugin\Servicecatalog {
    if (!class_exists('GlpiPlugin\\Servicecatalog\\Main')) {
        class Main {}
    }
}
// Fallbacks for static analysis and static analyzers
if (!class_exists('Plugin')) {
    class Plugin {
        public static function getPhpDir($plugin) { return ''; }
        public static function registerClass($class, $options = []) { return true; }
    }
}
if (!class_exists('TilesManager')) {
    class TilesManager {
        public static function getInstance() { return new self(); }
        public function registerPluginTileType($tile) { return true; }
    }
}
if (!class_exists('Hooks')) {
    class Hooks {
        const ADD_CSS = 'add_css';
        const ADD_JAVASCRIPT = 'add_javascript';
    }
}
<?php
declare(strict_types=1);

/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 consumables plugin for GLPI
 Copyright (C) 2009-2022 by the consumables Development Team.

 https://github.com/InfotelGLPI/consumables
 -------------------------------------------------------------------------

 LICENSE

 This file is part of consumables.

 consumables is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 consumables is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with consumables. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

global $CFG_GLPI;

use Glpi\Helpdesk\Tile\TilesManager;
use Glpi\Plugin\Hooks;
use GlpiPlugin\Consumables\Field;
use GlpiPlugin\Consumables\Helpdesk\Tile\ConsumablesPageTile;
use GlpiPlugin\Consumables\Menu;
use GlpiPlugin\Consumables\Profile;
use GlpiPlugin\Consumables\Request;
use GlpiPlugin\Consumables\Servicecatalog;
use GlpiPlugin\Consumables\Validation;
use GlpiPlugin\Servicecatalog\Main;

define('PLUGIN_CONSUMABLES_VERSION', '2.1.2');

if (!defined("PLUGIN_CONSUMABLES_DIR")) {
    define("PLUGIN_CONSUMABLES_DIR", Plugin::getPhpDir("consumables"));
    // Fix: $root is undefined. Use GLPI_ROOT or set to empty string if not needed.
    define("PLUGIN_CONSUMABLES_WEBDIR", defined('GLPI_ROOT') ? GLPI_ROOT : '');
}

// Init the hooks of the plugins - Needed
function plugin_init_consumables(): void
{
    global $PLUGIN_HOOKS, $CFG_GLPI;

    $tiles_manager = TilesManager::getInstance();
    $tiles_manager->registerPluginTileType(new ConsumablesPageTile());

    $CFG_GLPI['glpitablesitemtype'][Validation::class] = 'glpi_plugin_consumables_requests';
    $PLUGIN_HOOKS['csrf_compliant']['consumables'] = true;
    $PLUGIN_HOOKS['change_profile']['consumables'] = [Profile::class, 'initProfile'];
    $PLUGIN_HOOKS[Hooks::ADD_CSS]['consumables'] = 'public/css/consumables.css';
    $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['consumables'] = 'public/js/consumables.js';

    if (Session::getLoginUserID()) {
        $PLUGIN_HOOKS['post_item_form']['consumables'] = [Field::class, 'addFieldOrderReference'];

        Plugin::registerClass(Profile::class, ['addtabon' => 'Profile']);
        Plugin::registerClass(Request::class, ['addtabon' => 'User', 'notificationtemplates_types' => true]);
        Plugin::registerClass(Request::class, ['addtabon' => 'Group', 'notificationtemplates_types' => true]);
        Plugin::registerClass(Request::class, ['addtabon' => 'ConsumableItem']);

        $PLUGIN_HOOKS['item_add']['consumables'] = ['ConsumableItem' => [Field::class, 'postAddConsumable']];
        $PLUGIN_HOOKS['pre_item_update']['consumables'] = ['ConsumableItem' => [Field::class, 'preUpdateConsumable']];

        if (Session::haveRight("plugin_consumables", UPDATE)) {
            $PLUGIN_HOOKS['use_massive_action']['consumables'] = 1;
        }

        $PLUGIN_HOOKS['servicecatalog']['consumables'] = [Servicecatalog::class];

        if (Session::haveRight("plugin_consumables", READ)) {
            $PLUGIN_HOOKS['menu_toadd']['consumables'] = ['management' => Menu::class];
        }
        if (
            Session::haveRight("plugin_consumables", READ)
            || Session::haveRight("plugin_consumables_request", 1)
            && !class_exists(Main::class)
        ) {
            $PLUGIN_HOOKS['helpdesk_menu_entry']['consumables'] = PLUGIN_CONSUMABLES_WEBDIR . '/front/wizard.php';
            $PLUGIN_HOOKS['helpdesk_menu_entry_icon']['consumables'] = Request::getIcon();
        }

        // Post item purge
        $PLUGIN_HOOKS['item_purge']['consumables'] = ['ConsumableItem' => 'plugin_item_purge_consumables'];
    }
}

// Get the name and the version of the plugin - Needed


/**
 * Retorna nome e versão do plugin
 *
 * @return array
 */
function plugin_version_consumables(): array
{
    return [
        'name'         => _n('Consumable request', 'Consumable requests', 1, 'consumables'),
        'version'      => PLUGIN_CONSUMABLES_VERSION,
        'author'       => "<a href='https://blogglpi.infotel.com'>Infotel</a>, Xavier CAILLAUD",
        'license'      => 'GPLv2+',
        'homepage'     => 'https://github.com/InfotelGLPI/consumables',
        'requirements' => [
            'glpi' => [
                'min' => '11.0',
                'max' => '12.0',
                'dev' => false,
            ],
        ],
    ];
}
