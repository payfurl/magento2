<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Payfurl\Payment\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use Payfurl\Payment\Gateway\Request\PaymentDataBuilder;
use Payfurl\Payment\Model\Adapter\PayfurlAdapter;
use Psr\Log\LoggerInterface;

/**
 * Class AbstractTransaction
 */
abstract class AbstractTransaction implements ClientInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Logger
     */
    protected $customLogger;

    /**
     * @var \Payfurl\Payment\Model\Adapter\PayfurlAdapterFactory $adapterFactory
     */
    protected $adapterFactory;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param Logger $customLogger
     */
    public function __construct(
        LoggerInterface $logger,
        Logger $customLogger,
        \Payfurl\Payment\Model\Adapter\PayfurlAdapterFactory $adapterFactory
    ) {
        $this->logger = $logger;
        $this->customLogger = $customLogger;
        $this->adapterFactory = $adapterFactory;
    }

    /**
     * @inheritdoc
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $data = $transferObject->getBody();
        $storeId = $data[PaymentDataBuilder::STORE_ID] ?? null;
        unset($data[PaymentDataBuilder::STORE_ID]);
        $log = [
            'request' => $data,
            'client' => static::class
        ];
        $response['object'] = [];

        try {
            $response['object'] = $this->process($data, $this->adapterFactory->create(['storeId' => $storeId]));
        } catch (\Exception $e) {
            $message = __($e->getMessage() ?: 'Sorry, but something went wrong');
            $this->logger->critical($message);
            throw new ClientException($message);
        } finally {
            $log['response'] = (array) $response['object'];
            $this->customLogger->debug($log);
        }

        return $response;
    }

    /**
     * Process http request
     * @param array $data
     * @param PayfurlAdapter $adapter set up with the keys of the order's store
     * @return mixed
     * @throw \payFURL\Sdk\ResponseException
     */
    abstract protected function process(array $data, PayfurlAdapter $adapter);
}
