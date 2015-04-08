/**
 * WooCommerce MailChimp Plugin
 */
var SS_WC_MailChimp = function($) {

	var $enabled;
	var $apiKey;
	var $mainList;
	var $groups;
	var $subscribeCustomers;
	var $subscribeCustomersOn;
	var $optInLabel;
	var $optInCheckboxDefault;
	var $optInCheckboxLocation;
	var $replaceInterestGroups;
	var $doubleOptIn;
	var $sendWelcomeEmail;

	return {
		init: init,
		loadLists: loadLists,
		loadGroups: loadGroups,
		messages: {}
	};

	function init() {

		initHandlers();
		initLists();
		initGroups();

	} //end function init

	function initHandlers() {
		$enabled = $('#woocommerce_mailchimp_enabled');
		$apiKey = $('#woocommerce_mailchimp_api_key');
		$mainList = $('#woocommerce_mailchimp_main_list');
		$groups = $('#woocommerce_mailchimp_groups');
		$subscribeCustomers = $('#woocommerce_mailchimp_subscribe_customers');
		$subscribeCustomersOn = $('#woocommerce_mailchimp_subscribe_customers_on');
		$optInLabel = $('#woocommerce_mailchimp_opt_in_label');
		$optInCheckboxDefault = $('#woocommerce_mailchimp_opt_in_checkbox_default_status');
		$optInCheckboxLocation = $('#woocommerce_mailchimp_opt_in_checkbox_location');
		$replaceInterestGroups = $('#woocommerce_mailchimp_replace_interest_groups');
		$doubleOptIn = $('#woocommerce_mailchimp_double_opt_in');
		$sendWelcomeEmail = $('#woocommerce_mailchimp_send_welcome_email');

		$apiKey.closest('tr').hide();
		$mainList.closest('tr').hide();
			
		if ($mainList.val() === '') {
			$groups.attr('disabled','disabled');
		}
		$enabled.change(function() {
			if ( $enabled.prop('checked') === true ) {
				$apiKey.closest('tr').fadeIn();
				$mainList.closest('tr').fadeIn();
				$groups.closest('tr').fadeIn();
			}
			else {
				$apiKey.closest('tr').fadeOut();
				$mainList.closest('tr').fadeOut();
				$groups.closest('tr').fadeOut();
			}
		}).change();

		$mainList.change(function() {
			if ($mainList.val()) {
				$groups.removeAttr('disabled');
			} else {
				$groups.children().remove();
				$groups.attr('disabled','disabled');
			}
		});

		$apiKey.on('blur', function(e) {
			if ($apiKey.val() === '') {
				loadLists($apiKey.val());
			}
		});

		$apiKey.on('paste', function() {

			// Short pause to wait for paste to complete
	        setTimeout( function() {
				loadLists($apiKey.val());
		    }, 100);
	    });

	    $mainList.on('change', function() {
			if ($mainList.val() !== '') {
				loadGroups($apiKey.val(), $mainList.val());
			}
		});

	    $optInLabel.closest('tr').hide();
	    $optInCheckboxDefault.closest('tr').hide();
	    $optInCheckboxLocation.closest('tr').hide();
	    $subscribeCustomersOn.closest('tr').hide();
	    $replaceInterestGroups.closest('tr').hide();
		$doubleOptIn.closest('tr').hide();
		$sendWelcomeEmail.closest('tr').hide();
		$subscribeCustomers.change(function() {
			switch ($subscribeCustomers.val()) {
				case '0':
					$subscribeCustomersOn.closest('tr').fadeOut();
					$optInLabel.closest('tr').fadeOut();
					$optInCheckboxDefault.closest('tr').fadeOut();
					$optInCheckboxLocation.closest('tr').fadeOut();
					$replaceInterestGroups.closest('tr').fadeOut();
					$doubleOptIn.closest('tr').fadeOut();
					$sendWelcomeEmail.closest('tr').fadeOut();
					break;
				case '1':
					$subscribeCustomersOn.closest('tr').fadeIn();
					$optInLabel.closest('tr').fadeOut();
					$optInCheckboxDefault.closest('tr').fadeOut();
					$optInCheckboxLocation.closest('tr').fadeOut();
					$replaceInterestGroups.closest('tr').fadeIn();
					$doubleOptIn.closest('tr').fadeIn();
					$sendWelcomeEmail.closest('tr').fadeIn();
					break;
				case '2':
					$optInLabel.closest('tr').fadeIn();
					$optInCheckboxDefault.closest('tr').fadeIn();
					$optInCheckboxLocation.closest('tr').fadeIn();
					$replaceInterestGroups.closest('tr').fadeIn();
					$doubleOptIn.closest('tr').fadeIn();
					$sendWelcomeEmail.closest('tr').fadeIn();
					break;
			}
		}).change();

	} //end function initHandlers

	function initLists() {
		var listsLoadingIndicator = $('<div id="ss_wc_mailchimp_loading_lists" class="woocommerce-mailchimp-loading"><span class="woocommerce-mailchimp-loading-indicator"></span>'+SS_WC_MailChimp_Messages.connecting_to_mailchimp+'</div>');
		$mainList.after(listsLoadingIndicator.hide());

	} //end function initLists

	function initGroups() {
		// Reinitialize the <optgroup> elements by splitting out the option names
		var currentGroup = '';
		var lastGroup = '';
		var grouping;
		var $options = $groups.children('option').clone();
		$groups.attr('data-placeholder', SS_WC_MailChimp_Messages.select_groups_placeholder);
		$groups.children().remove();
		for (i = 0; i < $options.length; i++) {
			item = $options[i];
			currentGroup = item.text.split(':')[0];
			if (currentGroup !== lastGroup) {
				grouping = $('<optgroup>').attr('label', currentGroup);
				$groups.append(grouping);
			}
			item.text = item.text.split(':')[1];
			grouping.append(item);
			lastGroup = currentGroup;
		}
		//$groups.chosen({ placeholder_text_multiple: SS_WC_MailChimp_Messages.select_groups_placeholder });
		$groups.select2('destroy').select2();
		var groupsMessage = $('<div id="ss-wc-mailchimp-groups-msg" style="display: inline-block"/>');
		$groups.after(groupsMessage);
		if ($options.length === 0) {
			groupsMessage.text(SS_WC_MailChimp_Messages.interest_groups_not_enabled);
			$groups.siblings('.select2-container').remove();
			groupsMessage.show();
		} else {
			$groups.siblings('.select2-container').show();
			groupsMessage.hide();
		}
		

		// Add the loading indicator for groups (set to hidden by default)
		var groupsLoadingIndicator = $('<div id="ss_wc_mailchimp_loading_groups" class="woocommerce-mailchimp-loading"><span class="woocommerce-mailchimp-loading-indicator"></span>'+SS_WC_MailChimp_Messages.connecting_to_mailchimp+'</div>');
		$groups.parent().append(groupsLoadingIndicator.hide());

	} //end function reInitGroups

	function loadLists(apiKey) {

		/**
	     * Load service status
	     */
	    $mainList.attr('disabled','disabled');
	    $('#ss_wc_mailchimp_loading_lists').show();
        $.post(
            ajaxurl,
            {
                'action': 'woocommerce_mailchimp_get_lists',
                'data': { 'api_key': apiKey }
            },
            function(response) {
            	$('#ss_wc_mailchimp_loading_lists').hide();
            	var result = [];

                try {
                    result = $.parseJSON(response);
                } catch (err) {
                    console.error(err);
                    alert(SS_WC_MailChimp_Messages.error_loading_lists);
                }

                console.log(result);

                if (result) {
                	$mainList.select2('destroy');
                	$mainList.removeAttr('disabled');
                	$mainList.children().remove();
                	$.each(result, function(key, val) {   
                		$mainList	
	                		.append($('<option></option>')
	                			.attr('value',key)
	                			.text(val)); 
                	});
                	$mainList.select2();
                }
            }
        );

	} //end function loadLists

	function loadGroups(apiKey, listId) {
		/**
	     * Load interest groups
	     */
	    $groups.attr('disabled','disabled');

	    $groups.select2().hide();
	    $('#ss-wc-mailchimp-groups-msg').hide();
	    $('#ss_wc_mailchimp_loading_groups').show();
        $.post(
            ajaxurl,
            {
                'action': 'woocommerce_mailchimp_get_groups',
                'data': { 'api_key': apiKey, 'list_id': listId }
            },
            function(response) {
            	$('#ss_wc_mailchimp_loading_groups').hide();
            	var result = [];

                try {
                    result = $.parseJSON(response);
                } catch (err) {
                    console.error(err);
                    alert(SS_WC_MailChimp_Messages.error_loading_groups);
                }

                console.log(result);

                if (result.error) {
                	//alert(result.error);
                	$('#ss-wc-mailchimp-groups-msg').text(result.error).show();
                	$groups.children().remove();
                	$groups.select2('destroy');
                	$groups.hide();
                	return;
                }

                if (result.length === 0) {
                	$('#ss-wc-mailchimp-groups-msg').show();
                	return;
                }

                $groups.show();
                $groups.removeAttr('disabled');
                //$groups.siblings('.select2-container').show();
            	$groups.children().remove();
            	$.each(result, function(i, grouping) { 
            		$grouping = $('<optgroup>');
            			$groups	
	                	.append($grouping
	                		.attr('label', grouping.name));
            		$.each(grouping.groups, function(j, group) {
                		$grouping	
	                		.append($('<option></option>')
	                			.attr('value',grouping.name + ':' + group.name)
	                			.text(group.name)); 
                	});
                });
                $groups.select2('destroy').select2();
            }
        );

	} //end function loadGroups

}(jQuery);