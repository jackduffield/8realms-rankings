<?php

/**
 * Rankings Display Functionality
 *
 * Provides the necessary blocks and assets to transform and display data 
 * from match_data and elo_ratings to users in a series of tables.
 *
 * @package RankingsDisplay
 */

//------------------------------------------------------------------------------
// Utility Functions
//------------------------------------------------------------------------------

/**
 * Render faction icon with link.
 *
 * Renders an SVG icon for a given faction (if available) and wraps the icon and
 * faction name in an anchor link pointing to the faction rankings page.
 * Uses a static in-memory cache to avoid repeated file lookups.
 *
 * @param string $faction    Faction name.
 * @param string $plugin_dir Plugin directory URL for faction icons.
 * @return string HTML output for faction icon and link.
 */
function rankings_render_faction( $faction, $plugin_dir ) {
    static $cache = array();

    if ( isset( $cache[ $faction ] ) ) {
        return $cache[ $faction ];
    }

    $svg_file = sanitize_title( $faction ) . '.svg';
    $svg_path = realpath( $plugin_dir . 'img/faction-icons/' . $svg_file ); // Updated to use realpath

    if ( $svg_path && file_exists( $svg_path ) ) {
        $svg = '<img src="' . esc_url( $plugin_dir . 'img/faction-icons/' . $svg_file ) . '" alt="' . esc_attr( $faction ) . '" class="realms-faction-icon">';

        $parts      = preg_split( '/\s+/', $faction, 2 );
        $first_word = $parts[0];
        $remaining  = isset( $parts[1] ) ? ' ' . $parts[1] : '';

        $result = '<a class="nounderline" href="' . esc_url( get_site_url() . '/faction-rankings?faction=' . urlencode( $faction ) ) . '"><span class="nowrap">' . $svg . esc_html( $first_word ) . '</span>' . esc_html( $remaining ) . '</a>';

        $cache[ $faction ] = $result;
        return $result;
    }

    $result = '<a class="nounderline" href="' . esc_url( get_site_url() . '/faction-rankings?faction=' . urlencode( $faction ) ) . '">' . esc_html( $faction ) . '</a>';
    $cache[ $faction ] = $result;
    return $result;
}