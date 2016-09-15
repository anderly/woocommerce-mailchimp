/**
 * WooCommerce MailChimp Plugin
 */
var SS_WC_MailChimp = function($) {

	var $enabled;
	var $apiKey;
	var $listsLoadingIndicator;
	var $mainList;
	var $interestGroupsLoadingIndicator;
	var $interestGroups;
	var $displayOptIn;
	var $occurs;
	var $optInLabel;
	var $optInCheckboxDefault;
	var $optInCheckboxLocation;
	var $doubleOptIn;

	var namespace = 'ss_wc_mailchimp';

	return {
		init: init,
		loadLists: loadLists,
		loadGroups: loadGroups,
	};

	function init() {

		initHandles();
		initHandlers();
		initLists();
		initGroups();

	} //end function init

	function initHandles() {
		// Capture jQuery handles to elements
		$enabled = $('#' + namespace_prefixed('enabled'));
		$apiKey = $('#' + namespace_prefixed('api_key'));
		$mainList = $('#' + namespace_prefixed('list'));
		$interestGroups = $('#' + namespace_prefixed('interest_groups'));
		$displayOptIn = $('#' + namespace_prefixed('display_opt_in'));
		$occurs = $('#' + namespace_prefixed('occurs'));
		$optInLabel = $('#' + namespace_prefixed('opt_in_label'));
		$optInCheckboxDefault = $('#' + namespace_prefixed('opt_in_checkbox_default_status'));
		$optInCheckboxLocation = $('#' + namespace_prefixed('opt_in_checkbox_display_location'));
		$doubleOptIn = $('#' + namespace_prefixed('double_opt_in'));
	}

	function initHandlers() {
		//$apiKey.closest('tr').hide();
		$mainList.closest('tr').hide();
			
		if ($mainList.val() === '') {
			$interestGroups.attr('disabled','disabled');
		}
		$apiKey.change(function() {
			if ( $apiKey.val() === '' ) {
				toggleAllSettings('hide');
			} else {
				toggleAllSettings('show');
			}
		}).change();

		$mainList.change(function() {
			if ($mainList.val()) {
				$interestGroups.removeAttr('disabled');
			} else {
				$interestGroups.children().remove();
				$interestGroups.attr('disabled','disabled');
			}
		});

		$apiKey.on('blur', function(e) {
			if ($apiKey.val() === '') {
				loadLists($apiKey.val());
			}
		}).change();

		$apiKey.on('paste cut', function() {

			// Short pause to wait for paste to complete
	        setTimeout( function() {
				loadLists($apiKey.val());
				$apiKey.change();
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
		$doubleOptIn.closest('tr').hide();
		$displayOptIn.change(function() {
			switch ($displayOptIn.val()) {
				// case '0':
				// 	$occurs.closest('tr').fadeOut();
				// 	$optInLabel.closest('tr').fadeOut();
				// 	$optInCheckboxDefault.closest('tr').fadeOut();
				// 	$optInCheckboxLocation.closest('tr').fadeOut();
				// 	$doubleOptIn.closest('tr').fadeOut();
				// 	break;
				case 'no':
					$optInLabel.closest('tr').fadeOut();
					$optInCheckboxDefault.closest('tr').fadeOut();
					$optInCheckboxLocation.closest('tr').fadeOut();
					$doubleOptIn.closest('tr').fadeIn();
					break;
				case 'yes':
					$optInLabel.closest('tr').fadeIn();
					$optInCheckboxDefault.closest('tr').fadeIn();
					$optInCheckboxLocation.closest('tr').fadeIn();
					$doubleOptIn.closest('tr').fadeIn();
					break;
			}
		}).change();

	} //end function initHandlers

	function initLists() {
		$listsLoadingIndicator = $('<div id="ss_wc_mailchimp_loading_lists" class="woocommerce-mailchimp-loading"><span class="woocommerce-mailchimp-loading-indicator"></span>'+SS_WC_MailChimp_Messages.connecting_to_mailchimp+'</div>');
		$mainList.after($listsLoadingIndicator.hide());

	} //end function initLists

	function initGroups() {
		//return;

		// Reinitialize the <optgroup> elements by splitting out the option names
		var currentGroup = '';
		var lastGroup = '';
		var grouping;
		var $options = $interestGroups.children('option').clone();
		$interestGroups.attr('data-placeholder', SS_WC_MailChimp_Messages.select_groups_placeholder);
		// $interestGroups.children().remove();
		// for (i = 0; i < $options.length; i++) {
		// 	item = $options[i];
		// 	currentGroup = item.text.split(':')[0];
		// 	if (currentGroup !== lastGroup) {
		// 		grouping = $('<optgroup>').attr('label', currentGroup);
		// 		$interestGroups.append(grouping);
		// 	}
		// 	item.text = item.text.split(':')[1];
		// 	grouping.append(item);
		// 	lastGroup = currentGroup;
		// }
		$interestGroups.select2('destroy').select2();
		var groupsMessage = $('#ss-wc-mailchimp-groups-msg').length > 0 ? $('#ss-wc-mailchimp-groups-msg') : $('<div id="ss-wc-mailchimp-groups-msg" style="display: inline-block"/>');
		$interestGroups.after(groupsMessage);
		if ($options.length === 0) {
			groupsMessage.text(SS_WC_MailChimp_Messages.interest_groups_not_enabled);
			$interestGroups.siblings('.select2-container').remove();
			groupsMessage.show();
		} else {
			$interestGroups.siblings('.select2-container').show();
			groupsMessage.hide();
		}

		// Add the loading indicator for groups (set to hidden by default)
		$interestGroupsLoadingIndicator = $('<div id="ss_wc_mailchimp_loading_groups" class="woocommerce-mailchimp-loading"><span class="woocommerce-mailchimp-loading-indicator"></span>'+SS_WC_MailChimp_Messages.connecting_to_mailchimp+'</div>');
		$interestGroups.parent().append($interestGroupsLoadingIndicator.hide());

	} //end function initGroups

	function loadLists(apiKey) {

		/**
	     * Load lists
	     */
	    $mainList.attr('disabled','disabled');
	    $listsLoadingIndicator.show();
        $.post(
            ajaxurl,
            {
                'action': '' + namespace_prefixed('get_lists'),
                'data': { 'api_key': apiKey }
            },
            function(response) {
            	console.log(response);
            	$listsLoadingIndicator.hide();
            	var result = [];

                try {
                    result = $.parseJSON(response);
                } catch (err) {
                    console.error(err);
                    alert(SS_WC_MailChimp_Messages.error_loading_lists);
                }

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
	    $interestGroups.attr('disabled','disabled');

	    $interestGroups.children().remove();

	    $interestGroups.select2().hide();
	    $('#ss-wc-mailchimp-groups-msg').hide();
	    $interestGroupsLoadingIndicator.show();
        $.post(
            ajaxurl,
            {
                'action': '' + namespace_prefixed('get_interest_groups'),
                'data': { 'api_key': apiKey, 'list_id': listId }
            },
            function(response) {
            	console.log(response);
            	$interestGroupsLoadingIndicator.hide();
            	var result = [];

                try {
                    result = $.parseJSON(response);
                } catch (err) {
                    console.error(err);
                    alert(SS_WC_MailChimp_Messages.error_loading_groups);
                }

                if (result.error) {
                	$('#ss-wc-mailchimp-groups-msg').text(result.error).show();
                	$interestGroups.children().remove();
                	$interestGroups.select2('destroy');
                	$interestGroups.hide();
                	return;
                }

                if (result.length === 0) {
                	$('#ss-wc-mailchimp-groups-msg').show();
                	initGroups();
                	return;
                }

                $interestGroups.show();
                $interestGroups.removeAttr('disabled');
            	$interestGroups.children().remove();
            	$.each(result, function(id, grouping) {
            		$interestGroups.append(
            			$('<option></option>')
	                		.attr('value',id)
	                		.text(grouping)); 
                });

             	initGroups();
                //$interestGroups.select2('destroy').select2();
            }
        );

	} //end function loadGroups

	function toggleAllSettings( show_hide ) {
		if (show_hide == 'show') {
			$apiKey.closest('tr').nextAll('tr').fadeIn();
			$apiKey.closest('table').nextAll('h2, .form-table').fadeIn();
		} else {
			$apiKey.closest('tr').nextAll('tr').fadeOut();
			$apiKey.closest('table').nextAll('h2, .form-table').fadeOut();
		}
	}

	function namespace_prefixed( suffix ) {
		return namespace + '_' + suffix;
	}

}(jQuery);