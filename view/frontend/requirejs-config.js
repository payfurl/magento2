/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

let config = {
  map: {
    '*': {
      'payfurljs': 'https://assets.payfurl.com/v4.6.17.768/js/payfurl.js',
    },
  },
  paths: {
    'Magento_Checkout/js/view/shipping': 'Payfurl_Payment/js/view/shipping',
  },
};
