jQuery(document).ready(function($) {
	
	// Add module position
	$('.mp-emp-position-add').on('click', function() {
		var ID = parseInt($('.mp-emp-positions').children().last().attr('data-id')),
			newID = (exists(ID)) ? ID + 1 : 1;
			output = '';
		
		output += '<div class="mp-emp-position" data-id="' + newID + '">';
			output += '<div class="mp-emp-position-top">';
				output += '<div class="mp-emp-position-left">';
					output += '<input type="text" name="module_positions_positions[' + newID + ']" value="" placeholder="<?php _e('title', 'module_positions'); ?>" />';
				output += '</div>';
				
				output += '<div class="mp-emp-position-more mp-emp-position-more-no-items">';
				output += '</div>';
				
				output += '<div class="mp-emp-position-right mp-emp-position-right-border">';
					output += '<div data-id="' + newID + '" data-count="0" class="mp-emp-position-remove button-secondary">';
						output += '<?php _e('delete', 'module_positions'); ?>';
					output += '</div>';
				output += '</div>';							
				
				output += '<div class="mp-emp-position-center">';
					output += '<div class="mp-emp-position-shortcode">';
						output += '<label><?php _e('PHP code for your template:', 'module_positions'); ?>';
							output += '<input class="select" value="echo do_shortcode(\'[moduleposition id=&quot;' + newID + '&quot;]\');" />';
						output += '</label>';
					output += '</div>';
				output += '</div>';
			output += '</div>';
			output += '<div class="mp-emp-position-bottom">';
			output += '</div>';
		output += '</div>';
		
		$('.mp-emp-positions').append(output);
	});
	
	// Remove module position
	$('.mp-emp-positions').delegate('.mp-emp-position-remove', 'click', function() {
		var removeID = $(this).attr('data-id'),
			count = parseInt($(this).attr('data-count'));

		if (count > 0) {
			
			if (count == 1) {
				var r = confirm('<?php _e('delete confirm 1-1', 'module_positions'); ?> ' + count + ' <?php _e('delete confirm 1-2', 'module_positions'); ?>');
			} else {
				var r = confirm('<?php _e('delete confirm 2-1', 'module_positions'); ?> ' + count + ' <?php _e('delete confirm 2-2', 'module_positions'); ?>');
			}
			
			if (r == true) {
				var data = {
					action: 'my_action',
					modulepositions_delete: 'delete',
					modulepositions_id: removeID
				};
				$.post(ajaxurl, data, function(response) {
					$('.mp-emp-position[data-id=' + removeID + ']').hide().find('.mp-emp-position-left input').val('deleted');
				});
			}
		} else {
			$('.mp-emp-position[data-id=' + removeID + ']').hide().find('.mp-emp-position-left input').val('deleted');
		}
		
		return false;
	});
    
    // Load Order
	$('.mp-emp-position-bottom').sortable({
		update: function(event, ui) {
			var orderID = $(this).attr('data-id'),
				order = '';
			
			$(this).children('.mp-emp-position-item').each(function(index, element) {
                order += $(this).attr('data-id') + ',';
            });	
			
			$('.mp-emp-position-item-order[data-id="' + orderID + '"]').val(order);
		}
	});
    $('.mp-emp-position-bottom').disableSelection();
	$('.mp-emp-position-more').not('.mp-emp-position-more-no-items').on('click', function() {
		$(this).toggleClass('active');
		$(this).parent().parent().find('.mp-emp-position-bottom').toggleClass('active');
	});
	
	// Select shortcode code
    $('.mp-emp-positions').delegate('input.select', 'click', function() {
		$(this).select();
	});
	
	// Metabox: change menu
	$('.mp-mb-locations').on('change', function() {
		var location = $('option:selected', this).data('location-id');
		$('.mp-mb-locations-items').addClass('active').not('.mp-mb-locations-items[data-location-id="' + location + '"]').removeClass('active');
	});
	
	// Metabox: change selection type for general
	$('input.mp-mb-type-general').change(function() {
		if ($('.mp-mb-locations-items.active input.mp-mb-type:checked').val() == '1') {
			$('.mp-mb-locations-items.active .mp-mb-locations-items-bottom-input').attr('checked', 'checked').attr('disabled', 'disabled');
		} else if ($('.mp-mb-locations-items.active input.mp-mb-type:checked').val() == '2') {
			$('.mp-mb-locations-items.active .mp-mb-locations-items-bottom-input').removeAttr('checked').removeAttr('disabled');
		} else if ($('.mp-mb-locations-items.active input.mp-mb-type:checked').val() == '3') {
			$('.mp-mb-locations-items.active .mp-mb-locations-items-bottom-input').removeAttr('checked').removeAttr('disabled');
		} else {
			$('.mp-mb-locations-items.active .mp-mb-locations-items-bottom-input').removeAttr('checked').attr('disabled', 'disabled');
		}
	});
	
	// Metabox: change selection type for special
	$('input.mp-mb-type-special').change(function() {
		if ($(this).val() == '1') {
			$('.mp-mb-locations-items-special .mp-mb-locations-items-bottom-input').attr('checked', 'checked').attr('disabled', 'disabled');
		} else if ($(this).val() == '2') {
			$('.mp-mb-locations-items-special .mp-mb-locations-items-bottom-input').removeAttr('checked').removeAttr('disabled');
		} else if ($(this).val() == '3') {
			$('.mp-mb-locations-items-special .mp-mb-locations-items-bottom-input').removeAttr('checked').removeAttr('disabled');
		} else {
			$('.mp-mb-locations-items-special .mp-mb-locations-items-bottom-input').removeAttr('checked').attr('disabled', 'disabled');
		}
	});
    
    // Metabox: change selection type for sub
    $('input.mp-mb-type-sub').change(function() {
		if ($(this).val() == '1') {
			$(this).parents().eq(2).find('.mp-mb-locations-sub-bottom input').attr('checked', 'checked').attr('disabled', 'disabled');
		} else if ($(this).val() == '2') {
			$(this).parents().eq(2).find('.mp-mb-locations-sub-bottom input').removeAttr('checked').removeAttr('disabled');
		} else if ($(this).val() == '3') {
			$(this).parents().eq(2).find('.mp-mb-locations-sub-bottom input').removeAttr('checked').removeAttr('disabled');
		} else {
			$(this).parents().eq(2).find('.mp-mb-locations-sub-bottom input').removeAttr('checked').attr('disabled', 'disabled');
		}
	});
    
    // Metabox: open
	$('.mp-mb-locations-items-bottom-more').on('click', function() {
		$(this).toggleClass('active').parent().parent().find('.mp-mb-locations-sub').toggleClass('hidden');
        return false;
	});
	
	// Metabox: select subpages
	$('.mp-mb-locations-items-bottom-sub').on('click', function() {
		var $subs = $('.mp-mb-locations-items.active #' + $(this).parent().parent().attr('id') + ' .mp-mb-locations-items-bottom-input'),
			$subsChecked = $('.mp-mb-locations-items.active #' + $(this).parent().parent().attr('id') + ' .mp-mb-locations-items-bottom-input:checked');
		
		if ($subs.length == $subsChecked.length) {
			$subs.removeAttr('checked');
		} else {
			$subs.attr('checked', 'checked');
		}
        return false;
	});
	
	// Metabox: ajax for loading default settings
	$('.mp-smb-bottom a').on('click', function() {
		var data = {
			action: 'my_action',
			setting: 'markup'
		};

		$.post(ajaxurl, data, function(response) {
			$('.mp-smb-wrap textarea').val(response);
		});
		
		return false;
	});

	// Helper function
	function exists(data) {
		if (!data || data == null || data == 'undefined' || typeof(data) == 'undefined') {
			return false;
		} else {
			return true;
		}
	}
});