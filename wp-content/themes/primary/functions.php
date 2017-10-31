<?php
	require_once get_template_directory() . '/core/core.php';
	PhoenixTeam_Core::initInstance();

    function wp_remove_version() {
    return '';
    }
    add_filter('the_generator', 'wp_remove_version');
