<?php
/**
 * Plugin Name:       8Realms Rankings
 * Description:       Example block scaffolded with Create Block tool.
 * Version:           0.1.0
 * Requires at least: 6.7
 * Requires PHP:      7.4
 * Author:            The WordPress Contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       rankings
 *
 * @package CreateBlock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

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
        id mediumint(9) NOT NULL AUTO_INCREMENT,
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

register_activation_hook( __FILE__, function() {
    rankings_create_match_data_table();
    rankings_create_elo_ratings_table();
    rankings_backup_activate(); // Added to schedule backups
});

register_deactivation_hook( __FILE__, function() {
    rankings_backup_deactivate(); // Added to clear scheduled backups
});

//------------------------------------------------------------------------------
// Enqueue Plugin Styles
//------------------------------------------------------------------------------

/**
 * Enqueue the plugin stylesheet.
 *
 * @return void
 */
function rankings_enqueue_scripts() {
    wp_enqueue_style( 'rankings-styles', plugins_url( 'style.css', __FILE__ ) );
}
add_action( 'wp_enqueue_scripts', 'rankings_enqueue_scripts' );

//------------------------------------------------------------------------------
// Include Main Plugin Functionality
//------------------------------------------------------------------------------

// Include the Data Ingest functionality.
require_once plugin_dir_path(__FILE__) . 'rankings-ingest.php';

// Include the Data Management functionality.
require_once plugin_dir_path(__FILE__) . 'rankings-management.php';

// Include the Data Display functionality.
require_once plugin_dir_path(__FILE__) . 'rankings-display.php';

/**
 * Registers the block using a `blocks-manifest.php` file, which improves the performance of block type registration.
 * Behind the scenes, it also registers all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://make.wordpress.org/core/2025/03/13/more-efficient-block-type-registration-in-6-8/
 * @see https://make.wordpress.org/core/2024/10/17/new-block-type-registration-apis-to-improve-performance-in-wordpress-6-7/
 */
function create_block_rankings_block_init() {
	/**
	 * Registers the block(s) metadata from the `blocks-manifest.php` and registers the block type(s)
	 * based on the registered block metadata.
	 * Added in WordPress 6.8 to simplify the block metadata registration process added in WordPress 6.7.
	 *
	 * @see https://make.wordpress.org/core/2025/03/13/more-efficient-block-type-registration-in-6-8/
	 */
	if ( function_exists( 'wp_register_block_types_from_metadata_collection' ) ) {
		wp_register_block_types_from_metadata_collection( __DIR__ . '/build', __DIR__ . '/build/blocks-manifest.php' );
		return;
	}

	/**
	 * Registers the block(s) metadata from the `blocks-manifest.php` file.
	 * Added to WordPress 6.7 to improve the performance of block type registration.
	 *
	 * @see https://make.wordpress.org/core/2024/10/17/new-block-type-registration-apis-to-improve-performance-in-wordpress-6-7/
	 */
	if ( function_exists( 'wp_register_block_metadata_collection' ) ) {
		wp_register_block_metadata_collection( __DIR__ . '/build', __DIR__ . '/build/blocks-manifest.php' );
	}
	/**
	 * Registers the block type(s) in the `blocks-manifest.php` file.
	 *
	 * @see https://developer.wordpress.org/reference/functions/register_block_type/
	 */
	$manifest_data = require __DIR__ . '/build/blocks-manifest.php';
	foreach ( array_keys( $manifest_data ) as $block_type ) {
		register_block_type( __DIR__ . "/build/{$block_type}" );
	}
}
add_action( 'init', 'create_block_rankings_block_init' );

// EOF