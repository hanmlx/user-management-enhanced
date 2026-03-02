<?php
/**
 * Plugin Name: 用户管理增强插件
 * Description: 提供用户管理、角色转换、KBIS文件管理等功能
 * Version: 2.0
 * Author: 您的姓名
 * License: GPL v2 or later
 * Text Domain: user-management-enhanced
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

// 定义插件路径
define('UME_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('UME_PLUGIN_URL', plugin_dir_url(__FILE__));

// 插件激活时的操作
register_activation_hook(__FILE__, 'ume_plugin_activate');
function ume_plugin_activate() {
    if (!current_user_can('activate_plugins')) {
        wp_die('您没有权限激活此插件。');
    }
    
    // 检查必要组件
    if (!function_exists('acf')) {
        wp_die('此插件需要Advanced Custom Fields插件支持。请先安装并激活ACF插件。');
    }
    
    if (!class_exists('WooCommerce')) {
        wp_die('此插件需要WooCommerce插件支持。请先安装并激活WooCommerce插件。');
    }
}

// 包含子模块
require_once UME_PLUGIN_DIR . 'user-management-panel.php';
require_once UME_PLUGIN_DIR . 'kbis-file-manager.php';

// 加载文本域
add_action('init', 'ume_load_textdomain');
function ume_load_textdomain() {
    load_plugin_textdomain('user-management-enhanced', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}