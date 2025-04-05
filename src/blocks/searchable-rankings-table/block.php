<?php
/**
 * Render the Searchable Rankings block.
 *
 * Displays an input field for filtering players and renders the rankings table.
 *
 * @param array $attributes Block attributes.
 * @return string HTML output for the searchable rankings.
 */

function register_searchable_rankings_block() {
    register_block_type( __DIR__, array(
        'render_callback' => 'rankings_table_searchable_render_callback',
    ) );
}
add_action( 'init', 'register_searchable_rankings_block' );


function rankings_table_searchable_render_callback( $attributes ) {
    $output  = '<input type="text" id="search-input" placeholder="Search for a player..." onkeyup="filterRankings()">';
    $output .= '<div id="search-results">';
    $output .= rankings_table_render_callback( $attributes );
    $output .= '</div>';
    return $output;
}
