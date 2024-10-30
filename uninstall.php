<?php

//if uninstall not called from WordPress exit
if (!defined( 'WP_UNINSTALL_PLUGIN'))  {
	exit ();
}

$key_name = 'module_positions';

// Delete settings
delete_option($key_name . '_settings');

// Delete module positions
delete_option($key_name . '_positions');

// Delete posts & post meta fields
$data_posts = get_posts(array(
	'numberposts' => -1,
	'post_type' => 'modulepositions',
	'post_status' => 'any'
	)
);

foreach ($data_posts as $post) {
	wp_delete_post($post->ID);
}

?>