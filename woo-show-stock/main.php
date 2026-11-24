<?php
/**
 * Plugin Name: Show Stock Status for WooCommerce
 * Description: Show the “Stock Quantity” for each product in the shop, category and archive pages.
 * Author: Bright Plugins
 * Version: 1.0.7
 * Author URI: https://brightplugins.com/
 * Text Domain: woo-show-stock
 * Domain Path:  /languages/
 * Requires PHP: 7.4
 * Requires at least: 4.9
 * Tested up to: 7.1
 * WC tested up to: 11.3.1
 * Requires Plugins: woocommerce
 * WC requires at least: 3.4
 */

add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);
function brightvessel_woocommerce_show_stock( $return_as_value = 0 ) {
	global $product;

	$low_stock_notify = wc_get_low_stock_amount( $product ); // get low stock from product

	// check if product type is not variable
	if ( ! $product->is_type( 'variable' ) ) {
		if ( $product->get_manage_stock() ) { // if manage stock is enabled

			if ( ! $product->is_in_stock() ) {
				echo "<div class='remaining-out-stock'>" . esc_html__( ' Out of stock', 'woo-show-stock' ) . '</div>';
				return;
			}

			$stockNum = $product->get_stock_quantity();
			if ( $stockNum <= $low_stock_notify ) { // if stock is low
				if ( $return_as_value ) {
					return $stockNum; }
				return printf(
					/* translators: number of stock . */

					__( "<div class='remaining-low-stock'> Only %s left in stock</div>", 'woo-show-stock' ),
					$stockNum
				);
			}
			if ( $return_as_value ) {
				return $stockNum; }
			return printf(
				/* translators: number of stock . */
				__( "<div class='remaining'> %s left in stock </div>", 'woo-show-stock' ),
				$stockNum
			);
		}
	} elseif ( $product->get_manage_stock() ) {
		// if manage stock is enabled
			$product_variations = $product->get_available_variations();
			$display_type       = 'sumboth';
			$stock_str          = '';
		if ( ! empty( get_post_meta( $product->get_id(), '_woostock_display_type' ) ) ) {
			$display_type = get_post_meta( $product->get_id(), '_woostock_display_type', true );
			if ( 'product' == $display_type || 'sumboth' == $display_type ) {
				$stock     = $product->get_stock_quantity();
				$stock_str = '<br>Product: ' . $stock;
			} else {
				$stock = 0;
			}
		} else {
			$stock = $product->get_stock_quantity();
		}

		if ( 'variants' == $display_type || 'sumboth' == $display_type ) {
			$stock_vars = 0;
			foreach ( $product_variations as $variation ) {
				// check if variant controles stock
				$is_stock_manage = get_post_meta( $variation['variation_id'] ?? 0, '_manage_stock', true );
				if ( 'yes' == $is_stock_manage ) {
					if ( empty( $variation['max_qty'] ) ) {
						$variation['max_qty'] = 0;
					}
					$stock      += absint( $variation['max_qty'] );
					$stock_vars += absint( $variation['max_qty'] );
				}
			}
			$stock_str .= '<br>Variants: ' . $stock_vars;
		}

		if ( $stock > 0 ) {
			if ( $stock <= $low_stock_notify ) { // if stock is low
				if ( $return_as_value ) {
					return 'Total: ' . $stock . $stock_str; }
				return printf(
					/* translators: number of stock . */
					__( "<div class='remaining-low-stock bpss-low-stock'>Only %s left in stock </div>", 'woo-show-stock' ),
					$stock
				);
			}

			if ( $return_as_value ) {
				return 'Total: ' . $stock . $stock_str; }
			return printf(
				/* translators: number of stock . */
				__( "<div class='remaining bpss-remaining'> %s left in stock </div>", 'woo-show-stock' ),
				$stock
			);
		}
	} else {

		$product_variations = $product->get_available_variations();
		$stock              = 0; // $product->get_stock_quantity();
		foreach ( $product_variations as $variation ) {
			// check if variant controles stock
			$is_stock_manage = get_post_meta( $variation['variation_id'] ?? 0, '_manage_stock', true );
			if ( 'yes' == $is_stock_manage ) {
				if ( empty( $variation['max_qty'] ) ) {
					$variation['max_qty'] = 0;
				}
				$stock += absint( $variation['max_qty'] );
			}
		}

		if ( $stock > 0 ) {
			if ( $stock <= $low_stock_notify ) { // if stock is low
				if ( $return_as_value ) {
					return $stock; }
				return printf(
					/* translators: number of stock . */
					__( "<div class='remaining-low-stock bpss-low-stock'>Only %s left in stock</div>", 'woo-show-stock' ),
					$stock
				);
			}
			if ( $return_as_value ) {
				return $stock; }
			return printf(
				/* translators: number of stock . */
				__( "<div class='remaining bpss-remaining'> %s left in stock</div>", 'woo-show-stock' ),
				$stock
			);
		}
	}
}

if ( 'yes' === get_option( 'wc_always_show_stock' ) && null !== get_option( 'wc_show_stock_where' ) ) {
	add_action( get_option( 'wc_show_stock_where' ), 'brightvessel_woocommerce_show_stock', 10 );
}

// Add settings to the specific section we created before
define( 'SSFWBP_OFFER_URL', 'https://brightplugins.com/shop/?utm_source=freemium&utm_medium=admin_banner&utm_campaign=black_friday_2026&utm_content=stock_status' );
define( 'SSFWBP_ASSETS', plugin_dir_url( __FILE__ ) . 'assets' );

/**
 * Generate the Black Friday offer banner if the current date is before 2027-01-01.
 */
$is_offer_valid = time() < strtotime( '2027-01-01 00:00:00' );
if( $is_offer_valid ) {

	add_action( 'wp_ajax_bp_dismiss_bf_banner_stock_status_bp', function() {
		//...
		check_ajax_referer( 'bp_bf_banner_nonce_stock_status_bp', 'nonce' );
		//...
		if( !current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$user_id = get_current_user_id();

		if( get_transient( 'bp_bf_banner_dismissed_stock_status_bp_' . $user_id ) ) {
			wp_send_json_success();
		}

		set_transient( 'bp_bf_banner_dismissed_stock_status_bp_' . $user_id, true, (DAY_IN_SECONDS * 14) );

		wp_send_json_success();
	} );

	add_action('woocommerce_before_settings_products', 'ssfwbp_generate_offer_banner');
}

/**
 * 
 */
function ssfwbp_generate_offer_banner()
{
	$is_plugin_settings_page = is_admin() && isset($_GET['page']) && isset($_GET['tab']) && isset($_GET['section']) && $_GET['page'] === 'wc-settings' && $_GET['tab'] === 'products' && $_GET['section'] === 'inventory';

	$user_id      = get_current_user_id();
	$is_dismissed = get_transient( 'bp_bf_banner_dismissed_stock_status_bp_' . $user_id );


	if (!$is_plugin_settings_page || $is_dismissed) {
		return;
	}

	?>

	<div style="display: flex;justify-content: center;flex-wrap: wrap;align-items: center;flex-direction: row;gap: 20px;">

		<div class="bf-promo-banner" id="bf-promo-banner">
			<!-- Close button -->
			<button type="button" class="bf-promo-banner__close" aria-label="Dismiss notice" onclick="bpDismissBfBannerAjax()">
				<span aria-hidden="true">&times;</span>
			</button>

			<!-- Image (50% OFF) -->
			<div class="bf-promo-banner__media">
				<img 
					src="<?php echo esc_url( SSFWBP_ASSETS ); ?>/img/black-friday-2026-50-off.png" 
					alt="50% OFF" 
					class="bf-promo-banner__img"
					width="100" 
					height="100"
					loading="lazy"
				/>
			</div>

			<!-- Central text group -->
			<div class="bf-promo-banner__content">
				<h3 class="bf-promo-banner__title">
					Black Friday deals have arrived! <a href="<?php echo esc_url( SSFWBP_OFFER_URL ); ?>" target="_blank" class="bf-promo-banner__link"><span class="bf-promo-banner__highlight">50% OFF</span></a> <span class="bf-promo-banner__sparkle" aria-hidden="true">all Pro Plugins ✨</span>
				</h3>

				<div class="bf-promo-banner__body">
				<p class="bf-promo-banner__paragraph">
					Thanks for being part of the <strong>Bright Plugins</strong> family!
				</p>
				<p class="bf-promo-banner__paragraph">
					With major upgrades and new releases on the way, grab our lowest price of the year and <strong>lock in your discounted rate for as long as you renew</strong>—valid on all plugins and our new <strong>Club Membership</strong>.
				</p>
				</div>
			</div>

			<!-- Action button on the right -->
			<div class="bf-promo-banner__actions">
				<a href="<?php echo esc_url( SSFWBP_OFFER_URL ); ?>" target="_blank" class="bf-promo-banner__btn">
				Claim Your Discount Now!
				</a>
			</div>
		</div>
	</div>

	<style>
		/* Main banner container */
		.bf-promo-banner {
			position: relative;
			display: flex;
			flex-direction: row;
			align-items: center;
			justify-content: space-between;
			gap: 24px;
			width: 100%;
			margin: 0 20px 20px 20px;
			padding: 16px 40px 16px 0;
			background: #ffffff;
			border: 1px solid #dcdcdc;
			border-left: 4px solid #683078;
			border-radius: 4px;
			box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
			color: #1d2327;
			box-sizing: border-box;
		}

		/* 1. Image / Badge area */
		.bf-promo-banner__media {
			flex-shrink: 0;
			display: flex;
			align-items: center;
		}

		.bf-promo-banner__img {
			display: block;
			width: 200px;
			height: auto;
			max-height: 100%;
			object-fit: contain;
		}

		/* 2. Central content area */
		.bf-promo-banner__content {
			flex: 1;
			min-width: 0;
		}

		.bf-promo-banner__title {
			margin: 0 0 20px 0;
			font-size: 24px;
			font-weight: 700;
			line-height: 1.35;
			color: #1d2327;
			font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
		}

		/* Highlighted text 50% OFF in purple */
		.bf-promo-banner__highlight {
			color: #683078;
			font-weight: 700;
		}
		.bf-promo-banner__link {
			text-decoration: none;
		}

		.bf-promo-banner__sparkle {
			display: inline-block;
			margin-left: 2px;
			font-size: 24px;
		}

		/* Paragraph body */
		.bf-promo-banner__body {
			font-size: 18px;
			line-height: 1.5;
			color: #2c3338;
			max-width: 794px;
			font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
		}

		.bf-promo-banner__paragraph {
			margin: 0;
			font-size: 18px;
		}

		.bf-promo-banner__paragraph + .bf-promo-banner__paragraph {
			margin-top: 6px; /* Space between the two texts */
		}

		/* 3. Button area (Right) */
		.bf-promo-banner__actions {
			flex-shrink: 0;
			margin-left: auto;
		}

		.bf-promo-banner__btn {
			display: inline-block;
			padding: 11px 20px;
			background-color: #683078;
			color: #ffffff !important;
			font-size: 18px;
			font-weight: 600;
			line-height: 1.2;
			text-decoration: none !important;
			border-radius: 6px;
			white-space: nowrap;
			box-shadow: 0 1px 2px rgba(104, 48, 120, 0.2);
			transition: background-color 0.15s ease, transform 0.1s ease;
		}

		.bf-promo-banner__btn:hover,
		.bf-promo-banner__btn:focus {
			background-color: #51255e;
		}

		.bf-promo-banner__btn:active {
			transform: translateY(1px);
		}

		/* Close "X" button */
		.bf-promo-banner__close {
			position: absolute;
			top: 10px;
			right: 10px;
			display: flex;
			align-items: center;
			justify-content: center;
			width: 18px;
			height: 18px;
			padding: 0;
			background: transparent;
			color: #72777c;
			border: none;
			border-radius: 50%;
			font-size: 20px;
			line-height: 1;
			cursor: pointer;
			transition: color 0.15s ease, background 0.15s ease;
		}

		.bf-promo-banner__close:hover {
			color: #1d2327;
			background-color: rgba(0, 0, 0, 0.05);
		}

		/* Responsive for tablets and small laptops */

		@media (max-width: 1024px) {
			.bf-promo-banner {
				flex-direction: column;
				align-items: flex-start;
				padding: 24px;
				gap: 16px;
			}
		}

		@media (max-width: 900px) {
		
			.bf-promo-banner__actions {
				margin-left: 0;
				width: 100%;
			}

			.bf-promo-banner__btn {
				display: block;
				text-align: center;
				box-sizing: border-box;
			}
		}

		/* Mobile responsive */
		@media (max-width: 500px) {
			.bf-promo-banner {
				padding: 20px 16px;
			}
			.bf-promo-banner__media {
				margin-bottom: 4px;
			}
			.bf-promo-banner__img {
				width: 150px;
			}
			.bf-promo-banner__title, .bf-promo-banner__sparkle {
				font-size: 18px;
			}
			.bf-promo-banner__paragraph, .bf-promo-banner__btn {
				font-size: 15px;
			}
		}
	</style>

	<script>
		function bpDismissBfBannerAjax() {
			document.getElementById('bf-promo-banner').style.display = 'none';

			const formData = new URLSearchParams();
			formData.append('action', 'bp_dismiss_bf_banner_stock_status_bp');
			formData.append('nonce', '<?php echo esc_js( wp_create_nonce( "bp_bf_banner_nonce_stock_status_bp" ) ); ?>');

			fetch(ajaxurl, {
				method: 'POST',
				body: formData
			});
		}
	</script>
	<?php
}

add_filter( 'woocommerce_get_settings_products', 'brightvessel_woocommerce_show_stock_all_settings', 10, 2 );
/**
 * @param $settings
 * @param $current_section
 * @return mixed
 */
function brightvessel_woocommerce_show_stock_all_settings( $settings, $current_section ) {
	// Check the current section is what we want
	if ( 'inventory' === $current_section ) {
		$settings[] = array(
			'name' => __( 'Stock Settings', 'woo-show-stock' ),
			'type' => 'title',
			'desc' => __( 'The following options are used to configure how to show your stock', 'woo-show-stock' ),
			'id'   => 'stockoptions',
		);

		$settings[] = array(
			'name' => __( 'Always show stock', 'woo-show-stock' ),
			'type' => 'checkbox',
			'desc' => __( 'Always show available stock', 'woo-show-stock' ),
			'id'   => 'wc_always_show_stock',
		);

		$settings[] = array(
			'name' => __( 'Show detailed stock on admin products page', 'woo-show-stock' ),
			'type' => 'checkbox',
			'desc' => __( 'When product is variable it shows the Stock on product level and the Stock on variant level', 'woo-show-stock' ),
			'id'   => 'woostock_show_detailed_admin',
		);

		$settings[] = array(
			'name'    => __( 'Stock position', 'woo-show-stock' ),
			'type'    => 'select',

			'options' => array(
				'woocommerce_after_shop_loop_item'        => __( 'After shop loop (recommended)', 'woo-show-stock' ),
				'woocommerce_after_shop_loop_item_title'  => __( 'After title', 'woo-show-stock' ),
				'woocommerce_before_shop_loop_item_title' => __( 'Before title', 'woo-show-stock' ),
			),
			'desc'    => __( 'Where the actual stock should be displayed', 'woo-show-stock' ),
			'id'      => 'wc_show_stock_where',
		);
		// todo: add new settings for for hook priroty
		$settings[] = array(
			'type' => 'sectionend',
			'id'   => 'wc_settings_tab_stock',
		);
	}

	return $settings;
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ) . '', 'bpwcssPluginMeta' );
/**
 * links in Plugin Meta
 *
 * @param  [array] $links
 * @return void
 */
function bpwcssPluginMeta( $links ) {
	$row_meta = array(
		'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=products&section=inventory#stockoptions-description' ) . '">Stock Settings</a>',
		'support'  => '<a style="color:red;" target="_blank" href="' . esc_url( 'https://brightplugins.com/support' ) . '">Support</a>',

	);
	return array_merge( $links, $row_meta );
}

register_activation_hook( __FILE__, 'bpwcssPluginActivation' );
function bpwcssPluginActivation() {
	// set plugin instalation date
	$installed = get_option( 'bpwcss_installed' );
	if ( ! $installed ) {
		update_option( 'bpwcss_installed', date( 'Y/m/d' ) );
	}
}

function brightvessel_woocommerce_show_stock_check_notice() {
	if ( isset( $_GET['bvsclose'] ) && 'true' === $_GET['bvsclose'] ) {
		add_option( 'bvsclose', 1 );
	}
	if ( 1 !== (int) ( get_option( 'bvsclose' ) ) ) {
		add_action( 'admin_notices', 'brightvessel_woocommerce_show_stock_check_notice' );
	}
	// for review dismiss / check
	if ( isset( $_GET['bpwss-review-dismiss'] ) && $_GET['bpwss-review-dismiss'] == 1 ) {
		update_option( 'bpwss-review-dismiss', 1 );
	}
	if ( isset( $_GET['bpwss-later-dismiss'] ) && $_GET['bpwss-later-dismiss'] == 1 ) {
		set_transient( 'bpwss-later-dismiss', 1, 2 * DAY_IN_SECONDS );
	}
}
add_action( 'admin_init', 'brightvessel_woocommerce_show_stock_check_notice' );

if ( time() > strtotime( get_option( 'bpwcss_installed' ) . ' + 3 Days' ) ) {
	add_action( 'admin_notices', 'bpShowStockReview' );
}

/**
 * @return null
 */
function bpShowStockReview() {
	$dismissPram     = array( 'bpwss-review-dismiss' => '1' );
	$bpwssMaybeLater = array( 'bpwss-later-dismiss' => '1' );

	if ( get_option( 'bpwss-review-dismiss' ) || get_transient( 'bpwss-later-dismiss' ) ) {
		return;
	}?>
		<div class="notice ciplugin-review">
		<p style="font-size:15px;"><img draggable="false" class="emoji" alt="🎉" src="https://s.w.org/images/core/emoji/11/svg/1f389.svg"><strong style="font-size: 19px; margin-bottom: 5px; display: inline-block;" ><?php echo __( 'Thanks for using Show stock for WooCommerce.', 'wpgs' ); ?></strong><br> <?php _e( 'If you can spare a minute, please help us by leaving a 5 star review on WordPress.org.', 'wpgs' ); ?></p>
		<p class="dfwc-message-actions">
			<a style="margin-right:5px;" href="https://wordpress.org/support/plugin/woo-show-stock/reviews/#new-post" target="_blank" class="button button-primary button-primary"><?php _e( 'Happy To Help', 'wpgs' ); ?></a>
			<a style="margin-right:5px;" href="<?php echo esc_url( add_query_arg( $bpwssMaybeLater ) ); ?>" class="button button-alt"><?php _e( 'Maybe later', 'wpgs' ); ?></a>
			<a href="<?php echo esc_url( add_query_arg( $dismissPram ) ); ?>" class="dfwc-button-notice-dismiss button button-link"><?php _e( 'Hide Notification', 'wpgs' ); ?></a>
		</p>
		</div>
		<?php
}

// add_action( 'woocommerce_product_after_variable_attributes', 'wooshow_custom_variations_fields', 10, 3 ); // After all Variation fields

// Inventory tab
add_action( 'woocommerce_product_options_stock_status', 'wooshow_custom_simple_fields' );

// add_action( 'woocommerce_save_product_variation', 'wooshow_custom_variations_fields_save', 10, 2 );
add_action( 'woocommerce_process_product_meta', 'wooshow_custom_simple_fields_save', 10, 2 );



function wooshow_custom_simple_fields() {
	$display_type = get_post_meta( get_the_ID(), '_woostock_display_type', true );
	if ( empty( $display_type ) ) {
		$display_type = 'sumboth';
	}
	woocommerce_wp_radio(
		array(
			'id'          => '_woostock_display_type',
			'label'       => __( 'Show stock display type', 'woo-show-stock' ),
			'default'     => 'product',
			'options'     => array(
				'product'  => __( 'Display product level stock quantity', 'woo-show-stock' ),
				'variants' => __( 'Display the sum of all variants stock', 'woo-show-stock' ),
				'sumboth'  => __( 'Product stock + Variants stock (default)', 'woo-show-stock' ),
			),
			'desc_tip'    => true,
			'description' => __( 'This setting is only applied when a variable product uses both stock management (product level and variant level at same time). With both levels enabled the stock quantity can result in an inconsistent value. <br>So choose witch display type you would like to show when the product has both systems enabled.', 'woo-show-stock' ),
			'value'       => $display_type,
		)
	);
}

function wooshow_custom_simple_fields_save( $post_id ) {
	if ( isset( $_POST['_woostock_display_type'] ) && ! empty( $_POST['_woostock_display_type'] ) ) {
		$display_type = $_POST['_woostock_display_type'];
		$product      = wc_get_product( $post_id );
		$product->update_meta_data( '_woostock_display_type', $display_type );
		$product->save();
	}
}

// Admin product list: is_in_stock
function woostock_filter_woocommerce_admin_stock_html( $stock_html, $product ) {
	// Condition
	if ( $product->is_type( 'variable' ) ) {
		if ( ! empty( get_option( 'woostock_show_detailed_admin' ) ) && 'yes' == get_option( 'woostock_show_detailed_admin' ) ) {
			$stock_html .= '<br><mark class="someclass" style="background:transparent none; font-weight:400; line-height:1;">' . brightvessel_woocommerce_show_stock( $return_as_value = 1 ) . '</mark>';
		}
	}

	return $stock_html;
}
add_filter( 'woocommerce_admin_stock_html', 'woostock_filter_woocommerce_admin_stock_html', 10, 2 );

