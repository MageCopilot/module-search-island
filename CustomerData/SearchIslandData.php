<?php
declare(strict_types=1);

namespace MageCopilot\SearchIsland\CustomerData;

use MageCopilot\SearchIsland\Service\TypesenseScopedKeyGenerator;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Throwable;

/**
 * CustomerData section source for MageCopilot Search Island.
 * Emits authenticated B2B scoped search key and customer group to client private storage (mage-cache-storage).
 */
class SearchIslandData implements SectionSourceInterface
{
    private const XML_PATH_ENABLED = 'magecopilot_search_island/general/enabled';
    private const XML_PATH_PARENT_KEY = 'magecopilot_search_island/general/typesense_parent_search_key';

    /**
     * @param CustomerSession $customerSession
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     * @param TypesenseScopedKeyGenerator $keyGenerator
     */
    public function __construct(
        private readonly CustomerSession $customerSession,
        private readonly StoreManagerInterface $storeManager,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor,
        private readonly TypesenseScopedKeyGenerator $keyGenerator
    ) {
    }

    /**
     * Get private section data for customer.
     *
     * @return array<string, mixed>
     */
    public function getSectionData(): array
    {
        if (!$this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE)) {
            return [
                'customer_group_id' => 0,
                'typesense_scoped_key' => '',
            ];
        }

        $customerGroupId = (int) ($this->customerSession->isLoggedIn()
            ? $this->customerSession->getCustomerGroupId()
            : 0);

        $parentKey = $this->getDecryptedParentKey();
        $store = $this->storeManager->getStore();
        $storeId = (int) $store->getId();

        $scopedKey = '';
        if (!empty($parentKey) && strlen($parentKey) >= 4 && $customerGroupId > 0) {
            // Generate authenticated B2B scoped key with 24h TTL
            $scopedKey = $this->keyGenerator->generateScopedKey(
                $parentKey,
                $storeId,
                $customerGroupId,
                86400
            );
        }

        return [
            'customer_group_id' => $customerGroupId,
            'typesense_scoped_key' => $scopedKey,
        ];
    }

    /**
     * Retrieve decrypted Typesense parent search key from configuration.
     *
     * @return string
     */
    private function getDecryptedParentKey(): string
    {
        $encrypted = (string) $this->scopeConfig->getValue(
            self::XML_PATH_PARENT_KEY,
            ScopeInterface::SCOPE_STORE
        );

        if (empty($encrypted)) {
            return '';
        }

        try {
            return trim((string) $this->encryptor->decrypt($encrypted));
        } catch (Throwable) {
            return '';
        }
    }
}
