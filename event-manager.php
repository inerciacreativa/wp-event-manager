<?php
/**
 * Plugin Name: ic Event Manager
 * Plugin URI:  https://github.com/inerciacreativa/wp-event-manager
 * Version:     5.1.0
 * Text Domain: ic-event-manager
 * Domain Path: /languages
 * Description: Sencillo gestor de eventos.
 * Author:      Jose Cuesta
 * Author URI:  https://inerciacreativa.com/
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 */

use ic\Framework\Framework;
use ic\Plugin\EventManager\EventManager;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists(Framework::class)) {
	throw new RuntimeException(sprintf('Could not find %s class.', Framework::class));
}

if (!class_exists(EventManager::class)) {
	$autoload = __DIR__ . '/vendor/autoload.php';

	if (file_exists($autoload)) {
		/** @noinspection PhpIncludeInspection */
		include_once $autoload;
	} else {
		throw new RuntimeException(sprintf('Could not load %s class.', EventManager::class));
	}
}

include_once __DIR__ . '/source/helpers.php';

EventManager::create(__FILE__);
