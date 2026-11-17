<?php
declare(strict_types=1);

namespace MageCopilot\SearchIsland\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\ScopeInterface;

/**
 * Renders warning notice in system configuration if Search Island is enabled
 * but Typesense host or public search key are missing.
 */
class Notice extends Field
{
    public const MODE_MISSING_CREDENTIALS = 'missing_credentials';
    public const MODE_MANAGED_CLOUD = 'managed_cloud';
    public const MODE_SELF_HOSTED_LOCAL = 'self_hosted_local';
    public const MODE_SELF_HOSTED_REMOTE = 'self_hosted_remote';

    /**
     * @var string
     */
    protected $_template = 'MageCopilot_SearchIsland::system/config/notice.phtml';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param array $data
     * @phpstan-param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Render the warning notice row if credentials are missing while enabled.
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $enabled = $this->scopeConfig->isSetFlag(
            'magecopilot_search_island/general/enabled',
            ScopeInterface::SCOPE_STORE
        );

        $host = trim((string) $this->scopeConfig->getValue(
            'magecopilot_search_island/general/typesense_host',
            ScopeInterface::SCOPE_STORE
        ));

        $key = trim((string) $this->scopeConfig->getValue(
            'magecopilot_search_island/general/public_search_key',
            ScopeInterface::SCOPE_STORE
        ));

        $parentKey = trim((string) $this->scopeConfig->getValue(
            'magecopilot_search_island/general/typesense_parent_search_key',
            ScopeInterface::SCOPE_STORE
        ));

        $hasSearchKey = !empty($key) || !empty($parentKey);

        // Do not display notice when module is disabled.
        if (!$enabled) {
            return '';
        }

        if (empty($host) || !$hasSearchKey) {
            $this->setData('notice_mode', self::MODE_MISSING_CREDENTIALS);
        } else {
            $this->setData('notice_mode', self::classifyEndpointMode($host));
            $this->setData('endpoint_host', $host);
        }

        $html = $this->toHtml();
        $htmlId = $element->getHtmlId();

        return sprintf(
            '<tr id="row_%s"><td colspan="3" style="padding: 10px 0;">%s</td></tr>',
            $htmlId,
            $html
        );
    }

    /**
     * Get resolved notice mode for template rendering.
     *
     * @return string
     */
    public function getNoticeMode(): string
    {
        return (string) ($this->getData('notice_mode') ?: self::MODE_MISSING_CREDENTIALS);
    }

    /**
     * Classify Typesense host as Managed Memory Grid Cloud, Local Docker, or Custom Remote Node.
     *
     * @param string $rawHost
     * @return string
     */
    public static function classifyEndpointMode(string $rawHost): string
    {
        $stripped = trim($rawHost);
        $stripped = (string) preg_replace('#^https?://#i', '', $stripped);
        $authority = (string) strtok($stripped, '/?#');
        $host = self::extractHostname($authority);

        if ($host === '' || self::isLocalOrDockerHost($host)) {
            return self::MODE_SELF_HOSTED_LOCAL;
        }

        if (self::isManagedCloudHost($host)) {
            return self::MODE_MANAGED_CLOUD;
        }

        return self::MODE_SELF_HOSTED_REMOTE;
    }

    /**
     * Strip optional credentials and port from authority string.
     *
     * @param string $authority
     * @return string
     */
    private static function extractHostname(string $authority): string
    {
        if (str_contains($authority, '@')) {
            $parts = explode('@', $authority, 2);
            $authority = $parts[1] ?? '';
        }

        if (str_starts_with($authority, '[')) {
            $endBracket = strpos($authority, ']');
            if ($endBracket !== false) {
                return strtolower(substr($authority, 1, $endBracket - 1));
            }
        }

        $colonPos = strpos($authority, ':');
        if ($colonPos !== false) {
            $authority = substr($authority, 0, $colonPos);
        }

        return strtolower(trim($authority));
    }

    /**
     * Check whether hostname points to localhost, Docker service name, or RFC1918 LAN.
     *
     * @param string $host
     * @return bool
     */
    private static function isLocalOrDockerHost(string $host): bool
    {
        if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
            return true;
        }

        if (!str_contains($host, '.')) {
            return true;
        }

        foreach (['.local', '.internal', '.test', '.localhost'] as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return true;
            }
        }

        return (bool) preg_match(
            '/^(10\.|192\.168\.|172\.(1[6-9]|2\d|3[0-1])\.)/',
            $host
        );
    }

    /**
     * Check whether hostname matches Managed Memory Grid / Typesense Cloud endpoints.
     *
     * @param string $host
     * @return bool
     */
    private static function isManagedCloudHost(string $host): bool
    {
        foreach (['magecopilot.com', 'memorygrid.cloud', 'typesense.net', 'hf.space'] as $domain) {
            if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                return true;
            }
        }

        return false;
    }
}
