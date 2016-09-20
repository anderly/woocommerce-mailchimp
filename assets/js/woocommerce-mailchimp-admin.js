/**
 * WooCommerce MailChimp Plugin
 */
var SS_WC_MailChimp = function($) {

	var $enabled;
	var $apiKey;
	var $accountLoadingIndicator;
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
		checkApiKey: checkApiKey,
		loadLists: loadLists,
		loadGroups: loadGroups,
	};

	function init() {

		initHandles();
		initAccount();
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
		$mainList.closest('tr').hide();
			
		if ($mainList.val() === '') {
			$interestGroups.attr('disabled','disabled');
		}

		$apiKey.change(function() {
			checkApiKey($apiKey.val(), true);
		});
		checkApiKey($apiKey.val(), false)

		$mainList.change(function() {
			if ($mainList.val()) {
				$interestGroups.removeAttr('disabled');
			} else {
				$interestGroups.children().remove();
				$interestGroups.attr('disabled','disabled');
			}
		});

		$apiKey.on('paste cut', function() {
			// Short pause to wait for paste to complete
			setTimeout( function() {
				$apiKey.change();
				$apiKey.blur();
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
			if ( '' === $apiKey.val() ) return;

			switch ($displayOptIn.val()) {
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

	function initAccount() {
		$accountLoadingIndicator = $('<div id="ss_wc_mailchimp_loading_account" class="woocommerce-mailchimp-loading"><span id="woocommerce_mailchimp_account_indicator" class="woocommerce-mailchimp-loading-indicator"></span></div>');
		$apiKey.after($accountLoadingIndicator.hide());

	} //end function initAccount

	function initLists() {
		$listsLoadingIndicator = $('<div id="ss_wc_mailchimp_loading_lists" class="woocommerce-mailchimp-loading"><span class="woocommerce-mailchimp-loading-indicator">&nbsp;'+SS_WC_MailChimp_Messages.connecting_to_mailchimp+'</span></div>');
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
		$interestGroupsLoadingIndicator = $('<div id="ss_wc_mailchimp_loading_groups" class="woocommerce-mailchimp-loading"><span class="woocommerce-mailchimp-loading-indicator">&nbsp;'+SS_WC_MailChimp_Messages.connecting_to_mailchimp+'</span></div>');
		$interestGroups.parent().append($interestGroupsLoadingIndicator.hide());

	} //end function initGroups

	function checkApiKey(apiKey, shouldLoadLists = false) {

		if ( $apiKey.val() === '' ) {
			toggleAllSettings('hide');
		} else {
			toggleAllSettings('show');
		}

		if (apiKey === '') return;

		/**
		 * Load account
		 */
		$mainList.attr('disabled','disabled');
		$accountLoadingIndicator.show();
		$accountIndicator = $accountLoadingIndicator.children().first();
		$accountIndicator.removeClass('success').removeClass('error');
		$accountIndicator.addClass('loading');
		$accountIndicator.html('&nbsp;'+SS_WC_MailChimp_Messages.connecting_to_mailchimp);
		$.post(
			ajaxurl,
			{
				'action': '' + namespace_prefixed('get_account'),
				'data': { 'api_key': apiKey }
			},
			function(response) {
				console.log(response);
				$accountIndicator.removeClass('loading');
				var result = [];

				try {
					result = $.parseJSON(response);
				} catch (err) {
					console.error(err);
					$accountIndicator.addClass('error');
					$accountIndicator.html('&nbsp;'+SS_WC_MailChimp_Messages.error_loading_account);
					return;
				}

				if ( result.error ) {
					$accountIndicator.addClass('error');
					$accountIndicator.html(result.error);
					return;
				}

				if ( ! result.account_id ) {
					$accountIndicator.addClass('error');
					$accountIndicator.html('&nbsp;'+SS_WC_MailChimp_Messages.error_loading_account);
					return;
				}

				$accountIndicator.addClass('success');
				$mainList.removeAttr('disabled');
				$accountIndicator.html('');

				// API Key looks good. Let's load the lists.
				if ( shouldLoadLists ) {
					loadLists( apiKey );
				}
			}
		);

	} //end function checkApiKey

	function loadLists(apiKey) {

		/**
		 * Load lists
		 */
		$mainList.attr('disabled','disabled');
		$listsLoadingIndicator.show();
		$listsIndicator = $listsLoadingIndicator.children().first();
		$listsIndicator.addClass('loading');
		$.post(
			ajaxurl,
			{
				'action': '' + namespace_prefixed('get_lists'),
				'data': { 'api_key': apiKey }
			},
			function(response) {
				console.log(response);
				$listsLoadingIndicator.hide();
				$listsIndicator.removeClass('loading');
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
		$interestGroupsIndicator = $interestGroupsLoadingIndicator.children().first();
		$interestGroupsIndicator.addClass('loading');
		$.post(
			ajaxurl,
			{
				'action': '' + namespace_prefixed('get_interest_groups'),
				'data': { 'api_key': apiKey, 'list_id': listId }
			},
			function(response) {
				console.log(response);
				$interestGroupsLoadingIndicator.hide();
				$interestGroupsIndicator.removeClass('loading');
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