# Contributing

Thanks for helping with the payFURL module for Magento 2. Bug reports and pull requests are welcome on
[GitHub](https://github.com/payfurl/magento2). For a problem with a live store or your payFURL account, contact
payFURL support instead, with `var/log/payfurl.log` (turn **Debug** on first; personal and card details are masked
in it).

## Running the checks

You need PHP 8.2 to 8.5, [Composer](https://getcomposer.org/) and [just](https://just.systems/).

```bash
just test
```

It installs the dev dependencies, then runs the unit tests (`Test/Unit`), PHPStan and the Magento coding standard.
Magento's packages come from the [Mage-OS mirror](https://mirror.mage-os.org/), so no repo.magento.com keys are
needed. GitHub Actions run the same checks on every pull request, on each supported PHP version
(`.github/workflows/ci.yml`).

## Pull requests

* Keep the checks passing, and add a unit test for a change in behaviour.
* Add a line under `## [Unreleased]` in `CHANGELOG.md` saying what changes for merchants.
* payFURL runs browser end-to-end tests (a Magento store against payFURL's own stack) before each release, so
  they don't run on pull requests.

## payFURL staff

The end-to-end tests, a local Magento store with this checkout mounted (`just magento up`), and the release and
browser SDK update steps are in payFURL's platform repository: `docs/integrations/magento.md` and
`e2e/api-tests/magento/README.md`. With that checkout beside this one (or `PLATFORM_DIR` set), `just e2e` runs the
end-to-end tests against this checkout.
