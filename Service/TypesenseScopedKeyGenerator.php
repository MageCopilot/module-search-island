<?php
declare(strict_types=1);

namespace MageCopilot\SearchIsland\Service;

use InvalidArgumentException;

/**
 * Service for generating cryptographic HMAC-SHA256 scoped search keys for Typesense 30.x.
 * Ensures strict store isolation, inventory filtering, and administrative field protection.
 */
class TypesenseScopedKeyGenerator
{
    /**
     * Generate HMAC-SHA256 Scoped Search Key compatible with Typesense 30.x.
     *
     * @param string $parentSearchKey Master parent search key (minimum 4 characters)
     * @param int $storeId Active Magento store ID
     * @param int $customerGroupId Customer group ID (0 for guest/retail)
     * @param int $ttlSeconds Time-to-live in seconds (default 604800: 7 days to outlive Varnish FPC)
     * @return string Base64-encoded scoped key
     * @throws InvalidArgumentException
     */
    public function generateScopedKey(
        string $parentSearchKey,
        int $storeId,
        int $customerGroupId = 0,
        int $ttlSeconds = 604800
    ): string {
        if (strlen($parentSearchKey) < 4) {
            throw new InvalidArgumentException('Parent search key must be at least 4 characters.');
        }

        // Align expiration with midnight boundary for deterministic cache hits
        $expiresAt = (int) (ceil(time() / 86400) * 86400) + $ttlSeconds;

        $excludeFields = ['embedding', 'internal_margin', 'cost_price', 'supplier_sku'];

        // Strict B2B isolation: guest users cannot view wholesale group prices or tier structures
        if ($customerGroupId === 0) {
            $excludeFields[] = 'group_prices';
            $excludeFields[] = 'tier_prices';
            $excludeFields[] = 'price_group_1';
            $excludeFields[] = 'price_group_2';
            $excludeFields[] = 'price_group_3';
        } else {
            // Company field isolation: exclude multi-group map and isolate other customer group prices
            $excludeFields[] = 'group_prices';
            for ($g = 1; $g <= 3; $g++) {
                if ($g !== $customerGroupId) {
                    $excludeFields[] = "price_group_{$g}";
                }
            }
        }

        $embeddedParams = [
            'filter_by' => sprintf('store_ids:=[%d] && is_in_stock:=[true]', $storeId),
            'exclude_fields' => implode(',', array_values(array_unique($excludeFields))),
            'limit_hits' => 250,
            'expires_at' => $expiresAt
        ];

        $paramsJson = (string) json_encode($embeddedParams, JSON_UNESCAPED_SLASHES);
        $digest = base64_encode(hash_hmac('sha256', $paramsJson, $parentSearchKey, true));
        $keyPrefix = substr($parentSearchKey, 0, 4);

        return base64_encode($digest . $keyPrefix . $paramsJson);
    }

    /**
     * Unpack and decode a generated scoped search key.
     *
     * Useful for verification, testing, and debugging.
     *
     * @param string $scopedKey Base64-encoded scoped key
     * @return array<string, mixed> Decoded structure with digest, prefix, and params
     * @throws InvalidArgumentException
     */
    public function decodeScopedKey(string $scopedKey): array
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $raw = base64_decode($scopedKey, true);
        if ($raw === false || strlen($raw) < 48) {
            throw new InvalidArgumentException('Invalid scoped key format.');
        }

        $digest = substr($raw, 0, 44);
        $keyPrefix = substr($raw, 44, 4);
        $paramsJson = substr($raw, 48);

        $params = json_decode($paramsJson, true);
        if (!is_array($params)) {
            throw new InvalidArgumentException('Malformed parameters payload in scoped key.');
        }

        return [
            'digest' => $digest,
            'key_prefix' => $keyPrefix,
            'params' => $params
        ];
    }
}
