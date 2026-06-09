<?php

namespace Tests\Unit;

use App\Support\GoogleMaps;
use PHPUnit\Framework\TestCase;

class GoogleMapsTest extends TestCase
{
    public function test_it_builds_navigation_and_embed_urls_from_google_maps_coordinates(): void
    {
        $url = 'https://www.google.com/maps/place/Zaha/@31.9539,35.9106,17z';

        $this->assertSame(
            'https://www.google.com/maps/dir/?api=1&destination=31.9539%2C35.9106',
            GoogleMaps::navigationUrl($url)
        );

        $this->assertSame(
            'https://maps.google.com/maps?q=31.9539%2C35.9106&output=embed',
            GoogleMaps::embedUrl($url)
        );
    }

    public function test_it_uses_place_name_when_google_maps_url_has_no_location_query(): void
    {
        $url = 'https://www.google.com/maps?authuser=0';

        $this->assertSame(
            'https://www.google.com/maps/dir/?api=1&destination=%D9%85%D8%B1%D9%83%D8%B2%20%D8%B2%D9%87%D8%A7',
            GoogleMaps::navigationUrl($url, 'مركز زها')
        );

        $this->assertSame(
            'https://maps.google.com/maps?q=%D9%85%D8%B1%D9%83%D8%B2%20%D8%B2%D9%87%D8%A7&output=embed',
            GoogleMaps::embedUrl($url, 'مركز زها')
        );
    }
}
