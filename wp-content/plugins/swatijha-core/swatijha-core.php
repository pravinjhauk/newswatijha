<?php
/**
 * Plugin Name: Swati Jha Core
 * Description: Owned clinical content, editorial review and native blocks.
 * Version: 0.2.0
 * Requires at least: 6.6
 * Requires PHP: 8.3
 * License: GPL-2.0-or-later
 * Text Domain: swatijha-core
 */
namespace SwatiJha;
defined('ABSPATH') || exit;
define('SJ_CORE_FILE', __FILE__);
define('SJ_CORE_PATH', __DIR__);
foreach (['Model', 'Editorial', 'Graph', 'Blocks', 'Admin', 'Forms', 'Migration', 'Hardening', 'Mail'] as $module) {
    require_once __DIR__ . '/src/' . $module . '.php';
}
register_activation_hook(__FILE__, [Model::class, 'activate']);
Model::boot();
Editorial::boot();
Graph::boot();
Blocks::boot();
Admin::boot();
Forms::boot();
Migration::boot();
Hardening::boot();
Mail::boot();
