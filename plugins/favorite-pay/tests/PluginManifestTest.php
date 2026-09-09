<?php

declare(strict_types=1);

namespace FavoriteCMS\Tests\Unit\Plugins\FavoritePay;

use FavoriteCMS\Core\Application;
use FavoriteCMS\Plugins\PluginManager;
use PHPUnit\Framework\TestCase;

class PluginManifestTest extends TestCase
{
    private PluginManager $pluginManager;

    protected function setUp(): void
    {
        $app = new Application();
        $this->pluginManager = new PluginManager($app);
    }

    public function testManifestExistsAndIsValid(): void
    {
        $dir = dirname(__DIR__);
        $meta = $this->pluginManager->getPluginMetadata('favorite-pay', $dir);

        $this->assertSame('favorite-pay', $meta['id']);
        $this->assertSame('Favorite Pay', $meta['name']);
        $this->assertSame('1.0.9', $meta['version']);
        $this->assertSame('Favorite CMS Team', $meta['author']);
        $this->assertSame('plugin.php', $meta['entry_point']);
        $this->assertTrue($meta['valid'], 'Plugin manifest must be valid according to Core PluginManager');
        $this->assertTrue($meta['compatible'], 'Plugin must be compatible with server PHP');
        $this->assertEmpty($meta['errors']);
    }

    public function testPluginValidationPasses(): void
    {
        $dir = dirname(__DIR__);
        $meta = $this->pluginManager->getPluginMetadata('favorite-pay', $dir);
        $this->assertTrue($meta['valid']);
        $this->assertEmpty($meta['errors']);
    }
}
