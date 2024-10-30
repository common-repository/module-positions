<?php

class module_positions_widget extends WP_Widget {
	
	// Global vars
	protected $key_name = 'module_positions';

	public function __construct() {
		
		parent::__construct(
			'module_positions_widget',
			__('module positions', $this->key_name),
			array(
				'description' => __('widget description', $this->key_name)
			)
		);
		
	}

	public function widget($args, $instance) {
		
		if (!empty($instance['mp_id'])) echo do_shortcode('[moduleposition id="' . $instance['mp_id'] . '"]');
		
	}

	public function update($new_instance, $old_instance) {
		
		$instance = $old_instance;
		$instance['mp_id'] = $new_instance['mp_id'];
		return $instance;
		
	}

	public function form($instance) {

		$positions = get_option($this->key_name . '_positions');
		$disabled_select_positions = '';
		
		if (empty($positions)) $disabled_select_positions = ' disabled="disabled"';
		
		// Output module positions
		echo '<p>';
			echo '<select class="widefat"' . $disabled_select_positions . '" name="' . $this->get_field_name('mp_id') . '">';
				if (empty($positions)) {
					echo '<option value="0" disabled="disabled">';
						_e('no module position', $this->key_name);
					echo '</option>';
				} else {
					echo '<option value="0">';
						echo '—';
					echo '</option>';
					
					if (!empty($positions)) {
						foreach ($positions as $id => $position) {
							echo '<option value="' . $id . '"';
								if ($id == $instance['mp_id']) {
									echo ' selected="selected"';
								}
							echo '>';
								echo $position;
							echo '</option>';
						}
					}
				}
			echo '</select>';
		echo '</p>';

	}
	
}

?>