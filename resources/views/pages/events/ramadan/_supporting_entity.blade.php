@php($supporter = $ramadanIftar->host_type === 'local_community' ? $ramadanIftar->localCommunity : $ramadanIftar->communityOrganization)
<div class="card shadow-sm mb-3">
    <div class="card-header fw-semibold">الجهة الداعمة</div>
    <div class="card-body"><dl class="row mb-0">
        <dt class="col-sm-3">نوع الجهة الداعمة</dt><dd class="col-sm-9">{{ __('ramadan_iftars.options.'.$ramadanIftar->host_type) }}</dd>
        <dt class="col-sm-3">{{ $ramadanIftar->host_type === 'individual' ? 'اسم الفرد الداعم' : 'اسم الجهة الداعمة' }}</dt><dd class="col-sm-9">{{ $supporter?->name ?: '—' }}</dd>
        <dt class="col-sm-3">اسم ضابط الارتباط</dt><dd class="col-sm-9">{{ $ramadanIftar->contact_name ?? $supporter?->contact_name ?? '—' }}</dd>
        <dt class="col-sm-3">رقم هاتف ضابط الارتباط</dt><dd class="col-sm-9">{{ $ramadanIftar->contact_phone ?? $supporter?->contact_phone ?? '—' }}</dd>
    </dl></div>
</div>
