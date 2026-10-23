<?php
declare(strict_types=1);

/**
 * MageCopilot Search Island - Drop-in Instant Search for Hyvä and Luma
 *
 * @category  MageCopilot
 * @package   MageCopilot_SearchIsland
 * @author    MageCopilot Team <support@magecopilot.com>
 * @license   OSL-3.0
 * @link      https://magecopilot.com
 */

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'MageCopilot_SearchIsland',
    __DIR__
);
