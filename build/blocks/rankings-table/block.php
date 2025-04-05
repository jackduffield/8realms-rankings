<?php

function register_rankings_block() {
    register_block_type( __DIR__, array(
        'render_callback' => 'rankings_table_render_callback',
    ) );
}
add_action( 'init', 'register_rankings_block' );

/**
 * Render the Rankings Table block.
 *
 * Renders the overall Elo rankings table.
 *
 * @param array $attributes Block attributes.
 * @return string HTML output for the rankings table.
 */
function rankings_table_render_callback( $attributes ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'elo_ratings';
    $players    = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY rank ASC", ARRAY_A );

    if ( ! $players ) {
        return '<p>No rankings available.</p>';
    }

    $plugin_dir = plugin_dir_url( __FILE__ ) . 'img/faction-icons/'; // Updated path

    $output  = '<table class="realms-table rankings-table">';
    $output .= '<thead>';
    $output .= '<tr>';
    $output .= '<th>Rank</th>';
    $output .= '<th>Player Name</th>';
    $output .= '<th>Rating</th>';
    $output .= '<th>Preferred Faction</th>';
    $output .= '</tr>';
    $output .= '</thead>';
    $output .= '<tbody>';
    foreach ( $players as $player ) {
        $rating_class = $player['matches_played'] < 25 ? ' italic' : '';
        $output      .= '<tr>';
        $output      .= '<td>' . esc_html( $player['rank'] ) . '</td>';
        $output      .= '<td><a href="' . esc_url( get_site_url() . '/player-profile?player=' . urlencode( $player['player_name'] ) ) . '">' . esc_html( $player['player_name'] ) . '</a></td>';
        $output      .= '<td class="rating-cell' . $rating_class . '">' . esc_html( $player['rating'] ) . '</td>';
        $output      .= '<td>' . rankings_render_faction( $player['preferred_faction'], $plugin_dir ) . '</td>';
        $output      .= '</tr>';
    }
    $output .= '</tbody>';
    $output .= '</table>';
    return $output;
}