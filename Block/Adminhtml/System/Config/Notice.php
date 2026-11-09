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

        // If not enabled or if both host and a valid search key are present, do not render warning
        if (!$enabled || (!empty($host) && $hasSearchKey)) {
            return '';
        }

        $html = $this->toHtml();
        $htmlId = $element->getHtmlId();

        return sprintf(
            '<tr id="row_%s"><td colspan="3" style="padding: 10px 0;">%s</td></tr>',
            $htmlId,
            $html
        );
    }
}
