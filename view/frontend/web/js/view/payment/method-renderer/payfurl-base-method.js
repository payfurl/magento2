define(
  [
    'ko',
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'Payfurl_Payment/js/model/payfurl',
    'Payfurl_Payment/js/model/payfurl-configuration',
  ],
  function (ko, $, Component, quote, payfurl, payfurlConfig) {
    'use strict';

    window._quote = quote;

    return Component.extend({
      self: this,
      isAvailable: ko.observable(true),
      placeOrderButtonVisible: true,
      isCustomerLoggedIn: window.checkoutConfig.isCustomerLoggedIn,
      totals: quote.totals,
      checkCard: ko.observable('saved'),
      customerEmail: window.checkoutConfig.customerData?.email || quote.guestEmail,

      defaults: {
        template: 'Payfurl_Payment/payment/payfurl-card-form',
      },
      getCode: function() {
        return 'payfurl_card';
      },
      getTitle: function() {
        return payfurlConfig.getTitle();
      },
      getTotal: function () {
        return this.getSegment('grand_total').value;
      },
      getCurrency: function () {
        return quote.getTotals()()['quote_currency_code'];
      },
      initialize: function () {
        this._super();

        const self = this;

        quote.totals.subscribe(function () {
          self.setOrderInfo(quote.getTotals()(), quote.getItems());
        });
        quote.billingAddress.subscribe(function () {
          self.setBillingAddress(quote.billingAddress());
        });

        payfurl.onSuccess(function (response) {
          const payfurlToken = response.token || '';
          const payfurlChargeId = response.transactionId || '';

          payfurlConfig.setToken(payfurlToken);
          payfurlConfig.setChargeId(payfurlChargeId);
          if (self.isCustomerLoggedIn) {
            payfurlConfig.setSaveMyPaymentMethod($('#save_my_payment_method').is(':checked'));
          }

          self.isPlaceOrderActionAllowed(true);
          self.placeOrder();
        });
        payfurl.onFailure(function (errorMessage) {
          self.isPlaceOrderActionAllowed(false);
        });

        this.setBillingAddress(quote.billingAddress());
        this.setOrderInfo(quote.getTotals()(), quote.getItems());

        return this;
      },
      setBillingAddress: function (billingAddress) {
        let phone = billingAddress?.telephone?.replace(/^0/, '+61') || "";
        if (phone && !phone.match(/^\+/)) {
          phone = '+61' + phone;
        }
        payfurl
          .setBillingAddress({
            firstName: billingAddress?.firstname,
            lastName: billingAddress?.lastname,
            email: this.customerEmail,
            phoneNumber: phone,
            streetAddress: billingAddress?.street?.[0],
            city: billingAddress?.city,
            country: billingAddress?.countryId,
            region: billingAddress?.region,
            postalCode: billingAddress?.postcode,
            state: billingAddress?.regionCode,
          })
          .setCustomerInfo({
            firstName: billingAddress?.firstname,
            lastName: billingAddress?.lastname,
            email: this.customerEmail,
            phoneNumber: phone,
          });

        return this;
      },
      setOrderInfo: function (quoteTotal, items) {
        payfurl
          .setAmount(quoteTotal?.grand_total)
          .setOrderInfo({
            orderItems: items?.map(p => ({
              name: p.name,
              quantity: p.qty,
              sku: p.sku,
              unitPrice: p.price,
              totalAmount: p.price * p.qty,
            })),
            taxAmount: quoteTotal?.tax_amount,
            shippingAmount: quoteTotal?.shipping_amount,
            discountAmount: quoteTotal?.discount_amount,
          })

        return this;
      },
      getSavedPaymentMethods: function () {
        let savedPayments = payfurlConfig.getSavedPaymentMethods();
        if (!savedPayments || savedPayments.length === 0) {
          this.checkCard('new');
          return;
        }

        return savedPayments;
      },
      placeOrderWithSavedCard: function () {
        this.isPlaceOrderActionAllowed(false);
        payfurlConfig.setPaymentMethodId($('#payfurl-checkout-saved-select').val());
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

        for (i in this.totals()['total_segments']) {
          total = this.totals()['total_segments'][i];

          if (String(total.code) === String(code)) {
            return total;
          }
        }

        return null;
      },
    });
  },
);

