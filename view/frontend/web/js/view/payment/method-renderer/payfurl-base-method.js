define(
  [
    'ko',
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'Payfurl_Payment/js/model/payfurl',
    'Payfurl_Payment/js/model/payfurl-configuration',
    'Magento_Checkout/js/model/payment/additional-validators',
  ],
  function (ko, $, Component, quote, payfurl, payfurlConfig, additionalValidators) {
    'use strict';

    /*
     * The browser SDK keeps a single onSuccess/onFailure/onValidate callback, so they are registered once here and
     * routed to the renderer of the payment method the shopper selected. Registering them in every renderer meant the
     * last renderer to load placed every order, under its own payment method code.
     */
    const renderers = {};
    let wired = false;

    function selectedRenderer() {
      return renderers[quote.paymentMethod()?.method];
    }

    function customerEmail() {
      return window.checkoutConfig.customerData?.email || quote.guestEmail;
    }

    // Sent as entered: the shopper's country is not assumed.
    function customerInfo(billingAddress) {
      return {
        firstName: billingAddress?.firstname,
        lastName: billingAddress?.lastname,
        email: customerEmail(),
        phoneNumber: billingAddress?.telephone || '',
      };
    }

    function setBillingAddress(billingAddress) {
      payfurl
        .setBillingAddress({
          ...customerInfo(billingAddress),
          streetAddress: billingAddress?.street?.[0],
          city: billingAddress?.city,
          country: billingAddress?.countryId,
          region: billingAddress?.region,
          postalCode: billingAddress?.postcode,
          state: billingAddress?.regionCode,
        })
        .setCustomerInfo(customerInfo(billingAddress));
    }

    function setOrderInfo(quoteTotal, items) {
      payfurl
        .setAmount(quoteTotal?.grand_total)
        .setOrderInfo({
          orderItems: items?.map(p => ({
            name: p.name,
            quantity: Number(p.qty),
            sku: p.sku,
            unitPrice: Number(p.price),
            totalAmount: Number(p.price) * Number(p.qty),
          })),
          taxAmount: Number(quoteTotal?.tax_amount),
          shippingAmount: Number(quoteTotal?.shipping_amount),
          discountAmount: Math.abs(Number(quoteTotal?.discount_amount)),
        });
    }

    function wireOnce() {
      if (wired) return;
      wired = true;

      quote.totals.subscribe(() => setOrderInfo(quote.getTotals()(), quote.getItems()));
      quote.billingAddress.subscribe(() => setBillingAddress(quote.billingAddress()));
      setBillingAddress(quote.billingAddress());
      setOrderInfo(quote.getTotals()(), quote.getItems());

      // quote.guestEmail is not observable.
      if (!window.checkoutConfig.customerData?.email) {
        let email = quote.guestEmail;
        setInterval(() => {
          if (email === quote.guestEmail) return;
          email = quote.guestEmail;
          payfurl.setCustomerInfo(customerInfo(quote.billingAddress()));
        }, 500);
      }

      payfurl.onSuccess(function (response) {
        const renderer = selectedRenderer();
        if (!renderer) return;

        payfurlConfig.setToken(response.token || '');
        payfurlConfig.setChargeId(response.transactionId || '');
        payfurlConfig.setPaymentMethodId('');
        payfurlConfig.setSaveMyPaymentMethod(
          Boolean(renderer.isCustomerLoggedIn) && $('#save_my_payment_method').is(':checked'),
        );

        renderer.isPlaceOrderActionAllowed(true);
        renderer.placeOrder();
      });
      payfurl.onFailure(function () {
        selectedRenderer()?.isPlaceOrderActionAllowed(false);
      });
      payfurl.onValidate(() => {
        const form = document.getElementById(`${quote.paymentMethod()?.method}_form`);
        return form?.checkValidity() && additionalValidators.validate();
      });
    }

    return Component.extend({
      isAvailable: ko.observable(true),
      placeOrderButtonVisible: true,
      isCustomerLoggedIn: window.checkoutConfig.isCustomerLoggedIn,
      totals: quote.totals,
      checkCard: ko.observable('saved'),
      customerEmail: customerEmail(),

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
        renderers[this.getCode()] = this;
        wireOnce();

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
        payfurlConfig.setToken('');
        payfurlConfig.setChargeId('');
        payfurlConfig.setSaveMyPaymentMethod(false);
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
