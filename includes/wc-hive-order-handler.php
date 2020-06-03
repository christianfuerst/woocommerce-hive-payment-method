<?php
/**
 * WC_Hive_Order_Handler
 *
 * @package WooCommerce Hive Payment Method
 * @category Class Handler
 * @author ReCrypto
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Hive_Order_Handler {

	public static function init() {
		$instance = __CLASS__;

		add_action('wc_order_hive_status', array($instance, 'default_order_hive_status'));

		add_action('woocommerce_view_order', array($instance, 'payment_details'), 5);
		add_action('woocommerce_thankyou', array($instance, 'payment_details'), 5);
	}

	public static function default_order_hive_status($status) {
		return $status ? $status : 'pending';
	}

	public static function payment_details($order_id) {
		$order = wc_get_order($order_id);

		if ($order->get_payment_method() != 'wc_hive') 
			return; ?>

		<section class="woocommerce-hive-order-payment-details">

			<h2 class="woocommerce-hive-order-payment-details__title"><?php _e( 'Hive Payment details', 'wc-hive' ); ?></h2>

			<p class="woocommerce-hive-payment-memo-prompt"><em>If you haven't already completed your payment:</em> Please don't forget to include the <strong>"MEMO"</strong> when making a transfer for this transaction to Hive.</p>
			
			<table class="woocommerce-table woocommerce-table--hive-order-payment-details shop_table hive_order_payment_details">
				<tbody>
					<tr>
						<th><?php _e('Payee', 'wc-hive'); ?></th>
						<td><?php echo wc_order_get_hive_payee($order_id); ?></td>
					</tr>
					<tr>
						<th><?php _e('Memo', 'wc-hive'); ?></th>
						<td><?php echo wc_order_get_hive_memo($order_id); ?></td>
					</tr>
					<tr>
						<th><?php _e('Amount', 'wc-hive'); ?></th>
						<td><?php echo wc_order_get_hive_amount($order_id); ?></td>
					</tr>
					<tr>
						<th><?php _e('Currency', 'wc-hive'); ?></th>
						<td><?php echo wc_order_get_hive_amount_currency($order_id); ?></td>
					</tr>
					<tr>
						<th><?php _e('Status', 'wc-hive'); ?></th>
						<td><?php echo wc_order_get_hive_status($order_id); ?></td>
					</tr>
				</tbody>
			</table>

			<?php do_action( 'wc_hive_order_payment_details_after_table', $order ); ?>

		</section>

		<?php if ($transfer = get_post_meta($order->get_id(), '_wc_hive_transaction_transfer', true)) : ?>
		<section class="woocommerce-hive-order-transaction-details">

			<h2 class="woocommerce-hive-order-transaction-details__title"><?php _e( 'Hive Transfer details', 'wc-hive' ); ?></h2>

			<table class="woocommerce-table woocommerce-table--hive-order-transaction-details shop_table hive_order_payment_details">
				<tbody>
					<tr>
						<th><?php _e('Hive Transaction', 'wc-hive'); ?></th>
						<td><?php echo $transfer['transaction']; ?></td>
					</tr>
					<tr>
						<th><?php _e('Time', 'wc-hive'); ?></th>
						<td><?php echo $transfer['time']; ?></td>
					</tr>
					<tr>
						<th><?php _e('Memo', 'wc-hive'); ?></th>
						<td><?php echo $transfer['memo']; ?></td>
					</tr>					
				</tbody>
			</table>

			<?php do_action( 'wc_hive_order_transaction_details_after_table', $order ); ?>

		</section>
		<?php endif; ?>

		<?php
	}
}

WC_Hive_Order_Handler::init();
