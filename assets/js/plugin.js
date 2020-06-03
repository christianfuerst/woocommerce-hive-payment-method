(function($) {
	"use strict";

	function wc_get_base_currency() {
		return (wc_hive !== 'undefined' && 'cart' in wc_hive && 'base_currency' in wc_hive.cart) ? wc_hive.cart.base_currency : 'USD';
	}

	function wc_get_amount($currency) {
		return (wc_hive !== 'undefined' && 'cart' in wc_hive && 'amounts' in wc_hive.cart) ? wc_hive.cart.amounts[$currency + '_' + wc_get_base_currency()] : -1;
	}
	
	$(document).on('change', 'select[name="wc_hive-amount_currency"]', function(event) {
		var $currency = this.value;
		var $amount = wc_get_amount($currency);

		if ($amount > -1) {
			$('#wc_hive-amount').html($amount + ' ' + $currency);
		}
	});

	$(document).on('show_variation', '.variations_form', function(event, variation) {
		var $prices_html = 'prices_html' in variation ? variation.prices_html : null;

		if ($prices_html != null) {
			$(this).find('.woocommerce-variation-price').append($prices_html);
		}
	});
})(jQuery);
