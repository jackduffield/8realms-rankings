<?php

//------------------------------------------------------------------------------
// On Uninstall
//------------------------------------------------------------------------------

// This code is loaded when the plugin is uninstalled. It removes scheduled cron events,
// drops the custom tables, and deletes plugin-specific options.

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

global $wpdb;

// Clear scheduled cron events.
wp_clear_scheduled_hook('rankings_weekly_backup');

// Drop custom tables.
$tables = array(
    $wpdb->prefix . 'match_data',
    $wpdb->prefix . 'elo_ratings'
);
foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS $table" );
}

// Remove plugin options.
delete_option( 'backup_manager_auto_backup' );
