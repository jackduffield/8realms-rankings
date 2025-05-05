<?php

/**
 * Rankings Ingest Functionality
 *
 * Provides database table creation, data parsing, Elo rating calculation,
 * and admin pages for ingesting and processing tournament data.
 *
 * @package RankingsIngest
 */

//------------------------------------------------------------------------------
// Admin Menu Setup
//------------------------------------------------------------------------------

/**
 * Register admin menu pages for Rankings Ingest.
 *
 * Creates the main 'Ingest Rankings' menu with two submenus:
 * - BCP Parser for processing Best Coast Pairings data.
 * - SNL Parser for processing Stats and Ladders data.
 *
 * @return void
 */
function rankings_ingest_menu() {
    // Main menu page.
    add_menu_page( 'Ingest Rankings', 'Ingest Rankings', 'edit_others_posts', 'rankings-ingest', 'rankings_ingest_bcp_page' );

    // Submenu page for Ingest BCP Data.
    add_submenu_page( 'rankings-ingest', 'Ingest From BCP', 'Ingest From BCP', 'edit_others_posts', 'bcp-parser', 'rankings_ingest_bcp_page' );

    // Submenu page for Ingest SNL Data.
    add_submenu_page( 'rankings-ingest', 'Ingest From SNL', 'Ingest From SNL', 'edit_others_posts', 'snl-parser', 'rankings_ingest_snl_page' );

    // Submenu page for Ingest Milarki Data.
    add_submenu_page( 'rankings-ingest', 'Ingest From Milarki', 'Ingest From Milarki', 'edit_others_posts', 'milarki-parser', 'rankings_ingest_milarki_page' );

    // Remove the duplicate submenu for the top-level menu.
    remove_submenu_page( 'rankings-ingest', 'rankings-ingest' );
}
add_action( 'admin_menu', 'rankings_ingest_menu' );

//------------------------------------------------------------------------------
// Data Parsing & Display Functions
//------------------------------------------------------------------------------

/**
 * Parse BCP data and insert into the match_data table.
 *
 * Parses tournament data from Best Coast Pairings and inserts match details into
 * the match_data table. Returns parsed match data for display purposes.
 *
 * @param string $tournament_name Tournament name.
 * @param string $start_date      Tournament start date.
 * @param array  $rounds          Array of rounds' raw text.
 * @return array Parsed match data.
 * @throws Exception If an error occurs during insertion.
 */
function rankings_parse_bcp_data( $tournament_name, $start_date, $rounds ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'match_data';
    $data       = array();

    // Process each round.
    foreach ( $rounds as $round_number => $round_text ) {
        $lines         = preg_split( '/\r\n|\r|\n/', $round_text );
        $current_match = array();

        // Process each line.
        foreach ( $lines as $line ) {
            $line = trim( $line );
            if ( $line == 'View List' ) {
                continue;
            }
            if ( ! empty( $line ) ) {
                $current_match[] = $line;
            }
            // Once we have 7 parts, assume one match is complete.
            if ( count( $current_match ) == 7 ) {
                try {
                    $table_number     = $current_match[0];
                    $player_1_name    = $current_match[1];
                    $player_1_faction = preg_replace( '/:.*/', '', $current_match[2] );
                    $player_1_outcome = preg_replace( '/:.*/', '', $current_match[3] );
                    $player_2_name    = $current_match[4];
                    $player_2_faction = preg_replace( '/:.*/', '', $current_match[5] );
                    $player_2_outcome = preg_replace( '/:.*/', '', $current_match[6] );

                    $result = $wpdb->insert(
                        $table_name,
                        array(
                            'tournament_name'  => $tournament_name,
                            'start_date'       => $start_date,
                            'round'            => $round_number + 1,
                            'table_number'     => $table_number,
                            'player_1_name'    => $player_1_name,
                            'player_1_faction' => $player_1_faction,
                            'player_1_outcome' => $player_1_outcome,
                            'player_2_name'    => $player_2_name,
                            'player_2_faction' => $player_2_faction,
                            'player_2_outcome' => $player_2_outcome,
                            'source'           => 'Best Coast Pairings',
                        )
                    );

                    if ( $result === false ) {
                        throw new Exception( 'Error inserting match data: ' . $wpdb->last_error );
                    } else {
                        $data[] = array(
                            'Tournament Name'  => $tournament_name,
                            'Start Date'       => $start_date,
                            'Round'            => $round_number + 1,
                            'Table Number'     => $table_number,
                            'Player 1 Name'    => $player_1_name,
                            'Player 1 Faction' => $player_1_faction,
                            'Player 1 Outcome' => $player_1_outcome,
                            'Player 2 Name'    => $player_2_name,
                            'Player 2 Faction' => $player_2_faction,
                            'Player 2 Outcome' => $player_2_outcome,
                            'Source'           => 'Best Coast Pairings',
                        );
                    }
                    $current_match = array();
                } catch ( Exception $e ) {
                    throw new Exception( 'Error processing match: ' . htmlspecialchars( json_encode( $current_match ) ) . ' - ' . $e->getMessage() );
                }
            }
        }

        // Incomplete match data.
        if ( ! empty( $current_match ) ) {
            throw new Exception( 'Incomplete match data: ' . htmlspecialchars( json_encode( $current_match ) ) );
        }
    }

    return $data;
}

/**
 * Display parsed BCP data in a table.
 *
 * @param array $data Parsed match data.
 * @return void
 */
function rankings_show_bcp_parsed_data( $data ) {
    if ( empty( $data ) ) {
        echo '<p>No data available to display.</p>';
        return;
    }
    echo '<table class="widefat fixed" cellspacing="0">';
    echo '<thead><tr>';
    foreach ( array_keys( $data[0] ) as $key ) {
        echo '<th>' . esc_html( $key ) . '</th>';
    }
    echo '</tr></thead>';
    echo '<tbody>';
    foreach ( $data as $row ) {
        echo '<tr>';
        foreach ( $row as $value ) {
            echo '<td>' . esc_html( $value ) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table>';
}

/**
 * Parse SNL data and insert into the match_data table.
 *
 * Parses tournament data from Stats and Ladders and inserts match details into
 * the match_data table. Returns parsed match data for display purposes.
 *
 * @param string $tournament_name Tournament name.
 * @param string $start_date      Tournament start date.
 * @param array  $rounds          Array of rounds' raw text.
 * @return array Parsed match data.
 */
function rankings_parse_snl_data( $tournament_name, $start_date, $rounds ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'match_data';
    $data       = array();

    foreach ( $rounds as $round_number => $round_text ) {
        $lines = preg_split( '/\r\n|\r|\n/', $round_text );
        $i     = 0;
        while ( $i < count( $lines ) ) {
            if ( isset( $lines[ $i ], $lines[ $i + 1 ], $lines[ $i + 2 ], $lines[ $i + 3 ] ) ) {
                if (
                    preg_match( '/^\d+$/', trim( $lines[ $i ] ) ) &&
                    preg_match( '/^\S+/', trim( $lines[ $i + 1 ] ) ) &&
                    preg_match( '/^\S+/', trim( $lines[ $i + 2 ] ) )
                ) {
                    $table_number     = trim( $lines[ $i ] );
                    $player_1_name    = trim( $lines[ $i + 1 ] );
                    $player_1_faction = trim( $lines[ $i + 2 ] );
                    $outcome_line     = '';
                    $player_2_name    = '';
                    $player_2_faction = '';
                    $j                = $i + 3;
                    while ( $j < count( $lines ) && ! preg_match( '/^(DEF|LOST TO|DREW WITH)$/', trim( $lines[ $j ] ) ) ) {
                        $j++;
                    }
                    if ( $j < count( $lines ) ) {
                        $outcome_line = trim( $lines[ $j ] );
                    }
                    $player_2_name    = trim( $lines[ $j + 1 ] ?? '' );
                    $player_2_faction = trim( $lines[ $j + 2 ] ?? '' );

                    switch ( $outcome_line ) {
                        case 'DEF':
                            $player_1_outcome = 'Win';
                            $player_2_outcome = 'Loss';
                            break;
                        case 'LOST TO':
                            $player_1_outcome = 'Loss';
                            $player_2_outcome = 'Win';
                            break;
                        case 'DREW WITH':
                            $player_1_outcome = 'Draw';
                            $player_2_outcome = 'Draw';
                            break;
                        default:
                            $player_1_outcome = '';
                            $player_2_outcome = '';
                            break;
                    }

                    $wpdb->insert(
                        $table_name,
                        array(
                            'tournament_name'  => $tournament_name,
                            'start_date'       => $start_date,
                            'round'            => $round_number + 1,
                            'table_number'     => $table_number,
                            'player_1_name'    => $player_1_name,
                            'player_1_faction' => $player_1_faction,
                            'player_1_outcome' => $player_1_outcome,
                            'player_2_name'    => $player_2_name,
                            'player_2_faction' => $player_2_faction,
                            'player_2_outcome' => $player_2_outcome,
                            'source'           => 'Stats and Ladders',
                        )
                    );

                    $data[] = array(
                        'Tournament Name'  => $tournament_name,
                        'Start Date'       => $start_date,
                        'Round'            => $round_number + 1,
                        'Table Number'     => $table_number,
                        'Player 1 Name'    => $player_1_name,
                        'Player 1 Faction' => $player_1_faction,
                        'Player 1 Outcome' => $player_1_outcome,
                        'Player 2 Name'    => $player_2_name,
                        'Player 2 Faction' => $player_2_faction,
                        'Player 2 Outcome' => $player_2_outcome,
                        'Source'           => 'Stats and Ladders',
                    );

                    $i = $j + 3;
                } else {
                    $i++;
                }
            } else {
                $i++;
            }
        }
    }

    return $data;
}

/**
 * Display parsed SNL data in a table.
 *
 * @param array $data Parsed match data.
 * @return void
 */
function rankings_show_snl_parsed_data( $data ) {
    if ( empty( $data ) ) {
        echo '<p>No data available to display.</p>';
        return;
    }
    echo '<table class="widefat fixed" cellspacing="0">';
    echo '<thead><tr>';
    foreach ( array_keys( $data[0] ) as $key ) {
        echo '<th>' . esc_html( $key ) . '</th>';
    }
    echo '</tr></thead>';
    echo '<tbody>';
    foreach ( $data as $row ) {
        echo '<tr>';
        foreach ( $row as $value ) {
            echo '<td>' . esc_html( $value ) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table>';
}



/**
 * Parse Milarki data and insert into the match_data table.
 */

// Core parsing logic
function rankings_parse_milarki_data($tournament_name, $start_date, $rounds) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'match_data';

    // Known AoS faction names (sorted longest→shortest for greedy matching)
    $factions = [
        'Soulblight Gravelords','Stormcast Eternals','Ossiarch Bonereapers',
        'Disciples Of Tzeentch','Slaves To Darkness','Flesh Eater Courts',
        'Maggotkin Of Nurgle','Lumineth Realm Lords','Cities Of Sigmar',
        'Beasts Of Chaos','Flesh Eater Courts','Sylvaneth','Seraphon',
        'Skaven','Kruleboyz','Sons Of Behemat','Daughters Of Khaine',
        'Hedonites Of Slaanesh','Blades Of Khorne','Fyreslayers',
        'Kharadron Overlords','Gloomspite Gitz','Ogor Mawtribes',
        'Idoneth Deepkin','Nighthaunt','Ironjawz','Ironjaws', 'Orruk Warclans'
    ];
    usort($factions, function($a, $b) { return strlen($b) - strlen($a); });

    // Mapping of raw faction names to canonical names
    $faction_corrections = [
        'Beasts Of Chaos' => 'Beasts of Chaos',
        'Blades Of Khorne' => 'Blades of Khorne',
        'Disciples Of Tzeentch' => 'Disciples of Tzeentch',
        'Hedonites Of Slaanesh' => 'Hedonites of Slaanesh',
        'Maggotkin Of Nurgle' => 'Maggotkin of Nurgle',
        'Slaves To Darkness' => 'Slaves to Darkness',
        'Flesh Eater Courts' => 'Flesh-eater Courts',
        'Sons Of Behemat' => 'Sons of Behemat',
        'Cities Of Sigmar' => 'Cities of Sigmar',
        'Daughters Of Khaine' => 'Daughters of Khaine',
        'Lumineth Realm Lords' => 'Lumineth Realm-Lords',
        'Ironjaws' => 'Ironjawz'
    ];

    $parsed = [];

    foreach ($rounds as $round_index => $round_text) {
        $lines = preg_split('/\r\n|\r|\n/', $round_text);
        $i = 0;
        while ($i < count($lines)) {
            $line = trim($lines[$i]);
    
            // Skip rows with only two uppercase letters (initials)
            if (preg_match('/^[A-Z]{2}$/', $line)) {
                $i++;
                continue;
            }
    
            // Skip rows with "Victory Points"
            if (stripos($line, 'Victory Points') !== false) {
                $i++;
                continue;
            }
    
            // Skip rows with "Dropped"
            if (stripos($line, 'Dropped') !== false) {
                $i++;
                continue;
            }
    
            // Match table number and start parsing a match
            if (preg_match('/^Table\s*(\d+)/i', $line, $m)) {
                $table_number = intval($m[1]);
    
                // Player 1
                $i++;
                while (isset($lines[$i]) && (preg_match('/^[A-Z]{2}$/', trim($lines[$i])) || stripos($lines[$i], 'Dropped') !== false)) {
                    $i++; // Skip initials row or "Dropped" line if present
                }
                $p1_raw = trim($lines[$i] ?? '');
                $i++;
                // Skip lines containing the word Dropped
                if (stripos($lines[$i], 'Dropped') !== false) {
                    $i++;
                }

                // Score
                $score = trim($lines[$i] ?? '');
                if (!preg_match('/^\d+-\d+$/', $score)) {
                    throw new Exception("Invalid score format: '{$score}'");
                }
    
                // Player 2
                $i++;
                while (isset($lines[$i]) && (preg_match('/^[A-Z]{2}$/', trim($lines[$i])) || stripos($lines[$i], 'Dropped') !== false)) {
                    $i++; // Skip initials row or "Dropped" line if present
                }
                $p2_raw = trim($lines[$i] ?? '');
    
                // Skip lines containing the word Dropped
                if (stripos($lines[$i], 'Dropped') !== false) {
                    $i++;
                }
                
                // Skip the next two lines ("To the Battle", "Finished")
                $i += 2;
    
                // Remove brackets and content inside from player names
                $p1_raw = preg_replace('/\s*\(.*?\)/', '', $p1_raw); // Remove round brackets
                $p1_raw = preg_replace('/\s*\[.*?\]/', '', $p1_raw); // Remove square brackets
                $p2_raw = preg_replace('/\s*\(.*?\)/', '', $p2_raw); // Remove round brackets
                $p2_raw = preg_replace('/\s*\[.*?\]/', '', $p2_raw); // Remove square brackets
    
                // Split name/faction
                list($p1_name, $p1_faction) = rankings_split_milarki_name_faction($p1_raw, $factions);
                list($p2_name, $p2_faction) = rankings_split_milarki_name_faction($p2_raw, $factions);
    
                // Correct faction names to canonical format
                $p1_faction = $faction_corrections[$p1_faction] ?? $p1_faction;
                $p2_faction = $faction_corrections[$p2_faction] ?? $p2_faction;
    
                // Determine outcomes
                list($s1, $s2) = array_map('intval', explode('-', $score));
                $o1 = $s1 > $s2 ? 'Win' : ($s1 < $s2 ? 'Loss' : 'Draw');
                $o2 = $s1 < $s2 ? 'Win' : ($s1 > $s2 ? 'Loss' : 'Draw');
    
                // Insert into database
                $wpdb->insert($table_name, [
                    'tournament_name'  => $tournament_name,
                    'start_date'       => $start_date,
                    'round'            => $round_index + 1,
                    'table_number'     => $table_number,
                    'player_1_name'    => $p1_name,
                    'player_1_faction' => $p1_faction,
                    'player_1_outcome' => $o1,
                    'player_2_name'    => $p2_name,
                    'player_2_faction' => $p2_faction,
                    'player_2_outcome' => $o2,
                    'source'           => 'Milarki'
                ]);
    
                // Prepare for display
                $parsed[] = [
                    'Tournament Name'   => $tournament_name,
                    'Start Date'        => $start_date,
                    'Round'             => $round_index + 1,
                    'Table Number'      => $table_number,
                    'Player 1 Name'     => $p1_name,
                    'Player 1 Faction'  => $p1_faction,
                    'Player 1 Outcome'  => $o1,
                    'Player 2 Name'     => $p2_name,
                    'Player 2 Faction'  => $p2_faction,
                    'Player 2 Outcome'  => $o2,
                    'Source'            => 'Milarki'
                ];
            } else {
                // Not the start of a match—move on
                $i++;
            }
        }
    }

    return $parsed;
}

// Helper: split a raw “[Name][Faction]” string
function rankings_split_milarki_name_faction($raw, $factions) {
    foreach ($factions as $f) {
        if (strcasecmp(substr($raw, -strlen($f)), $f) === 0) {
            $name = trim(substr($raw, 0, strlen($raw) - strlen($f)));
            return [ $name, $f ];
        }
    }
    throw new Exception("Could not parse name/faction from “{$raw}”");
}

// Display parsed data in a table
function rankings_show_milarki_parsed_data($data) {
    if (empty($data)) {
        echo '<p>No data parsed.</p>';
        return;
    }
    echo '<table class="widefat fixed" cellspacing="0">';
    echo '<thead><tr>';
    foreach (array_keys($data[0]) as $col) {
        echo '<th>' . esc_html($col) . '</th>';
    }
    echo '</tr></thead><tbody>';
    foreach ($data as $row) {
        echo '<tr>';
        foreach ($row as $cell) {
            echo '<td>' . esc_html($cell) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table>';
}

//------------------------------------------------------------------------------
// Backend Pages for Data Ingest
//------------------------------------------------------------------------------

/**
 * Render the BCP Parser backend page.
 *
 * Displays a form to ingest BCP data and shows parsed results.
 *
 * @return void
 */
function rankings_ingest_bcp_page() {
    if ( ! current_user_can( 'edit_others_posts' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
        $tournament_name = sanitize_text_field( $_POST['tournament_name'] );
        $start_date      = sanitize_text_field( $_POST['start_date'] );
        $rounds          = array();
        for ( $i = 1; $i <= 5; $i++ ) {
            if ( ! empty( $_POST['round_' . $i] ) ) {
                $round_text = str_replace( 'No List', 'View List', $_POST['round_' . $i] );
                $rounds[]   = sanitize_textarea_field( $round_text );
            }
        }

        if ( count( $rounds ) < 1 || count( $rounds ) > 5 ) {
            echo '<div class="error"><p>Please enter between 1 and 5 rounds.</p></div>';
        } else {
            try {
                $data = rankings_parse_bcp_data( $tournament_name, $start_date, $rounds );
                echo '<div class="updated"><p>BCP data parsed and stored successfully!</p></div>';
                rankings_show_bcp_parsed_data( $data );
            } catch ( Exception $e ) {
                echo '<div class="error"><p>' . $e->getMessage() . '</p></div>';
            }
        }
    }
    ?>
    <div class="wrap">
        <h1>Ingest BCP Data</h1>
        <p>This form will parse plaintext from Best Coast Pairings and ingest it into <code>match_data</code>.
        To use this form, enter the event name and the start date, and then copy each round's results
        from the BCP website into the corresponding box. Make sure to only copy from the first table
        number to the last 'View List' on each page.</p>
        <form method="post" action="">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Tournament Name</th>
                    <td><input type="text" name="tournament_name" required/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Start Date</th>
                    <td><input type="date" name="start_date" required/></td>
                </tr>
                <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                    <tr valign="top">
                        <th scope="row">Round <?php echo $i; ?></th>
                        <td><textarea name="round_<?php echo $i; ?>" rows="10" cols="50"></textarea></td>
                    </tr>
                <?php endfor; ?>
            </table>
            <p class="submit">
                <input type="submit" class="button-primary" value="Parse BCP Data"/>
            </p>
        </form>
    </div>
    <?php
}


/**
 * Render the SNL Parser backend page.
 *
 * Displays a form to ingest SNL data and shows parsed results.
 *
 * @return void
 */
function rankings_ingest_snl_page() {
    if ( ! current_user_can( 'edit_others_posts' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
        $tournament_name = sanitize_text_field( $_POST['tournament_name'] );
        $start_date      = sanitize_text_field( $_POST['start_date'] );
        $rounds          = array();
        for ( $i = 1; $i <= 5; $i++ ) {
            if ( ! empty( $_POST['round_' . $i] ) ) {
                $rounds[] = sanitize_textarea_field( $_POST['round_' . $i] );
            }
        }

        if ( count( $rounds ) < 1 || count( $rounds ) > 5 ) {
            echo '<div class="error"><p>Please enter between 1 and 5 rounds.</p></div>';
        } else {
            try {
                $data = rankings_parse_snl_data( $tournament_name, $start_date, $rounds );
                echo '<div class="updated"><p>SNL data parsed and stored successfully!</p></div>';
                rankings_show_snl_parsed_data( $data );
            } catch ( Exception $e ) {
                echo '<div class="error"><p>' . $e->getMessage() . '</p></div>';
            }
        }
    }
    ?>
    <div class="wrap">
        <h1>Ingest SNL Data</h1>
        <p>This form will parse plaintext from Stats and Ladders and ingest it into <code>match_data</code>.
        To use this form, enter the event name and the start date, and then copy each round's results
        from the SNL website into the corresponding box. Make sure to only copy from the first table
        number to the last score on each page.</p>
        <form method="post" action="">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Tournament Name</th>
                    <td><input type="text" name="tournament_name" required/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Start Date</th>
                    <td><input type="date" name="start_date" required/></td>
                </tr>
                <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                    <tr valign="top">
                        <th scope="row">Round <?php echo $i; ?></th>
                        <td><textarea name="round_<?php echo $i; ?>" rows="10" cols="50"></textarea></td>
                    </tr>
                <?php endfor; ?>
            </table>
            <p class="submit">
                <input type="submit" class="button-primary" value="Parse SNL Data"/>
            </p>
        </form>
    </div>
    <?php
}

/**
 * Render the Milarki Parser backend page.
 *
 */

// Display the parser page
function rankings_ingest_milarki_page() {
    if ( ! current_user_can('edit_others_posts') ) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
        $tournament_name = sanitize_text_field( $_POST['tournament_name'] );
        $start_date      = sanitize_text_field( $_POST['start_date'] );
        $rounds          = array();
        for ( $i = 1; $i <= 5; $i++ ) {
            if ( ! empty( $_POST['round_' . $i] ) ) {
                $round_text = str_replace( 'No List', 'View List', $_POST['round_' . $i] );
                $rounds[]   = sanitize_textarea_field( $round_text );
            }
        }

        if ( count( $rounds ) < 1 || count( $rounds ) > 5 ) {
            echo '<div class="error"><p>Please enter between 1 and 5 rounds.</p></div>';
        } else {
            try {
                $data = rankings_parse_bcp_data( $tournament_name, $start_date, $rounds );
                echo '<div class="updated"><p>BCP data parsed and stored successfully!</p></div>';
                rankings_show_bcp_parsed_data( $data );
            } catch ( Exception $e ) {
                echo '<div class="error"><p>' . $e->getMessage() . '</p></div>';
            }
        }
    }

    // Render the input form
    ?>
    <div class="wrap">
      <h1>Ingest Milarki Data</h1>
        <p>This form will parse plaintext from Milarki and ingest it into <code>match_data</code>.
        To use this form, enter the event name and the start date, and then copy each round's results
        from the Milarki website into the corresponding box. Make sure to only copy from the first table
        number to the bottom "Finished" or "Victory Points" text on the last pairing.</p>
      <form method="post">
        <table class="form-table">
          <tr>
            <th scope="row">Tournament Name</th>
            <td><input type="text" name="tournament_name" value="<?php echo esc_attr($tournament_name); ?>" required style="width:100%"></td>
          </tr>
          <tr>
            <th scope="row">Start Date</th>
            <td><input type="date" name="start_date" value="<?php echo esc_attr($start_date); ?>" required></td>
          </tr>
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <tr>
              <th scope="row">Round <?php echo $i; ?></th>
              <td><textarea name="round_<?php echo $i; ?>" rows="6" style="width:100%;"><?php echo esc_textarea($rounds[$i]); ?></textarea></td>
            </tr>
          <?php endfor; ?>
        </table>
        <p class="submit">
          <input type="submit" class="button button-primary" value="Parse Milarki Data">
        </p>
      </form>
    </div>
    <?php
}

//------------------------------------------------------------------------------
// Elo Calculator Functions
//------------------------------------------------------------------------------

/**
 * Display Elo rankings.
 *
 * Displays Elo rankings from the elo_ratings table.
 *
 * @return void
 */
function rankings_show_elo_data() {
    if ( ! current_user_can( 'edit_others_posts' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'elo_ratings';
    $players    = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY rank ASC", ARRAY_A );

    if ( empty( $players ) ) {
        echo '<p>No rankings available.</p>';
        return;
    }

    echo '<table class="widefat fixed" cellspacing="0">';
    echo '<thead><tr>';
    echo '<th>Rank</th>';
    echo '<th>Player Name</th>';
    echo '<th>Rating</th>';
    echo '<th>Matches Played</th>';
    echo '<th>Preferred Faction</th>';
    echo '</tr></thead>';
    echo '<tbody>';
    foreach ( $players as $player ) {
        echo '<tr>';
        echo '<td>' . esc_html( $player['rank'] ) . '</td>';
        echo '<td>' . esc_html( $player['player_name'] ) . '</td>';
        echo '<td>' . esc_html( $player['rating'] ) . '</td>';
        echo '<td>' . esc_html( $player['matches_played'] ) . '</td>';
        echo '<td>' . esc_html( $player['preferred_faction'] ) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}

/**
 * Calculate Elo ratings and update rankings.
 *
 * Calculates Elo ratings based on match results in match_data, aggregates each player's
 * played factions to determine the preferred faction, inserts the computed ratings into
 * the elo_ratings table, and adjusts player rankings.
 *
 * @return void
 */
function rankings_calculate_elos() {
    error_log( 'calculate_elos() function triggered.' );
    global $wpdb;
    $table_name = $wpdb->prefix . 'elo_ratings';

    // Clear the Elo ratings table
    $wpdb->query( "TRUNCATE TABLE $table_name" );

    $tournament_data = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}match_data ORDER BY start_date ASC, round ASC", ARRAY_A );
    $player_elos     = array();

    foreach ( $tournament_data as $match ) {
        $player_1  = trim( $match['player_1_name'] );
        $player_2  = trim( $match['player_2_name'] );
        $outcome_1 = $match['player_1_outcome'];
        $outcome_2 = $match['player_2_outcome'];

        if ( ! isset( $player_elos[ $player_1 ] ) ) {
            $player_elos[ $player_1 ] = array(
                'rating'           => 1000,
                'matches_played'   => 0,
                'preferred_faction'=> array(),
            );
        }
        if ( ! isset( $player_elos[ $player_2 ] ) ) {
            $player_elos[ $player_2 ] = array(
                'rating'           => 1000,
                'matches_played'   => 0,
                'preferred_faction'=> array(),
            );
        }

        $player_elos[ $player_1 ]['preferred_faction'][] = trim( $match['player_1_faction'] );
        $player_elos[ $player_2 ]['preferred_faction'][] = trim( $match['player_2_faction'] );

        $player_1_rating = $player_elos[ $player_1 ]['rating'];
        $player_2_rating = $player_elos[ $player_2 ]['rating'];

        $k_factor_1 = $player_elos[ $player_1 ]['matches_played'] < 25 ? 50 : ( $player_1_rating < 2400 ? 25 : 10 );
        $k_factor_2 = $player_elos[ $player_2 ]['matches_played'] < 25 ? 50 : ( $player_2_rating < 2400 ? 25 : 10 );

        if ( $outcome_1 == 'Win' && $outcome_2 == 'Loss' ) {
            $expected_score_1 = 1 / ( 1 + pow( 10, ( $player_2_rating - $player_1_rating ) / 400 ) );
            $player_elos[ $player_1 ]['rating'] += $k_factor_1 * ( 1 - $expected_score_1 );
            $player_elos[ $player_2 ]['rating'] -= $k_factor_2 * ( 1 - $expected_score_1 );
        } elseif ( $outcome_1 == 'Loss' && $outcome_2 == 'Win' ) {
            $expected_score_2 = 1 / ( 1 + pow( 10, ( $player_1_rating - $player_2_rating ) / 400 ) );
            $player_elos[ $player_1 ]['rating'] -= $k_factor_1 * ( 1 - $expected_score_2 );
            $player_elos[ $player_2 ]['rating'] += $k_factor_2 * ( 1 - $expected_score_2 );
        } elseif ( $outcome_1 == 'Draw' && $outcome_2 == 'Draw' ) {
            $expected_score_1 = 1 / ( 1 + pow( 10, ( $player_2_rating - $player_1_rating ) / 400 ) );
            $expected_score_2 = 1 / ( 1 + pow( 10, ( $player_1_rating - $player_2_rating ) / 400 ) );
            $player_elos[ $player_1 ]['rating'] += $k_factor_1 * ( 0.5 - $expected_score_1 );
            $player_elos[ $player_2 ]['rating'] += $k_factor_2 * ( 0.5 - $expected_score_2 );
        }

        $player_elos[ $player_1 ]['matches_played'] += 1;
        $player_elos[ $player_2 ]['matches_played'] += 1;
    }

    // Compute preferred faction for each player
    $final_preferred = array();
    foreach ( $player_elos as $player => $data ) {
        if ( ! isset( $data['preferred_faction'] ) || ! is_array( $data['preferred_faction'] ) || empty( $data['preferred_faction'] ) ) {
            $final_preferred[ $player ] = '';
            continue;
        }
        $faction_counts = array_count_values( $data['preferred_faction'] );
        arsort( $faction_counts );
        $max_count = max( $faction_counts );
        $tied_factions = array_keys( array_filter( $faction_counts, function( $count ) use ( $max_count ) {
            return $count == $max_count;
        } ) );
        if ( count( $tied_factions ) === 1 ) {
            $final_preferred[ $player ] = $tied_factions[0];
        } else {
            $reversed = array_reverse( $data['preferred_faction'] );
            $chosen   = null;
            foreach ( $reversed as $f ) {
                if ( in_array( $f, $tied_factions ) ) {
                    $chosen = $f;
                    break;
                }
            }
            $final_preferred[ $player ] = $chosen ? $chosen : implode( ', ', $tied_factions );
        }
    }
    foreach ( $player_elos as $player => &$data ) {
        $data['preferred_faction'] = $final_preferred[ $player ];
    }
    unset( $data );

    // Insert computed Elo ratings into the elo_ratings table
    foreach ( $player_elos as $player => $data ) {
        $wpdb->insert(
            $wpdb->prefix . 'elo_ratings',
            array(
                'player_name'       => $player,
                'rating'            => $data['rating'],
                'matches_played'    => $data['matches_played'],
                'preferred_faction' => $data['preferred_faction'],
            )
        );
    }

    // Update player rankings
    $players = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "elo_ratings ORDER BY rating DESC, id ASC", ARRAY_A );
    $rank    = 1;
    for ( $i = 0; $i < count( $players ); $i++ ) {
        if ( $i > 0 && $players[ $i ]['rating'] == $players[ $i - 1 ]['rating'] ) {
            $players[ $i ]['rank'] = $players[ $i - 1 ]['rank'];
        } else {
            $players[ $i ]['rank'] = $rank;
        }
        $wpdb->update( $wpdb->prefix . 'elo_ratings', array( 'rank' => $players[ $i ]['rank'] ), array( 'id' => $players[ $i ]['id'] ) );
        $rank++;
    }
}

/**
 * Register the Elo Calculator submenu page.
 *
 * Adds the Elo Calculator page under the Data Ingest admin menu.
 *
 * @return void
 */
function elo_calculator_menu() {
    add_submenu_page( 'data-ingest', 'Calculate Elos', 'Calculate Elos', 'edit_others_posts', 'elo-calculator', 'elo_calculator_page' );
}
add_action( 'admin_menu', 'elo_calculator_menu' );

/**
 * Render the Elo Calculator admin page.
 *
 * Allows an administrator to manually trigger Elo calculations and view current rankings.
 *
 * @return void
 */
function elo_calculator_page() {
    if ( ! current_user_can( 'edit_others_posts' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    if ( isset( $_POST['rankings_calculate_elos'] ) && $_POST['rankings_calculate_elos'] === 'true' ) {
        rankings_calculate_elos();
        echo '<div class="updated notice"><p><strong>Elo calculations completed successfully.</strong></p></div>';
    }

    echo '<div class="wrap">';
    echo '<h1>Calculate Elos</h1>';
    echo '<p>The button below will update the <code>elo_ratings</code> table based on all data currently ingested into <code>match_data</code>.</p>';
    echo '<form method="post">';
    echo '<input type="hidden" name="rankings_calculate_elos" value="true">';
    echo '<input type="submit" class="button button-primary" value="Calculate Elos Now">';
    echo '</form>';
    echo '<h2>Current Rankings</h2>';
    rankings_show_elo_data();
    echo '</div>';
}

// EOF