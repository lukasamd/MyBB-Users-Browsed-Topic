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
 * Plugin Installator Class
 * 
 */
class usersBrowsedTopicInstaller
{

    public static function install()
    {
        global $db, $lang, $mybb;
        self::uninstall();

        $result = $db->simple_select('settinggroups', 'MAX(disporder) AS max_disporder');
        $max_disporder = $db->fetch_field($result, 'max_disporder');
        $disporder = 1;

        $settings_group = array(
            'gid' => 'NULL',
            'name' => 'usersBrowsedTopic',
            'title' => $db->escape_string($lang->usersBrowsedTopicName),
            'description' => $db->escape_string($lang->usersBrowsedTopicSettingGroupDesc),
            'disporder' => $max_disporder + 1,
            'isdefault' => '0'
        );
        $db->insert_query('settinggroups', $settings_group);
        $gid = (int) $db->insert_id();

        $setting = array(
            'sid' => 'NULL',
            'name' => 'usersBrowsedTopicEnable',
            'title' => $db->escape_string($lang->usersBrowsedTopicEnable),
            'description' => $db->escape_string($lang->usersBrowsedTopicEnableDesc),
            'optionscode' => 'onoff',
            'value' => '1',
            'disporder' => $disporder++,
            'gid' => $gid
        );
        $db->insert_query('settings', $setting);

        $setting = array(
            'sid' => 'NULL',
            'name' => 'usersBrowsedTopicVisibleUsers',
            'title' => $db->escape_string($lang->usersBrowsedTopicVisibleUsers),
            'description' => $db->escape_string($lang->usersBrowsedTopicVisibleUsersDesc),
            'optionscode' => 'onoff',
            'value' => '1',
            'disporder' => $disporder++,
            'gid' => $gid
        );
        $db->insert_query('settings', $setting);
        
        $setting = array(
            'sid' => 'NULL',
            'name' => 'usersBrowsedTopicTimeEnable',
            'title' => $db->escape_string($lang->usersBrowsedTopicTimeEnable),
            'description' => $db->escape_string($lang->usersBrowsedTopicTimeEnableDesc),
            'optionscode' => 'onoff',
            'value' => '1',
            'disporder' => $disporder++,
            'gid' => $gid
        );
        $db->insert_query('settings', $setting);
        
        $setting = array(
            'sid' => 'NULL',
            'name' => 'usersBrowsedTopicTimeFormat',
            'title' => $db->escape_string($lang->usersBrowsedTopicTimeFormat),
            'description' => $db->escape_string($lang->usersBrowsedTopicTimeFormatDesc),
            'optionscode' => 'text',
            'value' => $mybb->settings['timeformat'],
            'disporder' => $disporder++,
            'gid' => $gid
        );
        $db->insert_query('settings', $setting);
        
        $options = 'select\nusername=' . $lang->usersBrowsedTopicOrderByOptionUsername . '\n';
        $options .= 'dateline=' . $lang->usersBrowsedTopicOrderByOptionDateline . '\n';
        $options .= 'uid=' . $lang->usersBrowsedTopicOrderByOptionUserID . '\n';
        $options .= 'usergroup=' . $lang->usersBrowsedTopicOrderByOptionGroup;
        $setting = array(
            'sid' => 'NULL',
            'name' => 'usersBrowsedTopicOrderBy',
            'title' => $db->escape_string($lang->usersBrowsedTopicOrderBy),
            'description' => $db->escape_string($lang->usersBrowsedTopicOrderByDesc),
            'optionscode' => $options,
            'value' => 'username',
            'disporder' => $disporder++,
            'gid' => $gid
        );
        $db->insert_query('settings', $setting);
        
        $setting = array(
            'sid' => 'NULL',
            'name' => 'usersBrowsedTopicOrderByASC',
            'title' => $db->escape_string($lang->usersBrowsedTopicOrderByASC),
            'description' => $db->escape_string($lang->usersBrowsedTopicOrderByASCDesc),
            'optionscode' => 'onoff',
            'value' => '1',
            'disporder' => $disporder++,
            'gid' => $gid
        );
        $db->insert_query('settings', $setting);
        
        if(!$db->table_exists('threadsread_users'))
        {
            $sql = "CREATE TABLE " . TABLE_PREFIX . "threadsread_users(
                    tid INT UNSIGNED NOT NULL,
                    uid INT UNSIGNED NOT NULL,
                    dateline INT UNSIGNED NOT NULL,
                    UNIQUE KEY (tid, uid));";
            $db->query($sql);
        }
        
        rebuild_settings();
    }

    public static function uninstall()
    {
        global $db;
        
        $result = $db->simple_select('settinggroups', 'gid', "name = 'usersBrowsedTopic'");
        $gid = (int) $db->fetch_field($result, "gid");
        
        if ($gid > 0)
        {
            $db->delete_query('settings', "gid = '{$gid}'");
        }
        $db->delete_query('settinggroups', "gid = '{$gid}'");
        $db->drop_table('threadsread_users');
        
        rebuild_settings();
    }

}
