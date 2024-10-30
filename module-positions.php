<?php
/*
Plugin Name: Module Positions
Plugin URI: http://www.philipp-kuehn.com
Description: This Plugin for WordPress is an simplified equivalent to Joomla's module positions.
Version: 1.2.6
Author: Philipp Kühn
Author URI: http://www.philipp-kuehn.com
License: GPLv3

Copyright (c) 2013 Philipp Kühn

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

//error_reporting(E_ALL); 
//ini_set('display_errors', 1);

require_once('module-positions-widget.php');

class module_positions {
	
	// Global vars
	protected $key_name = 'module_positions';
	protected $standard_settings = array(
		'markup' => '<div>{{post_content}}</div>',
		'sub_order_by' => '0'
	);
	
	// Construct
	public function __construct() {
		
		// Listen for the activate event
		register_activation_hook(__FILE__, array($this, 'mp_activate'));

		// Deactivation plugin
		register_deactivation_hook(__FILE__, array($this, 'mp_deactivate'));
		
		// load languages
		add_action('plugins_loaded', array($this, 'mp_languages'));
		
		// Admin sub-menu
		add_action('admin_init', array($this, 'admin_init'));
		add_action('admin_menu', array($this, 'add_page'), 9);

		// Add Javascript
		function plugin_admin_head() {
			echo '<link rel="stylesheet" type="text/css" media="all" href="' . plugins_url('css/module-positions-css.css', __FILE__) . '" />';
			echo '<script type="text/javascript">';
				require_once('module-positions-js.php');
			echo '</script>';
		} add_action('admin_head', 'plugin_admin_head');
		
		function plugin_admin_print_scripts() {
			wp_enqueue_script('jquery-ui-sortable');
		} add_action('admin_print_scripts', 'plugin_admin_print_scripts');
		
		// Shortcode
		add_shortcode('moduleposition', array($this, 'mp_shortcode'));
		add_filter('wp_nav_menu_objects', array($this, 'mp_get_current_menu'));
		add_filter('the_excerpt', 'do_shortcode');
		
		// Metabox
		add_action('admin_menu', array($this, 'mp_create_settings_metabox'));
		add_action('admin_menu', array($this, 'mp_create_content_metabox'));
		add_action('save_post', array($this, 'mp_save_content_metabox'));	
		
		// Ajax
		add_action('wp_ajax_my_action', array($this, 'mp_ajax'));
		
		// Add column & sort function
		add_filter('manage_modulepositions_posts_columns', array($this, 'mp_columns_head'));  
		add_action('manage_modulepositions_posts_custom_column', array($this, 'mp_columns_content'), 10, 2);
		add_filter('manage_edit-modulepositions_sortable_columns', array($this, 'mp_columns_content_sort'));
		
		// Widget
		add_action('widgets_init',  array($this, 'mp_widget_init'));
		add_filter('widget_text', 'do_shortcode');

	}
	
	// Register Widget
	function mp_widget_init() {
		
		register_widget('module_positions_widget');
		
	}
	
	// Load languages
	public function mp_languages() {

		$language_file = plugin_dir_path(__FILE__) . '/languages/' . $this->key_name . '-' . get_locale() . '.mo';
		$language = (file_exists($language_file)) ? get_locale() : 'en_US';
		load_textdomain($this->key_name, plugin_dir_path(__FILE__) . '/languages/' . $this->key_name . '-' . $language . '.mo');
		
	}
	
	// Activate plugin
	public function mp_activate() {
		
		// Add modulposition
		$positions = get_option($this->key_name . '_positions');
		if (empty($positions)) {
			$positions[1] = 'Module Position 1';
			update_option($this->key_name . '_positions', $positions);
		}
		
		// Add settings
		$settings = get_option($this->key_name . '_settings');
		if (empty($settings)) {
			$settings['markup'] = $this->standard_settings['markup'];
			$settings['sub_order_by'] = $this->standard_settings['sub_order_by'];
			update_option($this->key_name . '_settings', $settings);
		}
		
	}
	
	// Deactivate plugin
	public function mp_deactivate() {
		
		// Nothing
		
	}
	
	// Add column head
	public function mp_columns_head($defaults) {
		
		$defaults['module_position'] = __('module position', $this->key_name);  
		return $defaults;  
		
	}
	
	// Add column content
	public function mp_columns_content($column_name, $post_ID) {

		$data = get_post_meta($post_ID, $this->key_name, true);
		if (!empty($data[$this->key_name . '_position'])) {
			$positions = get_option($this->key_name . '_positions');
			echo '[' . $data[$this->key_name . '_position'] . '] ';
			echo $positions[$data[$this->key_name . '_position']];
		} else {
			echo '—';
		}
		
	}
	
	// Add column sorting
	public function mp_columns_content_sort($columns){
		
		$columns['module_position'] = 'moduleposition';
		return $columns;
		
	}
	
	// Ajax
	public function mp_ajax() {
		
		if (ctype_alnum($_POST['setting'])) {
			$settings = get_option('module_positions_settings');
			echo $settings[$_POST['setting']];
		}
		
		else if (ctype_alnum($_POST['modulepositions_delete']) && ctype_alnum($_POST['modulepositions_id'])) {	
			$data_posts = get_posts(array('numberposts' => -1, 'post_type' => 'modulepositions'));
			$count = 0;
			foreach ($data_posts as $post) {
				$data = get_post_meta($post->ID, $this->key_name, true);
				if ($data[$this->key_name . '_position'] == $_POST['modulepositions_id']) $count++;
			}
			echo $count;
		}
		
		// This is required to return a proper result
		die();
		
	}
	
	// Shortcode
	public function mp_shortcode($atts) { 
		
		$id = $atts['id'];
		$output = '';
		
		// Call menus (without output) for getting active menu id in $GLOBALS['mp_active_menu_id']
		ob_start();
		$locations = get_nav_menu_locations();
		foreach ($locations as $key => $location) {
			wp_nav_menu(array('theme_location' => $key)); 
		}
		ob_end_clean();
		
		if (!isset($GLOBALS['mp_active_menu_id'])) {
			$GLOBALS['mp_active_menu_id'] = array();
		}
		
		// Check if special page
		if (is_404()) $active_special_type = '404';
		if (is_search()) $active_special_type = 'search';
		if (is_date() && is_archive() && !is_single()) $active_special_type = 'archive';
		if (is_tag()) $active_special_type = 'tag';
		if (is_author()) $active_special_type = 'author';
		
		// Check if special page
		if (isset($active_special_type)) {
			
			// Get all posts
			$data_posts = get_posts(array('numberposts' => -1, 'post_type' => 'modulepositions'));
			
			foreach ($data_posts as $post) {
				// Check if post is in selected module position
				$data = get_post_meta($post->ID, $this->key_name, true);
				
				if (!empty($data[$this->key_name . '_position'])) {
					
					// Check if post is in selected menu
					if ($data[$this->key_name . '_position'] == $id) {
						$special_types = array('404', 'search', 'archive', 'tag', 'author');
						
						foreach ($special_types as $key => $special_type) {
							 
							if (!isset($data[$this->key_name . '_type_special'])) {
								// Nothing
							} else {
								// Type 1 (all)
								if ($data[$this->key_name . '_type_special'] == 1) {
									$cache_post[] = $post;
									$cache_order[] = $data[$this->key_name . '_order'];
								}
								
								// Type 2 (all except)
								else if ($data[$this->key_name . '_type_special'] == 2) {
									$echo = false;
									foreach ($special_types as $special_type) {
										if (isset($data[$this->key_name . '_item_special'][$special_type]['status'])) {
											if ($special_type != $active_special_type && $data[$this->key_name . '_item_special'][$special_type]['status'] == 'true' && !$echo) {
												$cache_post[] = $post;
												$cache_order[] = $data[$this->key_name . '_order'];
												$echo = true;
											}
										}
									}
								}
								
								// Type 3 (selected)
								else if ($data[$this->key_name . '_type_special'] == 3) {
									foreach ($special_types as $special_type) {
										if (isset($data[$this->key_name . '_item_special'][$special_type]['status'])) {
											if ($special_type == $active_special_type && $data[$this->key_name . '_item_special'][$special_type]['status'] == 'true') {
												$cache_post[] = $post;
												$cache_order[] = $data[$this->key_name . '_order'];
											}
										}
									}
								}
								
								// Type 4 (none)
								else if ($data[$this->key_name . '_type_special'] == 4) {
									// Nothing
								}
							
							}
							
						}
						
					}
					
				}
				
			}
			
		}

		else {
		 	
			// Check if post
			if (is_single()) {
			
				// Get all posts
				$data_posts = get_posts(array('numberposts' => -1, 'post_type' => 'modulepositions'));
				$parent_menu_ids[] = (isset($GLOBALS['mp_parent_menu_id'])) ? $GLOBALS['mp_parent_menu_id'] : '';
				
				foreach ($data_posts as $post) {
					
					// Check if post is in selected module position
					$data = get_post_meta($post->ID, $this->key_name, true);
					
					if (!empty($data[$this->key_name . '_position'])) {
						
						// Check if post is in selected menu
						if ($data[$this->key_name . '_position'] == $id) {
							$locations = get_nav_menu_locations();
							$menu_id = (isset($locations[$data[$this->key_name . '_menu']])) ? $locations[$data[$this->key_name . '_menu']] : '';
							
							if (isset($data[$this->key_name . '_item_' . $menu_id]) && 
								is_array($data[$this->key_name . '_item_' . $menu_id])) {

								foreach ($data[$this->key_name . '_item_' . $menu_id] as $key => $selected_menu_id) {
	
									// Check if sub
									if (!empty($data[$this->key_name . '_item_' . $menu_id][$key]['type'])
										&& in_array($key, $parent_menu_ids)) {
											
										$active_post_id = get_the_ID();
										
										if (!isset($data[$this->key_name . '_item_' . $menu_id][$key]['type'])) {
											// Nothing
										} else {
											// Type 1 (all)
											if ($data[$this->key_name . '_item_' . $menu_id][$key]['type'] == 1) {
												$cache_post[] = $post;
												$cache_order[] = $data[$this->key_name . '_order'];
											}
											
											// Type 2 (all except)
											else if ($data[$this->key_name . '_item_' . $menu_id][$key]['type'] == 2) {
												$echo = false;
												foreach ($data[$this->key_name . '_item_' . $menu_id][$key]['sub'] as $key => $selected_menu_id) {
													// Check if post is not active id
													if ($key != $active_post_id) {
														$cache_post[] = $post;
														$cache_order[] = $data[$this->key_name . '_order'];
														$echo = true;
													}
												}
											}
											
											// Type 3 (selected)
											else if ($data[$this->key_name . '_item_' . $menu_id][$key]['type'] == 3) {
												foreach ($data[$this->key_name . '_item_' . $menu_id][$key]['sub'] as $key => $selected_menu_id) {
													// Check if post is active id
													if ($key == $active_post_id) {
														$cache_post[] = $post;
														$cache_order[] = $data[$this->key_name . '_order'];
													}
												}
											}
											
											// Type 4 (none)
											else if ($data[$this->key_name . '_item_' . $menu_id][$key]['type'] == 4) {
												// Nothing
											}
										
										}
										
									}
	
								}

							}
							
						}
					
					}
					
				}

			}
		
			// Get all posts
			$data_posts = get_posts(array('numberposts' => -1, 'post_type' => 'modulepositions'));
	
			foreach ($data_posts as $post) {
				
				// Check if post is in selected module position
				$data = get_post_meta($post->ID, $this->key_name, true);
				
				if (!empty($data[$this->key_name . '_position'])) {
					
					// Check if post is in selected menu
					if ($data[$this->key_name . '_position'] == $id) {
						$locations = get_nav_menu_locations();
						$menu_id = (isset($locations[$data[$this->key_name . '_menu']])) ? $locations[$data[$this->key_name . '_menu']] : '';
						
						if (!isset($data[$this->key_name . '_type_' . $menu_id])) {
							// Nothing
						} else {
						
							// Type 1 (all)
							if ($data[$this->key_name . '_type_' . $menu_id] == 1) {
								$cache_post[] = $post;
								$cache_order[] = $data[$this->key_name . '_order'];
							}
							
							// Type 2 (all except)
							else if ($data[$this->key_name . '_type_' . $menu_id] == 2) {
								$echo = false;
								foreach ($data[$this->key_name . '_item_' . $menu_id] as $key => $selected_menu_id) {
									// Check if post is not in current (active) menu
									if (isset($data[$this->key_name . '_item_' . $menu_id][$key]['status'])) {
										if (!in_array($key, $GLOBALS['mp_active_menu_id']) && $data[$this->key_name . '_item_' . $menu_id][$key]['status'] == 'true' && !$echo) {
											$cache_post[] = $post;
											$cache_order[] = $data[$this->key_name . '_order'];
											$echo = true;
										}
									}
								}
							}
							
							// Type 3 (selected)
							else if ($data[$this->key_name . '_type_' . $menu_id] == 3) {
								foreach ($data[$this->key_name . '_item_' . $menu_id] as $key => $selected_menu_id) {
									// Check if post is in current (active) menu
									if (isset($data[$this->key_name . '_item_' . $menu_id][$key]['status'])) {
										if (in_array($key, $GLOBALS['mp_active_menu_id']) && $data[$this->key_name . '_item_' . $menu_id][$key]['status'] == 'true') {
											$cache_post[] = $post;
											$cache_order[] = $data[$this->key_name . '_order'];
										}
									}
								}
							}
							
							// Type 4 (none)
							else if ($data[$this->key_name . '_type_' . $menu_id] == 4) {
								// Nothing
							}
						
						}
						
					}
					
				}
				
			}
		
		}
		
		if (isset($cache_post) && !empty($cache_post)) {
		
			// Sort array
			array_multisort($cache_order, $cache_post);
			
			// Remove duplicated elements
			if (!function_exists('array_multi_unique')) {
				function array_multi_unique($multiArray){
					$uniqueArray = array();
					foreach($multiArray as $subArray){
						if (!in_array($subArray, $uniqueArray)) $uniqueArray[] = $subArray;
					}
					return $uniqueArray;
				}
			}
			$cache_post = array_multi_unique($cache_post);
			
			// Create Output
			foreach ($cache_post as $key => $post) {
				$output .= $this->mp_shortcode_build_output($post);
			}
			
			// Allow shortcodes in output and add wpautop-filter
			return do_shortcode(wpautop($output));
		
		}
		
	}
	
	// Build shortcode output with choosen syntax
	public function mp_shortcode_build_output($post) {
		
		$output = '';
		$data = get_post_meta($post->ID, $this->key_name, true);
		$markup = $data[$this->key_name . '_markup'];
		$markup = preg_replace('~\{\{(.+?)\}\}~e', '$post->$1', $markup);
		$output .= $markup;
		return $output;
		
	}
	
	// Get current (active) menu
	public function mp_get_current_menu($sorted_menu_items) {
		
		foreach ($sorted_menu_items as $menu_item) {
			if ($menu_item->current_item_parent) {
				$GLOBALS['mp_parent_menu_id'][] = $menu_item->ID;
			}
			if ($menu_item->current) {
				$GLOBALS['mp_active_menu_id'][] = $menu_item->ID;
			}
		}
		return $sorted_menu_items;
		
	}
	
	// Create Metabox
	public function mp_create_settings_metabox() {
		
		add_meta_box( 'new-meta-boxes2', __('HTML-markup', $this->key_name), array($this, 'mp_display_settings_metabox'), 'modulepositions', 'side', 'low' );
		
	}
	
	// Metabox Markup
	public function mp_display_settings_metabox() {
		
		global $post;
		
		echo '<div class="mp-smb-wrap">';
			echo '<div class="mp-smb-top">';
				_e('change markup description', $this->key_name);
			echo '</div>';	
					
			echo '<textarea rows="6" name="' . $this->key_name . '_markup">';
				$data = get_post_meta($post->ID, $this->key_name, true);
				if (!empty($data[$this->key_name . '_markup'])) {
					echo $data[$this->key_name . '_markup'];
				} else {
					$settings = get_option($this->key_name . '_settings');
					echo $settings['markup'];
				}
			echo '</textarea>';
			
			echo '<div class="mp-smb-bottom">';
				echo '<a href="#">' . __('reset', $this->key_name) . '</a>';
			echo '</div>';
		echo '</div>';
		
	}
	
	// Create Metabox
	public function mp_create_content_metabox() {
		
		add_meta_box( 'new-meta-boxes', __('visible in:', $this->key_name), array($this, 'mp_display_content_metabox'), 'modulepositions', 'normal', 'high' );
		
	}
	
	// Metabox Markup
	public function mp_display_content_metabox() {
		
		global $post;
		
		$new_ui = (get_bloginfo('version') >= 3.8) ? 'mp-mb-wrap-new-ui' : '';

		echo '<div class="mp-mb-wrap ' . $new_ui . '">';
			wp_nonce_field(plugin_basename(__FILE__), $this->key_name . '_wpnonce', false, true);
			
			$locations = get_nav_menu_locations();
			$registered_locations = get_registered_nav_menus();
			
			echo '<div class="mp-mb-top">';
				
				// Check if menu is available
				$disabled_select_locations = '';
				if (empty($registered_locations)) $disabled_select_locations = 'disabled="disabled"';
				
				// Output menus
				$data = get_post_meta($post->ID, $this->key_name, true);
				echo '<select class="mp-mb-locations" ' . $disabled_select_locations . 'name="' . $this->key_name . '_menu">';
				
					if (empty($registered_locations)) {
						echo '<option value="0" disabled="disabled">';
							_e('no registered menu', $this->key_name);
						echo '</option>';
					} else {
						echo '<option value="0" disabled="disabled">';
							_e('menu', $this->key_name);
						echo '</option>';
						
						$selected = false;
						foreach ($registered_locations as $key => $location) {
							$menu = wp_get_nav_menu_object($locations[$key]);
							$menuID = (isset($menu->term_id)) ? $menu->term_id : '';
							$menuName = (isset($menu->name)) ? $menu->name : '';
							echo '<option data-location-id="' . $menuID . '" value="' . $key . '"';
								if (empty($data[$this->key_name . '_menu']) && !$selected) {
									echo ' selected="selected"';
									$active_location = $menuID;
									$selected = true;
								} else {
									if ($data[$this->key_name . '_menu'] == $key) {
										echo ' selected="selected"';
										$active_location = $menuID;
									}
								}
							echo '>';
								echo $location . ' → ' . $menuName;
							echo '</option>';
						}
					}
				echo '</select>';
				
				// Check if module position is available
				$positions = get_option($this->key_name . '_positions');
				$disabled_select_positions = '';
				if (empty($positions)) $disabled_select_positions = 'disabled="disabled"';
				
				// Output module positions
				echo '<select class="mp-mb-position" ' . $disabled_select_positions . 'name="' . $this->key_name . '_position">';
					if (empty($positions)) {
						echo '<option value="0" disabled="disabled">';
							_e('no module position', $this->key_name);
						echo '</option>';
					} else {
						echo '<option value="0" disabled="disabled">';
							_e('module position', $this->key_name);
						echo '</option>';
						echo '<option value="0">';
							echo '—';
						echo '</option>';
	
						foreach ($positions as $id => $position) {
							echo '<option value="' . $id . '"';
								if ($data[$this->key_name . '_position'] == $id) {
									echo ' selected="selected"';
								}
							echo '>';
								echo $position;
							echo '</option>';
						}
					}
				echo '</select>';
				
				echo '<input type="hidden" name="' . $this->key_name . '_order" value="' . $data[$this->key_name . '_order'] . '" />';

			echo '</div>';
			
			$menuExists = array();
			foreach ($locations as $key => $location) {
				
				$menu = wp_get_nav_menu_object($locations[$key]);
				
				if (!empty($menu)) {
					
					if (!in_array($menu->term_id, $menuExists)) {
					
						$menu_items = wp_get_nav_menu_items($menu->term_id);
						$active = ($active_location == $menu->term_id) ? ' active' : '';
						
						echo '<div class="mp-mb-locations-items' . $active . '" data-location-id="' . $locations[$key] . '">';		
							
							//echo $menu->term_id;
							//echo 'ACTIVE ' . $data[$this->key_name . '_type_' . $menu->term_id];
							$active_type = (isset($data[$this->key_name . '_type_' . $menu->term_id])) ? $data[$this->key_name . '_type_' . $menu->term_id] : 4;
							if (empty($active_type)) $active_type = 4;
									
							echo '<div class="mp-mb-locations-items-top">';
							
								// Type 1 (all)
								echo '<label>';
									echo '<input class="mp-mb-type mp-mb-type-general" type="radio" name="' . $this->key_name . '_type_' . $menu->term_id . '" value="1"';
										if ($active_type == 1) echo ' checked="checked"';
									echo '/>';
									_e(' all', $this->key_name);
								echo '</label>';
								
								// Type 2 (all except)
								echo '<label>';
									echo '<input class="mp-mb-type mp-mb-type-general" type="radio" name="' . $this->key_name . '_type_' . $menu->term_id . '" value="2"';
										if ($active_type == 2) echo ' checked="checked"';
									echo '/>';
									_e(' all except', $this->key_name);
								echo '</label>';
								
								// Type 3 (selected)
								echo '<label>';
									echo '<input class="mp-mb-type mp-mb-type-general" type="radio" name="' . $this->key_name . '_type_' . $menu->term_id . '" value="3"';
										if ($active_type == 3) echo ' checked="checked"';
									echo '/>';
									_e(' selected', $this->key_name);
								echo '</label>';
								
								// Type 4 (none)
								echo '<label>';
									echo '<input class="mp-mb-type mp-mb-type-general" type="radio" name="' . $this->key_name . '_type_' . $menu->term_id . '" value="4"';
										if ($active_type == 4) echo ' checked="checked"';
									echo '/>';
									_e(' none', $this->key_name);
								echo '</label>';
								
							echo '</div>';
							
							if (!empty($menu_items)) {
							
								$columns = (count($menu_items) >= 3) ? ' mp-mb-locations-items-bottom-multicolumns' : '';
								echo '<div class="mp-mb-locations-items-bottom' . $columns . '">';
		
									$parent_count = 0;
									$column_items = ceil(count($menu_items) / 3);
									$previous_ID = null;
									$previous_parent = null;
									$parent_spacer = '';
									
									foreach ($menu_items as $key => $menu_item) {
										
										// Prepare '-' spacer for submenus
										if ($menu_item->menu_item_parent == $previous_ID) {
											$parent_count++;
										} else if ($menu_item->menu_item_parent != 0 && $menu_item->menu_item_parent != $previous_ID) {
											if ($menu_item->menu_item_parent != $previous_parent) {
												$parent_count--;
											}
										} else {
											$parent_count = 0;
										}
										
										for ($i = 1; $i <= $parent_count; $i++) $parent_spacer .= '– ';
										
										// Check if active and/or disabled
										if ($active_type == 1) {
											$active_type_attr = ' checked="checked" disabled="disabled"';
										} else if ($active_type == 4) {
											$active_type_attr = ' disabled="disabled"';
										} else {
											$active_type_attr = '';
										}
										
										// Columns
										if ($key == 0) echo '<div class="mp-mb-locations-items-bottom-column">';
										
										// Check if item has children
										// But only select first level
										if ((isset($menu_items[$key + 1]->menu_item_parent) && $menu_items[$key + 1]->menu_item_parent == $menu_item->ID) || $parent_count > 0) {
											
											$menu_item_firstparent = ($menu_item->menu_item_parent != $previous_ID && $parent_count == 0) ? true : false;
											
											if ($parent_count == 0) {
												$menu_item_firstparent_id = ' id="mp-mb-locations-items-firstparent-' . $menu_item->ID . '"';
											}
										} else {
											$menu_item_firstparent = false;
											$menu_item_firstparent_id = '';
										}
										
										// Output
										echo '<div' . $menu_item_firstparent_id . '>';
											echo '<div class="mp-mb-locations-items-bottom-options">';
												
												// Check if item has children
												// But only select first level
												if ($menu_item_firstparent) {
													echo '<div class="mp-mb-locations-items-bottom-sub" title="' . __('select all subpages', $this->key_name) . '"></div>';
												}
												
												// Check if category
												if ($menu_item->object == 'category') {
													echo '<div class="mp-mb-locations-items-bottom-more" title="' . __('show all posts', $this->key_name) . '"></div>';
												}
											echo '</div>';
											
											echo '<label>';
												echo '<input class="mp-mb-locations-items-bottom-input" type="checkbox" name="' . $this->key_name . '_item_' . $menu->term_id . '[' . $menu_item->ID . '][status]" value="true"';
													if (!empty($data[$this->key_name . '_item_' . $menu->term_id])) {
														if (isset($data[$this->key_name . '_item_' . $menu->term_id][$menu_item->ID]['status'])) {
															if ($data[$this->key_name . '_item_' . $menu->term_id][$menu_item->ID]['status'] == 'true') { 
																echo ' checked="checked"';
															}
														}
													}
												echo $active_type_attr . '/>';
												echo $parent_spacer . $menu_item->title;
											echo '</label>';
											
											// Check if category
											if ($menu_item->object == 'category') {
												
												// Order
												$settings = get_option('module_positions_settings');
												if (empty($settings['sub_order_by'])) {
													$sub_order_by = '0';
												}
												if ($settings['sub_order_by'] == '0') {
													$cat_posts = get_posts(array('numberposts' => -1, 'category' => $menu_item->object_id));
												}
												else if ($settings['sub_order_by'] == '1') {
													$cat_posts = get_posts(array('numberposts' => -1, 'category' => $menu_item->object_id, 'orderby' => 'title', 'order'=> 'ASC'));
												}
												
												if (!empty($cat_posts)) {
													echo '<div class="mp-mb-locations-sub hidden">';
														
														$active_type_sub = (isset($data[$this->key_name . '_item_' . $menu->term_id][$menu_item->ID]) && isset($data[$this->key_name . '_item_' . $menu->term_id][$menu_item->ID]['type'])) ? $data[$this->key_name . '_item_' . $menu->term_id][$menu_item->ID]['type'] : 4;
														if (empty($active_type_sub)) $active_type_sub = 4;
													
														echo '<div class="mp-mb-locations-sub-top">';
														
															// Type 1 (all)
															echo '<label>';
																echo '<input class="mp-mb-type mp-mb-type-sub" type="radio" name="' . $this->key_name . '_item_' . $menu->term_id . '[' . $menu_item->ID . '][type]" value="1"';
																	if ($active_type_sub == 1) echo ' checked="checked"';
																echo '/>';
																_e(' all', $this->key_name);
															echo '</label>';
															
															// Type 2 (all except)
															echo '<label>';
																echo '<input class="mp-mb-type mp-mb-type-sub" type="radio" name="' . $this->key_name . '_item_' . $menu->term_id . '[' . $menu_item->ID . '][type]" value="2"';
																	if ($active_type_sub == 2) echo ' checked="checked"';
																echo '/>';
																_e(' all except', $this->key_name);
															echo '</label>';
															
															// Type 3 (selected)
															echo '<label>';
																echo '<input class="mp-mb-type mp-mb-type-sub" type="radio" name="' . $this->key_name . '_item_' . $menu->term_id . '[' . $menu_item->ID . '][type]" value="3"';
																	if ($active_type_sub == 3) echo ' checked="checked"';
																echo '/>';
																_e(' selected', $this->key_name);
															echo '</label>';
															
															// Type 4 (none)
															echo '<label>';
																echo '<input class="mp-mb-type mp-mb-type-sub" type="radio" name="' . $this->key_name . '_item_' . $menu->term_id . '[' . $menu_item->ID . '][type]" value="4"';
																	if ($active_type_sub == 4) echo ' checked="checked"';
																echo '/>';
																_e(' none', $this->key_name);
															echo '</label>';
															
														echo '</div>';
														
														// Check if active and/or disabled
														if ($active_type_sub == 1) {
															$active_type_sub_attr = ' checked="checked" disabled="disabled"';
														} else if ($active_type_sub == 4) {
															$active_type_sub_attr = ' disabled="disabled"';
														} else {
															$active_type_sub_attr = '';
														}
														
														// Output for input fields
														echo '<div class="mp-mb-locations-sub-bottom">';
															foreach ($cat_posts as $post) {
																echo '<label>';
																	echo '<input class="" type="checkbox" name="' . $this->key_name . '_item_' . $menu->term_id . '[' . $menu_item->ID . '][sub][' . $post->ID . ']" value="' . $post->ID . '"';
																	
																	if (!empty($data[$this->key_name . '_item_' . $menu->term_id])) {
																		if (isset($data[$this->key_name . '_item_' . $menu->term_id][$menu_item->ID]['sub'][$post->ID])) { 
																			echo ' checked="checked"';
																		}
																	}
																	echo $active_type_sub_attr . '/>';
																	echo $post->post_title;
																echo '</label>';
															}
														echo '</div>';
													echo '</div>';
												}
												
											}
										echo '</div>';
	
										// Columns
										if (($key + 1) % ($column_items) == 0) {
											echo '</div>';
											
											if (($key + 1) < count($menu_items)) {
												echo '<div class="mp-mb-locations-items-bottom-column">';
											}
										} else if (($key + 1) == count($menu_items)) {
											echo '</div>';
										}
										 									
										// Data for preparing '-' spacer for submenus
										$parent_spacer = '';
										$previous_ID = $menu_item->ID;
										$previous_parent = $menu_item->menu_item_parent;
									}
								
							
								echo '</div>';
							
							}
	
						echo '</div>';
					}
					
					// Cache ID for avoiding multiple outputs
					$menuExists[] = $menu->term_id;
					
				}
			}

			$active_type_special = (isset($data[$this->key_name . '_type_special'])) ? $data[$this->key_name . '_type_special'] : 4;
			if (empty($active_type_special)) $active_type_special = 4;

			// Special pages
			echo '<div class="mp-mb-locations-items-special">';	
				echo '<div class="mp-mb-locations-items-top">';
					// Type 1 (all)
					echo '<label>';
						echo '<input class="mp-mb-type mp-mb-type-special" type="radio" name="' . $this->key_name . '_type_special" value="1"';
							if ($active_type_special == 1) echo ' checked="checked"';
						echo '/>';
						_e(' all', $this->key_name);
					echo '</label>';
					
					// Type 2 (all except)
					echo '<label>';
						echo '<input class="mp-mb-type mp-mb-type-special" type="radio" name="' . $this->key_name . '_type_special" value="2"';
							if ($active_type_special == 2) echo ' checked="checked"';
						echo '/>';
						_e(' all except', $this->key_name);
					echo '</label>';
					
					// Type 3 (selected)
					echo '<label>';
						echo '<input class="mp-mb-type mp-mb-type-special" type="radio" name="' . $this->key_name . '_type_special" value="3"';
							if ($active_type_special == 3) echo ' checked="checked"';
						echo '/>';
						_e(' selected', $this->key_name);
					echo '</label>';
					
					// Type 4 (none)
					echo '<label>';
						echo '<input class="mp-mb-type mp-mb-type-special" type="radio" name="' . $this->key_name . '_type_special" value="4"';
							if ($active_type_special == 4) echo ' checked="checked"';
						echo '/>';
						_e(' none', $this->key_name);
					echo '</label>';
				echo '</div>';
				
				$special_types = array('404', 'search', 'archive', 'tag', 'author');
				$columns = (count($special_types) >= 3) ? ' mp-mb-locations-items-bottom-multicolumns' : '';
				echo '<div class="mp-mb-locations-items-bottom' . $columns . '">';

					$column_items = ceil(count($special_types) / 3);
					
					foreach ($special_types as $key => $special_type) {
						
						// Check if active and/or disabled
						if ($active_type_special == 1) {
							$active_type_special_attr = ' checked="checked" disabled="disabled"';
						} else if ($active_type_special == 4) {
							$active_type_special_attr = ' disabled="disabled"';
						} else {
							$active_type_special_attr = '';
						}
						
						// Columns
						if ($key == 0) echo '<div class="mp-mb-locations-items-bottom-column">';
						
						// Output
						echo '<div>';
							echo '<label>';
								echo '<input class="mp-mb-locations-items-bottom-input" type="checkbox" name="' . $this->key_name . '_item_special[' . $special_type . '][status]" value="true"';
									if (!empty($data[$this->key_name . '_item_special'])) {
										if (isset($data[$this->key_name . '_item_special'][$special_type]['status'])) {
											if ($data[$this->key_name . '_item_special'][$special_type]['status'] == 'true') { 
												echo ' checked="checked"';
											}
										}
									}
								echo $active_type_special_attr . '/>';
								if ($special_type == '404') _e('404', $this->key_name);
								else if ($special_type == 'search') _e('search', $this->key_name);
								else if ($special_type == 'archive') _e('archive', $this->key_name);
								else if ($special_type == 'tag') _e('tag', $this->key_name);
								else if ($special_type == 'author') _e('author', $this->key_name);
							echo '</label>';
						echo '</div>';
						
						// Columns
						if (($key + 1) % ($column_items) == 0) {
							echo '</div>';
							
							if (($key + 1) < count($special_types)) {
								echo '<div class="mp-mb-locations-items-bottom-column">';
							}
						} else if (($key + 1) == count($special_types)) {
							echo '</div>';
						}
					
					}
				
				echo '</div>';
			
			echo '</div>';
			
			// WPML FIX - cache locations for saving
			// because at saving I get only locations from the standard language			
			$cachelocations = '';
			foreach ($locations as $key => $location) {
				$cachelocations .= $location . ',';
			}
			echo '<input type="hidden" name="' . $this->key_name . '_cachelocations" value="' . $cachelocations . '" />';
	
		echo '</div>';
		
	}
	
	// Save metadata
	public function mp_save_content_metabox($post_id) {
		
		global $post;
		
		if (isset($_POST[ $this->key_name . '_wpnonce'])) {
			if (!wp_verify_nonce($_POST[ $this->key_name . '_wpnonce'], plugin_basename(__FILE__))) {
				return $post_id;
			}
		}
		
		if (!current_user_can('edit_post', $post_id)) {
			return $post_id;
		}
		
		// Get post meta
		$post_meta = get_post_meta($post_id, $this->key_name, true);
		
		// Set order only on first save or if changed module position
		if (empty($post_meta) || empty($post_meta[$this->key_name . '_order']) || $post_meta[$this->key_name . '_position'] != $_POST[$this->key_name . '_position']) {
			
			$data_posts = get_posts(array('numberposts' => -1, 'post_type' => 'modulepositions'));
			$order = 0;
			foreach ($data_posts as $post) {
				$position_posts = get_post_meta($post->ID, $this->key_name, true);
				
				// Check highest order number and add +1
				if (!empty($position_posts) && isset($_POST[$this->key_name . '_position'])) {
					if ($position_posts[$this->key_name . '_position'] == $_POST[$this->key_name . '_position']) {
						$order = ($position_posts[$this->key_name . '_order'] >= $order) ? $position_posts[$this->key_name . '_order'] + 1 : $order;
					}
				}
			}
			$data[$this->key_name . '_order'] = $order;
		} else {
			$data[$this->key_name . '_order'] = $_POST[$this->key_name . '_order'];
		}
		
		// Save new data in array
		$data[$this->key_name . '_markup'] = (empty($_POST[$this->key_name . '_markup'])) ? '' : $_POST[$this->key_name . '_markup'];
		$data[$this->key_name . '_menu'] = (empty($_POST[$this->key_name . '_menu'])) ? '' : $_POST[$this->key_name . '_menu'];
		$data[$this->key_name . '_position'] = (empty($_POST[$this->key_name . '_position'])) ? '' : $_POST[$this->key_name . '_position'];
		
		// Save data for each menu
		// $locations = get_nav_menu_locations();
		// print_r($locations);

		if (!empty($_POST[$this->key_name . '_cachelocations'])) {
			$locations = explode(',', $_POST[$this->key_name . '_cachelocations']);
			
			foreach ($locations as $key => $location) {
				if (is_numeric($location)) {
					$menu = wp_get_nav_menu_object($locations[$key]);
					
					if (!empty($menu)) {
						$data[$this->key_name . '_type_' . $menu->term_id] = (empty($_POST[$this->key_name . '_type_' . $menu->term_id])) ? '' : $_POST[$this->key_name . '_type_' . $menu->term_id];
						$data[$this->key_name . '_item_' . $menu->term_id] = (empty($_POST[$this->key_name . '_item_' . $menu->term_id])) ? '' : $_POST[$this->key_name . '_item_' . $menu->term_id];
					}
				}
			}
			
		}

		// Save data for specials
		$data[$this->key_name . '_type_special'] = (empty($_POST[$this->key_name . '_type_special'])) ? '' : $_POST[$this->key_name . '_type_special'];
		$data[$this->key_name . '_item_special'] = (empty($_POST[$this->key_name . '_item_special'])) ? '' : $_POST[$this->key_name . '_item_special'];
		
		// Save updated data
		update_post_meta($post_id, $this->key_name, $data);

	}
	
	// White list our options using the Settings API
	public function admin_init() {
		
		register_setting($this->key_name . '_positions', $this->key_name . '_positions', array($this, 'mp_positions_validate'));
		register_setting($this->key_name . '_settings', $this->key_name . '_settings', array($this, 'mp_settings_validate'));
		
	}

	// Add entry in the settings menu
	public function add_page() {
	
		// Add new post type
		register_post_type('modulepositions', array(
			'labels' => array(
				'name' => __('Content', $this->key_name),
				'singular_name' => __('Content (singular)', $this->key_name),
				'edit_item' => __('Edit content', $this->key_name)
			),
			'show_in_menu' => 'modulepositions_positions',
			'supports' => array('title', 'editor'),
			'rewrite'            => false,
			'query_var'          => false,
			'publicly_queryable' => false,
			'public'             => true
		));
		
		// Add page for module positions		
		add_menu_page(__('module positions', $this->key_name), __('module positions', $this->key_name), 'edit_themes', 'modulepositions_positions', array($this, 'mp_edit_positions'), '', 50);
		
		// Add settings page
		add_options_page(__('module positions', $this->key_name), __('module positions', $this->key_name), 'manage_options', 'modulepositions_settings', array($this, 'mp_edit_settings'));
		
		// Remove permalink from admin panel
		add_filter('get_sample_permalink_html', function($return, $id, $new_title, $new_slug) {
			global $post;
			return $post->post_type === 'modulepositions' ? '' : $return;     
		}, '', 4);
		add_filter('pre_get_shortlink', function($false, $post_id) {
			return 'modulepositions' === get_post_type($post_id) ? '' : $false;
		}, 10, 2 );
		
		// Remove permalink for custom post types
		add_action('post_type_link', 'mp_remove_permalink', 10, 2);
		function mp_remove_permalink($link, $post) {
			return $post->post_type === 'modulepositions' ? '/' : $link;
		}
		
	}
	
	// Edit menu positions
	public function mp_edit_positions() {
		
		$data_posts = get_posts(array('numberposts' => -1, 'post_type' => 'modulepositions'));
		$positions = get_option($this->key_name . '_positions');
		ksort($positions);

		echo '<div class="wrap">';
			echo '<div id="icon-modulepositions_positions" class="icon32 icon32-posts-post"><br></div>';
			echo '<h2>' . __('edit module positions', $this->key_name) . '</h2>';
			echo '<form method="post" action="options.php">';
				settings_fields($this->key_name . '_positions');
				
				echo '<div class="mp-emp-positions">';
					foreach ($positions as $id => $position) {
						if (!empty($positions[$id])) {
							
							$count = 0;
							foreach ($data_posts as $post) {
								$data = get_post_meta($post->ID, $this->key_name, true);
								if ($data[$this->key_name . '_position'] == $id) $count++;
							}
							
							echo '<div class="mp-emp-position" data-id="' . $id . '">';
								echo '<div class="mp-emp-position-top">';
									echo '<div class="mp-emp-position-left">';
										echo '<div class="mp-emp-position-table">';
											echo '<div class="mp-emp-position-cell">';
												echo '<input type="text" name="' . $this->key_name . '_positions[' . $id . ']" value="' . $position . '" placeholder="' . __('title', $this->key_name) . '" />';
											echo '</div>';
										echo '</div>';
									echo '</div>';
									
									$no_items = ($count == 0) ? ' mp-emp-position-more-no-items' : '';
									echo '<div class="mp-emp-position-more' . $no_items . '">';
									echo '</div>';
									
									echo '<div class="mp-emp-position-right mp-emp-position-right-border">';
										echo '<div class="mp-emp-position-table">';
											echo '<div class="mp-emp-position-cell">';
												echo '<div data-id="' . $id . '"  data-count="' . $count . '" class="mp-emp-position-remove button-secondary">';
													_e('delete', $this->key_name);
												echo '</div>';
											echo '</div>';
										echo '</div>';
									echo '</div>';							
									
									echo '<div class="mp-emp-position-center">';
										echo '<div class="mp-emp-position-table">';
											echo '<div class="mp-emp-position-cell">';
												echo '<div class="mp-emp-position-shortcode">';
													echo '<label>' . __('PHP code for your template:', $this->key_name);
														echo '<input class="select" value="echo do_shortcode(\'[moduleposition id=&quot;' . $id . '&quot;]\');" />';
													echo '</label>';
												echo '</div>';
											echo '</div>';
										echo '</div>';
									echo '</div>';
								echo '</div>';
								echo '<div class="mp-emp-position-bottom" data-id="' . $id . '">';
									
									// Cache for sorting
									foreach ($data_posts as $post) {
										$data = get_post_meta($post->ID, $this->key_name, true);
										if ($data[$this->key_name . '_position'] == $id) {
											$cache_post[] = $post;
											$cache_order[] = $data[$this->key_name . '_order'];
										}
									}
									
									if (isset($cache_post) && !empty($cache_post)) {
									
										// Sort posty by order of post meta
										array_multisort($cache_order, $cache_post);
										
										echo '<input class="mp-emp-position-item-order" type="hidden" data-id="' . $id . '" name="' . $this->key_name . '_positions[' . $id . '-order]" />';
										
										// Sorted output
										foreach ($cache_post as $key => $post) {
											echo '<div class="mp-emp-position-item" data-id="' . $post->ID . '" data-order="' . $cache_order[$key] . '">';
												echo $post->post_title;
											echo '</div>';
										}
										
										// Clear array for next module position
										unset($cache_post);
										unset($cache_order);
									
									}							
									
								echo '</div>';
							echo '</div>';
							
						}
					}
				echo '</div>';
				
				// Add save button
				echo '<p class="submit">';
					echo '<span class="button-secondary mp-emp-position-add">';
						_e('add', $this->key_name);
					echo '</span>';
				
					echo '<input type="submit" class="button-primary" value="';
						_e('Save Changes', $this->key_name);
					echo '" />';
				echo '</p>';
				
				// Description
				echo '<h3 class="title">' . __('description title how-to', $this->key_name) . '</h3>';
				echo '<p>' . __('description text how-to', $this->key_name) . '</p>';
				echo '<h3 class="title">' . __('description title order', $this->key_name) . '</h3>';
				echo '<p>' . __('description text order', $this->key_name) . '</p>';
				echo '<h3 class="title">' . __('description title settings', $this->key_name) . '</h3>';
				echo '<p>' . __('description text settings', $this->key_name) . '</p>';
				
			echo '</form>';
		echo '</div>';
		
	}
	
	// Edit menu positions
	public function mp_edit_settings() {
		
		$settings = get_option('module_positions_settings');
        
		echo '<div class="wrap">';
			echo '<div id="icon-modulepositions_positions" class="icon32 icon32-posts-post"><br></div>';
			echo '<h2>' . __('settings › modulepositions', $this->key_name) . '</h2>';
			echo '<form method="post" action="options.php">';
				settings_fields($this->key_name . '_settings');
				
				echo '<table class="form-table">';
					echo '<tr valign="top">';
						echo '<th scope="row">';
							_e('standard HTML-markup', $this->key_name);
						echo '</th>';
						echo '<td>';
							echo '<fieldset>';
								echo '<p>';
									_e('change markup description', $this->key_name);
								echo '</p>';
								echo '<p>';
									echo '<textarea class="large-text code" rows="6" name="' . $this->key_name . '_settings[markup]">';
										if (!empty($settings['markup'])) {
											echo $settings['markup'];
										}
									echo '</textarea>';
								echo '</p>';
							echo '</fieldset>';
						echo '</td>';
					echo '</tr>';
					echo '<tr valign="top">';
						echo '<th scope="row">';
							_e('standard sub order', $this->key_name);
						echo '</th>';
						echo '<td>';
							echo '<fieldset>';
								echo '<p>';
									_e('sub order description', $this->key_name);
								echo '</p>';
								echo '<p>';
									echo '<select name="' . $this->key_name . '_settings[sub_order_by]">';
										if (!empty($settings['sub_order_by'])) {
											$sub_order_by = $settings['sub_order_by'];
										} else {
											$sub_order_by = 0;
										}
										echo '<option value="0"';
											$sub_order_by_selected = ($sub_order_by == 0) ? ' selected="selected"' : '';
											echo $sub_order_by_selected;
											echo '>';
											_e('post_date', $this->key_name);
										echo '</option>';
										echo '<option value="1"';
											$sub_order_by_selected = ($sub_order_by == 1) ? ' selected="selected"' : '';
											echo $sub_order_by_selected;
											echo '>';
											_e('title', $this->key_name);
										echo '</option>';
									echo '</select>';
								echo '</p>';
							echo '</fieldset>';
						echo '</td>';
					echo '</tr>';
				echo '<table>';
				
				// Save button
				echo '<p class="submit">';
					echo '<input type="submit" class="button-primary" value="';
						_e('Save Changes', $this->key_name);
					echo '" />';
				echo '</p>';
				
				// Donate
				echo '<div class="mp-donate">';
					echo '<h3>' . __('donate', $this->key_name) . '</h3>';
					echo '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=R8543KFNL7NR8" target="_blank">';
						echo '<img src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" alt="Buy me a beer!" name="Buy me a beer!"/>';
					echo '</a>';
				echo '</div>';
				
			echo '</form>';
		echo '</div>';
		
	}
	
	// Validate data for menu positions
	public function mp_positions_validate($input) {
		
		$valid = array();
		foreach ($input as $key => $value) {
			
			// Positions
			if (is_numeric($key)) {
				if ($value == 'deleted') {
	
					// Set moduleposition of all posts width deleted moduleposition to '0'
					$data_posts = get_posts(array('numberposts' => -1, 'post_type' => 'modulepositions'));
					foreach ($data_posts as $post) {
						$data = get_post_meta($post->ID, $this->key_name, true);
						if ($data[$this->key_name . '_position'] == $key) {
							$data[$this->key_name . '_position'] = 0;
							update_post_meta($post->ID, $this->key_name, $data);
						}
					}
					
				} else {
					$valid[$key] = $value;
				}
			}
			
			// Order
			else {
				$idArray = explode('-', $key);
				$id = $idArray[0];
				$orderArray = explode(',', $value);
				
				$order = 0;
				foreach ($orderArray as $orderID) {
					if (is_numeric($orderID)) {
						$data = get_post_meta($orderID, $this->key_name, true);
						$data[$this->key_name . '_order'] = $order;
						update_post_meta($orderID, $this->key_name, $data);
						$order++;
					}
				}
			}
		}
		return $valid;
		
	}
	
	// Validate data for setting
	public function mp_settings_validate($input) {
		
		if ($input['markup'] == '') {
			$input['markup'] = $this->standard_settings['markup'];
		}
		return $input;

	}

}

new module_positions();

?>