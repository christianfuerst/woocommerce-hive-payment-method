![WooCommerce Steem](https://steemitimages.com/0x0/https://ps.w.org/woo-steem/assets/banner-1544x500.png?rev=1670250)

# WooCommerce Steem
Accept Steem payments directly to your WooCommerce shop!

## Supported Steem Currencies
- Steem (STEEM)
- Steem Backed Dollars (SBD)

## Limitation
- Currently supports different fiat currencies such as: AUD, BGN, BRL, CAD, CHF, CNY, CZK, DKK, GBP, HKD, HRK, HUF, IDR, ILS, INR, JPY, KRW, MXN, MYR, NOK, NZD, PHP, PLN, RON, RUB, SEK, SGD, THB, TRY, ZAR, EUR
- If none of the fiat currency listed above, it will default 1:1 conversion rate.

## How is customer payment made?
When the customer initiates payment the SteemConnect window will be opened. SteemConnect will be populated with the payment amount, currency and memo. The automatically generated memo is a random key that is matched to the order.

## How does it confirm Steem Transfers?
It uses queries the store's STEEM wallet history every 5 minutes to and checks for a transaction that matches the payment MEMO, amount and currency (STEEM or SBD). When the matching payment is found, the order is marked from "payment pending" to "processing".

## Payment reminder email
If the customer does not complete the payment via SteemConnect within several minutes of initiating the payment, a confirmation email will be sent reminding the customer to make payment manually via steem. The payment reminder email will include instructions including the memo.

## Note
You will <strong>NOT</strong> require any Steem keys for this plugin to work. You just have to provide your Steem username and you're good to go.

## Screenshots
![steem01.jpg](https://cdn.steemitimages.com/DQmbiehWamh8pBhsuCcgowhYZPTXLvjrR8V2huzwpvSrpRA/steem01.jpg)

![steem02.jpg](https://cdn.steemitimages.com/DQmNmiHaLkFMBJ27G2RyFHsJH7hSaJTLcxYsLLWXykXV199/steem02.jpg)

![steem03.jpg](https://cdn.steemitimages.com/DQmZwWzUJ92xj7Q9BrA7NH184xFbYA4XvbKKT7PvrP4PCxJ/steem03.jpg)

![steem04.jpg](https://cdn.steemitimages.com/DQmf2Wh9hvQ2HFh12UfjtaUpKQgwV1rpqd2ynYbdJwbTg5o/steem04.jpg)

![steem05.jpg](https://cdn.steemitimages.com/DQmd4c4QtHo8mtSJj9ksSmQLoqzRsDXK2U3zZbEjXAKGihi/steem05.jpg)

![steem06.jpg](https://cdn.steemitimages.com/DQmZqDR1nCHFM2X4xcAtioEhyY4ZsqGggkJxjbGemigTxMC/steem06.jpg)

## Links
- WordPress Plugin Repository (coming soon)

## Thanks
- [@ReCrypto](https://steemit.com/@arcange) for being the original author and inventor of this plugin before it was forked and updated into what it is today. Thank you @ReCrypto for your hard work and starting this plugin project!

## Support
Please support me by following me on Steem [@sagescrub](https://steemit.com/@sagescrub) or if you feel like donating, that would really help a lot on my future efforts with this plugin

Steem: @sagescrub
