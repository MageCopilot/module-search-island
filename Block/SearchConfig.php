<?php
declare(strict_types=1);

namespace MageCopilot\SearchIsland\Block;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Provides secure frontend configuration for the MageCopilot Search Island micro-island.
 */
class SearchConfig extends Template
{
    private const XML_PATH_ENABLED = 'magecopilot_search_island/general/enabled';
    private const XML_PATH_TYPESENSE_HOST = 'magecopilot_search_island/general/typesense_host';
    private const XML_PATH_PUBLIC_KEY = 'magecopilot_search_island/general/public_search_key';
    private const XML_PATH_COLLECTION = 'magecopilot_search_island/general/collection_name';
    private const XML_PATH_PLACEHOLDER = 'magecopilot_search_island/general/placeholder';
    private const XML_PATH_URL_SUFFIX = 'magecopilot_search_island/general/url_suffix';

    private ScopeConfigInterface $scopeConfig;
    private StoreManagerInterface $storeManager;

    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Check if the Search Island feature is enabled for the current store.
     * Requires the enable flag AND valid configured Typesense host and public search key.
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
        $key = trim((string) $this->scopeConfig->getValue(
            self::XML_PATH_PUBLIC_KEY,
            ScopeInterface::SCOPE_STORE
        ));

        return !empty($host) && !empty($key);
    }

    /**
     * Retrieve secure client-side configuration array.
     *
     * @return array<string, mixed>
     */
    public function getFrontendConfig(): array
    {
        $store = $this->storeManager->getStore();
        $baseUrl = rtrim((string) $store->getBaseUrl(), '/');

        return [
            'enabled' => $this->isEnabled(),
            'storeId' => (int) $store->getId(),
            'typesenseHost' => trim((string) $this->scopeConfig->getValue(
                self::XML_PATH_TYPESENSE_HOST,
                ScopeInterface::SCOPE_STORE
            )),
            'typesenseKey' => trim((string) $this->scopeConfig->getValue(
                self::XML_PATH_PUBLIC_KEY,
                ScopeInterface::SCOPE_STORE
            )),
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
            'locale' => (string) ($this->scopeConfig->getValue('general/locale/code', ScopeInterface::SCOPE_STORE) ?: 'en_US'),
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
}
