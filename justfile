# payFURL's platform checkout, where the e2e suite lives (e2e/api-tests/magento).
platform := env("PLATFORM_DIR", justfile_directory() / "../platform")

# Unit tests, PHPStan and the Magento coding standard (errors only), as CI runs them
test: install
    vendor/bin/phpunit
    vendor/bin/phpstan analyse --no-progress --memory-limit=1G
    vendor/bin/phpcs -q --warning-severity=0

# Dev dependencies, Magento's from the Mage-OS mirror (no composer.lock: each PHP version gets its own Magento)
install:
    composer update --no-interaction --no-progress --ignore-platform-req='ext-*'

# The browser e2e tests against this checkout, run from the platform checkout in Docker; args go to pytest
e2e *args:
    cd {{platform}} && MAGENTO_PLUGIN_DIR={{justfile_directory()}} just e2e magento {{args}}
