/**
 * WooCommerce MailChimp Plugin
 */
var SS_WC_MailChimp = function($) {

	var enabled;
	var api_key;
	var main_list;
	var groups;

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

	}

	function initHandlers() {
		enabled = $('#woocommerce_mailchimp_enabled');
		api_key = $('#woocommerce_mailchimp_api_key');
		main_list = $('#woocommerce_mailchimp_main_list');
		groups = $('#woocommerce_mailchimp_groups');

		api_key.closest('tr').hide();
		main_list.closest('tr').hide();
			
		if (main_list.val() === '') {
			groups.attr('disabled','disabled');
		}
		enabled.change(function() {
			if ( enabled.prop('checked') === true ) {
				api_key.closest('tr').fadeIn();
				main_list.closest('tr').fadeIn();
				groups.closest('tr').fadeIn();
			}
			else {
				api_key.closest('tr').fadeOut();
				main_list.closest('tr').fadeOut();
				groups.closest('tr').fadeOut();
			}
		}).change();

		main_list.change(function() {
			if (main_list.val()) {
				groups.removeAttr('disabled');
			} else {
				groups.children().remove();
				groups.attr('disabled','disabled');
			}
		});

		api_key.on('blur', function(e) {
			if (api_key.val() === '') {
				loadLists(api_key.val());
			}
		});

		api_key.on('paste', function() {

			// Short pause to wait for paste to complete
	        setTimeout( function() {
				loadLists(api_key.val());
		    }, 100);
	    });

	    main_list.on('change', function() {
			if (main_list.val() !== '') {
				loadGroups(api_key.val(), main_list.val());
			}
		});

	} //end function initHandlers

	function initLists() {
		var listsLoadingIndicator = $('<div id="ss_wc_mailchimp_loading_lists" style="display:inline-block;"><img src="/wp-content/plugins/woocommerce-mailchimp/assets/img/loading.gif" style="margin-bottom: -2px;margin-left: 2px;" /> ' + SS_WC_MailChimp_Messages.connecting_to_mailchimp + '</div>');
		main_list.after(listsLoadingIndicator.hide());

	} //end function initLists

	function initGroups() {
		// Reinitialize the <optgroup> elements by splitting out the option names
		var currentGroup = '';
		var lastGroup = '';
		var grouping;
		var $options = groups.children('option').clone();
		groups.children().remove();
		for (i = 0; i < $options.length; i++) {
			item = $options[i];
			currentGroup = item.text.split(':')[0];
			if (currentGroup !== lastGroup) {
				grouping = $('<optgroup>').attr('label', currentGroup);
				groups.append(grouping);
			}
			item.text = item.text.split(':')[1];
			grouping.append(item);
			lastGroup = currentGroup;
		}
		groups.chosen({ placeholder_text_multiple: SS_WC_MailChimp_Messages.select_groups_placeholder });
		var groupsMessage = $('<div id="ss-wc-mailchimp-groups-msg" style="display: inline-block"/>');
		groups.after(groupsMessage);
		if ($options.length === 0) {
			groupsMessage.text(SS_WC_MailChimp_Messages.interest_groups_not_enabled);
			groups.siblings('.chosen-container').hide();
			groupsMessage.show();
		} else {
			groups.siblings('.chosen-container').show();
			groupsMessage.hide();
		}
		

		// Add the loading indicator for groups (set to hidden by default)
		var groupsLoadingIndicator = $('<div id="ss_wc_mailchimp_loading_groups" style="display:inline-block;vertical-align:top;margin-top:5px;"><img src="/wp-content/plugins/woocommerce-mailchimp/assets/img/loading.gif" style="margin-bottom: -2px;margin-left: 2px;" /> ' + SS_WC_MailChimp_Messages.connecting_to_mailchimp + '</div>');
		groups.siblings('.chosen-container')
			.after(groupsLoadingIndicator.hide());

	} //end function reInitGroups

	function loadLists(apiKey) {

		/**
	     * Load service status
	     */
	    main_list.attr('disabled','disabled');
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
                	main_list.children().remove();
                	$.each(result, function(key, val) {   
                		main_list	
	                		.append($('<option></option>')
	                			.attr('value',key)
	                			.text(val)); 
                	});
                }
                main_list.removeAttr('disabled');
            }
        );
	}

	function loadGroups(apiKey, listId) {
		/**
	     * Load service status
	     */
	    groups.attr('disabled','disabled');
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
                	groups.children().remove();
                	groups.siblings('.chosen-container').hide();
                	groups.trigger("chosen:updated");
                	return;
                }

                groups.siblings('.chosen-container').show();
                $('#ss-wc-mailchimp-groups-msg').hide();
                if (result.length === 0) {
                	$('#ss-wc-mailchimp-groups-msg').show();
                } 
                groups.siblings('.chosen-container').show();
            	groups.children().remove();
            	$.each(result, function(i, grouping) { 
            		$grouping = $('<optgroup>');
            			groups	
	                	.append($grouping
	                		.attr('label', grouping.name));
            		$.each(grouping.groups, function(j, group) {
                		$grouping	
	                		.append($('<option></option>')
	                			.attr('value',grouping.name + ':' + group.name)
	                			.text(group.name)); 
                	});
                });
                groups.removeAttr('disabled');
                groups.trigger('chosen:updated');
            }
        );
	}

}(jQuery);