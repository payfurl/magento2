define(
  [
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list',
  ],
  function (
    Component,
    rendererList,
  ) {
    'use strict';

    window._checkoutConfig = window.checkoutConfig.payment;
    const providersInfo = window.checkoutConfig.payment?.payfurl?.providersInfo;
    if (!providersInfo) {
      return Component.extend({});
    }

    if (providersInfo.hasCardProviders) {
      rendererList.push(
        {
          type: 'payfurl_card',
          component: 'Payfurl_Payment/js/view/payment/method-renderer/payfurl-card-method',
        },
      );
    }

    if (providersInfo.hasPaypalProviders) {
      rendererList.push(
        {
          type: 'payfurl_paypal',
          component: 'Payfurl_Payment/js/view/payment/method-renderer/payfurl-paypal-method',
        },
      );
    }

    if (providersInfo.hasGooglePayProviders) {
      rendererList.push(
        {
          type: 'payfurl_googlepay',
          component: 'Payfurl_Payment/js/view/payment/method-renderer/payfurl-googlepay-method',
        },
      );
    }

    if (providersInfo.hasBnplProviders) {
      for (const provider of providersInfo.bnplProviders) {
        let type = provider.providerType;
        rendererList.push(
          {
            type: `payfurl_checkout_${type}`,
            component: 'Payfurl_Payment/js/view/payment/method-renderer/payfurl-checkout-method',
            config: {
              provider: provider,
            }
          },
        );
      }
    }

    if (providersInfo.hasPayToProviders) {
      rendererList.push(
        {
          type: 'payfurl_payto',
          component: 'Payfurl_Payment/js/view/payment/method-renderer/payfurl-payto-method',
        },
      );
    }

    /** Add view logic here if needed */
    return Component.extend({});
  },
);
