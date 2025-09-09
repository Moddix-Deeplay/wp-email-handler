<?php
/**
 * Plugin Name: WP Email Handler
 * Description: Plugin for use SMTP for email delivery
 * Version: 1.0.0
 * Author: Moddix
 */

use Moddix\WpEmailHandler\WpEmailHandler;

// Проверяем, что WordPress загружен и класс существует
if (defined('ABSPATH') && function_exists('add_action') && class_exists(WpEmailHandler::class)) {
    add_action('plugins_loaded', function () {
        new WpEmailHandler();
    });
} else {
    error_log('WpEmailHandler not loaded');
}
