<?php
declare(strict_types=1);

namespace Payfurl\Payment\Test\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Model\Config decrypts the encrypted settings it reads under payment/payfurl/ (the webhook signing key). A
 * backend_model on one of them in etc/config.xml makes Magento's config reader decrypt it first, so Config decrypted
 * it twice and every signed webhook was refused with 401.
 */
class ConfigXmlTest extends TestCase
{
    public function testSettingsModelConfigDecryptsAreReadEncrypted(): void
    {
        $config = simplexml_load_file(__DIR__ . '/../../etc/config.xml');

        $declared = array_map(
            fn(\SimpleXMLElement $node) => $node->getName(),
            $config->xpath('/config/default/payment/payfurl/*[@backend_model]') ?: []
        );

        $this->assertSame([], $declared);
    }
}
