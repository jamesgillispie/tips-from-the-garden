<?php

namespace Tests\Feature;

use Tests\TestCase;

class AppMetadataTest extends TestCase
{
    public function test_public_pages_advertise_saved_app_and_social_preview_assets(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee(asset('site.webmanifest'), false)
            ->assertSee('rel="apple-touch-icon"', false)
            ->assertSee(asset('apple-touch-icon.png'), false)
            ->assertSee('property="og:image"', false)
            ->assertSee(asset('og-image.png'), false)
            ->assertSee('name="twitter:card"', false)
            ->assertSee('summary_large_image', false);

        $this->assertFileExists(public_path('icons/app-icon.svg'));
        $this->assertFileExists(public_path('icons/icon-192.png'));
        $this->assertFileExists(public_path('icons/icon-512.png'));
        $this->assertFileExists(public_path('apple-touch-icon.png'));
        $this->assertFileExists(public_path('site.webmanifest'));
        $this->assertFileExists(public_path('og-image.svg'));
        $this->assertFileExists(public_path('og-image.png'));
    }

    public function test_robots_txt_blocks_the_journal_subdomain(): void
    {
        $expected = <<<'ROBOTS'
        # Internal tool for journal.manorhousegardens.org.
        User-agent: *
        Disallow: /
        ROBOTS.PHP_EOL;

        $this->assertFileDoesNotExist(public_path('robots.txt'));

        $this->get('https://journal.manorhousegardens.org/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSeeText($expected);
    }

    public function test_robots_txt_allows_the_primary_domain_to_be_crawled(): void
    {
        $expected = <<<'ROBOTS'
        # Public site for manorhousegardens.org.
        User-agent: *
        Disallow:
        ROBOTS.PHP_EOL;

        $this->get('https://manorhousegardens.org/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSeeText($expected);
    }
}
