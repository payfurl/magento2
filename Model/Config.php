<?php
namespace Payfurl\Payment\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Payfurl\Payment\Model\Payfurl;
use Magento\Store\Model\ScopeInterface;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    const ACTIVE = 'active';
    const ENV = 'env';

    const ENV_SANDBOX = 'sandbox';

    const ENV_LIVE = 'prod';
    const SANDBOX_PUBLIC_KEY = 'sandbox_public_key';

    const SANDBOX_SECRET_KEY = 'sandbox_secret_key';
    const LIVE_PUBLIC_KEY = 'public_key';

    const LIVE_SECRET_KEY = 'live_secret_key';

    const TITLE = 'title';

    const CAN_GET_SAVED_PAYMENTS = 'can_get_saved_payments';

    protected $scopeConfig;
    protected $storeId;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        string $methodCode = null,
        string $pathPattern = self::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
        $this->scopeConfig = $scopeConfig;
        $this->storeId = null;
    }

    public function isActive(): bool
    {
        return (bool)(int)$this->getScopeConfigValue(self::ACTIVE);
    }

    public function getEnv(): string
    {
        return $this->getScopeConfigValue(self::ENV);
    }

    public function getSandboxPublicKey()
    {
        return $this->getScopeConfigValue(self::SANDBOX_PUBLIC_KEY);
    }

    public function getLivePublicKey()
    {
        return $this->getScopeConfigValue(self::LIVE_PUBLIC_KEY);
    }

    public function getSandboxSecretKey()
    {
        return $this->getScopeConfigValue(self::SANDBOX_SECRET_KEY);
    }

    public function getLiveSecretKey()
    {
        return $this->getScopeConfigValue(self::LIVE_SECRET_KEY);
    }

    public function getTitle(): string
    {
        return $this->getScopeConfigValue(self::TITLE);
    }

    public function getSecretKey()
    {
        if ($this->getEnv() == self::ENV_LIVE) {
            return $this->getLiveSecretKey();
        }
        return $this->getSandboxSecretKey();
    }

    public function getPublicKey()
    {
        if ($this->getEnv() == self::ENV_LIVE) {
            return $this->getLivePublicKey();
        }
        return $this->getSandboxPublicKey();
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

    protected function getScopeConfigValue($path)
    {
        return $this->scopeConfig->getValue(
            'payment/' . Payfurl::METHOD_CODE . '/' . $path,
            ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
    }
}
