<?php
/**
 * Plugin Name: Gaenity Community Suite
 * Description: Advanced community platform with resources, discussions, polls, live chat, expert connections, and region-specific content for business communities worldwide.
 * Version: 2.0.0
 * Author: Skillscore IT Solutions and Training
 * Author URI: https://skillscore.com.ng
 * Text Domain: gaenity-community
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'GAENITY_COMMUNITY_PLUGIN_FILE' ) ) {
    define( 'GAENITY_COMMUNITY_PLUGIN_FILE', __FILE__ );
}

if ( ! class_exists( 'Gaeinity_Community_Plugin' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-gaenity-community-plugin.php';
}

global $gaenity_community_plugin;
$gaenity_community_plugin = new Gaeinity_Community_Plugin();
$gaenity_community_plugin->init();
