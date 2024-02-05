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
        template: 'Payfurl_Payment/payment/payfurl-payto-form',
      },
      getCode: function () {
        return 'payfurl_payto_method';
      },
      getTitle: function () {
        return 'PayTo';
      },
      getFormId: function () {
        return 'payfurl-payto-form';
      },
      initPaymentPayToForm: function () {
        payfurl
          .addPayTo(
            this.getFormId(),
            this.getTotal(),
          );

        return this;
      },
      getData: function () {
        return {
          'method': this.item.method,
          'additional_data': {
            'payfurlToken': payfurlConfig.getToken(),
            'payfurlChargeId': payfurlConfig.getChargeId(),
            'paymentMethodId': payfurlConfig.getPaymentMethodId(),
            'payfurlSaveMyPayment': payfurlConfig.getSaveMyPaymentMethod(),
          },
        };
      },
    });
  },
);

