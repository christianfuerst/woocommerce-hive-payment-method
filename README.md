# WooCommerce Hive Payment Method
Accept HIVE, HBD and HIVE-ENGINE payments in your WooCommerce store via HiveSigner. Automatically converts from fiat to HIVE.

## Supported Hive Currencies
- Hive (HIVE)
- Hive Backed Dollars (HBD)
- Hive-Engine Tokens

## Details
* There is no extra transaction fee. Payments are made directly between customer and store owner via HiveSigner. 
* This plugin will automatically detect if payment was made once it is posted to Hive Blockchain. Payment detection is not instant; it can take up to 5 minutes for the payment to be detected.
* If payment is not completed within several minutes of submitting an order an automatic payment reminder email will be sent to the customer with instructions for submitting payment. This is a fallback for 1) the customer doesn't complete the transaction, and 2) the payment detection functionality in this plugin stops working for any reason.
* Currency exchange rate between FIAT and HIVE/HBD is automatically calculated at time of checkout.
* Currency exchange rate between FIAT and HIVE/HBD can be optionally displayed below the product price on the product page.
* Support for YITH WooCommerce Subscription plugin subscription renewals

## Currency Limitations
- Currently supports different fiat currencies such as: AUD, BGN, BRL, CAD, CHF, CNY, CZK, DKK, GBP, HKD, HRK, HUF, IDR, ILS, INR, JPY, KRW, MXN, MYR, NOK, NZD, PHP, PLN, RON, RUB, SEK, SGD, THB, TRY, ZAR, EUR
- If none of the fiat currency listed above, it will default 1:1 conversion rate.

## How it Works Behind The Scenes
* Exchange rates are updated once an hour
* FIAT foreign exchange rates are gathered from the European Central Bank's free API
* HIVE/HBD exchange rates are determined by querying three exchanges and taking the average: Binance and Bittrex.
* Binance rates are determined by converting USDT (Tether) -> BTC -> HIVE (HBD is not supported by Binance)
* Bittrex rates are determined by converting USD -> BTC -> HIVE / HBD
* HIVE-ENGINE token rates are determinded by converting USD -> BTC -> HIVE -> HIVE-ENGINE token
* Your store's wallet is scanned every 2 minutes for pending transactions (if there are any orders with pending payment)
* If an order is Pending Payment for too long it will be automatically canceled by WooCommerce default settings. You can change the timing or disable this feature in WooCommerce -> Settings -> Products -> Inventory -> Hold Stock (Minutes)

## Technical Requirements
WooCommerce plugin must be installed before you install this plugin.

This plugin requires WordPress CRON jobs to be enabled. If CRON jobs are not enabled, currency exchange rates will not be updated and this plugin will not be able to search for HIVE payment records. If your exchange rates are not updating or if orders were paid for but still say "Payment Pending" or are automatically canceled, it is likely that CRON jobs are not enabled on your server or are not functioning properly.

Order payments should normally be reflected in the order automatically within 5-10 minutes max. If the order is is still status Payment Pending or becomes cancelled more than 10-15 minutes, it is likely that your CRON jobs are not enabled.

An alternative to using WordPress CRON jobs is setting up a real Crontab. A real Crontab is more efficient than using WordPress CRON jobs, and so you may prefer this approach. You can find instructions for setting up a real Crontab here: https://helloacm.com/setting-up-a-real-crontab-for-wordpress/

## Installation

1. Upload the plugin files to the `/wp-content/plugins/woocommerce-hive-payment-method` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Turn on Hive as a payment method in WooCommerce->Settings->Payments. Turn on the "Enabled" switch.
4. Update settings for this plugin in WooCommerce->Settings->Payments and clicking "Manage" next to "Hive"
5. Make sure to put your Hive username in the "Payee" box so that you will receive payments.

## Security Note
You will <strong>NOT</strong> be required to enter any Hive private keys into this plugin. You only have to provide your Hive username so that the plugin knows where payments should be sent.

## Frequently Asked Questions

**How is customer payment made?**
When the customer initiates payment the HiveSigner window will be opened. HiveSigner will be populated with the payment amount, currency and memo. The automatically generated memo is a random key that is matched to the order.

**How does it confirm Hive Transfers?**
It uses queries the store's HIVE wallet history every 2 minutes to and checks for a transaction that matches the payment MEMO, amount and currency (HIVE or HBD). When the matching payment is found, the order is marked from "payment pending" to "processing".

**What is the payment reminder email?**
If the customer does not complete the payment via HiveSigner within several minutes of initiating the payment, a confirmation email will be sent reminding the customer to make payment manually via Hive. The payment reminder email will include instructions including the memo.

**How can I support this plugin?**
Please support me by following me on Hive [@roomservice](https://peakd.com/@roomservice) and consider voting me for your witness, that would really help a lot on my future efforts with this plugin.

Hive: @roomservice
Discord: @roomservice#8215

## Screenshots
![image.png](https://files.peakd.com/file/peakd-hive/roomservice/8rGIS9iN-image.png)

![image.png](https://files.peakd.com/file/peakd-hive/roomservice/9Im4Htqq-image.png)

![image.png](https://files.peakd.com/file/peakd-hive/roomservice/sxgs9Kye-image.png)

![image.png](https://files.peakd.com/file/peakd-hive/roomservice/W6Ap7oJ5-image.png)

![image.png](https://files.peakd.com/file/peakd-hive/roomservice/3UpP4le4-image.png)

## Thanks
* Special thanks to [@ReCrypto](https://peakd.com/@recrypto) and [@sagescrub](https://peakd.com/@recrypto) for being the authors of the original "WooCommerce Steem Payment Method" plugin before it was forked and updated into this plugin "WooCommerce Hive Payment Method".

## Disclaimer
Authors claim no responsibility for missed transactions, loss of your funds, loss of customer funds, incorrect or delayed exchange rates or any other issue you may encounter as a result of using this plugin. Use this plugin at your own risk.