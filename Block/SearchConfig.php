<?php
declare(strict_types=1);

namespace MageCopilot\SearchIsland\Block;

use MageCopilot\SearchIsland\Service\TypesenseScopedKeyGenerator;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Provides secure frontend configuration for the MageCopilot Search Island micro-island.
 * Generates cryptographic scoped search keys with 7-day TTL to guarantee zero FPC/Varnish desync.
 */
class SearchConfig extends Template
{
    private const XML_PATH_ENABLED = 'magecopilot_search_island/general/enabled';
    private const XML_PATH_TYPESENSE_HOST = 'magecopilot_search_island/general/typesense_host';
    private const XML_PATH_PARENT_KEY = 'magecopilot_search_island/general/typesense_parent_search_key';
    private const XML_PATH_PUBLIC_KEY = 'magecopilot_search_island/general/public_search_key';
    private const XML_PATH_COLLECTION = 'magecopilot_search_island/general/collection_name';
    private const XML_PATH_PLACEHOLDER = 'magecopilot_search_island/general/placeholder';
    private const XML_PATH_URL_SUFFIX = 'magecopilot_search_island/general/url_suffix';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var TypesenseScopedKeyGenerator
     */
    private TypesenseScopedKeyGenerator $keyGenerator;

    /**
     * @var EncryptorInterface
     */
    private EncryptorInterface $encryptor;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param TypesenseScopedKeyGenerator $keyGenerator
     * @param EncryptorInterface $encryptor
     * @param array $data
     * @phpstan-param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        TypesenseScopedKeyGenerator $keyGenerator,
        EncryptorInterface $encryptor,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->keyGenerator = $keyGenerator;
        $this->encryptor = $encryptor;
    }

    /**
     * Check if the Search Island feature is enabled for the current store.
     *
     * Requires the enable flag AND valid configured Typesense host and key (parent or public).
     */
    public function isEnabled(): bool
    {
        if (!$this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE
        )) {
            return false;
        }

        $host = trim((string) $this->scopeConfig->getValue(
            self::XML_PATH_TYPESENSE_HOST,
            ScopeInterface::SCOPE_STORE
        ));

        $parentKey = $this->getDecryptedParentKey();
        $publicKey = trim((string) $this->scopeConfig->getValue(
            self::XML_PATH_PUBLIC_KEY,
            ScopeInterface::SCOPE_STORE
        ));

        return !empty($host) && (!empty($parentKey) || !empty($publicKey));
    }

    /**
     * Retrieve secure client-side configuration array.
     *
     * @return array<string, mixed>
     */
    public function getFrontendConfig(): array
    {
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManager->getStore();
        $storeId = (int) $store->getId();
        $baseUrl = rtrim((string) $store->getBaseUrl(), '/');

        // Resolve search key: Prefer cryptographic scoped key with 7-day TTL
        $searchKey = $this->resolveFrontendSearchKey($storeId);

        return [
            'enabled' => $this->isEnabled(),
            'storeId' => $storeId,
            'typesenseHost' => trim((string) $this->scopeConfig->getValue(
                self::XML_PATH_TYPESENSE_HOST,
                ScopeInterface::SCOPE_STORE
            )),
            'typesenseKey' => $searchKey,
            'collection' => (string) ($this->scopeConfig->getValue(
                self::XML_PATH_COLLECTION,
                ScopeInterface::SCOPE_STORE
            ) ?: 'products'),
            'placeholder' => (string) ($this->scopeConfig->getValue(
                self::XML_PATH_PLACEHOLDER,
                ScopeInterface::SCOPE_STORE
            ) ?: 'Search by product name, SKU, or category...'),
            'urlSuffix' => (string) ($this->scopeConfig->getValue(
                self::XML_PATH_URL_SUFFIX,
                ScopeInterface::SCOPE_STORE
            ) ?: '.html'),
            'baseUrl' => $baseUrl,
            'telemetryEndpoint' => $baseUrl . '/magecopilot/search/telemetry',
            'currency' => (string) $store->getCurrentCurrencyCode(),
            'locale' => (string) ($this->scopeConfig->getValue(
                'general/locale/code',
                ScopeInterface::SCOPE_STORE
            ) ?: 'en_US'),
        ];
    }

    /**
     * Return JSON string of frontend configuration.
     */
    public function getFrontendConfigJson(): string
    {
        return (string) json_encode(
            $this->getFrontendConfig(),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * Resolve frontend search key.
     * When parent key is available, generates an HMAC-SHA256 scoped key for guest (customerGroupId = 0)
     * with a 7-day TTL aligned to midnight boundary.
     *
     * @param int $storeId
     * @return string
     */
    private function resolveFrontendSearchKey(int $storeId): string
    {
        $parentKey = $this->getDecryptedParentKey();
        if (!empty($parentKey) && strlen($parentKey) >= 4) {
            return $this->keyGenerator->generateScopedKey(
                $parentKey,
                $storeId,
                0, // Guest scope for public cached block
                604800 // 7 days TTL to outlive Varnish FPC
            );
        }

        // Fallback to legacy static public search key
        return trim((string) $this->scopeConfig->getValue(
            self::XML_PATH_PUBLIC_KEY,
            ScopeInterface::SCOPE_STORE
        ));
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
            $decrypted = $this->encryptor->decrypt($encrypted);
            return trim((string) $decrypted);
        } catch (\Throwable) {
            return '';
        }
    }
}
