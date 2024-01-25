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
        let quoteTotal = quote.getTotals()();
        let orderTotal = this.getSegment('grand_total').value;
        const billingAddress = quote.billingAddress();
        const items = quote.getItems();
        let phone = billingAddress.telephone.replace(/^0/, '+61');
        if (!phone.match(/^\+/)) {
          phone = '+61' + phone;
        }
        const orderItems = items.map(p => ({
          name: p.name,
          quantity: p.qty,
          sku: p.sku,
          unitPrice: p.price,
          totalAmount: p.price * p.qty,
        }));
        const customer = {
          firstName: billingAddress.firstname,
          lastName: billingAddress.lastname,
          email: this.customerEmail,
          phoneNumber: phone,
        };
        const address = {
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
        };
        payfurl
          .setOrderInfo({
            orderItems,
            taxAmount: quoteTotal.tax_amount,
            shippingAmount: quoteTotal.shipping_amount,
            discountAmount: quoteTotal.discount_amount,
          })
          .setBillingAddress(address)
          .setCustomerInfo(customer)
          .addPayTo(
            this.getFormId(),
            orderTotal,
            quoteTotal['quote_currency_code'],
            payfurlConfig.getProvidersInfo()?.payToProviders?.[0],
            {
              maximumAmount: 500,
              description: "PayTo",
            },
            this.provider?.options,
          );

        return this;
      },
      /**
       * Get payment method data
       */
    });
  },
);

