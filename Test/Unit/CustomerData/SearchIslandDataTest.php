<?php
declare(strict_types=1);

namespace MageCopilot\SearchIsland\Test\Unit\CustomerData;

use MageCopilot\SearchIsland\CustomerData\SearchIslandData;
use MageCopilot\SearchIsland\Service\TypesenseScopedKeyGenerator;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SearchIslandDataTest extends TestCase
{
    /** @var CustomerSession|MockObject */
    private $customerSessionMock;

    /** @var StoreManagerInterface|MockObject */
    private $storeManagerMock;

    /** @var StoreInterface|MockObject */
    private $storeMock;

    /** @var ScopeConfigInterface|MockObject */
    private $scopeConfigMock;

    /** @var EncryptorInterface|MockObject */
    private $encryptorMock;

    private TypesenseScopedKeyGenerator $keyGenerator;
    private SearchIslandData $sectionData;

    private const SAMPLE_PARENT_KEY = 'mcp_master_search_parent_secret_key_777';

    protected function setUp(): void
    {
        $this->customerSessionMock = $this->createStub(CustomerSession::class);
        $this->storeManagerMock = $this->createStub(StoreManagerInterface::class);
        $this->storeMock = $this->createStub(StoreInterface::class);
        $this->scopeConfigMock = $this->createStub(ScopeConfigInterface::class);
        $this->encryptorMock = $this->createStub(EncryptorInterface::class);
        $this->keyGenerator = new TypesenseScopedKeyGenerator();

        $this->storeManagerMock->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->method('getId')->willReturn(1);

        $this->sectionData = new SearchIslandData(
            $this->customerSessionMock,
            $this->storeManagerMock,
            $this->scopeConfigMock,
            $this->encryptorMock,
            $this->keyGenerator
        );
    }

    public function testGetSectionDataReturnsEmptyWhenDisabled(): void
    {
        $this->scopeConfigMock->method('isSetFlag')->willReturn(false);

        $result = $this->sectionData->getSectionData();
        $this->assertSame(0, $result['customer_group_id']);
        $this->assertSame('', $result['typesense_scoped_key']);
    }

    public function testGetSectionDataReturnsGuestWhenNotLoggedIn(): void
    {
        $this->scopeConfigMock->method('isSetFlag')->willReturn(true);
        $this->customerSessionMock->method('isLoggedIn')->willReturn(false);
        $this->customerSessionMock->method('getCustomerGroupId')->willReturn(0);

        $result = $this->sectionData->getSectionData();
        $this->assertSame(0, $result['customer_group_id']);
        $this->assertSame('', $result['typesense_scoped_key']);
    }

    public function testGetSectionDataGeneratesB2BScopedKeyWhenLoggedIn(): void
    {
        $this->scopeConfigMock->method('isSetFlag')->willReturn(true);
        $this->customerSessionMock->method('isLoggedIn')->willReturn(true);
        $this->customerSessionMock->method('getCustomerGroupId')->willReturn(2); // Wholesale
        $this->scopeConfigMock->method('getValue')->willReturn('encrypted_parent_secret');
        $this->encryptorMock->method('decrypt')->willReturn(self::SAMPLE_PARENT_KEY);

        $result = $this->sectionData->getSectionData();
        $this->assertSame(2, $result['customer_group_id']);
        $this->assertNotEmpty($result['typesense_scoped_key']);

        $decoded = $this->keyGenerator->decodeScopedKey($result['typesense_scoped_key']);
        $this->assertSame('store_ids:=[1] && is_in_stock:=[true]', $decoded['params']['filter_by']);
        $this->assertArrayNotHasKey('exclude_fields', $decoded['params']);
    }
}
