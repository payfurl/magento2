# Changelog

All notable changes to `payfurl/module-payment` are listed here, newest first, in the
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) format. Versions follow
[Semantic Versioning](https://semver.org/) from 2.0.0: a browser SDK update is a patch, a new feature a minor
version, a breaking change a major one. The release workflow publishes each version's section as its GitHub
release notes.

## [Unreleased]

## [2.0.0] - 2026-11-05

### Changed

- **Breaking:** requires Magento Open Source or Adobe Commerce 2.4.7, 2.4.8 or 2.4.9 and PHP 8.2 to 8.5.
  `composer.json` now declares those versions. To upgrade, update Magento first, then run
  `composer require payfurl/module-payment:^2.0` and the commands in the README's "Upgrade" section.
- **Breaking:** the module no longer replaces Magento's core
  `Magento\Payment\Gateway\Data\Order\OrderAdapter` for every payment method on the store. If your own code
  called the methods it added (`getOrderGrandTotal`, `getOrderCurrentCode`, `getCustomerEmail`, ...) on a
  payment's order adapter, read them from the sales order (`$payment->getOrder()`) instead.
- Every payment is checked with payFURL before Magento invoices the order. The charge must have succeeded for
  the order's amount and currency, and a charge already used by another order is refused. Before, an order was
  invoiced as paid for any charge the browser sent, including a failed, pending or other order's charge.
- Payments that are still pending when the order is placed (PayID, PayTo and other checkout methods) leave the
  order in **Payment Review** with an unpaid invoice, until **Get Payment Update** or a payFURL webhook settles
  it.
- A saved card can only be charged for the logged-in customer it belongs to.
- Payments use the API keys of the order's store, so admin refunds on a multi-website store use the right keys.
- Phone numbers are sent as entered: the `+61` prefix is no longer added.
- Product codes and names on the payFURL order are no longer cut to 12 characters, and a configurable product
  is listed once.
- Each payFURL payment form (card, PayPal, Apple Pay, Google Pay, PayTo, checkout methods) loads when the shopper
  first selects it, not when the payment step opens. Click to Pay starts when the shopper selects the card method,
  so a shopper who pays another way is not sent a Click to Pay passcode, and the email they type is the one used.
- Checkout files a theme overrides from 1.x (the card form template and the payment method renderers) keep working:
  the 1.x method names and the `customerEmail` property are still there. 2.0's checkout fixes apply only to the
  module's own files, so remove overrides you no longer need.
- The debug log (`var/log/payfurl.log`) masks the shopper's name, email, phone, address and card details.

### Added

- **Webhook signing key** setting and a webhook endpoint (`https://<your store>/payfurl/webhook`) that settles
  pending payments.

### Fixed

- Paying with a new card works again: **Place Order** pays with the selected saved card, or with the card
  entered in the card form.
- The order's payment method is the one the shopper chose: it is no longer rewritten after payment, which could
  set a method code the module doesn't have (Optty, Pesapal, PayGLocal).
- Checkout methods the module has no payment method for (Pesapal, PayGLocal, Zip) are no longer offered, where
  they could not be paid.
- The **Validate Credentials** button no longer uses an inline script, which a strict Content Security Policy
  blocks.

## [1.5.0] - 2026-06-02

### Changed

- Browser SDK updated from v4.6.22 to v4.8.6
  ([SDK changelog](https://docs.payfurl.com/client_sdk/changelog)).

### Fixed

- The Terms and Conditions checkbox shows for card payments, before **Place Order**.

## Earlier versions

Versions 1.0.5 to 1.4.3 (July 2023 to February 2025) are listed with their tags on
[GitHub](https://github.com/payfurl/magento2/tags).

[Unreleased]: https://github.com/payfurl/magento2/compare/v2.0.0...HEAD
[2.0.0]: https://github.com/payfurl/magento2/compare/v1.5.0...v2.0.0
[1.5.0]: https://github.com/payfurl/magento2/compare/v1.4.3...v1.5.0
