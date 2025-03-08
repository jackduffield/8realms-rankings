<br/>
<picture>
  <source media="(prefers-color-scheme: dark)" srcset="https://8realms.net/wp-content/uploads/2024/06/8realmswhite.png">
  <source media="(prefers-color-scheme: light)" srcset="https://8realms.net/wp-content/uploads/2025/03/8realms.png">
  <img alt="8Realms Logo" src="https://8realms.net/wp-content/uploads/2024/06/8realmswhite.png" width="50%" align="middle">
</picture>
<br/>

# 8Realms Rankings

**8Realms Rankings** is a suite of functionality which allows the user to ingest, manage and display Elo ratings and rankings for Age of Sigmar on a WordPress site.

It is designed for use at [8realms.net](https://8realms.net). It ingests tournament data from multiple sources, calculates Elo ratings, and displays player rankings and profiles via custom Gutenberg blocks. The plugin also provides a robust admin interface for managing and editing tournament data, scheduling backups, and restoring tables.

The plugin is coded primarily in PHP and JavaScript with supporting SQL, HTML and CSS.


## Features

**Data Ingestion & Parsing**
  - Parse raw data from Best Coast Pairings and Stats and Ladders, as well as manually entering matches if required.
  - Store match data in the `match_data` table.

**Elo Rating Calculator**  
  - Calculate Elo ratings using a dynamic K-factor based on player experience and rating.
  - Update player rankings in the `elo_ratings` table.

**Data Manager**  
  - Use admin pages to view, edit, and delete tournament data at the event or match level.
  - Quickly locate specific matches or tournaments using detailed seach functions.

**Backup Manager**  
  - Create, delete and restore from backups of both `match_data` and `elo_ratings`.
  - Schedule regular backups for plugin data.
  - Download backups as CSV files.

**Gutenberg Blocks**
  - Display overall player rankings based on Elo scores.
  - Enable live filtering of rankings.
  - Show detailed player profiles with stats and match history.
  - List tournament events included in the `match_data` table.
  - Display rankings for a particular faction.

**Custom Styles**  
  - Adjust plugin styles based on the current WordPress theme.

## Installation

1. **Upload the Plugin:**  
   Upload the `8realms-rankings` folder to the `/wp-content/plugins/` directory of your WordPress installation.

2. **Activate the Plugin:**  
   In your WordPress admin dashboard, go to **Plugins** and activate **8Realms Rankings**.

3. **Configure Backups (Optional):**  
   The plugin automatically schedules weekly backups. To adjust backup settings, navigate to the **Manage Backups** section in the Data Management admin menu.

## Usage

### Frontend Functionality

In the block editor, add any of the following blocks to a post or page: 

- Insert the **Rankings Table** block in any post or page to display the overall Elo rankings.

- Insert the **Searchable Rankings Table** block to allow visitors to filter rankings by player name using a built-in JavaScript filter.

- Insert the **Events List** block to show distinct tournaments (events) with start dates.

- Insert the **Faction Rankings** block to display rankings for a specific faction. (Visitors must supply a query parameter, e.g., `?faction=Faction+Name` in the URL.)

- Insert the **Player Profile** block to display a detailed profile for a player. (The profile is displayed when the URL includes a query parameter like `?player=PlayerName`.)

### Backend Functionality

Use the **Data Ingest** admin page to paste raw tournament data (from BCP or SNL) into the provided form fields. Only include the values in the table, not headings or any other page content. Data is parsed and stored in the `match_data` table.

Use the **Manage Data** admin page to view all ingested tournaments. Submenus allow users to view detailed event data, edit or delete individual match records, and search within `match_data` based on various criteria.

Use the **Manage Backups** admin page to create backups of the `match_data` and `elo_ratings` tables, restore a backup from a CSV file, delete backup files, and enable or disable automatic backups (scheduled by default for 04:00 UTC on Mondays).

Use the **Calculate Elos** admin page to trigger manual Elo rating recalculations. Updated rankings then become visible on the Manage Data page and within the frontend blocks.

## File Structure

    8realms-rankings/
    ├── 8realms-rankings.php               # Main plugin file; entry point for initialization and hooks
    ├── backups/                           # Directory for storing backup CSV files of database tables
    ├── blocks.js                          # Registers Gutenberg blocks for displaying rankings, profiles, events, etc.
    ├── data-display.php                   # Contains functions for front-end display (player profiles, rankings, events, faction rankings)
    ├── data-ingest.php                    # Handles parsing and ingestion of tournament data from BCP and SNL sources
    ├── data-management.php                # Admin functions to manage tournament data (view, edit, delete, search) and backup management
    ├── editor.css                         # Editor-specific styles for Gutenberg blocks
    ├── faction-icons/                     # Default SVG icons for factions
    ├── faction-icons-black/               # Black-themed SVG icons for factions
    ├── faction-icons-black-large/         # Large black-themed SVG icons for factions
    ├── faction-icons-large/               # Large default SVG icons for factions
    ├── filter.js                          # JavaScript for live filtering of the rankings table on the front end
    ├── grand-alliance-icons/              # Default SVG icons for grand alliances
    ├── grand-alliance-icons-black/        # Black-themed SVG icons for grand alliances
    ├── LICENSE.md                         # Plugin license details
    ├── README.md                          # Plugin documentation and usage instructions
    └── style.css                          # Front-end stylesheet for tables, player profiles, and overall plugin styling

## Changelog

### 1.0.0
- Initial release with full functionality:
  - Data ingestion from BCP and SNL.
  - Elo rating calculation with dynamic K-factors.
  - Admin area for data management, backup, and restore.
  - Custom Gutenberg blocks for front-end display.
  - Responsive design and table filtering.

## Contributing

Contributions are welcome! To contribute:

1. Fork the repository.
2. Create a new branch for your feature or bug fix.
3. Submit a pull request with detailed information about your changes.

## License

This project is licensed under the [GNU General Public Licence](https://www.gnu.org/licenses/gpl-3.0.en.html).

## Support

For support or to report issues, please open an issue on the GitHub repository.