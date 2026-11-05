<?php
namespace Payfurl\Payment\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Payfurl\Payment\Model\Payfurl;
use Magento\Store\Model\ScopeInterface;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    const ACTIVE = 'active';
    const DEBUG = 'debug';
    const ENV = 'env';
    const ENABLE_GOOGLEPAY = 'enable_googlepay';
    const ENABLE_APPLEPAY = 'enable_applepay';

    const ENV_SANDBOX = 'sandbox';

    const ENV_LIVE = 'production';
    const SANDBOX_PUBLIC_KEY = 'sandbox_public_key';

    const SANDBOX_SECRET_KEY = 'sandbox_secret_key';
    const LIVE_PUBLIC_KEY = 'public_key';

    const LIVE_SECRET_KEY = 'secret_key';

    const TITLE = 'title';

    const WEBHOOK_SIGNING_KEY = 'webhook_signing_key';

    const CAN_GET_SAVED_PAYMENTS = 'can_get_saved_payments';

    protected $scopeConfig;
    protected $storeId;
    protected $_encryptor;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        ?string $methodCode = null,
        string $pathPattern = self::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
        $this->scopeConfig = $scopeConfig;
        $this->storeId = null;
        $this->_encryptor = $encryptor;
    }

    public function isActive(): bool
    {
        return (bool)(int)$this->getScopeConfigValue(self::ACTIVE);
    }

    public function isDebug(): bool
    {
        return (bool)(int)$this->getScopeConfigValue(self::DEBUG);
    }

    public function isGooglePay(): bool
    {
        return (bool)(int)$this->getScopeConfigValue(self::ENABLE_GOOGLEPAY);
    }

    public function isApplePay(): bool
    {
        return (bool)(int)$this->getScopeConfigValue(self::ENABLE_APPLEPAY);
    }

    public function getEnv($storeId = null): string
    {
        return (string)$this->getScopeConfigValue(self::ENV, $storeId);
    }

    public function getSandboxPublicKey($storeId = null)
    {
        return $this->_encryptor->decrypt((string)$this->getScopeConfigValue(self::SANDBOX_PUBLIC_KEY, $storeId));
    }

    public function getLivePublicKey($storeId = null)
    {
        return $this->_encryptor->decrypt((string)$this->getScopeConfigValue(self::LIVE_PUBLIC_KEY, $storeId));
    }

    public function getSandboxSecretKey($storeId = null)
    {
        return $this->_encryptor->decrypt((string)$this->getScopeConfigValue(self::SANDBOX_SECRET_KEY, $storeId));
    }

    public function getLiveSecretKey($storeId = null)
    {
        return $this->_encryptor->decrypt((string)$this->getScopeConfigValue(self::LIVE_SECRET_KEY, $storeId));
    }

    public function getTitle(): string
    {
        return $this->getScopeConfigValue(self::TITLE);
    }

    public function getSecretKey($storeId = null)
    {
        if ($this->getEnv($storeId) == self::ENV_LIVE) {
            return $this->getLiveSecretKey($storeId);
        }
        return $this->getSandboxSecretKey($storeId);
    }

    public function getPublicKey($storeId = null)
    {
        if ($this->getEnv($storeId) == self::ENV_LIVE) {
            return $this->getLivePublicKey($storeId);
        }
        return $this->getSandboxPublicKey($storeId);
    }

    /**
     * Signing key of the merchant's payFURL webhook subscription.
     */
    public function getWebhookSigningKey($storeId = null): string
    {
        return (string)$this->_encryptor->decrypt((string)$this->getScopeConfigValue(self::WEBHOOK_SIGNING_KEY, $storeId));
    }

    public function canGetSavedPayments(): bool
    {
        return (bool)(int)$this->getScopeConfigValue(self::CAN_GET_SAVED_PAYMENTS);
    }

    /**
     * Payment environments is used for current application with provided credentials
     * https://github.com/payfurl/phpsdk/blob/master/src/Config.php
     *
     * @return array
     */
    public function getEnvironments(): array
    {
        return [
            self::ENV_SANDBOX => __('Sandbox'),
            self::ENV_LIVE => __('Production')
        ];
    }

    /**
     * @param string $path
     * @param int|string|null $storeId the order's store for gateway calls; null is the current store
     * @return mixed
     */
    protected function getScopeConfigValue($path, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            'payment/payfurl/' . $path,
            ScopeInterface::SCOPE_STORE,
            $storeId ?? $this->storeId
        );
    }
}
