<?php
declare(strict_types=1);

namespace MageCopilot\SearchIsland\Test\Unit\Block;

use MageCopilot\SearchIsland\Block\SearchConfig;
use MageCopilot\SearchIsland\Service\TypesenseScopedKeyGenerator;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SearchConfigTest extends TestCase
{
    /** @var Context&MockObject */
    private MockObject $contextMock;

    /** @var ScopeConfigInterface&MockObject */
    private MockObject $scopeConfigMock;

    /** @var StoreManagerInterface&MockObject */
    private MockObject $storeManagerMock;

    /** @var StoreInterface&MockObject */
    private MockObject $storeMock;

    /** @var EncryptorInterface&MockObject */
    private MockObject $encryptorMock;

    /**
     * @var TypesenseScopedKeyGenerator
     */
    private TypesenseScopedKeyGenerator $keyGenerator;

    /**
     * @var SearchConfig
     */
    private SearchConfig $block;

    private const PARENT_KEY = 'mcp_master_search_parent_secret_key_xyz';
    private const PUBLIC_KEY = 'mcp_public_search_fallback_key';

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->storeMock = $this->createMock(Store::class);
        $this->encryptorMock = $this->createMock(EncryptorInterface::class);
        $this->keyGenerator = new TypesenseScopedKeyGenerator();

        $this->storeManagerMock->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->method('getId')->willReturn(1);
        $this->storeMock->method('getBaseUrl')->willReturn('https://example.com/');
        $this->storeMock->method('getCurrentCurrencyCode')->willReturn('USD');

        $this->block = new SearchConfig(
            $this->contextMock,
            $this->scopeConfigMock,
            $this->storeManagerMock,
            $this->keyGenerator,
            $this->encryptorMock
        );
    }

    public function testIsEnabledReturnsTrueWhenParentKeyConfigured(): void
    {
        $this->scopeConfigMock->method('isSetFlag')->willReturn(true);
        $this->scopeConfigMock->method('getValue')->willReturnCallback(function ($path) {
            return match ($path) {
                'magecopilot_search_island/general/typesense_host' => 'https://typesense.example.com',
                'magecopilot_search_island/general/typesense_parent_search_key' => 'encrypted_token_123',
                default => null
            };
        });
        $this->encryptorMock->method('decrypt')->with('encrypted_token_123')->willReturn(self::PARENT_KEY);

        $this->assertTrue($this->block->isEnabled());
    }

    public function testGetFrontendConfigGeneratesScopedGuestKeyWhenParentKeyConfigured(): void
    {
        $this->scopeConfigMock->method('isSetFlag')->willReturn(true);
        $this->scopeConfigMock->method('getValue')->willReturnCallback(function ($path) {
            return match ($path) {
                'magecopilot_search_island/general/typesense_host' => 'https://typesense.example.com',
                'magecopilot_search_island/general/typesense_parent_search_key' => 'encrypted_token_123',
                'magecopilot_search_island/general/collection_name' => 'products',
                'general/locale/code' => 'en_US',
                default => null
            };
        });
        $this->encryptorMock->method('decrypt')->with('encrypted_token_123')->willReturn(self::PARENT_KEY);

        $config = $this->block->getFrontendConfig();

        $this->assertTrue($config['enabled']);
        $this->assertSame(1, $config['storeId']);
        $this->assertSame('https://typesense.example.com', $config['typesenseHost']);
        $this->assertNotSame(self::PARENT_KEY, $config['typesenseKey']);

        // Decode the generated scoped key and verify it contains guest rules and 7-day TTL
        $decoded = $this->keyGenerator->decodeScopedKey($config['typesenseKey']);
        $this->assertSame('store_ids:=[1] && is_in_stock:=[true]', $decoded['params']['filter_by']);
        $expectedExclude = 'embedding,internal_margin,cost_price,supplier_sku,' .
            'group_prices,tier_prices,price_group_1,price_group_2,price_group_3';
        $this->assertSame($expectedExclude, $decoded['params']['exclude_fields']);

        $expectedMinExpires = (int) (ceil(time() / 86400) * 86400) + 604800;
        $this->assertSame($expectedMinExpires, $decoded['params']['expires_at']);
    }

    public function testGetFrontendConfigFallsBackToPublicKeyWhenParentKeyEmpty(): void
    {
        $this->scopeConfigMock->method('isSetFlag')->willReturn(true);
        $this->scopeConfigMock->method('getValue')->willReturnCallback(function ($path) {
            return match ($path) {
                'magecopilot_search_island/general/typesense_host' => 'https://typesense.example.com',
                'magecopilot_search_island/general/typesense_parent_search_key' => '',
                'magecopilot_search_island/general/public_search_key' => self::PUBLIC_KEY,
                default => null
            };
        });

        $config = $this->block->getFrontendConfig();
        $this->assertSame(self::PUBLIC_KEY, $config['typesenseKey']);
    }
}
