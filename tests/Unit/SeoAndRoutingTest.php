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

    public function testSlugifyText(): void
    {
        $this->assertSame('puding-cokelat-spesial', slugify_text('Puding Cokelat Spesial!'));
        $this->assertSame('salad-buah-creamy-lezat', slugify_text('  Salad Buah Creamy & Lezat  '));
    }
}
