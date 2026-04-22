<?php
/**
 * Plugin Name: Post Social Engagement
 * Plugin URI: https://yourwebsite.com/
 * Description: Adds Facebook-style Like, Comment, and Share buttons to WordPress posts
 * Version: 1.0.2
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: post-social-engagement
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants.
define( 'PSE_VERSION', '1.0.2' );
define( 'PSE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PSE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include required files.
require_once PSE_PLUGIN_DIR . 'includes/class-database.php';
require_once PSE_PLUGIN_DIR . 'includes/class-ajax-handler.php';
require_once PSE_PLUGIN_DIR . 'includes/class-settings.php';
require_once PSE_PLUGIN_DIR . 'includes/class-frontend.php';

// Global database object.
global $pse_db;
$pse_db = new PSE_Database();

// Activation hook - Force table creation
register_activation_hook( __FILE__, 'pse_activate_plugin' );
function pse_activate_plugin() {
    global $pse_db;
    $pse_db->create_tables();
    
    $default_settings = array(
        'show_on_home'     => true,
        'show_on_archive'  => true,
        'enable_likes'     => true,
        'enable_comments'  => true,
        'enable_shares'    => true,
        'comment_approval' => false,
        'button_position'  => 'bottom',
    );
    
    if ( ! get_option( 'pse_settings' ) ) {
        add_option( 'pse_settings', $default_settings );
    }
}

// Also check on init if table exists
add_action( 'init', 'pse_check_tables' );
function pse_check_tables() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pse_likes';
    
    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) != $table_name ) {
        global $pse_db;
        $pse_db->create_tables();
    }
}