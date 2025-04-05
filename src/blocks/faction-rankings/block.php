<?php

function register_faction_rankings_block() {
    register_block_type( __DIR__, array(
        'render_callback' => 'rankings_render_callback',
    ) );
}
add_action( 'init', 'register_faction_rankings_block' );
/**
 * Render the Faction Rankings block.
 *
 * Displays Elo rankings for players whose preferred faction matches the query parameter.
 *
 * @param array $attributes Block attributes.
 * @return string HTML output for the faction rankings.
 */
function rankings_faction_table_render_callback( $attributes ) {
    if ( ! isset( $_GET['faction'] ) ) {
        return '<p>Please specify a faction using the <code>?faction=[Faction Name]</code> query parameter.</p>';
    }

    $faction = sanitize_text_field( urldecode( $_GET['faction'] ) );
    global $wpdb;
    $table_name = $wpdb->prefix . 'elo_ratings';

    $players = $wpdb->get_results(
        $wpdb->prepare( "SELECT * FROM $table_name WHERE preferred_faction = %s ORDER BY rating DESC", $faction ),
        ARRAY_A
    );

    if ( empty( $players ) ) {
        return '<p>No rankings available for this faction.</p>';
    }

    $plugin_icon_url = plugins_url( 'img/faction-icons-large/', __FILE__ ); // Updated path
    $svg_file        = sanitize_title( $faction ) . '.svg';
    $svg_url         = $plugin_icon_url . $svg_file;
    $svg_path        = plugin_dir_path( __FILE__ ) . 'img/faction-icons-large/' . $svg_file; // Updated path

    if ( file_exists( $svg_path ) ) {
        $faction_icon = '<img src="' . esc_url( $svg_url ) . '" alt="' . esc_attr( $faction ) . '" class="realms-faction-icon-large">';
    } else {
        $faction_icon = '';
    }

    $output  = '<div class="faction-rankings-header">';
    $output .= $faction_icon . '<h1>' . esc_html( $faction ) . ' Rankings</h1>';
    $output .= '</div>';

    $output .= '<table class="realms-table faction-rankings">';
    $output .= '<thead>';
    $output .= '<tr>';
    $output .= '<th><span class="truncate-large">Faction</span><span class="truncate-small">Fact.</span> Rank</th>';
    $output .= '<th><span class="truncate-large">Overall</span><span class="truncate-small">Ovrl.</span> Rank</th>';
    $output .= '<th><span class="truncate-large">Player Name</span><span class="truncate-small">Name</span></th>';
    $output .= '<th><span class="truncate-large">Rating</span><span class="truncate-small">Elo</span></th>';
    $output .= '<th><span class="truncate-large">Matches Played</span><span class="truncate-small">#</span></th>';
    $output .= '</tr>';
    $output .= '</thead>';
    $output .= '<tbody>';

    $faction_rank = 1;
    foreach ( $players as $player ) {
        $rating_class = $player['matches_played'] < 25 ? ' italic' : '';
        $output      .= '<tr>';
        $output      .= '<td>' . esc_html( $faction_rank++ ) . '</td>';
        $output      .= '<td>' . esc_html( $player['rank'] ) . '</td>';
        $output      .= '<td><a href="' . esc_url( get_site_url() . '/player-profile?player=' . urlencode( $player['player_name'] ) ) . '">' . esc_html( $player['player_name'] ) . '</a></td>';
        $output      .= '<td class="rating-cell' . $rating_class . '">' . esc_html( $player['rating'] ) . '</td>';
        $output      .= '<td>' . esc_html( $player['matches_played'] ) . '</td>';
        $output      .= '</tr>';
    }

    $output .= '</tbody>';
    $output .= '</table>';

    return $output;
}