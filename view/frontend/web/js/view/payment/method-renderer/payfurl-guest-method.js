define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'payfurljs',
        'jquery'
    ],
    function (Component, quote, payfurl, $) {
        'use strict';

        return Component.extend({
            totals: quote.totals,
            defaults: {
                template: 'Payfurl_Payment/payment/payfurl-guest'
            },
            initialize: function () {
                this._super();

                let self = this;

                /*change amount after quote change*/
                quote.totals.subscribe(function (newValue) {
                    payfurl.setAmount(self.getSegment("grand_total").value);
                });

                payfurl.onSuccess(function(response){
                    let payfurlToken = "";

                    let payfurlChargeId = "";

                    if (typeof response.token !== "undefined") {
                        payfurlToken = response.token;
                    }

                    if (typeof response.transactionId !== "undefined") {
                        payfurlChargeId = response.transactionId;
                    }

                    window.checkoutConfig.payment[self.item.method].payfurlToken = payfurlToken;
                    window.checkoutConfig.payment[self.item.method].payfurlChargeId = payfurlChargeId
                    self.isPlaceOrderActionAllowed(true);
                    self.placeOrder();
                });

                payfurl.onFailure(function(errorMessage){
                    self.isPlaceOrderActionAllowed(false);
                });

                return this;
            },
            initPaymentForm: function () {
                let quoteTotal = quote.getTotals()();
                let orderTotal = this.getSegment("grand_total").value;
                payfurl.init(this.getEnv(), this.getPublicKey())
                    .addDropIn(
                        'payfurl-checkout-form',
                        orderTotal,
                        quoteTotal['quote_currency_code'],
                        {threeDSEmail: quote.guestEmail}
                    );

                return this;
            },
            getEnv: function() {
                return window.checkoutConfig.payment[this.item.method].env;
            },
            getPublicKey: function () {
                return window.checkoutConfig.payment[this.item.method].publicKey;
            },
            /**
             * Get payment method data
             */
            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'payfurlToken' : window.checkoutConfig.payment[this.item.method].payfurlToken,
                        "payfurlChargeId" : window.checkoutConfig.payment[this.item.method].payfurlChargeId
                    }
                };
            },
            /**
             * @param {*} code
             * @return {*}
             */
            getSegment: function (code) {
                let i, total;

                if (!this.totals()) {
                    return null;
                }

                for (i in this.totals()['total_segments']) { //eslint-disable-line guard-for-in
                    total = this.totals()['total_segments'][i];

                    if (total.code == code) { //eslint-disable-line eqeqeq
                        return total;
                    }
                }

                return null;
            }
        });
    }
);

