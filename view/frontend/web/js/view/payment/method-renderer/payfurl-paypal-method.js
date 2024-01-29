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

    return PayfurlBaseMethod.extend({
      defaults: {
        template: 'Payfurl_Payment/payment/payfurl-paypal-form',
      },
      getCode: function() {
        return 'payfurl_paypal';
      },
      getTitle: function() {
        return 'Paypal';
      },
      getFormId: function () {
        return 'payfurl-paypal-form';
      },
      initPaymentPaypalForm: function () {
        payfurl
          .addPaypal(
            this.getFormId(),
            this.getTotal(),
            this.getCurrency(),
          );

        return this;
      },
    });
  },
);

