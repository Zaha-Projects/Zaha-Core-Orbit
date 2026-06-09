@php
    $mapUrl = trim((string) ($mapUrl ?? ''));
    $mapPlaceName = trim((string) ($mapPlaceName ?? ''));
    $mapAddress = trim((string) ($mapAddress ?? ''));
    $mapEmbedUrl = \App\Support\GoogleMaps::embedUrl($mapUrl, $mapPlaceName, $mapAddress);
    $mapNavigationUrl = \App\Support\GoogleMaps::navigationUrl($mapUrl, $mapPlaceName, $mapAddress);
@endphp

<div class="col-12 js-outside-location">
    <div class="monthly-map-preview js-monthly-map-preview"
        data-empty-title="أدخل رابط Google Maps لعرض الموقع هنا"
        data-empty-message="سيظهر الموقع على الخريطة تلقائياً، ويمكن فتح الاتجاهات عبر Google Maps."
        data-open-label="التنقل عبر Google Maps"
        data-preview-label="معاينة موقع النشاط">
        <div class="monthly-map-preview__header">
            <div>
                <div class="monthly-map-preview__eyebrow">موقع النشاط</div>
                <h3 class="monthly-map-preview__title">معاينة موقع النشاط على الخريطة</h3>
            </div>
            @if($mapNavigationUrl)
                <a class="btn btn-sm btn-primary js-monthly-map-open" href="{{ $mapNavigationUrl }}" target="_blank" rel="noopener noreferrer">
                    <i class="fas fa-location-arrow me-1" aria-hidden="true"></i>
                    التنقل عبر Google Maps
                </a>
            @else
                <a class="btn btn-sm btn-primary js-monthly-map-open d-none" href="#" target="_blank" rel="noopener noreferrer">
                    <i class="fas fa-location-arrow me-1" aria-hidden="true"></i>
                    التنقل عبر Google Maps
                </a>
            @endif
        </div>

        <div class="monthly-map-preview__frame js-monthly-map-frame">
            @if($mapEmbedUrl)
                <iframe class="js-monthly-map-iframe" src="{{ $mapEmbedUrl }}" title="معاينة موقع النشاط" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
            @else
                <div class="monthly-map-preview__empty js-monthly-map-empty">
                    <i class="fas fa-map-marked-alt" aria-hidden="true"></i>
                    <strong>أدخل رابط Google Maps لعرض الموقع هنا</strong>
                    <span>سيظهر الموقع على الخريطة تلقائياً، ويمكن فتح الاتجاهات عبر Google Maps.</span>
                </div>
            @endif
        </div>
    </div>
</div>
