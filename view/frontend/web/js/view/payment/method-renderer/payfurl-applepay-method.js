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
        template: 'Payfurl_Payment/payment/payfurl-applepay-form',
      },
      getCode: function() {
        return 'payfurl_applepay';
      },
      getTitle: function() {
        return 'Apple Pay';
      },
      getFormId: function () {
        return 'payfurl-applepay-form';
      },
      initPaymentApplePayForm: function () {
        payfurl
          .addApplePay(
            this.getFormId(),
            this.getTotal(),
            this.getCurrency(),
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

