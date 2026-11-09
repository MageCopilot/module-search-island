<?php
declare(strict_types=1);

namespace MageCopilot\SearchIsland\Test\Unit\Service;

use InvalidArgumentException;
use MageCopilot\SearchIsland\Service\TypesenseScopedKeyGenerator;
use PHPUnit\Framework\TestCase;

class TypesenseScopedKeyGeneratorTest extends TestCase
{
    /**
     * @var TypesenseScopedKeyGenerator
     */
    private TypesenseScopedKeyGenerator $generator;
    private const SAMPLE_PARENT_KEY = 'mcp_search_parent_secret_key_1234567890';

    protected function setUp(): void
    {
        $this->generator = new TypesenseScopedKeyGenerator();
    }

    public function testGenerateScopedKeyThrowsOnShortParentKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Parent search key must be at least 4 characters.');
        $this->generator->generateScopedKey('abc', 1);
    }

    public function testGenerateScopedKeyProducesValidGuestKey(): void
    {
        $storeId = 1;
        $customerGroupId = 0;
        $ttlSeconds = 604800; // 7 days

        $scopedKey = $this->generator->generateScopedKey(
            self::SAMPLE_PARENT_KEY,
            $storeId,
            $customerGroupId,
            $ttlSeconds
        );

        $this->assertNotEmpty($scopedKey);

        $decoded = $this->generator->decodeScopedKey($scopedKey);
        $this->assertSame(substr(self::SAMPLE_PARENT_KEY, 0, 4), $decoded['key_prefix']);
        $this->assertIsArray($decoded['params']);

        $params = $decoded['params'];
        $this->assertSame('store_ids:=[1] && is_in_stock:=[true]', $params['filter_by']);
        $this->assertArrayHasKey('expires_at', $params);
        $this->assertArrayHasKey('exclude_fields', $params);
        $expectedExclude = 'embedding,internal_margin,cost_price,supplier_sku,' .
            'group_prices,tier_prices,price_group_1,price_group_2,price_group_3';
        $this->assertSame($expectedExclude, $params['exclude_fields']);
        $this->assertSame(250, $params['limit_hits']);

        // Verify expiration is aligned with midnight boundary
        $expectedMinExpires = (int) (ceil(time() / 86400) * 86400) + $ttlSeconds;
        $this->assertSame($expectedMinExpires, $params['expires_at']);

        // Verify HMAC authenticity
        $paramsJson = (string) json_encode($params, JSON_UNESCAPED_SLASHES);
        $expectedDigest = base64_encode(hash_hmac('sha256', $paramsJson, self::SAMPLE_PARENT_KEY, true));
        $this->assertSame($expectedDigest, $decoded['digest']);
    }

    public function testGenerateScopedKeyProducesB2BKeyWithCompanyFieldIsolation(): void
    {
        $storeId = 2;
        $customerGroupId = 2; // Wholesale
        $ttlSeconds = 86400; // 1 day

        $scopedKey = $this->generator->generateScopedKey(
            self::SAMPLE_PARENT_KEY,
            $storeId,
            $customerGroupId,
            $ttlSeconds
        );

        $decoded = $this->generator->decodeScopedKey($scopedKey);
        $params = $decoded['params'];

        $this->assertSame('store_ids:=[2] && is_in_stock:=[true]', $params['filter_by']);
        $this->assertArrayHasKey('exclude_fields', $params);
        $this->assertSame(
            'embedding,internal_margin,cost_price,supplier_sku,group_prices,price_group_1,price_group_3',
            $params['exclude_fields']
        );
        $this->assertSame(250, $params['limit_hits']);
    }

    public function testDecodeScopedKeyThrowsOnInvalidPayload(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->generator->decodeScopedKey('invalid-base64-content');
    }
}
