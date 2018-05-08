<?php
/**
 * Plugin Name: ic Event Manager
 * Plugin URI:  https://github.com/inerciacreativa/wp-event-manager
 * Version:     2.0.6
 * Text Domain: ic-event-manager
 * Domain Path: /languages
 * Description: Sencillo gestor de eventos.
 * Author:      Jose Cuesta
 * Author URI:  https://inerciacreativa.com/
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 */

if (!defined('ABSPATH')) {
    exit;
}

include_once __DIR__ . '/vendor/autoload.php';
include_once __DIR__ . '/source/helpers.php';

ic\Plugin\EventManager\EventManager::create(__FILE__);
