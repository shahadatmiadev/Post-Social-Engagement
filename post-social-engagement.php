<?php
/**
 * Plugin Name: Xohanni Post Social Engagement
 * Plugin URI: https://github.com/shahadatmiadev/Post-Social-Engagement
 * Description: Adds Facebook-style Like, Comment, and Share buttons to WordPress posts
 * Version: 1.1.0
 * Author: Shahadat Mia
 * Requires at least: 5.7
 * Requires PHP:      7.4
 * Text Domain: post-social-engagement
 * Domain Path: /languages
 * License: GPL v2 or later
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants.
define( 'XOPSE_VERSION', '1.1.0' );
define( 'XOPSE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'XOPSE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include required files.
require_once XOPSE_PLUGIN_DIR . 'includes/class-database.php';
require_once XOPSE_PLUGIN_DIR . 'includes/class-ajax-handler.php';
require_once XOPSE_PLUGIN_DIR . 'includes/class-settings.php';
require_once XOPSE_PLUGIN_DIR . 'includes/class-frontend.php';

// Global database object.
global $xopse_db;
$xopse_db = new XOPSE_Database();

// Activation hook - Force table creation
register_activation_hook( __FILE__, 'xopse_activate_plugin' );
function xopse_activate_plugin() {
    global $xopse_db;
    $xopse_db->create_tables();

    $default_settings = array(
        'show_on_home'     => true,
        'show_on_archive'  => true,
        'enable_likes'     => true,
        'enable_comments'  => true,
        'enable_shares'    => true,
        'comment_approval' => false,
        'button_position'  => 'bottom',
    );

    if ( ! get_option( 'xopse_settings' ) ) {
        add_option( 'xopse_settings', $default_settings );
    }
}

// Also check on init if table exists
add_action( 'init', 'xopse_check_tables' );
function xopse_check_tables() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'xopse_likes';

    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) != $table_name ) {
        global $xopse_db;
        $xopse_db->create_tables();
    }
}
