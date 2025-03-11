<?php

/**
 * Rankings Management Functionality
 *
 * This file contains the functions to manage the match_data table,
 * perform backups/restores, manage tournament data (view, edit, delete, search),
 * and provide combined admin menus.
 *
 * @package RankingsManagement
 */


//------------------------------------------------------------------------------
// Backup Manager Functionality
//------------------------------------------------------------------------------

/**
 * Activate backup scheduling on plugin activation.
 *
 * Schedules a weekly backup every Monday at 04:00.
 *
 * @return void
 */
function rankings_backup_activate() {
    if ( ! wp_next_scheduled( 'rankings_weekly_backup' ) ) {
        wp_schedule_event( strtotime( 'next Monday 04:00' ), 'weekly', 'rankings_weekly_backup' );
    }
}
register_activation_hook( __FILE__, 'rankings_backup_activate' );

/**
 * Clear the backup scheduling on plugin deactivation.
 *
 * @return void
 */
function data_management_backup_deactivate() {
    wp_clear_scheduled_hook( 'rankings_weekly_backup' );
}
register_deactivation_hook( __FILE__, 'rankings_backup_deactivate' );

/**
 * Perform the weekly backup.
 *
 * Backs up the Elo ratings and match_data tables.
 *
 * @return void
 */
function rankings_weekly_backup() {
    rankings_backup_table( 'elo_ratings' );
    rankings_backup_table( 'match_data' );
}
add_action( 'rankings_weekly_backup', 'rankings_weekly_backup' );

/**
 * Backup a given table to a CSV file.
 *
 * @param string $table The table name (without prefix) to backup.
 * @return void
 */
function rankings_backup_table( $table ) {
    global $wpdb;
    $table_name = $wpdb->prefix . $table;
    $backup_dir = plugin_dir_path( __FILE__ ) . 'backups/';
    if ( ! is_dir( $backup_dir ) ) {
        mkdir( $backup_dir, 0755, true );
    }
    $backup_file = $backup_dir . $table . '_' . date( 'Ymd_His' ) . '.csv';

    $results = $wpdb->get_results( "SELECT * FROM $table_name", ARRAY_A );
    if ( $results ) {
        $fp      = fopen( $backup_file, 'w' );
        $columns = array_keys( $results[0] );
        fputcsv( $fp, $columns );
        foreach ( $results as $row ) {
            fputcsv( $fp, $row );
        }
        fclose( $fp );
    }
}

/**
 * Restore a table from a backup CSV file.
 *
 * @param string $table       The table name (without prefix) to restore.
 * @param string $backup_file The backup file name (located in the backups folder).
 * @return void
 */
function rankings_restore_table( $table, $backup_file ) {
    global $wpdb;
    $table_name       = $wpdb->prefix . $table;
    $backup_file_path = plugin_dir_path( __FILE__ ) . 'backups/' . $backup_file;

    if ( ( $handle = fopen( $backup_file_path, 'r' ) ) !== false ) {
        // Clear the table before restoring.
        $wpdb->query( "TRUNCATE TABLE $table_name" );
        $columns = fgetcsv( $handle, 1000, ',' );
        while ( ( $data = fgetcsv( $handle, 1000, ',' ) ) !== false ) {
            $row = array_combine( $columns, $data );
            $wpdb->insert( $table_name, $row );
        }
        fclose( $handle );
    }
}

/**
 * Delete a backup CSV file.
 *
 * @param string $backup_file The backup file name to delete.
 * @return void
 */
function rankings_delete_backup( $backup_file ) {
    $backup_file_path = plugin_dir_path( __FILE__ ) . 'backups/' . $backup_file;
    if ( file_exists( $backup_file_path ) ) {
        unlink( $backup_file_path );
    }
}

/**
 * List all backup files for a given table.
 *
 * @param string $table The table name (without prefix) for which to list backups.
 * @return void
 */
function rankings_list_backups( $table ) {
    $backup_dir   = plugin_dir_path( __FILE__ ) . 'backups/';
    $backup_files = glob( $backup_dir . $table . '_*.csv' );
    echo '<table class="widefat fixed" cellspacing="0">';
    echo '<thead><tr><th>Backup File</th><th>Date Created</th><th>Actions</th></tr></thead>';
    echo '<tbody>';
    if ( empty( $backup_files ) ) {
        echo '<tr><td colspan="3">No backups available.</td></tr>';
    } else {
        foreach ( $backup_files as $file ) {
            $file_name = basename( $file );
            $file_date = date( 'Y-m-d H:i:s', filemtime( $file ) );
            echo '<tr>';
            echo '<td>' . esc_html( $file_name ) . '</td>';
            echo '<td>' . esc_html( $file_date ) . '</td>';
            echo '<td>';
            echo '<form method="post" style="display:inline;">';
            echo '<input type="hidden" name="backup_file" value="' . esc_attr( $file_name ) . '">';
            echo '<input type="submit" name="restore_' . $table . '" value="Restore" class="button button-secondary" onclick="return confirm(\'Are you sure you want to restore this backup?\')">';
            echo '</form> ';
            echo '<form method="post" style="display:inline;">';
            echo '<input type="hidden" name="backup_file" value="' . esc_attr( $file_name ) . '">';
            echo '<input type="submit" name="delete_backup" value="Delete" class="button button-secondary" onclick="return confirm(\'Are you sure you want to delete this backup?\')">';
            echo '</form>';
            echo '</td>';
            echo '</tr>';
        }
    }
    echo '</tbody></table>';
}

/**
 * Display the main Data Manager page.
 *
 * Allows administrators to view and manage tournament data stored in match_data.
 *
 * @return void
 */
function rankings_rankings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    if ( isset( $_GET['action'] ) && $_GET['action'] === 'edit' && isset( $_GET['id'] ) ) {
        rankings_edit_row_form( intval( $_GET['id'] ) );
        return;
    }

    if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
        if ( isset( $_POST['delete_tournament'] ) ) {
            rankings_delete_tournament( $_POST['tournament_name'] );
        } elseif ( isset( $_POST['view_tournament'] ) ) {
            rankings_view_tournament( $_POST['tournament_name'] );
        } elseif ( isset( $_POST['delete_row'] ) ) {
            rankings_delete_row( $_POST['id'] );
            rankings_view_tournament( $_POST['tournament_name'] );
        } elseif ( isset( $_POST['edit_row'] ) ) {
            rankings_edit_row_form( $_POST['id'] );
        } elseif ( isset( $_POST['update_row'] ) ) {
            rankings_update_row( $_POST );
            rankings_view_tournament( $_POST['tournament_name'] );
        }
    } else {
        rankings_show_tournament_table();
    }
}

/**
 * Delete tournament data for a given tournament.
 *
 * @param string $tournament_name The tournament name.
 * @return void
 */
function rankings_delete_tournament( $tournament_name ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'match_data';
    $wpdb->delete( $table_name, array( 'tournament_name' => $tournament_name ) );
    echo '<div class="updated"><p>Tournament data deleted successfully!</p></div>';
    rankings_show_tournament_table();
}

/**
 * View tournament data for a given tournament.
 *
 * @param string $tournament_name The tournament name.
 * @return void
 */
function rankings_view_tournament( $tournament_name ) {
    if ( ! current_user_can( 'edit_others_posts' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'match_data';
    $data       = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name WHERE tournament_name = %s", $tournament_name ), ARRAY_A );

    if ( empty( $data ) ) {
        echo '<p>No data available for this tournament.</p>';
        return;
    }
    echo '<div class="wrap">';
    echo '<h1>Event Data</h1>';
    echo '<p>This page displays all data for a single event currently ingested into <code>match_data</code>. Use the buttons on the right hand side to edit or delete specific matches.</p>';
    echo '<table class="widefat fixed" cellspacing="0">';
    echo '<thead><tr>';
    foreach ( array_keys( $data[0] ) as $key ) {
        echo '<th>' . esc_html( $key ) . '</th>';
    }
    echo '<th>Actions</th>';
    echo '</tr></thead>';
    echo '<tbody>';
    foreach ( $data as $row ) {
        echo '<tr>';
        foreach ( $row as $value ) {
            echo '<td>' . esc_html( $value ) . '</td>';
        }
        echo '<td>
            <form method="post" style="display:inline;">
                <input type="hidden" name="id" value="' . esc_attr( $row['id'] ) . '"/>
                <input type="hidden" name="tournament_name" value="' . esc_attr( $row['tournament_name'] ) . '"/>
                <input type="submit" name="edit_row" value="Edit" class="button button-secondary"/>
                <input type="submit" name="delete_row" value="Delete" class="button button-secondary"/>
            </form>
        </td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '<form method="post" style="display:inline;">
        <input type="submit" value="Back" class="button button-secondary"/>
    </form>';
    echo '</div>';
}

/**
 * Display a table of tournaments.
 *
 * Lists distinct tournament names from the match_data table.
 *
 * @return void
 */
function rankings_show_tournament_table() {
    if ( ! current_user_can( 'edit_others_posts' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }
    global $wpdb;
    $table_name  = $wpdb->prefix . 'match_data';
    $tournaments = $wpdb->get_results( "SELECT DISTINCT tournament_name, start_date, source FROM $table_name", ARRAY_A );

    if ( empty( $tournaments ) ) {
        echo '<p>No tournaments available.</p>';
        return;
    }
    echo '<div class="wrap">';
    echo '<h1>Manage Data</h1>';
    echo '<p>This page displays all events currently ingested into <code>match_data</code>. Use the buttons on the right hand side to view or delete event data.</p>';
    echo '<table class="widefat fixed" cellspacing="0">';
    echo '<thead><tr>';
    echo '<th>Tournament Name</th>';
    echo '<th>Start Date</th>';
    echo '<th>Source</th>';
    echo '<th>Actions</th>';
    echo '</tr></thead>';
    echo '<tbody>';
    foreach ( $tournaments as $tournament ) {
        echo '<tr>';
        foreach ( $tournament as $value ) {
            echo '<td>' . esc_html( $value ) . '</td>';
        }
        echo '<td>
            <form method="post" style="display:inline;">
                <input type="hidden" name="tournament_name" value="' . esc_attr( $tournament['tournament_name'] ) . '"/>
                <input type="submit" name="view_tournament" value="View" class="button button-secondary"/>
                <input type="submit" name="delete_tournament" value="Delete" class="button button-secondary" onclick="return confirm(\'Are you sure you want to delete this tournament?\')"/>
            </form>
        </td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

/**
 * Delete a single row of tournament data.
 *
 * @param int $id The row id.
 * @return void
 */
function rankings_delete_row( $id ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'match_data';
    $wpdb->delete( $table_name, array( 'id' => $id ) );
    echo '<div class="updated"><p>Row deleted successfully!</p></div>';
}

/**
 * Display a form to edit a row.
 *
 * @param int $id The row id.
 * @return void
 */
function rankings_edit_row_form( $id ) {
    if ( ! current_user_can( 'edit_others_posts' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'match_data';
    $row        = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ), ARRAY_A );

    if ( empty( $row ) ) {
        echo '<p>Match not found.</p>';
        return;
    }
    ?>
    <div class="wrap">
        <h2>Edit Match</h2>
        <p>Use this menu to update the data for a specific match in <code>match_data</code>.</p>
        <form method="post">
            <input type="hidden" name="id" value="<?php echo esc_attr( $row['id'] ); ?>"/>
            <input type="hidden" name="tournament_name" value="<?php echo esc_attr( $row['tournament_name'] ); ?>"/>
            <input type="hidden" name="start_date" value="<?php echo esc_attr( $row['start_date'] ); ?>"/>
            <input type="hidden" name="round" value="<?php echo esc_attr( $row['round'] ); ?>"/>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Tournament Name</th>
                    <td><input type="text" name="tournament_name" value="<?php echo esc_attr( $row['tournament_name'] ); ?>" disabled/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Start Date</th>
                    <td><input type="date" name="start_date" value="<?php echo esc_attr( $row['start_date'] ); ?>" disabled/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Round</th>
                    <td><input type="number" name="round" value="<?php echo esc_attr( $row['round'] ); ?>" disabled/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Table Number</th>
                    <td><input type="number" name="table_number" value="<?php echo esc_attr( $row['table_number'] ); ?>" required/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Player 1 Name</th>
                    <td><input type="text" name="player_1_name" value="<?php echo esc_attr( $row['player_1_name'] ); ?>" required/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Player 1 Faction</th>
                    <td><input type="text" name="player_1_faction" value="<?php echo esc_attr( $row['player_1_faction'] ); ?>" required/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Player 1 Outcome</th>
                    <td><input type="text" name="player_1_outcome" value="<?php echo esc_attr( $row['player_1_outcome'] ); ?>" required/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Player 2 Name</th>
                    <td><input type="text" name="player_2_name" value="<?php echo esc_attr( $row['player_2_name'] ); ?>" required/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Player 2 Faction</th>
                    <td><input type="text" name="player_2_faction" value="<?php echo esc_attr( $row['player_2_faction'] ); ?>" required/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Player 2 Outcome</th>
                    <td><input type="text" name="player_2_outcome" value="<?php echo esc_attr( $row['player_2_outcome'] ); ?>" required/></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="update_row" value="Update" class="button-primary"/>
            </p>
        </form>
    </div>
    <?php
}

/**
 * Update a row with edited values.
 *
 * @param array $data The POST data containing updated row values.
 * @return void
 */
function rankings_update_row( $data ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'match_data';

    $id               = intval( $data['id'] );
    $table_number     = intval( $data['table_number'] );
    $player_1_name    = sanitize_text_field( $data['player_1_name'] );
    $player_1_faction = sanitize_text_field( $data['player_1_faction'] );
    $player_1_outcome = sanitize_text_field( $data['player_1_outcome'] );
    $player_2_name    = sanitize_text_field( $data['player_2_name'] );
    $player_2_faction = sanitize_text_field( $data['player_2_faction'] );
    $player_2_outcome = sanitize_text_field( $data['player_2_outcome'] );

    $wpdb->update(
        $table_name,
        array(
            'table_number'    => $table_number,
            'player_1_name'   => $player_1_name,
            'player_1_faction'=> $player_1_faction,
            'player_1_outcome'=> $player_1_outcome,
            'player_2_name'   => $player_2_name,
            'player_2_faction'=> $player_2_faction,
            'player_2_outcome'=> $player_2_outcome,
        ),
        array( 'id' => $id )
    );

    echo '<div class="updated"><p>Row updated successfully!</p></div>';
}


//------------------------------------------------------------------------------
// Search Functionality
//------------------------------------------------------------------------------

/**
 * Render the search page for tournament data.
 *
 * @return void
 */
function rankings_search_page() {
    if ( ! current_user_can( 'edit_others_posts' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    $search_params = array(
        'tournament_name' => '',
        'start_date'      => '',
        'round'           => '',
        'table_number'    => '',
        'player_name'     => '',
        'player_faction'  => ''
    );

    if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
        foreach ( $search_params as $key => &$value ) {
            if ( ! empty( $_POST[ $key ] ) ) {
                $value = sanitize_text_field( $_POST[ $key ] );
            }
        }
        unset( $value );
        rankings_show_search_results( $search_params );
    } else {
        rankings_show_search_form( $search_params );
    }
}

/**
 * Display the search form.
 *
 * @param array $search_params The default search parameters.
 * @return void
 */
function rankings_show_search_form( $search_params ) {
    ?>
    <div class="wrap">
        <h1>Search Data</h1>
        <p>This form allows for searching of the <code>match_data</code> table, to quickly identify and update specific data.</p>
        <form method="post" action="">
            <table class="form-table">
                <?php foreach ( $search_params as $key => $value ) : ?>
                    <tr valign="top">
                        <th scope="row"><?php echo ucwords( str_replace( '_', ' ', $key ) ); ?></th>
                        <td><input type="text" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>"/></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <p class="submit">
                <input type="submit" class="button-primary" value="Search"/>
            </p>
        </form>
    </div>
    <?php
}

/**
 * Display search results from the match_data table.
 *
 * @param array $search_params The search parameters.
 * @return void
 */
function rankings_show_search_results( $search_params ) {
    if ( ! current_user_can( 'edit_others_posts' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'match_data';

    $query = "SELECT * FROM $table_name WHERE 1=1";
    if ( ! empty( $search_params['tournament_name'] ) ) {
        $query .= $wpdb->prepare( " AND tournament_name LIKE %s", '%' . $wpdb->esc_like( $search_params['tournament_name'] ) . '%' );
    }
    if ( ! empty( $search_params['start_date'] ) ) {
        $query .= $wpdb->prepare( " AND start_date = %s", $search_params['start_date'] );
    }
    if ( ! empty( $search_params['round'] ) ) {
        $query .= $wpdb->prepare( " AND round = %d", $search_params['round'] );
    }
    if ( ! empty( $search_params['table_number'] ) ) {
        $query .= $wpdb->prepare( " AND table_number = %d", $search_params['table_number'] );
    }
    if ( ! empty( $search_params['player_name'] ) ) {
        $query .= $wpdb->prepare( " AND (player_1_name LIKE %s OR player_2_name LIKE %s)", '%' . $wpdb->esc_like( $search_params['player_name'] ) . '%', '%' . $wpdb->esc_like( $search_params['player_name'] ) . '%' );
    }
    if ( ! empty( $search_params['player_faction'] ) ) {
        $query .= $wpdb->prepare( " AND (player_1_faction LIKE %s OR player_2_faction LIKE %s)", '%' . $wpdb->esc_like( $search_params['player_faction'] ) . '%', '%' . $wpdb->esc_like( $search_params['player_faction'] ) . '%' );
    }

    $results = $wpdb->get_results( $query, ARRAY_A );

    if ( empty( $results ) ) {
        echo '<p>No matching results found.</p>';
        return;
    }
    echo '<div class="wrap">';
    echo '<h1>Search Results</h1>';
    echo '<p>Below are rows in <code>match_data</code> which match the query. Use the buttons on the right hand side to edit data.</p>';
    echo '<table class="widefat fixed" cellspacing="0">';
    echo '<thead><tr>';
    foreach ( array_keys( $results[0] ) as $key ) {
        echo '<th>' . esc_html( $key ) . '</th>';
    }
    echo '<th>Actions</th>';
    echo '</tr></thead>';
    echo '<tbody>';
    foreach ( $results as $row ) {
        echo '<tr>';
        foreach ( $row as $value ) {
            echo '<td>' . esc_html( $value ) . '</td>';
        }
        echo '<td>';
        $edit_url = add_query_arg(
            array(
                'page'            => 'data-manager',
                'action'          => 'edit',
                'id'              => $row['id'],
                'tournament_name' => $row['tournament_name'],
            ),
            admin_url( 'admin.php' )
        );
        echo '<a href="' . esc_url( $edit_url ) . '" class="button button-secondary">Edit</a>';
        echo '<form method="post" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to delete this row?\');">
                <input type="hidden" name="id" value="' . esc_attr( $row['id'] ) . '"/>
                <input type="hidden" name="tournament_name" value="' . esc_attr( $row['tournament_name'] ) . '"/>
                <input type="submit" name="delete_row" value="Delete" class="button button-secondary"/>
            </form>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '<form method="post" style="display:inline;">
        <input type="submit" value="Back" class="button button-secondary"/>
    </form>';
    echo '</div>';
}

//------------------------------------------------------------------------------
// Combined Admin Menu for Data Management
//------------------------------------------------------------------------------

/**
 * Create the admin menu and submenus for Data Management.
 *
 * Adds a main menu item "Data Management" with submenus:
 * - Manage Data (view, edit, delete events)
 * - Search Data (search tournament data)
 * - Manage Backups (backup and restore tables)
 *
 * @return void
 */
function manage_rankings_menu() {
    add_menu_page( 'Manage Rankings', 'Manage Rankings', 'edit_others_posts', 'rankings-management', 'rankings_page' );
    add_submenu_page( 'rankings-management', 'Manage Data', 'Manage Data', 'edit_others_posts', 'data-manager', 'rankings_page' );
    add_submenu_page( 'rankings-management', 'Search Data', 'Search Data', 'edit_others_posts', 'search-data', 'rankings_search_page' );
    add_submenu_page( 'rankings-management', 'Manage Backups', 'Manage Backups', 'edit_others_posts', 'backup-manager', 'rankings_backup_page' );
    remove_submenu_page( 'rankings-management', 'rankings-management' );
}
add_action( 'admin_menu', 'manage_rankings_menu' );


//------------------------------------------------------------------------------
// Backup Manager Backend Page
//------------------------------------------------------------------------------

/**
 * Render the Backup Manager page.
 *
 * Provides backup and restore functionality for both the elo_ratings and match_data tables,
 * and options to schedule automatic backups.
 *
 * @return void
 */
function rankings_backup_page() {
    if ( ! current_user_can( 'edit_others_posts' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    echo '<div class="wrap">';
    echo '<h1>Manage Backups</h1>';
    echo '<p>This page controls backups of the core <code>match_data</code> and <code>elo_ratings</code> tables. Backups are stored within the plugin in the <code>/backups</code> folder.</p>';

    // Handle backup and restore actions.
    if ( isset( $_POST['backup_elo_ratings'] ) ) {
        data_management_backup_table( 'elo_ratings' );
        echo '<div class="updated"><p>Elo Ratings table backed up.</p></div>';
    }
    if ( isset( $_POST['backup_match_data'] ) ) {
        data_management_backup_table( 'match_data' );
        echo '<div class="updated"><p>Match Data table backed up.</p></div>';
    }
    if ( isset( $_POST['restore_elo_ratings'] ) ) {
        data_management_restore_table( 'elo_ratings', $_POST['backup_file'] );
        echo '<div class="updated"><p>Elo Ratings table restored.</p></div>';
    }
    if ( isset( $_POST['restore_match_data'] ) ) {
        data_management_restore_table( 'match_data', $_POST['backup_file'] );
        echo '<div class="updated"><p>Match Data table restored.</p></div>';
    }
    if ( isset( $_POST['delete_backup'] ) ) {
        data_management_delete_backup( $_POST['backup_file'] );
        echo '<div class="updated"><p>Backup deleted.</p></div>';
    }

    // Backup forms for Match Data table.
    echo '<form method="post">';
    echo '<h2>Backup Match Data Table</h2>';
    echo '<p>The <code>match_data</code> table contains all currently ingested match data.</p>';
    echo '<input type="submit" name="backup_match_data" value="Backup Now" class="button button-primary">';
    echo '</form>';
    echo '<h3>Available Backups for Match Data</h3>';
    data_management_list_backups( 'match_data' );

    // Backup forms for Elo Ratings table.
    echo '<form method="post">';
    echo '<h2>Backup Elo Ratings Table</h2>';
    echo '<p>The <code>elo_ratings</code> table contains the most recent Elo ratings calculated by the plugin.</p>';
    echo '<input type="submit" name="backup_elo_ratings" value="Backup Now" class="button button-primary">';
    echo '</form>';
    echo '<h3>Available Backups for Elo Ratings</h3>';
    data_management_list_backups( 'elo_ratings' );

    // Settings for automatic backups.
    echo '<form method="post">';
    echo '<h2>Schedule Automatic Backups</h2>';
    echo '<p>Use these options to ensure that backups are made weekly.</p>';
    $auto_backup = get_option( 'backup_manager_auto_backup', 'no' );
    $checked     = ( $auto_backup == 'yes' ) ? 'checked' : '';
    echo '<label><input type="checkbox" name="auto_backup" value="yes" ' . $checked . '> Enable automatic backups every Monday at 04:00</label><br>';
    echo '<input type="submit" name="save_settings" value="Save Settings" class="button button-primary">';
    echo '</form>';
    if ( isset( $_POST['save_settings'] ) ) {
        $auto_backup = isset( $_POST['auto_backup'] ) ? 'yes' : 'no';
        update_option( 'backup_manager_auto_backup', $auto_backup );
        if ( $auto_backup == 'yes' ) {
            data_management_backup_activate();
        } else {
            data_management_backup_deactivate();
        }
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }
    echo '</div>';
}
 
// EOF