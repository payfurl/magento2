<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Payfurl\Payment\Gateway\Validator;

/**
 * Processes errors codes from Payfurl response.
 */
class ErrorCodeProvider
{
    /**
     * Retrieves list of error codes from Payfurl response.
     *
     * @param  $response
     * @return array
     */
    public function getErrorCodes($response): array
    {
        $result = [];

        if (!empty($response['errorCode'])) {
            $result[] = $response['errorCode'];
        }

        return $result;
    }
}
