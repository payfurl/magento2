define(
  [
    'ko',
    'jquery',
    'Payfurl_Payment/js/view/payment/method-renderer/payfurl-base-method',
    'Magento_Checkout/js/model/quote',
    'Payfurl_Payment/js/model/payfurl',
    'Payfurl_Payment/js/model/payfurl-configuration',
  ],
  function (ko, $, PayfurlBaseMethod, quote, payfurl, payfurlConfig) {
    'use strict';

    window._quote = quote;
    return PayfurlBaseMethod.extend({
      self: this,
      defaults: {
        template: 'Payfurl_Payment/payment/payfurl-checkout-form',
      },
      getCode: function() {
        return `payfurl_checkout_${this.getProviderType()}`;
      },
      getTitle: function() {
        const info = payfurlConfig.getCheckoutInfo(this.getProviderType());
        return info?.name || 'Checkout';
      },
      getProviderType: function() {
        return this.provider?.providerType;
      },
      getProviderLogo: function() {
        if (this.getProviderType() === "azupay") return "payid";
        return this.getProviderType();
      },
      getFormId: function() {
        return `payfurl-checkout-form-${this.getProviderType()}`;
      },
      initPaymentCheckoutForm: function () {
        payfurl
          .addBnpl(
            this.getFormId(),
            this.getTotal(),
            this.getCurrency(),
            this.getProviderType(),
            this.provider?.providerId,
            {},
            this.provider?.options
          );

        return this;
      },
      /**
       * Get payment method data
       */
    });
  },
);

