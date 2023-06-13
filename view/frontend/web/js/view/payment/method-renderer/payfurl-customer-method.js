define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'payfurljs',
        'jquery',
        'Magento_Checkout/js/model/full-screen-loader',
        'ko'
    ],
    function (Component, quote, payfurl, $, fullScreenLoader, ko) {
        'use strict';

        return Component.extend({
            totals: quote.totals,
            checkCard: ko.observable('saved'),
            defaults: {
                template: 'Payfurl_Payment/payment/payfurl-customer'
            },
            initialize: function () {
                this._super();

                let self = this;

                /*change amount after quote change*/
                quote.totals.subscribe(function (newValue) {
                    payfurl.setAmount(self.getSegment("grand_total").value);
                });

                // Credit Card form handle
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
                    let payfurlSaveMyPayment = $("#save_my_payment_method").is(':checked') ? true : false;
                    window.checkoutConfig.payment[self.item.method].payfurlSaveMyPayment = payfurlSaveMyPayment;
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
                        {threeDSEmail: this.getCustomerEmail()}
                    );

                // handle hide/show checkbox when click PayPal
                let saveCheckbox = $('#save_my_payment_method');
                $(document).on('click', '#combined-tab1', function (e) {
                    saveCheckbox.hide();
                    saveCheckbox.next().hide();
                });
                $(document).on('click', '#combined-tab0', function (e) {
                    saveCheckbox.show();
                    saveCheckbox.next().show();
                });

                return this;
            },
            getEnv: function() {
                return window.checkoutConfig.payment[this.item.method].env;
            },
            getPublicKey: function () {
                return window.checkoutConfig.payment[this.item.method].publicKey;
            },
            getCustomerEmail: function() {
                return window.checkoutConfig.customerData.email;
            },
            /**
             * Get payment method data
             */
            getData: function () {
                let payfurlData = window.checkoutConfig.payment[this.item.method];
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'payfurlToken' : payfurlData.payfurlToken,
                        'payfurlChargeId' : payfurlData.payfurlChargeId,
                        'paymentMethodId': payfurlData.paymentMethodId,
                        'payfurlSaveMyPayment': payfurlData.payfurlSaveMyPayment
                    }
                };
            },
            getSavedPaymentMethods: function() {
                let savedPayments = window.checkoutConfig.payment[this.item.method].getSavedPayments;
                if (!savedPayments || savedPayments.length == 0) {
                    this.checkCard('new');
                    return;
                }

                return savedPayments;
            },
            placeOrderWithSavedCard: function() {
                this.isPlaceOrderActionAllowed(false);
                let payfurlPaymentMethodId = $("#payfurl-checkout-saved-select").val();
                window.checkoutConfig.payment[this.item.method].paymentMethodId = payfurlPaymentMethodId;
                this.isPlaceOrderActionAllowed(true);
                this.placeOrder();

                return this;
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

