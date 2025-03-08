<?php
/*
Plugin Name: 8Realms Rankings
Description: A plugin that creates an Elo Rating and Ranking system for Age of Sigmar. Incorporates three suites of functionality to ingest, manage and display data.
Version: 1.0
Author: Jack Duffield
*/

// Include the Data Ingest functionality.
require_once plugin_dir_path(__FILE__) . 'data-ingest.php';

// Include the Data Management functionality.
require_once plugin_dir_path(__FILE__) . 'data-management.php';

// Include the Data Display functionality.
require_once plugin_dir_path(__FILE__) . 'data-display.php';

?>
