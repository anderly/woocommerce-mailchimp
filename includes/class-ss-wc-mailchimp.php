<?php
/**
 * WooCommerce MailChimp class
 *
 * @copyright   Copyright (c) 2015, Saint Systems, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.0
*/
class SS_WC_MailChimp {

	/**
	 * The ID for this newsletter extension, such as 'mailchimp'
	 */
	public $id = 'mailchimp';

	/**
	 * The label for the extension, probably just shown as the title of the metabox
	 */
	public $label = 'MailChimp';

	/**
	 * Newsletter lists retrieved from the API
	 */
	public $lists;

	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		global $woocommerce;

		$this->mailchimp = null;

		$this->id   = 'mailchimp';
		$this->label = __( 'MailChimp', 'ss_wc_mailchimp' );

		$this->settings_url = admin_url( 'admin.php?page=wc-settings&tab=' . $this->id );

		$this->register_hooks();

	} //end function __construct

	/**
	 * Register plugin hooks
	 *
	 * @access public
	 * @return void
	 */
	public function register_hooks() {

		if ( is_admin() ) {
			// Add the "Settings" links on the Plugins administration screen
			add_filter( 'plugin_action_links_' . WOOCOMMERCE_MAILCHIMP_PLUGIN_NAME, array( $this, 'plugin_settings_link' ) );
			add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_mailchimp_settings' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts') );
			//add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
			//add_filter( 'save_post', array( $this, 'save_metabox' ), 1, 2 );
			//add_action( 'woocommerce_product_write_panel_tabs', array( $this, 'add_mailchimp_product_tab' ) );
			add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_mailchimp_product_data_tab' ) );
			add_action( 'woocommerce_product_data_panels', array( $this, 'add_mailchimp_product_data_fields' ) );
			add_action( 'woocommerce_process_product_meta', array( $this, 'add_mailchimp_product_data_fields_save' ) );
		}

		add_action( 'wp_ajax_woocommerce_mailchimp_get_lists', array( $this, 'ajax_get_lists' ) );
		add_action( 'wp_ajax_woocommerce_mailchimp_get_interest_categories', array( $this, 'ajax_get_interest_categories' ) );

	} //end function ensure_tab

	function add_mailchimp_product_data_fields_save( $post_id ) {

    	// Opening Hours Custom Fields
    	if ( !isset( $_POST['_mailchimp_list'] ) ) return;
        $mailchimp_lists = $_POST['_mailchimp_list'];
    	update_post_meta( $post_id, '_mailchimp_list', $mailchimp_lists );

	}

	/**
	 * Add the MailChimp tab to WooCommerce products
	 * @param [mixed] $product_data_tabs [existing product data tabs array]
	 */
	function add_mailchimp_product_data_tab( $product_data_tabs ) {

		$product_data_tabs['mailchimp'] = array(
			'label'  => __( 'MailChimp', 'ss_wc_mailchimp' ),
			'target' => 'mailchimp_product_data',
			'class'  => array( 'hide_if_grouped' ),
		);

		return $product_data_tabs;
	}

	function add_mailchimp_product_data_fields() {

		global $woocommerce, $post;
		        
		?>
        <div id="mailchimp_product_data" class="panel woocommerce_options_panel">

			<?php

			echo '<div class="options_group">';
                echo '<p>';
                    _e( 'Select the lists you wish customers to be subscribed to when purchasing.', 'ss_wc_mailchimp' );
                echo '</p>';
            echo '</div>';

            $lists = $this->get_lists();

            $saved_lists = get_post_meta( $post->ID, '_mailchimp_list', true );

    		foreach( $lists as $list_id => $list_name ) {

    			echo '<div class="options_group">';

    			woocommerce_wp_checkbox( array( 
					'id'            => '_mailchimp_list[]', 
					'wrapper_class' => 'show_if_simple', 
					'label'         => __( 'MailChimp List', 'ss_wc_mailchimp' ),
					'cbvalue'		=> $list_id,
					'value'			=> in_array( $list_id, $saved_lists ) ? $list_id : null,
					'description'   => $list_name,
					'default'  		=> '0',
					'desc_tip'    	=> false,
				) );

				$interest_categories = $this->get_interest_categories( $list_id );

				$saved_interest_categories = get_post_meta( $post->ID, "_mailchimp_list($list_id)_interest_categories", true );

        		woocommerce_wp_select( array(
					'id'          => "_mailchimp_list($list_id)_interest_categories",
					'label'       => __( 'Interest Categories', 'ss_wc_mailchimp' ),
					'options'     => $interest_categories,
					'desc_tip'    => true,
					'style'       => 'min-width:300px;',
					'class'    	  => 'wc-enhanced-select',
					'description' => __( 'Choose a tax class for this product. Tax classes are used to apply different tax rates specific to certain types of product.', 'ss_wc_mailchimp' ),
					'custom_attributes' => array( 'multiple' => 'multiple' ),
				) );

				echo '</div>';

        	}

        	?>
            
        </div>
		<?php

	} //end function woo_add_mailchimp_fields

	/**
	 * Add Settings link to plugins list
	 *
	 * @param  array $links Plugin links
	 * @return array Modified plugin links
	 */
	public function plugin_settings_link( $links ) {

		$plugin_links = array(
			'<a href="' . $this->settings_url . '">' . __( 'Settings', 'ss_wc_mailchimp' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );

	} //end function plugin_settings_link

	/**
	 * Add the Integration to WooCommerce
	 */
	function add_mailchimp_settings( $settings ) {

		$settings[] = include( WOOCOMMERCE_MAILCHIMP_PLUGIN_PATH . '/includes/class-ss-wc-settings-mailchimp.php' );

		return $settings;

	} //end function add_mailchimp_settings

	/**
     * Load scripts required for admin
     * 
     * @access public
     * @return void
     */
    public function enqueue_scripts() {

    	// Plugin scripts and styles
		wp_register_script( 'woocommerce-mailchimp-admin', WOOCOMMERCE_MAILCHIMP_PLUGIN_URL . '/assets/js/woocommerce-mailchimp-admin.js', array( 'jquery' ), WOOCOMMERCE_MAILCHIMP_VERSION );
		wp_register_style( 'woocommerce-mailchimp', WOOCOMMERCE_MAILCHIMP_PLUGIN_URL . '/assets/css/style.css', array(), WOOCOMMERCE_MAILCHIMP_VERSION );

        // Localize javascript messages
		$translation_array = array(
			'connecting_to_mailchimp' 		=> __( 'Connecting to MailChimp', 'ss_wc_mailchimp' ),
			'error_loading_lists' 			=> __( 'Error loading lists. Please check your api key.', 'ss_wc_mailchimp' ),
			'error_loading_groups' 			=> __( 'Error loading groups. Please check your MailChimp Interest Groups for the selected list.', 'ss_wc_mailchimp' ),
			'select_groups_placeholder'		=> __( 'Select one or more groups (optional)', 'ss_wc_mailchimp' ),
			'interest_groups_not_enabled' 	=> __( 'This list does not have interest groups enabled', 'ss_wc_mailchimp' ),
		);
		wp_localize_script( 'woocommerce-mailchimp-admin', 'SS_WC_MailChimp_Messages', $translation_array );

        // Scripts
		wp_enqueue_script( 'woocommerce-mailchimp-admin' );

		// Styles
		wp_enqueue_style( 'woocommerce-mailchimp' );

	} //end function enqueue_scripts

	/**
     * Return all lists from MailChimp to be used in select fields
     * 
     * @access public
     * @return array
     */
    public function ajax_get_lists() {

        try {

        	if ( !$_POST['data']['api_key'] || empty( $_POST['data']['api_key'] ) ) {

        		return $this->toJSON( array( '' => __( 'Enter your api key above to see your lists', 'ss_wc_mailchimp' ) ) );

        	}

        	if ( !$this->load_mailchimp( $_POST['data']['api_key'] ) ) {

        		throw new Exception( __( 'Unable to load mailchimp api object.', 'ss_wc_mailchimp' ) );

        	}

            if ( !$this->mailchimp ) {

                throw new Exception( __( 'Unable to load lists', 'ss_wc_mailchimp' ) );

            }

            $lists = $this->get_lists();

			// if ( count( $lists < 1 ) ) {

//                 throw new Exception(__('No lists found', 'ss_wc_mailchimp'));

//             }

            $results = array_merge( array('' => 'Select a list...'), $lists );

        }
        catch ( Exception $e ) {

            return $this->toJSON( array( 'error' => $e->getMessage() ) );

        }

        return $this->toJSON( $results );

    } //end function ajax_get_lists

    /**
	 * Retrieves the lists from MailChimp
	 */
	public function get_lists() {

		if ( !$this->load_mailchimp( ) ) {

    		throw new Exception( __( 'Unable to load mailchimp api object.', 'ss_wc_mailchimp' ) );

    	}

        if ( !$this->mailchimp ) {

            throw new Exception( __( 'Unable to load lists', 'ss_wc_mailchimp' ) );

        }

		$lists = get_transient( 'ss_wc_mc_lists' );
		if ( false === $lists ) {

			$lists = $this->mailchimp->get_lists();

			set_transient( 'ss_wc_mc_lists', $lists, 24*24*24 );
		}

		if ( ! empty( $lists ) ) {
			$this->lists = $lists;
		}

		return (array) $this->lists;
	}

	/**
	 * Retrieves the interest categories from MailChimp
	 */
	public function get_interest_categories( $list_id ) {

		if ( !$this->load_mailchimp( ) ) {

    		throw new Exception( __( 'Unable to load mailchimp api object.', 'ss_wc_mailchimp' ) );

    	}

        if ( !$this->mailchimp ) {

            throw new Exception( __( 'Unable to load lists', 'ss_wc_mailchimp' ) );

        }

		$interest_categories = get_transient( 'ss_wc_mc_interest_categories_' . $list_id );
		if ( false === $interest_categories ) {

			$interest_categories = $this->mailchimp->get_interest_category_with_interests( $list_id );

			set_transient( 'ss_wc_mc_interest_categories_' . $list_id, $interest_categories, 24*24*24 );
		}

		return $interest_categories;
	}

    /**
     * Return interest categories for the passed MailChimp List to be used in select fields
     * 
     * @access public
     * @return array
     */
    public function ajax_get_interest_categories() {

        try {

        	if ( !$_POST['data']['api_key'] || empty( $_POST['data']['api_key'] ) ) {

        		return $this->toJSON( array( '' => __( 'Enter your api key above to see your lists', 'ss_wc_mailchimp' ) ) );

        	}

        	if ( !$_POST['data']['list_id'] || empty( $_POST['data']['list_id'] ) ) {

        		return $this->toJSON( array( '' => __( 'Please select a list from above.', 'ss_wc_mailchimp' ) ) );

        	}

        	if ( !$this->load_mailchimp( $_POST['data']['api_key'] ) ) {

        		throw new Exception( __( 'Unable to load mailchimp api object.', 'ss_wc_mailchimp' ) );

        	}

            if ( !$this->mailchimp ) {

                throw new Exception( __( 'Unable to load groups', 'ss_wc_mailchimp' ) );

            }

            $interest_categories = $this->get_interest_categories( $_POST['data']['list_id'] );

            // if ( count( $interest_categories ) < 1 ) {

            //     throw new Exception( __( 'No interest categories found', 'ss_wc_mailchimp' ) );

            // }

            $results = $interest_categories;

        }
        catch ( Exception $e ) {

            return $this->toJSON( array( 'error' => $e->getMessage() ) );

        }

        return $this->toJSON( $results );

    } //end function ajax_get_interest_categories

    /**
     * Return merge vars for the passed MailChimp List
     * 
     * @access public
     * @return array
     */
    public function ajax_get_merge_vars() {

        try {

        	if ( !$_POST['data']['api_key'] || empty( $_POST['data']['api_key'] ) ) {

        		return $this->toJSON( array( '' => __( 'Enter your api key above to see your lists', 'ss_wc_mailchimp' ) ) );

        	}

        	if ( !$_POST['data']['list_id'] || empty( $_POST['data']['list_id'] ) ) {

        		return $this->toJSON( array( '' => __( 'Please select a list from above.', 'ss_wc_mailchimp' ) ) );

        	}

        	if ( !$this->load_mailchimp( $_POST['data']['api_key'] ) ) {

        		throw new Exception( __( 'Unable to load mailchimp api object.', 'ss_wc_mailchimp' ) );

        	}

            if ( !$this->mailchimp ) {

                throw new Exception( __( 'Unable to load merge vars', 'ss_wc_mailchimp' ) );

            }

            $groups = $this->mailchimp->get_merge_vars( $_POST['data']['list_id'] );

            if ( count( $groups ) < 1 ) {

                throw new Exception( __( 'No merge vars found', 'ss_wc_mailchimp' ) );

            }

            $results = $groups;

        }
        catch ( Exception $e ) {

            return $this->toJSON( array( 'error' => $e->getMessage() ) );

        }

        return $this->toJSON( $results );

    } //end function get_merge_vars

    function toJSON( $response ) {

    	// Commented out due to json_encode not preserving quotes around MailChimp ids
    	// header('Content-Type: application/json');
    	echo json_encode( $response );
        exit();

    } //end function toJSON

    /**
     * Load MailChimp object
     * 
     * @access public
     * @return mixed
     */
    public function load_mailchimp( $api_key = null ) {

    	if ( !$api_key ) {

    		$api_key = get_option( 'woocommerce_mailchimp_api_key' );

    	}

        if ( $this->mailchimp && $this->mailchimp->api_key == $api_key ) {

            return true;

        }

        if ( empty( $api_key ) || $api_key == null ) return false;

        // Load MailChimp class if not yet loaded
        if ( !class_exists( 'SS_MailChimp_API' ) ) {

            require_once WOOCOMMERCE_MAILCHIMP_PLUGIN_PATH . '/includes/class-ss-mailchimp-api.php';

        }

        try {

        	$this->mailchimp = new SS_MailChimp_API( $api_key );

        	return true;

        } catch ( Exception $e ) {

            return false;

        }

    } //end function load_mailchimp

    /**
     * Return whether or not we have an api key
     * 
     * @access public
     * @return bool
     */
    public function has_api_key() {

    	$api_key = get_option( 'woocommerce_mailchimp_api_key' );

    	if ( $api_key ) {

    		return true;

    	}

    	return false;

    } //end function has_api_key

//       /**
	//  * Register the metabox on the 'download' post type
	//  */
	// public function add_metabox() {
	// 	if ( current_user_can( 'edit_product', get_the_ID() ) ) {
	// 		add_meta_box( 'ss_wc_mc_' . $this->id, $this->label, array( $this, 'render_metabox' ), 'product', 'side' );
	// 	}
	// }

	// /**
	//  * Display the metabox, which is a list of newsletter lists
	//  */
	// public function render_metabox() {

	// 	global $post;

	// 	echo '<p>' . __( 'Select the lists you wish customers to be subscribed to when purchasing.', 'ss_wc_mailchimp' ) . '</p>';

	// 	$checked = (array) get_post_meta( $post->ID, '_ss_wc_mc_' . esc_attr( $this->id ), true );
	// 	foreach( $this->get_lists() as $list_id => $list_name ) {
	// 		echo '<label>';
	// 			echo '<input type="checkbox" name="_ss_wc_mc_' . esc_attr( $this->id ) . '[]" value="' . esc_attr( $list_id ) . '"' . checked( true, in_array( $list_id, $checked ), false ) . '>';
	// 			echo '&nbsp;' . $list_name;
	// 		echo '</label><br/>';

	// 		$interest_categories = $this->get_interest_categories( $list_id );
	// 		if ( ! empty( $interest_categories ) ) {
	// 			foreach ( $interest_categories as $id => $name ){
	// 				echo '<label>';
	// 					echo '&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="_ss_wc_mc_' . esc_attr( $this->id ) . '[]" value="' . esc_attr( $id ) . '"' . checked( true, in_array( $id, $checked ), false ) . '>';
	// 					echo '&nbsp;' . $name;
	// 				echo '</label><br/>';
	// 			}
	// 		}
	// 	}
	// }

	// /**
	//  * Save the metabox
	//  */
	// public function save_metabox( $post_id, $post  ) {

	// 	// $fields[] = '_edd_' . esc_attr( $this->id );
	// 	// return $fields;
	// 	// 
	// 	// Is the user allowed to edit the post or page?
	// 	if ( !current_user_can( 'edit_product', $post->ID ))
	// 		return $post->ID;

	// 	// OK, we're authenticated: we need to find and save the data
	// 	// We'll put it into an array to make it easier to loop though.

	// 	$events_meta['_location'] = $_POST['_location'];

	// 	// Add values of $events_meta as custom fields

	// 	foreach ($events_meta as $key => $value) { // Cycle through the $events_meta array!
	// 		if( $post->post_type == 'revision' ) return; // Don't store custom data twice
	// 		$value = implode(',', (array)$value); // If $value is an array, make it a CSV (unlikely)
	// 		if(get_post_meta($post->ID, $key, FALSE)) { // If the custom field already has a value
	// 			update_post_meta($post->ID, $key, $value);
	// 		} else { // If the custom field doesn't have a value
	// 			add_post_meta($post->ID, $key, $value);
	// 		}
	// 		if(!$value) delete_post_meta($post->ID, $key); // Delete if blank
	// 	}
	// }

} //end class SS_WC_Settings_MailChimp