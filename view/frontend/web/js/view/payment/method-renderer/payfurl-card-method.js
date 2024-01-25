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
        template: 'Payfurl_Payment/payment/payfurl-card-form',
      },
      getCode: function () {
        return 'payfurl_card';
      },
      getTitle: function () {
        return payfurlConfig.getTitle();
      },
      initPaymentCardForm: function () {
        let quoteTotal = quote.getTotals()();
        let orderTotal = this.getSegment('grand_total').value;
        const billingAddress = quote.billingAddress();
        const items = quote.getItems();
        let phone = billingAddress.telephone.replace(/^0/, '+61');
        if (!phone.match(/^\+/)) {
          phone = '+61' + phone;
        }
        payfurl
          .setOrderInfo({
            orderItems: items.map(p => ({
              name: p.name,
              quantity: p.qty,
              sku: p.sku,
              unitPrice: p.price,
              totalAmount: p.price * p.qty,
            })),
            taxAmount: quoteTotal.tax_amount,
            shippingAmount: quoteTotal.shipping_amount,
            discountAmount: quoteTotal.discount_amount,
          })
          .setBillingAddress({
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
          })
          .setCustomerInfo({
            firstName: billingAddress.firstname,
            lastName: billingAddress.lastname,
            email: this.customerEmail,
            phoneNumber: phone,
          })
          .addCardLeastCost(
            'payfurl-card-form',
            orderTotal,
            quoteTotal['quote_currency_code'],
            { threeDSEmail: this.customerEmail },
          );

        return this;
      },
    });
  },
);

