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
            customerEmail: quote.guestEmail,
            defaults: {
                template: 'Payfurl_Payment/payment/payfurl-guest'
            },
            initialize: function () {
                this._super();

                let self = this;
                window.quote = quote;

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

                window.addEventListener("message", self.messageHandler);

                return this;
            },
            messageHandler: function(event) {
                if (!event?.data?.status) return;

                if (event?.data?.status === 'checkout') {
                    const saveCheckbox = $('#save_my_payment_method');
                    if (saveCheckbox) {
                        saveCheckbox.hide();
                        saveCheckbox.next().hide();
                    }

                    const checkoutButton = $('button[data-role="review-save"]');
                    if (checkoutButton) {
                        checkoutButton.hide();
                    }
                    return;
                }

                if (event.data.status === "activate" && event.data.component === "card") {
                    const saveCheckbox = $('#save_my_payment_method');
                    if (saveCheckbox) {
                        saveCheckbox.show();
                        saveCheckbox.next().show();
                    }

                    const checkoutButton = $('button[data-role="review-save"]');
                    if (checkoutButton) {
                        checkoutButton.show();
                    }
                }
            },
            initPaymentForm: function () {
                let quoteTotal = quote.getTotals()();
                let orderTotal = this.getSegment('grand_total').value;
                const billingAddress = quote.billingAddress();
                const items = quote.getItems();
                let phone = billingAddress.telephone.replace(/^0/, '+61');
                if (!phone.match(/^\+/)) {
                    phone = '+61' + phone;
                }
                payfurl.init(this.getEnv(), this.getPublicKey(), true)
                      .setOrderInfo({
                          orderItems: items.map(p => ({
                              name: p.name,
                              quantity: p.qty,
                              sku: p.sku,
                              unitPrice: p.price,
                              totalAmount: p.price * p.qty,
                          })),
                          taxAmount: quoteTotal.tax_amount,
                          shippingAmount: quoteTotal.shipping_amount,
                          discountAmount: quoteTotal.discount_amount,
                      })
                      .setBillingAddress({
                          firstName: billingAddress.firstname,
                          lastName: billingAddress.lastname,
                          email: this.customerEmail,
                          phoneNumber: phone,
                          streetAddress: billingAddress.street[0],
                          city: billingAddress.city,
                          country: billingAddress.countryId,
                          region: billingAddress.region,
                          postalCode: billingAddress.postcode,
                          state: billingAddress.regionCode,
                      })
                      .setCustomerInfo({
                          firstName: billingAddress.firstname,
                          lastName: billingAddress.lastname,
                          email: this.customerEmail,
                          phoneNumber: phone,
                      })
                    .addDropIn(
                        'payfurl-checkout-form',
                        orderTotal,
                        quoteTotal['quote_currency_code'],
                        {threeDSEmail: this.customerEmail}
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

