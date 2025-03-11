<?php
/*
Plugin Name: 8Realms Rankings
Description: A plugin that creates an Elo Rating and Ranking system for Age of Sigmar. Incorporates three suites of functionality to ingest, manage and display data.
Version: 1.0
Author: Jack Duffield
*/


//------------------------------------------------------------------------------
// Core Tables Creation on Activation
//------------------------------------------------------------------------------

/**
 * Create the match_data table.
 *
 * Creates the 'match_data' table used to store parsed tournament data
 * from matches for management and display.
 *
 * @return void
 */
function rankings_create_match_data_table() {
    global $wpdb;
    $table_name      = $wpdb->prefix . 'match_data';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        tournament_name varchar(255) NOT NULL,
        start_date date NOT NULL,
        round int NOT NULL,
        table_number int NOT NULL,
        player_1_name varchar(255) NOT NULL,
        player_1_faction varchar(255) NOT NULL,
        player_1_outcome varchar(255) NOT NULL,
        player_2_name varchar(255) NOT NULL,
        player_2_faction varchar(255) NOT NULL,
        player_2_outcome varchar(255) NOT NULL,
        source varchar(255) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'rankings_create_match_data_table' );

/**
 * Create the elo_ratings table.
 *
 * Creates the 'elo_ratings' table used to store processed Elo ratings
 * calculated by the plugin for management and display.
 *
 * @return void
 */
function rankings_create_elo_ratings_table() {
    global $wpdb;
    $table_name      = $wpdb->prefix . 'elo_ratings';
    $charset_collate = $wpdb->get_charset_collate();


    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL,
        player_name text NOT NULL,
        rating int(11) NOT NULL DEFAULT 1000,
        matches_played int(11) NOT NULL DEFAULT 0,
        preferred_faction text NOT NULL,
        rank int(11) NOT NULL DEFAULT 0,
        PRIMARY KEY  (id)
    ) $charset_collate;";

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'rankings_create_elo_ratings_table' );

//------------------------------------------------------------------------------
// Enqueue Plugin Styles
//------------------------------------------------------------------------------

/**
 * Enqueue the plugin stylesheet.
 *
 * @return void
 */
function rankings_management_enqueue_scripts() {
    wp_enqueue_style( 'rankings-styles', plugins_url( 'style.css', __FILE__ ) );
}
add_action( 'wp_enqueue_scripts', 'data_management_enqueue_scripts' );

//------------------------------------------------------------------------------
// Include Main Plugin Functionality
//------------------------------------------------------------------------------

// Include the Data Ingest functionality.
require_once plugin_dir_path(__FILE__) . 'rankings-ingest.php';

// Include the Data Management functionality.
require_once plugin_dir_path(__FILE__) . 'rankings-management.php';

// Include the Data Display functionality.
require_once plugin_dir_path(__FILE__) . 'rankings-display.php';

//------------------------------------------------------------------------------
// On Uninstall
//------------------------------------------------------------------------------

// This file is loaded when the plugin is uninstalled. It removes scheduled cron events,
// drops the custom tables, and deletes plugin-specific options.

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

function eightrealms_rankings_uninstall() {
    global $wpdb;

    // Clear scheduled cron events.
    wp_clear_scheduled_hook('data_management_weekly_backup');

    // Drop custom tables.
    $tables = array(
        $wpdb->prefix . 'match_data',
        $wpdb->prefix . 'elo_ratings'
    );

    foreach ( $tables as $table ) {
        $wpdb->query( "DROP TABLE IF EXISTS $table" );
    }

    // Remove plugin options (if any were stored).
    delete_option( 'backup_manager_auto_backup' );
}

register_uninstall_hook( __FILE__, 'eightrealms_rankings_uninstall' );

// EOF