define(
  [
    'ko',
    'jquery',
    'Payfurl_Payment/js/view/payment/method-renderer/payfurl-base-method',
    'Magento_Checkout/js/model/quote',
    'payfurljs',
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
        return `payfurl_checkout_${this.provider?.providerType}`;
      },
      getTitle: function() {
        const info = payfurlConfig.getCheckoutInfo(this.provider?.providerType);
        return info?.name || 'Checkout';
      },
      getFormId: function() {
        return `payfurl-checkout-form-${this.provider?.providerType}`;
      },
      initPaymentCheckoutForm: function () {
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
          .init(payfurlConfig.getEnv(), payfurlConfig.getPublicKey(), payfurlConfig.isDebug())
          .setOrderInfo({
            orderItems,
            taxAmount: quoteTotal.tax_amount,
            shippingAmount: quoteTotal.shipping_amount,
            discountAmount: quoteTotal.discount_amount,
          })
          .setBillingAddress(address)
          .setCustomerInfo(customer)
          .addBnpl(
            this.getFormId(),
            orderTotal,
            quoteTotal['quote_currency_code'],
            this.provider?.providerType,
            this.provider?.providerId,
            {
              taxAmount: quoteTotal.tax_amount,
              shippingAmount: quoteTotal.shipping_amount,
              discountAmount: quoteTotal.discount_amount,
              orderItems,
              customer,
              billingAddress: address,
            },
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

