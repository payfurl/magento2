define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';

        if (window.checkoutConfig.isCustomerLoggedIn) {
            rendererList.push(
                {
                    type: 'payfurl',
                    component: 'Payfurl_Payment/js/view/payment/method-renderer/payfurl-customer-method'
                }
            );
            return Component.extend({});
        }

        rendererList.push(
            {
                type: 'payfurl',
                component: 'Payfurl_Payment/js/view/payment/method-renderer/payfurl-guest-method'
            },
            // other payment method renderers if required
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
