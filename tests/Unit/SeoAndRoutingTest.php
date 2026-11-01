<?php
use PHPUnit\Framework\TestCase;

/**
 * Test helper SEO, URL generation, dan normalisasi WhatsApp.
 */
class SeoAndRoutingTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../includes/seo_helpers.php';
    }

    public function testFormatWhatsappNumberConvertsLocalPrefix(): void
    {
        // Format lokal 08... harus diubah ke 628...
        $this->assertSame('6281234567890', format_whatsapp_number('0812-3456-7890'));
        $this->assertSame('6281234567890', format_whatsapp_number('0812 3456 7890'));
        $this->assertSame('6281234567890', format_whatsapp_number('081234567890'));

        // Format internasional +628... harus dibersihkan tanda plus-nya
        $this->assertSame('6281234567890', format_whatsapp_number('+62 812-3456-7890'));

        // Format awalan 8... langsung
        $this->assertSame('6281234567890', format_whatsapp_number('81234567890'));

        // Input kosong / null
        $this->assertSame('', format_whatsapp_number(''));
        $this->assertSame('', format_whatsapp_number(null));
    }

    public function testWhatsappUrlGeneratesValidUrl(): void
    {
        $url1 = whatsapp_url('0812-3456-7890', 'Halo Alfi Kitchen');
        $this->assertSame('https://wa.me/6281234567890?text=Halo%20Alfi%20Kitchen', $url1);

        $url2 = whatsapp_url('081234567890');
        $this->assertSame('https://wa.me/6281234567890', $url2);

        $urlEmpty = whatsapp_url('');
        $this->assertSame('#', $urlEmpty);
    }

    public function testProductPageOffersWhatsappOrderingForEachVariant(): void
    {
        $productPage = file_get_contents(__DIR__ . '/../../product.php');
        $productStyles = file_get_contents(__DIR__ . '/../../assets/css/product.css');

        $this->assertStringContainsString('product-order-button', $productPage);
        $this->assertStringContainsString('$rp[\'name\']', $productPage);
        $this->assertStringContainsString('trim((string) $rp[\'name\'])', $productPage);
        $this->assertStringContainsString('whatsapp_url($wa_footer_number, $waMessage)', $productPage);
        $this->assertStringContainsString('grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 380px))', $productStyles);
        $this->assertStringContainsString('overflow-wrap: anywhere', $productStyles);
    }

    public function testFloatingWhatsappButtonIsIconOnlyAndAccessible(): void
    {
        $footer = file_get_contents(__DIR__ . '/../../footer.php');

        $this->assertStringContainsString('width: 56px', $footer);
        $this->assertStringContainsString('aria-label="Pesan via WhatsApp"', $footer);
        $this->assertStringContainsString('safe-area-inset-bottom', $footer);
        $this->assertStringNotContainsString('<span>Pesan via WhatsApp</span>', $footer);
    }

    public function testSlugifyText(): void
    {
        $this->assertSame('puding-cokelat-spesial', slugify_text('Puding Cokelat Spesial!'));
        $this->assertSame('salad-buah-creamy-lezat', slugify_text('  Salad Buah Creamy & Lezat  '));
    }

    public function testWebManifestExistsAndIsValidJson(): void
    {
        $manifestPath = __DIR__ . '/../../manifest.webmanifest';
        $this->assertFileExists($manifestPath);

        $json = json_decode(file_get_contents($manifestPath), true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey('name', $json);
        $this->assertArrayHasKey('short_name', $json);
        $this->assertArrayHasKey('icons', $json);
        $this->assertNotEmpty($json['icons']);
        $this->assertSame('standalone', $json['display'] ?? '');
    }

    public function testHeaderIncludesManifestAndMobileMetaTags(): void
    {
        $headerContent = file_get_contents(__DIR__ . '/../../header.php');
        $this->assertStringContainsString('rel="manifest"', $headerContent);
        $this->assertStringContainsString('manifest.webmanifest', $headerContent);
        $this->assertStringContainsString('name="apple-mobile-web-app-capable"', $headerContent);
    }

    public function testHomeViewUsesNativeTouchSliderZeroUnusedJs(): void
    {
        $homeView = file_get_contents(__DIR__ . '/../../views/home.view.php');
        $this->assertStringNotContainsString('swiper-bundle.min.js', $homeView);
        $this->assertStringNotContainsString('swiper-bundle.min.css', $homeView);
        $this->assertStringNotContainsString('cdn.jsdelivr.net', $homeView);
        $this->assertStringContainsString('touchstart', $homeView);
        $this->assertStringContainsString('touchend', $homeView);
        $this->assertStringContainsString('swiper-pagination-bullet', $homeView);
    }

    public function testHomeViewUsesResponsiveHeroSrcset(): void
    {
        $homeView = file_get_contents(__DIR__ . '/../../views/home.view.php');
        $this->assertStringContainsString('srcset=', $homeView);
        $this->assertStringContainsString('420w', $homeView);
        $this->assertStringContainsString('640w', $homeView);
        $this->assertStringContainsString('800w', $homeView);
    }

    public function testHomepageProductListDoesNotClipCategories(): void
    {
        $header = file_get_contents(__DIR__ . '/../../header.php');
        $homeView = file_get_contents(__DIR__ . '/../../views/home.view.php');

        $this->assertStringNotContainsString('max-height:250px', $header);
        $this->assertStringNotContainsString('overflow-y:auto; flex:1', $header);
        $this->assertStringContainsString('Produk sedang disiapkan.', $homeView);
    }

    public function testThumbPhpSupportsAmpQueryParamAndOptimalQuality(): void
    {
        $thumbContent = file_get_contents(__DIR__ . '/../../thumb.php');
        $this->assertStringContainsString("\$_GET['amp;w']", $thumbContent);
        $this->assertStringContainsString("\$qParam", $thumbContent);
        $this->assertStringContainsString("\$cachePath", $thumbContent);
        $this->assertStringContainsString("imagewebp(\$dst, \$cachePath, \$q)", $thumbContent);
    }

    public function testRobotsTxtExistsAndPointsToSitemap(): void
    {
        $robotsPath = __DIR__ . '/../../robots.txt';
        $this->assertFileExists($robotsPath);
        $content = file_get_contents($robotsPath);
        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('Sitemap:', $content);
    }
}
