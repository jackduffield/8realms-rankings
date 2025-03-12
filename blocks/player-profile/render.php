<?php

/**
 * Render the Player Profile block.
 *
 * @param array $attributes Block attributes.
 * @return string HTML output for the player profile.
 */
function rankings_player_profile_render_callback( $attributes ) {
    global $wpdb;

    // Prefer the attribute value in the editor and on the front end
    if ( isset( $attributes['player'] ) && ! empty( $attributes['player'] ) ) {
        $player_name = sanitize_text_field( $attributes['player'] );
    } elseif ( isset( $_GET['player'] ) ) {
        $player_name = sanitize_text_field( $_GET['player'] );
    }

    if ( isset($player_name) ) {
        $elo_table   = $wpdb->prefix . 'elo_ratings';
        $match_table = $wpdb->prefix . 'match_data';
        $plugin_dir  = plugin_dir_url( __FILE__ ) . 'faction-icons/';

        $player_info = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $elo_table WHERE player_name = %s", $player_name ),
            ARRAY_A
        );

        if ( $player_info ) {
            ob_start();

            echo '<div class="player-profile">';
            echo '<h3>Player Profile</h3>';

            // ...existing code...

            echo '</div>';

            return ob_get_clean();
        } else {
            return '<p>Player not found.</p>';
        }
    } else {
        return '<p>No player specified. Add "?player=PlayerName" to the URL.</p>';
    }
}

register_block_type('rankings/player-profile', array(
    'render_callback' => 'rankings_player_profile_render_callback',
));