<?php
/**
 * This file is part of User Browsed Topic plugin for MyBB.
 * Copyright (C) Lukasz Tkacz <lukasamd@gmail.com>
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
 
/**
 * Disallow direct access to this file for security reasons
 * 
 */
if (!defined("IN_MYBB")) exit;

/**
 * Plugin Activator Class
 * 
 */
class usersBrowsedTopicActivator
{

    private static $tpl = array();

    private static function getTpl()
    {
        global $db;

    	self::$tpl[] = array(
    		"tid"		=> NULL,
    		"title"		=> 'usersBrowsed',
    		"template"	=> $db->escape_string('<span class="smalltext">{$lang->usersBrowsedTopic} {$users_list}</span><br />'),
    		"sid"		=> "-1",
    		"version"	=> "1.0",
    		"dateline"	=> TIME_NOW,
    	);
        
    	self::$tpl[] = array(
    		"tid"		=> NULL,
    		"title"		=> 'usersBrowsedUser',
    		"template"	=> $db->escape_string('{$comma}<a href="{$user[\'profilelink\']}"{$user[\'dateline\']}>{$user[\'username\']}</a>'),
    		"sid"		=> "-1",
    		"version"	=> "1.0",
    		"dateline"	=> TIME_NOW,
    	);
    }

    public static function activate()
    {
        global $db;
        self::deactivate();

        for ($i = 0; $i < sizeof(self::$tpl); $i++)
        {
            $db->insert_query('templates', self::$tpl[$i]);
        }
        find_replace_templatesets("showthread", '#{\$usersbrowsing}#', '{\$usersbrowsing}<!-- PLUGIN_USERS_BROWSED_TOPIC -->');
    }

    public static function deactivate()
    {
        global $db;
        self::getTpl();

        for ($i = 0; $i < sizeof(self::$tpl); $i++)
        {
            $db->delete_query('templates', "title = '" . self::$tpl[$i]['title'] . "'");
        }

        require_once(MYBB_ROOT . '/inc/adminfunctions_templates.php');
        find_replace_templatesets("showthread", '#' . preg_quote('<!-- PLUGIN_USERS_BROWSED_TOPIC -->') . '#', '');
    }
    
}
