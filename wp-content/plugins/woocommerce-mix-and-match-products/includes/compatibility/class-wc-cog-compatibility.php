<?php
/**
 * Cost of Goods Compatibility.
 *
 * @since  1.0.5
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_MNM_COG_Compatibility {

	public static function init() {

		// Cost of Goods support.
		add_filter( 'wc_cost_of_goods_set_order_item_cost_meta_item_cost', array( __CLASS__, 'cost_of_goods_set_order_item_bundled_item_cost' ), 10, 3 );
	}

	/**
	 * Cost of goods compatibility: Zero order item cost for bundled products that belong to statically priced bundles.
	 *
	 * @param  double    $cost
	 * @param  array     $item
	 * @param  WC_Order  $order
	 * @return double
	 */
	public static function cost_of_goods_set_order_item_bundled_item_cost( $cost, $item, $order ) {

		if ( $parent_item = wc_mnm_get_mnm_order_item_container( $item, $order ) ) {

			$parent_obj = wc_get_product( $parent_item[ 'product_id' ] );

			$bundled_item_priced_individually = isset( $parent_item[ 'per_product_pricing' ] ) ? $parent_item[ 'per_product_pricing' ] : $parent_obj->is_priced_per_product();

			if ( 'no' === $bundled_item_priced_individually ) {
				return 0;
			}
		}

		return $cost;
	}
}

WC_MNM_COG_Compatibility::init();

