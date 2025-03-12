<?php
function render_player_profile_block($attributes) {
    // Your server-side rendering logic here
    return '<div>Player Profile: ' . esc_html($attributes['player']) . '</div>';
}
register_block_type('rankings/player-profile', array(
    'render_callback' => 'render_player_profile_block',
));