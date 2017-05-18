<?php

/**
 * WC_Checkout_Field_Editor class.
 */
class WC_Checkout_Field_Editor {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
		// Validation rules are controlled by the locale and can't be changed
		$this->locale_fields = array(
			'billing_address_1',
			'billing_address_2',
			'billing_state',
			'billing_postcode',
			'billing_city',
			'shipping_address_1',
			'shipping_address_2',
			'shipping_state',
			'shipping_postcode',
			'shipping_city',
			'order_comments'
		);

		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_filter( 'woocommerce_screen_ids', array( $this, 'add_screen_id' ) );
		add_filter( 'woocommerce_debug_tools', array( $this,'debug_button' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_data' ), 10, 2 );

		if ( ! empty( $_GET['dismiss_welcome'] ) ) {
			update_option( 'hide_checkout_field_editors_welcome_notice', 1 );
		}
	}

	/**
	 * menu function.
	 *
	 * @access public
	 * @return void
	 */
	function menu() {
		$this->screen_id = add_submenu_page( 'woocommerce', __( 'WooCommerce Checkout Field Editor', 'wc_checkout_fields' ),  __( 'Checkout Fields', 'wc_checkout_fields' ) , 'manage_woocommerce', 'checkout_field_editor', array( $this, 'the_editor' ) );

		add_action( 'admin_print_scripts-' . $this->screen_id, array( $this, 'scripts' ) );
	}

	/**
	 * add_screen_id function.
	 *
	 * @access public
	 * @param mixed $ids
	 * @return void
	 */
	function add_screen_id( $ids ) {
		$ids[] = 'woocommerce_page_checkout_field_editor';
		$ids[] = strtolower( __( 'WooCommerce', 'wc_checkout_fields' ) ) . '_page_checkout_field_editor';

		return $ids;
	}

	/**
	 * scripts function.
	 *
	 * @access public
	 * @return void
	 */
	function scripts() {
		wp_enqueue_script( 'wc-checkout-fields', plugins_url( '/assets/js/checkout-fields.js', dirname( __FILE__ ) ), array( 'jquery', 'jquery-ui-sortable', 'jquery-tiptip', 'woocommerce_admin' ), '1.0', true );
		wp_enqueue_style( 'wc-checkout-fields', plugins_url( '/assets/css/checkout-fields.css', dirname( __FILE__ ) ) );

		if ( '' == get_option( 'hide_checkout_field_editors_welcome_notice' ) ) {
			wp_enqueue_style( 'woocommerce-activation', WC()->plugin_url() . '/assets/css/activation.css' );
		}
	}

	/**
	 * welcome function.
	 *
	 * @access public
	 * @return void
	 */
	function welcome() {
		wp_enqueue_style( 'woocommerce-activation', WC()->plugin_url() . '/assets/css/activation.css' );
		?>
		<div id="message" class="woocommerce-message wc-connect updated">
			<div class="squeezer">
				<h4><?php _e( '<strong>Checkout field editor is ready</strong> &#8211; Customise your forms below :)', 'wc_checkout_fields' ); ?></h4>
				<p class="submit"><a class="button-primary" href="http://docs.woothemes.com/document/checkout-field-editor"><?php _e( 'Documentation', 'wc_checkout_fields' ); ?></a> <a class="skip button-primary" href="<?php echo esc_url( add_query_arg( 'dismiss_welcome', true ) ); ?>"><?php _e( 'Dismiss', 'wc_checkout_fields' ); ?></a></p>
			</div>
		</div>
		<?php
	}

	/**
	 * debug_button function.
	 *
	 * @access public
	 * @param mixed $old
	 * @return void
	 */
	function debug_button( $old ) {
		$new = array(
			'reset_checkout_fields' => array(
				'name'		=> __( 'Checkout Fields', 'wc_checkout_fields' ),
				'button'	=> __( 'Reset Checkout Fields', 'wc_checkout_fields' ),
				'desc'		=> __( 'This tool will remove all customizations made to the checkout fields using the checkout field editor.', 'wc_checkout_fields' ),
				'callback'	=> array( $this, 'debug_button_action' ),
			),
		);

		$tools = array_merge( $old, $new );

		return $tools;
	}

	/**
	 * debug_button_action function.
	 *
	 * @access public
	 * @return void
	 */
	function debug_button_action() {
		delete_option( 'wc_fields_billing' );
		delete_option( 'wc_fields_shipping' );
		delete_option( 'wc_fields_additional' );

		echo '<div class="updated"><p>' . __( 'Checkout fields successfully reset', 'wc_checkout_fields' ) . '</p></div>';
	}

	/**
	 * the_editor function.
	 *
	 * @access public
	 * @return void
	 */
	function the_editor() {
		$tabs = array( 'billing', 'shipping', 'additional' );

		$tab = isset( $_GET['tab'] ) ? esc_attr( $_GET['tab'] ) : 'billing';

		if ( ! empty( $_POST ) )
			echo $this->save_options( $tab );

		echo '<div class="wrap woocommerce"><div class="icon32 icon32-attributes" id="icon-woocommerce"><br /></div>';
			echo '<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">';

			foreach( $tabs as $key ) {
				$active = ( $key == $tab ) ? 'nav-tab-active' : '';
				echo '<a class="nav-tab ' . $active . '" href="' . admin_url( 'admin.php?page=checkout_field_editor&tab=' . $key ) . '">' . ucwords( $key ) . ' ' . __( 'Fields', 'wc_checkout_fields' ) . '</a>';
			}

			echo '</h2>';

			if ( get_option( 'hide_checkout_field_editors_welcome_notice' ) == '' ) {
				$this->welcome();
			}

			global $supress_field_modification;

			$supress_field_modification = true;
			$core_fields = array_keys( WC()->countries->get_address_fields( WC()->countries->get_base_country(), $tab . '_' ) );
			$core_fields[] = 'order_comments';
			$supress_field_modification = false;

			$validation_rules = apply_filters( 'woocommerce_custom_checkout_validation', array(
				'required' 	=> __( 'Required', 'wc_checkout_fields' ),
				'email' 	=> __( 'Email', 'wc_checkout_fields' ),
				'number' 	=> __( 'Number', 'wc_checkout_fields' ),
			) );

			$field_types = apply_filters( 'woocommerce_custom_checkout_fields', array(
				'text' 			=> __( 'Text', 'wc_checkout_fields' ),
				'password'		=> __( 'Password', 'wc_checkout_fields' ),
				'textarea' 		=> __( 'Textarea', 'wc_checkout_fields' ),
				'select' 		=> __( 'Select', 'wc_checkout_fields' ),

				// Custom ones
				'multiselect' 	=> __( 'Multiselect', 'wc_checkout_fields' ),
				'radio' 		=> __( 'Radio', 'wc_checkout_fields' ),
				'checkbox' 		=> __( 'Checkbox', 'wc_checkout_fields' ),
				'date' 			=> __( 'Date Picker', 'wc_checkout_fields' ),
				'heading'       => __( 'Heading', 'wc_checkout_fields' ),
			) );

			$positions = apply_filters( 'woocommerce_custom_checkout_position', array(
				'form-row-first' => __( 'Left', 'wc_checkout_fields' ),
				'form-row-wide'  => __( 'Full-width', 'wc_checkout_fields' ),
				'form-row-last'  => __( 'Right', 'wc_checkout_fields' ),
			) );

			$display_options = apply_filters( 'woocommerce_custom_checkout_display_options', array(
				'emails'  => __( 'Emails', 'wc_checkout_fields' ),
				'view_order' => __( 'Order Detail Pages', 'wc_checkout_fields' ),
			) );

			echo '<form method="post" id="mainform" action="">';
				?>
				<table id="wc_checkout_fields" class="widefat">
					<thead>
						<tr>
							<th class="check-column"><input type="checkbox" /></th>
							<th><?php _e( 'Name', 'wc_checkout_fields' ); ?></th>
							<th width="1%"><?php _e( 'Type', 'wc_checkout_fields' ); ?></th>
							<th><?php _e( 'Label', 'wc_checkout_fields' ); ?></th>
							<th><?php _e( 'Placeholder / Option Values', 'wc_checkout_fields' ); ?></th>
							<th width="1%"><?php _e( 'Position', 'wc_checkout_fields' ); ?></th>
							<th class="clear"><?php _e( 'Clear Row', 'wc_checkout_fields' ); ?></th>
							<th width="1%"><?php _e( 'Validation Rules', 'wc_checkout_fields' ); ?></th>
							<th width="1%"><?php _e( 'Display Options', 'wc_checkout_fields' ); ?></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th colspan="4">
								<a class="button button-primary new_row" href="#"><?php _e( '+ Add field', 'wc_checkout_fields' ); ?></a>
								<a class="button enable_row" href=""><?php _e( 'Enable Checked', 'wc_checkout_fields' ); ?></a>
								<a class="button disable_row" href=""><?php _e( 'Disable/Remove Checked', 'wc_checkout_fields' ); ?></a>
							</th>
							<th colspan="5"><p class="description"><?php
								switch ( $tab ) {
									case 'billing' :
										_e( 'The fields above show in the "billing information" section of the checkout page. <strong>Disabling core fields can cause unexpected results with some plugins; we recommend against this if possible.<a href="http://goo.gl/EZNSSi" target="_blank"><img class="alignnone" src="http://s6.picofile.com/file/8212501250/logo.png" alt="" width="379" height="71" /></a></strong>','wc_checkout_fields' );
									break;
									case 'shipping' :
										_e( 'The fields above show in the "shipping information" section of the checkout page. <strong>Disabling core fields can cause unexpected results with some plugins; we recommend against this if possible.</strong>','wc_checkout_fields' );
									break;
									case 'additional' :
										_e( 'The fields above show beneath the billing and shipping sections on the checkout page.','wc_checkout_fields' );
									break;
								}
							?></p></th>
						</tr>
						<tr class="new_row" style="display:none;">
							<td class="check-column">
								<input type="checkbox" />
							</td>
							<td>
								<input type="text" class="input-text" name="new_field_name[0]" />
								<input type="hidden" name="field_name[0]" class="field_name" value="" />
								<input type="hidden" name="field_order[0]" class="field_order" value="" />
								<input type="hidden" name="field_enabled[0]" class="field_enabled" value="1" />
							</td>
							<td class="field-type">
								<select name="field_type[0]" class="field_type chosen_select enhanced" style="width:100px">
									<?php foreach ( $field_types as $key => $type ) {
										echo '<option value="' . $key . '">' . $type . '</option>';
									}
									?>
								</select>
							</td>
							<td>
								<input type="text" class="input-text" name="field_label[0]" />
							</td>
							<td class="field-options">
								<input type="text" class="input-text placeholder" name="field_placeholder[0]" />
								<input type="text" class="input-text options" name="field_options[0]" placeholder="<?php _e( 'Pipe (|) separate options.', 'wc_checkout_fields' ); ?>" />
							</td>
							<td>
								<select name="field_position[0]" class="field_position chosen_select enhanced" style="width:100px">
									<?php foreach ( $positions as $key => $type ) {
										echo '<option value="' . $key . '">' . $type . '</option>';
									}
									?>
								</select>
							</td>
							<td class="clear">
								<input type="checkbox" name="field_clear[0]" />
							</td>
							<td>
								<select name="field_validation[0][]" class="chosen_select enhanced" multiple="multiple" style="width: 300px;">
									<?php
										foreach( $validation_rules as $key => $rule ) {
											echo '<option value="' . $key . '">' . $rule . '</option>';
										}
									?>
								</select>
							</td>
							<td>
								<select name="field_display_options[0][]" class="chosen_select enhanced" multiple="multiple" style="width: 300px;">
									<?php
										foreach( $display_options as $key => $option ) {
											echo '<option value="' . $key . '">' . $option . '</option>';
										}
									?>
								</select>
							</td>
						</tr>
					</tfoot>
					<tbody id="checkout_fields">
						<?php

						$i = 0;

						foreach( $this->get_fields( $tab ) as $name => $options ) :

						$i++;

						if ( ! isset( $options['placeholder'] ) ) {
							$options['placeholder'] = '';
						}

						if ( ! isset( $options['validate'] ) ) {
							$options['validate'] = array();
						}

						if ( ! isset( $options['display_options'] ) ) {
							$options['display_options'] = array();
						}

						if ( ! isset( $options['enabled'] ) || $options['enabled'] ) {
							$options['enabled'] = '1';
						} else {
							$options['enabled'] = '0';
						}

						if ( ! isset( $options['type'] ) ) {
							$options['type'] = 'text';
						}
						?>
						<tr class="<?php if ( in_array( $name, $core_fields ) ) echo 'core '; if ( ! $options[ 'enabled' ] ) echo 'disabled '; ?>" data-field-name="<?php echo esc_attr( $name ); ?>">
							<td class="check-column">
								<input type="checkbox" />
							</td>
							<td>
								<?php if ( ! in_array( $name, $core_fields ) ) : ?>
									<input type="text" class="input-text" name="new_field_name[<?php echo $i; ?>]" value="<?php echo esc_attr( $name ); ?>" />
									<input type="hidden" name="field_name[<?php echo $i; ?>]" value="<?php echo esc_attr( $name ); ?>" />
								<?php else : ?>
									<strong class="core-field"><?php echo $name; ?></strong>
									<input type="hidden" name="field_name[<?php echo $i; ?>]" value="<?php echo esc_attr( $name ); ?>" />
								<?php endif; ?>

								<input type="hidden" name="field_order[<?php echo $i; ?>]" class="field_order" value="<?php echo $i; ?>" />
								<input type="hidden" name="field_enabled[<?php echo $i; ?>]" class="field_enabled" value="<?php echo $options[ 'enabled' ]; ?>" />
							</td>
							<td class="field-type">
								<?php if ( in_array( $name, array(
									'billing_state',
									'billing_city',
									'billing_country',
									'billing_postcode',
									'shipping_state',
									'shipping_city',
									'shipping_country',
									'shipping_postcode'
								) ) ) : ?>
									<span class="na tips" data-tip="<?php _e( 'This field is address locale dependent and cannot be modified.', 'wc_checkout_fields' ); ?>">&ndash;</span>
								<?php elseif ( in_array( $name, array( 'order_comments' ) ) ) : ?>
									<span class="na tips" data-tip="<?php _e( 'This field cannot be modified.', 'wc_checkout_fields' ); ?>">&ndash;</span>
								<?php else : ?>
									<select name="field_type[<?php echo $i; ?>]" class="field_type chosen_select" style="width:100px">
										<?php foreach ( $field_types as $key => $type ) {
											echo '<option value="' . $key . '" ' . selected( $options[ 'type' ], $key, false ) . '>' . $type . '</option>';
										}
										?>
									</select>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( in_array( $name, array(
									'billing_state',
									'billing_city',
									'billing_postcode',
									'shipping_state',
									'shipping_city',
									'shipping_postcode'
								) ) ) : ?>
									<span class="na tips" data-tip="<?php _e( 'This field is address locale dependent and cannot be modified.', 'wc_checkout_fields' ); ?>">&ndash;</span>
								<?php else : ?>
									<input type="text" class="input-text" name="field_label[<?php echo $i; ?>]" value="<?php echo isset( $options['label'] ) ? esc_attr( $options['label'] ) : ''; ?>" />
								<?php endif; ?>
							</td>
							<td class="field-options">
								<?php if ( in_array( $name, array(
									'billing_state',
									'billing_city',
									'billing_country',
									'billing_postcode',
									'shipping_state',
									'shipping_city',
									'shipping_country',
									'shipping_postcode'
								) ) ) : ?>
									<span class="na tips" data-tip="<?php _e( 'This field is address locale dependent and cannot be modified.', 'wc_checkout_fields' ); ?>">&ndash;</span>
								<?php else : ?>
									<input type="text" class="input-text placeholder" name="field_placeholder[<?php echo $i; ?>]" value="<?php echo $options['placeholder']; ?>" />
									<input type="text" class="input-text options" name="field_options[<?php echo $i; ?>]" placeholder="<?php _e( 'Pipe (|) separate options.', 'wc_checkout_fields' ); ?>" value="<?php if ( isset( $options['options'] ) ) echo implode( ' | ', $options['options'] ); ?>" />
									<span class="na">&ndash;</span>
								<?php endif; ?>
							</td>
							<td>
								<select name="field_position[<?php echo $i; ?>]" class="field_position chosen_select" style="width:100px">
									<?php foreach ( $positions as $key => $type ) {
										echo '<option value="' . $key . '" ' . selected( in_array( $key, $options['class'] ), true, false ) . '>' . $type . '</option>';
									}
									?>
								</select>
							</td>
							<td class="clear">
								<input type="checkbox" name="field_clear[<?php echo $i; ?>]" <?php checked( isset( $options['clear'] ) && $options['clear'], true ); ?> />
							</td>
							<td class="field-validation">
								<?php if ( in_array( $name, $this->locale_fields ) ) : ?>
									&ndash;
								<?php else : ?>
								<div class="options">
									<select name="field_validation[<?php echo $i; ?>][]" class="chosen_select" multiple="multiple" style="width: 300px;">
										<?php
											foreach( $validation_rules as $key => $rule ) {
												echo '<option value="' . $key . '" ' . selected( ! empty( $options[ $key ] ) || in_array( $key, $options[ 'validate' ] ), true, false ) . '>' . $rule . '</option>';
											}
										?>
									</select>
								</div>
								<span class="na">&ndash;</span>
								<?php endif; ?>
							</td>
							<td class="field-validation">
								<?php if ( in_array( $name, $core_fields ) ) : ?>
									&ndash;
								<?php else : ?>
								<div class="options">
									<select name="field_display_options[<?php echo $i; ?>][]" class="chosen_select" multiple="multiple" style="width: 300px;">
										<?php
											foreach( $display_options as $key => $option ) {
												echo '<option value="' . $key . '" ' . selected( ! empty( $options[ 'display_options' ] ) && in_array( $key, $options[ 'display_options' ] ), true, false ) . '>' . $option . '</option>';
											}
										?>
									</select>
								</div>
								<span class="na">&ndash;</span>
								<?php endif; ?>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php
			echo '<p class="submit"><input type="submit" class="button-primary" value="' . __( 'Save Changes', 'wc_checkout_fields' ) . '" /></p>';
			echo '</form>';
		echo '</div>';
	}

	/**
	 * get_fields function.
	 *
	 * @access public
	 * @param mixed $key
	 * @return void
	 */
	public static function get_fields( $key ) {
		$fields = array_filter( get_option( 'wc_fields_' . $key, array() ) );

		if ( empty( $fields ) || sizeof( $fields ) == 0 ) {
			if ( $key === 'billing' || $key === 'shipping' ) {
				$fields = WC()->countries->get_address_fields( WC()->countries->get_base_country(), $key . '_' );

			} elseif ( $key === 'additional' ) {
				$fields = array(
					'order_comments' => array(
						'type'        => 'textarea',
						'class'       => array('notes'),
						'label'       => __( 'Order Notes', 'wc_checkout_fields' ),
						'placeholder' => _x( 'Notes about your order, e.g. special notes for delivery.', 'placeholder', 'wc_checkout_fields' )
					)
				);
			}
		}

		return $fields;
	}

	/**
	 * save_options function.
	 *
	 * @access public
	 * @param mixed $fields
	 * @param mixed $tab
	 * @return void
	 */
	function save_options( $tab ) {
		$o_fields              = $this->get_fields( $tab );
		$fields                = $o_fields;
		$core_fields           = array_keys( WC()->countries->get_address_fields( WC()->countries->get_base_country(), $tab . '_' ) );
		$core_fields[]         = 'order_comments';
		$field_names           = ! empty( $_POST['field_name'] ) ? $_POST['field_name'] : array();
		$new_field_names       = ! empty( $_POST['new_field_name'] ) ? $_POST['new_field_name'] : array();
		$field_labels          = ! empty( $_POST['field_label'] ) ? $_POST['field_label'] : array();
		$field_order           = ! empty( $_POST['field_order'] ) ? $_POST['field_order'] : array();
		$field_enabled         = ! empty( $_POST['field_enabled'] ) ? $_POST['field_enabled'] : array();
		$field_type            = ! empty( $_POST['field_type'] ) ? $_POST['field_type'] : array();
		$field_placeholder     = ! empty( $_POST['field_placeholder'] ) ? $_POST['field_placeholder'] : array();
		$field_options         = ! empty( $_POST['field_options'] ) ? $_POST['field_options'] : array();
		$field_position        = ! empty( $_POST['field_position'] ) ? $_POST['field_position'] : array();
		$field_clear           = ! empty( $_POST['field_clear'] ) ? $_POST['field_clear'] : array();
		$field_validation      = ! empty( $_POST['field_validation'] ) ? $_POST['field_validation'] : array();
		$field_display_options = ! empty( $_POST['field_display_options'] ) ? $_POST['field_display_options'] : array();
		$max                   = max( array_map( 'absint', array_keys( $field_names ) ) );

		for ( $i = 0; $i <= $max; $i ++ ) {
			$name     = empty( $field_names[ $i ] ) ? '' : urldecode( sanitize_title( wc_clean( stripslashes( $field_names[ $i ] ) ) ) );
			$new_name = empty( $new_field_names[ $i ] ) ? '' : urldecode( sanitize_title( wc_clean( stripslashes( $new_field_names[ $i ] ) ) ) );

			// Check reserved names
			if ( $new_name && in_array( $new_name, array(
				'billing_first_name', 'billing_last_name', 'billing_company', 'billing_address_1', 'billing_address_2', 'billing_city', 'billing_state', 'billing_country', 'billing_postcode', 'billing_phone', 'billing_email',
				'shipping_first_name', 'shipping_last_name', 'shipping_company', 'shipping_address_1', 'shipping_address_2', 'shipping_city', 'shipping_state', 'shipping_country', 'shipping_postcode', 'customer_note', 'order_comments'
			) ) ) {
				continue;
			}

			if ( $name && $new_name && $new_name !== $name ) {
				if ( isset( $fields[ $name ] ) ) {
					$fields[ $new_name ] = $fields[ $name ];
				} else {
					$fields[ $new_name ] = array();
				}

				unset( $fields[ $name ] );

				$name = $new_name;
			} else {
				$name = $name ? $name : $new_name;
			}

			if ( ! $name ) {
				continue;
			}

			if ( ! isset( $fields[ $name ]  ) ) {
				$fields[ $name ] = array();
			}

			$o_type                     = isset( $o_fields[ $name ]['type'] ) ? $o_fields[ $name ]['type'] : 'text';

			$fields[ $name ]['type']    = empty( $field_type[ $i ] ) ? $o_type : wc_clean( $field_type[ $i ] );
			$fields[ $name ]['label']   = empty( $field_labels[ $i ] ) ? '' : wp_kses_post( trim( stripslashes( $field_labels[ $i ] ) ) );

			$fields[ $name ]['clear']   = empty( $field_clear[ $i ] ) ? false : true;
			$fields[ $name ]['options'] = empty( $field_options[ $i ] ) ? array() : array_map( 'wc_clean', explode( '|', $field_options[ $i ] ) );

			// Keys = values
			if ( ! empty( $fields[ $name ]['options'] ) ) {
				$fields[ $name ]['options'] = array_combine( $fields[ $name ]['options'], $fields[ $name ]['options'] );
			}

			$fields[ $name ]['placeholder'] = empty( $field_placeholder[ $i ] ) ? '' : wc_clean( stripslashes( $field_placeholder[ $i ] ) );
			$fields[ $name ]['order']       = empty( $field_order[ $i ] ) ? '' : wc_clean( $field_order[ $i ] );
			$fields[ $name ]['enabled']     = empty( $field_enabled[ $i ] ) ? false : true;

			// Non-locale
			if ( ! in_array( $name, $this->locale_fields ) ) {
				$fields[ $name ]['validate']    = empty( $field_validation[ $i ] ) ? array() : $field_validation[ $i ];

				// require
				if ( in_array( 'required', $fields[ $name ]['validate'] ) ) {
					$fields[ $name ]['required'] = true;
				} else {
					$fields[ $name ]['required'] = false;
				}
			}

			// custom
			if ( ! in_array( $name, array(
				'billing_first_name', 'billing_last_name', 'billing_company', 'billing_address_1', 'billing_address_2', 'billing_city', 'billing_state', 'billing_country', 'billing_postcode', 'billing_phone', 'billing_email',
				'shipping_first_name', 'shipping_last_name', 'shipping_company', 'shipping_address_1', 'shipping_address_2', 'shipping_city', 'shipping_state', 'shipping_country', 'shipping_postcode', 'customer_note', 'order_comments'
			) ) ) {
				$fields[ $name ]['custom'] = true;

				$fields[ $name ]['display_options'] = empty( $field_display_options[ $i ] ) ? array() : $field_display_options[ $i ];
			} else {
				$fields[ $name ]['custom'] = false;
			}

			// position
			$classes = isset( $o_fields[ $name ]['class'] ) ? $o_fields[ $name ]['class'] : array();
			$classes = array_diff( $classes, array( 'form-row-first', 'form-row-last', 'form-row-wide' ) );

			if ( isset( $field_position[ $i ] ) ) {
				$classes[] = $field_position[ $i ];
			}

			$fields[ $name ]['class'] = $classes;

			// Remove
			if ( $fields[ $name ]['custom'] && ! $fields[ $name ]['enabled'] ) {
				unset( $fields[ $name ] );
			}
		}

		uasort( $fields, array( $this, 'sort_fields' ) );

		$result = update_option( 'wc_fields_' . $tab, $fields );

		if ( $result == true ) {
			echo '<div class="updated"><p>' . __( 'Your changes were saved.', 'wc_checkout_fields' ) . '</p></div>';
		} else {
			echo '<div class="error"><p> ' . __( 'Your changes were not saved due to an error (or you made none!).', 'wc_checkout_fields' ) . '</p></div>';
		}
	}

	/**
	 * sort_fields function.
	 *
	 * @access public
	 * @param mixed $a
	 * @param mixed $b
	 * @return void
	 */
	function sort_fields( $a, $b ) {
	    if ( ! isset( $a['order'] ) || $a['order'] == $b['order'] ) {
	        return 0;
	    }

	    return ( $a['order'] < $b['order'] ) ? -1 : 1;
	}

	/**
	 * save_data function.
	 *
	 * @access public
	 * @param mixed $id
	 * @param mixed $posted
	 * @return void
	 */
	function save_data( $order_id, $posted ) {
		$types = array( 'billing', 'shipping', 'additional' );

		foreach ( $types as $type ) {
			$fields = $this->get_fields( $type );

			foreach ( $fields as $name => $field ) {
				
				if ( empty( $posted[ $name ] ) ) {
					continue;
				}

				if ( ! empty( $field['custom'] ) ) {
					$value = wc_clean( $posted[ $name ] );

					if ( $value ) {
						update_post_meta( $order_id, $name, $value );
					}
				}
			}
		}
	}
}
