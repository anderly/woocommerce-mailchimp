( function () {
	'use strict';

	var el = window.wp.element.createElement;
	var useState = window.wp.element.useState;
	var useEffect = window.wp.element.useEffect;
	var useCallback = window.wp.element.useCallback;
	var registerPlugin = window.wp.plugins.registerPlugin;
	var ExperimentalOrderMeta = window.wc.blocksCheckout.ExperimentalOrderMeta;
	var getSetting = window.wc.wcSettings.getSetting;
	var __ = window.wp.i18n.__;

	var settings = getSetting( 'woocommerce-mailchimp_data', {} );

	/**
	 * SVG checkmark matching WooCommerce's native block checkout checkbox.
	 */
	var CheckMark = function () {
		return el( 'svg', {
			className: 'wc-block-components-checkbox__mark',
			'aria-hidden': 'true',
			viewBox: '0 0 24 20',
		}, el( 'path', {
			d: 'M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z',
		} ) );
	};

	/**
	 * Opt-in checkbox rendered inside the block-based checkout.
	 *
	 * Uses the same markup structure as WooCommerce's native
	 * `wc-block-components-checkbox` so it inherits checkout styling.
	 *
	 * ExperimentalOrderMeta passes `checkoutExtensionData` as a prop
	 * to its direct children, giving us access to `setExtensionData`.
	 */
	var MailchimpOptIn = function ( props ) {
		var checkoutExtensionData = props.checkoutExtensionData;

		var defaultChecked = settings.optInDefaultStatus === 'checked';
		var result = useState( defaultChecked );
		var isChecked = result[0];
		var setIsChecked = result[1];

		var setExtensionData =
			checkoutExtensionData && checkoutExtensionData.setExtensionData
				? checkoutExtensionData.setExtensionData
				: null;

		useEffect( function () {
			if ( setExtensionData ) {
				setExtensionData(
					'woocommerce-mailchimp',
					'ss_wc_mailchimp_opt_in',
					isChecked
				);
			}
		}, [ isChecked, setExtensionData ] );

		var onChange = useCallback( function () {
			setIsChecked( function ( prev ) {
				return ! prev;
			} );
		}, [] );

		if ( ! settings.displayOptIn ) {
			return null;
		}

		var label = settings.optInLabel || __( 'Subscribe to our newsletter', 'woocommerce-mailchimp' );

		return el(
			'div',
			{ className: 'wc-block-components-mailchimp-opt-in', style: { padding: '16px' } },
			el( 'div', { className: 'wc-block-components-checkbox' },
				el( 'label', { htmlFor: 'ss_wc_mailchimp_opt_in' },
					el( 'input', {
						id: 'ss_wc_mailchimp_opt_in',
						className: 'wc-block-components-checkbox__input',
						type: 'checkbox',
						'aria-invalid': 'false',
						checked: isChecked,
						onChange: onChange,
					} ),
					el( CheckMark ),
					el( 'span', {
						className: 'wc-block-components-checkbox__label',
					}, label )
				)
			)
		);
	};

	var render = function () {
		return el( ExperimentalOrderMeta, null, el( MailchimpOptIn ) );
	};

	registerPlugin( 'woocommerce-mailchimp', {
		render: render,
		scope: 'woocommerce-checkout',
	} );
} )();
