![WooCommerce Steem](https://steemitimages.com/0x0/https://ps.w.org/woo-steem/assets/banner-1544x500.png?rev=1670250)

# WooCommerce Steem Payment Method
Accept STEEM or SBD payments in your WooCommerce store via SteemConnect. Automatically converts from fiat (USD, EUR, etc) to STEEM. No transaction fees.

## Supported Steem Currencies
- Steem (STEEM)
- Steem Backed Dollars (SBD)

## Details
* There is no extra transaction fee. Payments are made directly between customer and store owner via SteemConnect. 
* This plugin will automatically detect if payment was made once it is posted to Steem Blockchain. 
* If payment is not completed within several minutes of submitting an order an automatic payment reminder email will be sent to the customer with instructions for submitting payment. This is a fallback for 1) the customer doesn't complete the transaction, and 2) the payment detection functionality in this plugin stops working for any reason.
* Currency exchange rate between FIAT and STEEM/SBD is automatically calculated at time of checkout.
* Currency exchange rate between FIAT and STEEM/SBD can be optionally displayed below the product price on the product page.

## Currency Limitations
- Currently supports different fiat currencies such as: AUD, BGN, BRL, CAD, CHF, CNY, CZK, DKK, GBP, HKD, HRK, HUF, IDR, ILS, INR, JPY, KRW, MXN, MYR, NOK, NZD, PHP, PLN, RON, RUB, SEK, SGD, THB, TRY, ZAR, EUR
- If none of the fiat currency listed above, it will default 1:1 conversion rate.

## How it Works Behind The Scenes
* Exchange rates are updated every hour
* FIAT foreign exchange rates are gathered from the European Central Bank's free API
* STEEM exchange rates are determined using Poloniex by converting USDT -> BTC -> STEEM (or SBD)
* Your store's steem wallet is scanned every 5 minutes for pending transactions (if there are any orders with pending payment)
* If an order is Pending Payment for too long it will be automatically canceled by WooCommerce default settings. You can change the timing or disable this feature in WooCommerce -> Settings -> Products -> Inventory -> Hold Stock (Minutes)

## Technical Requirements
This plugin requires WordPress CRON jobs to be enabled. If CRON jobs are not enabled, currency exchange rates will not be updated and this plugin will not be able to search for STEEM payment records. If your exchange rates are not updating or if orders were paid for but still say "Payment Pending" or are automatically canceled, it is likely that CRON jobs are not enabled on your server or are not functioning properly. 

Order payments should normally be reflected in the order automatically within 5-10 minutes max. If the order is is still status Payment Pending or becomes cancelled more than 10-15 minutes, it is likely that your CRON jobs are not enabled.

## Security Note
You will <strong>NOT</strong> be required to enter any steem private keys into this plugin. You only have to provide your steem username so that the plugin knows where payments should be sent.

## Screenshots
![steem01.jpg](https://cdn.steemitimages.com/DQmbiehWamh8pBhsuCcgowhYZPTXLvjrR8V2huzwpvSrpRA/steem01.jpg)

![steem02.jpg](https://cdn.steemitimages.com/DQmNmiHaLkFMBJ27G2RyFHsJH7hSaJTLcxYsLLWXykXV199/steem02.jpg)

![steem03.jpg](https://cdn.steemitimages.com/DQmZwWzUJ92xj7Q9BrA7NH184xFbYA4XvbKKT7PvrP4PCxJ/steem03.jpg)

![steem04.jpg](https://cdn.steemitimages.com/DQmf2Wh9hvQ2HFh12UfjtaUpKQgwV1rpqd2ynYbdJwbTg5o/steem04.jpg)

![steem05.jpg](https://cdn.steemitimages.com/DQmd4c4QtHo8mtSJj9ksSmQLoqzRsDXK2U3zZbEjXAKGihi/steem05.jpg)

![steem06.jpg](https://cdn.steemitimages.com/DQmZqDR1nCHFM2X4xcAtioEhyY4ZsqGggkJxjbGemigTxMC/steem06.jpg)

## Thanks
* Special thanks to [@justyy](https://steemit.com/@justyy) for providing free steem APIs. This plugin uses one of @justyy's apis to find matching transactions. Consider giving @justyy a vote for witness to support his efforts providing free steem APIs and other tools.

* Special thanks to [@ReCrypto](https://steemit.com/@recrypto) for being the author and inventor of the original "WooCommerce Steem" plugin before it was forked and updated into this plugin "WooCommerce Steem Payment Method". Thank you @ReCrypto for sharing your hard work!

## Disclaimer
Authors claim no responsibility for missed transactions, loss of your funds, loss of customer funds, incorrect or delayed exchange rates or any other issue you may encounter as a result of using this plugin. Use this plugin at your own risk.

## Installation

1. Upload the plugin files to the `/wp-content/plugins/woocommerce-steem-payment-method` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Turn on Steem as a payment method in WooCommerce->Settings->Payments. Turn on the "Enabled" switch.
1. Update settings for this plugin in WooCommerce->Settings->Payments and clicking "Manage" next to "Steem"
1. Make sure to put your Steem username in the "Payee" box so that you will receive payments.
1. Make sure that WordPress CRON jobs are enabled. If you are not sure how, you should ask your web host to help.
1. Recommended: Try some sample test transactions with small amounts to make sure payments are received and they are registered in the order. 

