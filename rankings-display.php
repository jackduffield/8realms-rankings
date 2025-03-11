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
// Plugin Initialisation & Registration
//------------------------------------------------------------------------------

/**
 * Initialise Rankings Display blocks and assets.
 *
 * Registers block editor scripts/styles and custom blocks:
 * - Rankings
 * - Searchable Rankings
 * - Events
 * - Faction Rankings
 *
 * @return void
 */
function rankings_init() {
    wp_register_script(
        'rankings-editor-script',
        plugins_url( 'blocks.js', __FILE__ ),
        array(
            'wp-blocks',
            'wp-element',
            'wp-editor',
            'wp-components',
            'wp-data',
            'wp-server-side-render',
        )
    );

    wp_register_style(
        'rankings-editor-style',
        plugins_url( 'editor.css', __FILE__ ),
        array( 'wp-edit-blocks' )
    );

    wp_register_style( 'rankings', plugins_url( 'style.css', __FILE__ ) );

    register_block_type(
        'rankings/rankings',
        array(
            'editor_script'   => 'rankings-editor-script',
            'editor_style'    => 'rankings-editor-style',
            'style'           => 'rankings',
            'render_callback' => 'rankings_render_callback',
        )
    );

    register_block_type(
        'rankings/searchable-rankings',
        array(
            'editor_script'   => 'rankings-editor-script',
            'editor_style'    => 'rankings-editor-style',
            'style'           => 'rankings',
            'render_callback' => 'rankings_searchable_render_callback',
        )
    );

    register_block_type(
        'rankings/events',
        array(
            'editor_script'   => 'rankings-editor-script',
            'editor_style'    => 'rankings-editor-style',
            'style'           => 'rankings',
            'render_callback' => 'rankings_events_render_callback',
        )
    );

    register_block_type(
        'rankings/faction-rankings',
        array(
            'editor_script'   => 'rankings-editor-script',
            'editor_style'    => 'rankings-editor-style',
            'style'           => 'rankings',
            'render_callback' => 'rankings_faction_render_callback',
        )
    );
    register_block_type(
        'rankings/player-profile',
        array(
            'render_callback' => 'rankings_player_profile_render_callback',
        )
    );
}
add_action( 'init', 'rankings_init' );

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
    $svg_path = $plugin_dir . $svg_file;

    $svg_real_path = str_replace(
        wp_normalize_path( WP_CONTENT_URL ),
        wp_normalize_path( WP_CONTENT_DIR ),
        wp_normalize_path( $svg_path )
    );

    if ( file_exists( $svg_real_path ) ) {
        $svg = '<img src="' . esc_url( plugins_url( 'faction-icons/' . $svg_file, __FILE__ ) ) . '" alt="' . esc_attr( $faction ) . '" class="realms-faction-icon"> ';

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

//------------------------------------------------------------------------------
// Asset Enqueue Functions
//------------------------------------------------------------------------------

/**
 * Enqueue front-end assets for Data Display.
 *
 * Enqueues necessary JavaScript and CSS files for front-end display.
 *
 * @return void
 */
function rankings_display_enqueue_assets() {
    wp_enqueue_script(
        'rankings-filter',
        plugins_url( 'filter.js', __FILE__ ),
        array( 'jquery' ),
        null,
        true
    );
    wp_enqueue_style(
        'rankings-styles',
        plugins_url( 'style.css', __FILE__ )
    );
}
add_action( 'wp_enqueue_scripts', 'rankings_display_enqueue_assets' );

/**
 * Enqueue block editor assets for Data Display.
 *
 * Enqueues necessary JavaScript and CSS files for the block editor.
 *
 * @return void
 */
function rankings_display_enqueue_editor_assets() {
    wp_enqueue_script(
        'rankings-editor-script',
        plugins_url( 'blocks.js', __FILE__ ),
        array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data' ),
        '1.0',
        true
    );

    wp_enqueue_style(
        'rankings-editor-style',
        plugins_url( 'editor.css', __FILE__ ),
        array( 'wp-edit-blocks' ),
        '1.0'
    );
}
add_action( 'enqueue_block_editor_assets', 'rankings_display_enqueue_editor_assets' );

//------------------------------------------------------------------------------
// Render Callback Functions
//------------------------------------------------------------------------------

/**
 * Render the Rankings Table block.
 *
 * Renders the overall Elo rankings table.
 *
 * @param array $attributes Block attributes.
 * @return string HTML output for the rankings table.
 */
function rankings_render_callback( $attributes ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'elo_ratings';
    $players    = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY rank ASC", ARRAY_A );

    if ( empty( $players ) ) {
        return '<p>No rankings available.</p>';
    }

    $plugin_dir = plugin_dir_url( __FILE__ ) . 'faction-icons/';

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

/**
 * Render the Searchable Rankings block.
 *
 * Displays an input field for filtering players and renders the rankings table.
 *
 * @param array $attributes Block attributes.
 * @return string HTML output for the searchable rankings.
 */
function rankings_searchable_render_callback( $attributes ) {
    $output  = '<input type="text" id="search-input" placeholder="Search for a player..." onkeyup="filterRankings()">';
    $output .= '<div id="search-results">';
    $output .= rankings_render_callback( $attributes );
    $output .= '</div>';
    return $output;
}

/**
 * Render the Events block.
 *
 * Renders a table of distinct events.
 *
 * @param array $attributes Block attributes.
 * @return string HTML output for the events table.
 */
function events_render_callback( $attributes ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'match_data';
    $events     = $wpdb->get_results( "SELECT DISTINCT tournament_name, start_date FROM $table_name ORDER BY start_date DESC", ARRAY_A );

    if ( empty( $events ) ) {
        return '<p>No events available.</p>';
    }

    $output  = '<table class="realms-table events-list">';
    $output .= '<thead>';
    $output .= '<tr>';
    $output .= '<th>Event Name</th>';
    $output .= '<th>Start Date</th>';
    $output .= '</tr>';
    $output .= '</thead>';
    $output .= '<tbody>';
    foreach ( $events as $event ) {
        $output .= '<tr>';
        $output .= '<td>' . esc_html( $event['tournament_name'] ) . '</td>';
        $output .= '<td>' . esc_html( $event['start_date'] ) . '</td>';
        $output .= '</tr>';
    }
    $output .= '</tbody>';
    $output .= '</table>';
    return $output;
}

/**
 * Render the Faction Rankings block.
 *
 * Displays Elo rankings for players whose preferred faction matches the query parameter.
 *
 * @param array $attributes Block attributes.
 * @return string HTML output for the faction rankings.
 */
function rankings_faction_render_callback( $attributes ) {
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

    $plugin_icon_url = plugins_url( 'faction-icons-large/', __FILE__ );
    $svg_file        = sanitize_title( $faction ) . '.svg';
    $svg_url         = $plugin_icon_url . $svg_file;
    $svg_path        = plugin_dir_path( __FILE__ ) . 'faction-icons-large/' . $svg_file;

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

/**
 * Render the Player Profile block.
 *
 * Renders a detailed player profile including rankings, stats, and match history.
 *
 * @param array $attributes Block attributes.
 * @return string HTML output for the player profile.
 */
function rankings_player_profile_render_callback( $attributes ) {
    global $wpdb;

    if ( isset( $_GET['player'] ) ) {
        $player_name = sanitize_text_field( $_GET['player'] );
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

            // ----- Faction Icons Podium -----
            $faction_counts = array();
            $match_list     = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT 
                        bcp.tournament_name AS event_name,
                        bcp.start_date, 
                        bcp.round, 
                        bcp.player_1_name, 
                        bcp.player_1_outcome, 
                        bcp.player_2_name, 
                        bcp.player_2_outcome, 
                        bcp.player_1_faction, 
                        bcp.player_2_faction,
                        elo_1.rating AS player_1_elo,
                        elo_2.rating AS player_2_elo
                    FROM $match_table bcp
                    LEFT JOIN $elo_table elo_1 ON bcp.player_1_name = elo_1.player_name
                    LEFT JOIN $elo_table elo_2 ON bcp.player_2_name = elo_2.player_name
                    WHERE bcp.player_1_name = %s OR bcp.player_2_name = %s
                    ORDER BY bcp.start_date DESC, bcp.round DESC",
                    $player_name,
                    $player_name
                ),
                ARRAY_A
            );

            foreach ( $match_list as $match ) {
                $faction = ( $match['player_1_name'] === $player_name ) ? $match['player_1_faction'] : $match['player_2_faction'];
                if ( ! isset( $faction_counts[ $faction ] ) ) {
                    $faction_counts[ $faction ] = 0;
                }
                $faction_counts[ $faction ]++;
            }

            $most_played = array();
            if ( ! empty( $faction_counts ) ) {
                $max_count = max( $faction_counts );
                foreach ( $faction_counts as $faction => $count ) {
                    if ( $count == $max_count ) {
                        $most_played[] = $faction;
                    }
                }
                $others = array();
                foreach ( $faction_counts as $faction => $count ) {
                    if ( ! in_array( $faction, $most_played ) ) {
                        $others[ $faction ] = $count;
                    }
                }
                arsort( $others );
                $others_keys = array_keys( $others );
                $n           = count( $others_keys );
                $left        = array_slice( $others_keys, 0, floor( $n / 2 ) );
                $right       = array_slice( $others_keys, floor( $n / 2 ) );
                if ( count( $left ) < count( $right ) ) {
                    $left[] = null;
                } elseif ( count( $right ) < count( $left ) ) {
                    $right[] = null;
                }
                $left        = array_reverse( $left );
                $final_order = array_merge( $left, $most_played, $right );
            } else {
                $final_order = array();
            }

            echo '<div style="text-align: center; margin-bottom: 10px;">';
            foreach ( $final_order as $faction ) {
                if ( $faction === null ) {
                    echo '<span style="display:inline-block;width:24px;height:24px;margin:0 5px;"></span>';
                } else {
                    $icon_with_anchor = rankings_render_faction( $faction, $plugin_dir );
                    if ( preg_match( '/<img[^>]+>/i', $icon_with_anchor, $matches ) ) {
                        $icon_html = $matches[0];
                    } else {
                        $icon_html = $icon_with_anchor;
                    }
                    if ( in_array( $faction, $most_played ) ) {
                        $icon_html = str_replace(
                            'class="realms-faction-icon"',
                            'class="realms-faction-icon" style="width:48px;height:48px;"',
                            $icon_html
                        );
                    } else {
                        $icon_html = str_replace(
                            'class="realms-faction-icon"',
                            'class="realms-faction-icon" style="width:24px;height:24px;"',
                            $icon_html
                        );
                    }
                    echo '<span style="margin: 0 5px;">' . $icon_html . '</span>';
                }
            }
            echo '</div>';

            // ----- Player Name and Stats -----
            echo '<h1>' . esc_html( $player_name ) . '</h1>';

            $wins            = 0;
            $draws           = 0;
            $losses          = 0;
            $events          = array();
            $matchups        = array();
            $opponent_counts = array();

            foreach ( $match_list as $match ) {
                if ( $match['player_1_name'] === $player_name ) {
                    $result           = $match['player_1_outcome'];
                    $opponent         = $match['player_2_name'];
                    $opponent_faction = $match['player_2_faction'];
                    $date             = $match['start_date'];
                } else {
                    $result           = $match['player_2_outcome'];
                    $opponent         = $match['player_1_name'];
                    $opponent_faction = $match['player_1_faction'];
                    $date             = $match['start_date'];
                }

                if ( strcasecmp( $result, 'Win' ) === 0 ) {
                    $wins++;
                } elseif ( strcasecmp( $result, 'Draw' ) === 0 ) {
                    $draws++;
                } else {
                    $losses++;
                }

                $event = $match['event_name'];
                if ( ! isset( $events[ $event ] ) ) {
                    $events[ $event ] = array( 'wins' => 0, 'draws' => 0, 'losses' => 0 );
                }
                if ( strcasecmp( $result, 'Win' ) === 0 ) {
                    $events[ $event ]['wins']++;
                } elseif ( strcasecmp( $result, 'Draw' ) === 0 ) {
                    $events[ $event ]['draws']++;
                } else {
                    $events[ $event ]['losses']++;
                }

                if ( strcasecmp( $result, 'Win' ) === 0 ) {
                    if ( ! isset( $matchups[ $opponent_faction ] ) ) {
                        $matchups[ $opponent_faction ] = array( 'wins' => 0, 'last_win_date' => 0 );
                    }
                    $matchups[ $opponent_faction ]['wins']++;
                    $timestamp = strtotime( $date );
                    if ( $timestamp > $matchups[ $opponent_faction ]['last_win_date'] ) {
                        $matchups[ $opponent_faction ]['last_win_date'] = $timestamp;
                    }
                }

                if ( ! isset( $opponent_counts[ $opponent ] ) ) {
                    $opponent_counts[ $opponent ] = array( 'match_count' => 0, 'last_played_date' => null );
                }
                $opponent_counts[ $opponent ]['match_count']++;
                $timestamp = strtotime( $date );
                if ( $opponent_counts[ $opponent ]['last_played_date'] === null || $timestamp < $opponent_counts[ $opponent ]['last_played_date'] ) {
                    $opponent_counts[ $opponent ]['last_played_date'] = $timestamp;
                }
            }

            $best_event         = null;
            $best_score         = -INF;
            foreach ( $events as $event_name => $record ) {
                $score = $record['wins'] + ( 0.5 * $record['draws'] );
                if ( $score > $best_score ) {
                    $best_score = $score;
                    $best_event = $record;
                }
            }
            $best_record_display = $best_event ? "{$best_event['wins']} - {$best_event['draws']} - {$best_event['losses']}" : '-';

            $best_matchup = null;
            foreach ( $matchups as $faction => $data ) {
                if ( $best_matchup === null ) {
                    $best_matchup = $faction;
                } else {
                    if ( $data['wins'] > $matchups[ $best_matchup ]['wins'] ) {
                        $best_matchup = $faction;
                    } elseif ( $data['wins'] == $matchups[ $best_matchup ]['wins'] && $data['last_win_date'] > $matchups[ $best_matchup ]['last_win_date'] ) {
                        $best_matchup = $faction;
                    }
                }
            }
            $best_matchup_display = $best_matchup ? $best_matchup : '-';

            $selected_opponent = null;
            foreach ( $opponent_counts as $opp => $data ) {
                if ( $data['match_count'] < 2 ) {
                    continue;
                }
                if ( $selected_opponent === null ) {
                    $selected_opponent = $opp;
                } else {
                    if ( $data['match_count'] > $opponent_counts[ $selected_opponent ]['match_count'] ) {
                        $selected_opponent = $opp;
                    } elseif ( $data['match_count'] == $opponent_counts[ $selected_opponent ]['match_count'] && $data['last_played_date'] < $opponent_counts[ $selected_opponent ]['last_played_date'] ) {
                        $selected_opponent = $opp;
                    }
                }
            }
            $nemesis_display = $selected_opponent ? $selected_opponent : '-';

            $ranking_url = site_url( '/ranking' );
            echo '<div class="player-profile-overview-tables">';
                echo '<table class="realms-table player-profile-rank">';
                    echo '<thead>';
                        echo '<tr><th>UK Rank</th></tr>';
                    echo '</thead>';
                    echo '<tbody>';
                        echo '<tr><td><a href="' . esc_url( $ranking_url ) . '" style="color: inherit; text-decoration: none;">' . esc_html( $player_info['rank'] ) . '</a></td></tr>';
                    echo '</tbody>';
                echo '</table>';

                echo '<table class="realms-table player-profile-rating">';
                    echo '<thead>';
                        echo '<tr><th>Elo Rating</th></tr>';
                    echo '</thead>';
                    echo '<tbody>';
                        echo '<tr><td><a href="' . esc_url( $ranking_url ) . '" style="color: inherit; text-decoration: none;">' . esc_html( $player_info['rating'] ) . '</a></td></tr>';
                    echo '</tbody>';
                echo '</table>';
            echo '</div>';

            echo '<h3>Key Stats</h3>';
            echo '<table class="realms-table player-profile-stats">';
            echo '<tbody>';
            echo '<tr><td>Matches Played</td><td>' . esc_html( $player_info['matches_played'] ) . '</td></tr>';
            echo '<tr><td>Preferred Faction</td><td>' . rankings_render_faction( $player_info['preferred_faction'], $plugin_dir ) . '</td></tr>';
            echo '<tr><td>All-Time Record</td><td>' . esc_html( "$wins - $draws - $losses" ) . '</td></tr>';
            echo '<tr><td>Event Record</td><td>' . $best_record_display . '</td></tr>';
            echo '<tr><td>Best Matchup</td><td>' . $best_matchup_display . '</td></tr>';
            echo '<tr><td>Nemesis</td><td>' . $nemesis_display . '</td></tr>';
            echo '</tbody>';
            echo '</table>';

            // ----- Player Match History (large and small versions) -----
            if ( $match_list ) {
                echo '<h3 style="text-align: center">Match History</h3>';

                echo '<table class="realms-table player-profile-history">';
                echo '<thead>';
                echo '<tr>';
                echo '<th>Event Name</th>';
                echo '<th>Round</th>';
                echo '<th>Opponent</th>';
                echo '<th>Opponent Faction</th>';
                echo '<th>Opponent Elo</th>';
                echo '<th>Result</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';

                $event_row_counts = array();
                foreach ( $match_list as $match ) {
                    $event_name = $match['event_name'];
                    if ( ! isset( $event_row_counts[ $event_name ] ) ) {
                        $event_row_counts[ $event_name ] = 0;
                    }
                    $event_row_counts[ $event_name ]++;
                }

                $last_event = null;
                foreach ( $match_list as $match ) {
                    if ( $match['player_1_name'] === $player_name ) {
                        $result           = $match['player_1_outcome'];
                        $opponent         = $match['player_2_name'];
                        $opponent_faction = $match['player_2_faction'];
                        $opponent_elo     = $match['player_2_elo'];
                    } else {
                        $result           = $match['player_2_outcome'];
                        $opponent         = $match['player_1_name'];
                        $opponent_faction = $match['player_1_faction'];
                        $opponent_elo     = $match['player_1_elo'];
                    }
                    $event_with_date = $match['event_name'] . ' - ' . date( 'M Y', strtotime( $match['start_date'] ) );

                    echo '<tr>';
                    if ( $match['event_name'] !== $last_event ) {
                        echo '<td rowspan="' . $event_row_counts[ $match['event_name'] ] . '">' . esc_html( $event_with_date ) . '</td>';
                        $last_event = $match['event_name'];
                    } else {
                        echo '<td class="hidden"></td>';
                    }
                    echo '<td>' . esc_html( $match['round'] ) . '</td>';
                    $opponent_link = '<a href="' . esc_url( add_query_arg( 'player', urlencode( $opponent ), site_url( '/player-profile/' ) ) ) . '">' . esc_html( $opponent ) . '</a>';
                    echo '<td>' . wp_kses_post( $opponent_link ) . '</td>';
                    echo '<td>' . rankings_render_faction( $opponent_faction, $plugin_dir ) . '</td>';
                    echo '<td>' . esc_html( $opponent_elo !== null ? $opponent_elo : 'N/A' ) . '</td>';
                    echo '<td>' . esc_html( $result ) . '</td>';
                    echo '</tr>';
                }

                echo '</tbody>';
                echo '</table>';

                echo '<table class="realms-table player-profile-history-small">';
                echo '<thead>';
                echo '<tr>';
                echo '<th><span class="truncate-large">Round</span><span class="truncate-small">#</span></th>';
                echo '<th><span class="truncate-large">Opponent</span><span class="truncate-small">Opp.</span></th>';
                echo '<th><span class="truncate-large">Opponent Faction</span><span class="truncate-small">Opp. Fact.</span></th>';
                echo '<th><span class="truncate-large">Opponent Elo</span><span class="truncate-small">Opp. Elo</span></th>';
                echo '<th><span class="truncate-large">Result</span><span class="truncate-small">Res.</span></th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';

                $last_event = null;
                foreach ( $match_list as $match ) {
                    if ( $match['event_name'] !== $last_event ) {
                        $event_with_date = $match['event_name'] . ' - ' . date( 'M Y', strtotime( $match['start_date'] ) );
                        echo '<tr class="event-header"><td colspan="5">' . esc_html( $event_with_date ) . '</td></tr>';
                        $last_event = $match['event_name'];
                    }
                    if ( $match['player_1_name'] === $player_name ) {
                        $result           = $match['player_1_outcome'];
                        $opponent         = $match['player_2_name'];
                        $opponent_faction = $match['player_2_faction'];
                        $opponent_elo     = $match['player_2_elo'];
                    } else {
                        $result           = $match['player_2_outcome'];
                        $opponent         = $match['player_1_name'];
                        $opponent_faction = $match['player_1_faction'];
                        $opponent_elo     = $match['player_1_elo'];
                    }
                    $opponent_link = '<a href="' . esc_url( add_query_arg( 'player', urlencode( $opponent ), site_url( '/player-profile/' ) ) ) . '">' . esc_html( $opponent ) . '</a>';
                    echo '<tr>';
                    echo '<td>' . esc_html( $match['round'] ) . '</td>';
                    echo '<td>' . wp_kses_post( $opponent_link ) . '</td>';
                    echo '<td>' . rankings_render_faction( $opponent_faction, $plugin_dir ) . '</td>';
                    echo '<td>' . esc_html( $opponent_elo !== null ? $opponent_elo : 'N/A' ) . '</td>';
                    echo '<td>' . esc_html( $result ) . '</td>';
                    echo '</tr>';
                }

                echo '</tbody>';
                echo '</table>';
            } else {
                echo '<p>No match history available for this player.</p>';
            }

            echo '</div>';

            return ob_get_clean();
        } else {
            return '<p>Player not found.</p>';
        }
    } else {
        return '<p>No player specified. Add "?player=PlayerName" to the URL.</p>';
    }
}

// EOF