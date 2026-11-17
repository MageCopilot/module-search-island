<?php
declare(strict_types=1);

namespace MageCopilot\SearchIsland\Test\Unit\Block\Adminhtml\System\Config;

use MageCopilot\SearchIsland\Block\Adminhtml\System\Config\Notice;
use PHPUnit\Framework\TestCase;

class NoticeTest extends TestCase
{
    public function testClassifyEndpointModeDetectsSelfHostedLocalDocker(): void
    {
        $this->assertSame(
            Notice::MODE_SELF_HOSTED_LOCAL,
            Notice::classifyEndpointMode('http://typesense:8108')
        );
        $this->assertSame(
            Notice::MODE_SELF_HOSTED_LOCAL,
            Notice::classifyEndpointMode('http://127.0.0.1:8108/')
        );
        $this->assertSame(
            Notice::MODE_SELF_HOSTED_LOCAL,
            Notice::classifyEndpointMode('https://10.0.1.24:8108')
        );
    }

    public function testClassifyEndpointModeDetectsManagedMemoryGridCloud(): void
    {
        $this->assertSame(
            Notice::MODE_MANAGED_CLOUD,
            Notice::classifyEndpointMode('https://cluster-abc.a1.typesense.net')
        );
        $this->assertSame(
            Notice::MODE_MANAGED_CLOUD,
            Notice::classifyEndpointMode('https://grid-us-east.memorygrid.cloud')
        );
        $this->assertSame(
            Notice::MODE_MANAGED_CLOUD,
            Notice::classifyEndpointMode('https://search.magecopilot.com')
        );
    }

    public function testClassifyEndpointModeDetectsSelfHostedRemoteNode(): void
    {
        $this->assertSame(
            Notice::MODE_SELF_HOSTED_REMOTE,
            Notice::classifyEndpointMode('https://search.merchant-example.com:8108')
        );
    }
}
