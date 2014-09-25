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
 * Create plugin object
 * 
 */
$plugins->objects['usersBrowsedTopic'] = new usersBrowsedTopic();

/**
 * Standard MyBB info function
 * 
 */
function usersBrowsedTopic_info()
{
	global $lang;

	$lang->load("usersBrowsedTopic");
    $lang->usersBrowsedTopicDesc = '<form action="https://www.paypal.com/cgi-bin/webscr" method="post" style="float:right;">' .
        '<input type="hidden" name="cmd" value="_s-xclick">' . 
        '<input type="hidden" name="hosted_button_id" value="3BTVZBUG6TMFQ">' .
        '<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">' .
        '<img alt="" border="0" src="https://www.paypalobjects.com/pl_PL/i/scr/pixel.gif" width="1" height="1">' .
        '</form>' . $lang->usersBrowsedTopicDesc;
    

	return Array(
		'name' => $lang->usersBrowsedTopicName,
		'description' => $lang->usersBrowsedTopicDesc,
		'website' => 'http://lukasztkacz.com',
		'author' => 'Lukasz "LukasAMD" Tkacz',
		'authorsite' => 'http://lukasztkacz.com',
		'version' => '1.1.1',
		'compatibility' => '18*'
	);
}

/**
 * Standard MyBB installation functions 
 * 
 */
function usersBrowsedTopic_install() 
{
    require_once('usersBrowsedTopic.settings.php');
    usersBrowsedTopicInstaller::install();
}

function usersBrowsedTopic_is_installed() 
{
    global $mybb;

    return (isset($mybb->settings['usersBrowsedTopicEnable']));
}

function usersBrowsedTopic_uninstall() 
{
    require_once('usersBrowsedTopic.settings.php');
    usersBrowsedTopicInstaller::uninstall();
}

/**
 * Standard MyBB activation functions 
 * 
 */
function usersBrowsedTopic_activate()
{
    require_once('usersBrowsedTopic.tpl.php');
    usersBrowsedTopicActivator::activate();
}

function usersBrowsedTopic_deactivate()
{
    require_once('usersBrowsedTopic.tpl.php');
    usersBrowsedTopicActivator::deactivate();
}


/**
 * Plugin Class 
 * 
 */
class usersBrowsedTopic
{
    /**
     * Add all needed hooks
     *      
     */
    public function __construct()
    {
        global $plugins;

        // Check file and state
        if (!$this->getConfig('Enable') || THIS_SCRIPT != 'showthread.php')
        {
            return;
        }
        
        $plugins->hooks["showthread_start"][10]["usersBrowsedTopic_collectData"] = array("function" => create_function('', 'global $plugins; $plugins->objects[\'usersBrowsedTopic\']->collectData();'));
        $plugins->hooks["pre_output_page"][10]["usersBrowsedTopic_modifyOutput"] = array("function" => create_function('&$arg', 'global $plugins; $plugins->objects[\'usersBrowsedTopic\']->modifyOutput($arg);'));   
        $plugins->hooks["pre_output_page"][10]["usersBrowsedTopic_pluginThanks"] = array("function" => create_function('&$arg', 'global $plugins; $plugins->objects[\'usersBrowsedTopic\']->pluginThanks($arg);'));       
    }

    /**
     * Collect data
     * 
     */
    public function collectData()
    {
        global $db, $mybb, $tid;
        
        if ($mybb->user['uid'] > 0)
        {
            $db->query("INSERT IGNORE INTO " . TABLE_PREFIX . "threadsread_users
                        SET tid = '{$tid}', uid = '{$mybb->user['uid']}', dateline = '" . TIME_NOW . "'");
        } 
    } 
 
    /**
     * Change code to output data
     *      
     */
    public function modifyOutput(&$content)
    {
        global $db, $lang, $mybb, $templates, $tid, $usersBrowsed, $usersBrowsedTopicUser;
        
        // Check option and user
        if ($this->getConfig('VisibleUsers') && $mybb->user['uid'] == 0)
        {
            return;
        }

        $lang->load('usersBrowsedTopic');
        $users_list = '';
        
        // Get users list
        $order_by = 'username';
        if (in_array($order_by, array('dateline', 'username', 'uid', 'usergroup')))
        {
            $order_by = $this->getConfig('OrderBy');    
        }
        
        $order_asc = 'DESC';
        if ($this->getConfig('OrderByASC'))
        {
            $order_asc = 'ASC';
        }
        
        $sql = "SELECT tu.uid, tu.username, tu.usergroup, tu.displaygroup, tubt.dateline
                FROM " . TABLE_PREFIX . "threadsread_users AS tubt 
                INNER JOIN " . TABLE_PREFIX . "users AS tu ON tubt.uid = tu.uid
                WHERE tubt.tid = '{$tid}'  
                ORDER BY {$order_by} {$order_asc}";
        $result = $db->query($sql);
        while($row = $db->fetch_array($result))
        {
            $user['profilelink'] = get_profile_link($row['uid']);
            $user['username'] = format_name($row['username'], $row['usergroup'], $row['displaygroup']);
            
            // Get dateline
            $user['dateline'] = '';
            if ($this->getConfig('TimeEnable'))
            {
                $user['dateline'] = my_date($this->getConfig('TimeFormat'), $row['dateline']);
                $user['dateline'] = ' title="' . $lang->usersBrowsedTopicHover . $user['dateline'] . '"';
            }
            
            eval("\$users_list .= \"".$templates->get("usersBrowsedUser")."\";");
            $comma = $lang->comma;
        }

        // Replace output code
        eval("\$output .= \"".$templates->get("usersBrowsed")."\";");
        $content = str_replace('<!-- PLUGIN_USERS_BROWSED_TOPIC -->', $output, $content);
    } 

    /**
     * Helper function to get variable from config
     * 
     * @param string $name Name of config to get
     * @return string Data config from MyBB Settings
     */
    private function getConfig($name)
    {
        global $mybb;

        return $mybb->settings["usersBrowsedTopic{$name}"];
    }
    
    /**
     * Say thanks to plugin author - paste link to author website.
     * Please don't remove this code if you didn't make donate
     * It's the only way to say thanks without donate :)     
     */
    public function pluginThanks(&$content)
    {
        global $session, $lukasamd_thanks;
        
        if (!isset($lukasamd_thanks) && $session->is_spider)
        {
            $thx = '<div style="margin:auto; text-align:center;">This forum uses <a href="http://lukasztkacz.com">Lukasz Tkacz</a> MyBB addons.</div></body>';
            $content = str_replace('</body>', $thx, $content);
            $lukasamd_thanks = true;
        }
    }
}