@extends('layouts.app')
@section('title', 'دليل الموقع والأدوار')
@push('styles')
<style>.guide-hero{background:linear-gradient(135deg,#075985,#0891b2);color:#fff;border-radius:24px}.guide-role{border:0;border-radius:20px;box-shadow:0 8px 28px rgba(15,23,42,.08)}.guide-role[hidden]{display:none!important}.guide-flow{border-right:4px solid #0891b2;padding-right:1rem}.guide-chip{display:inline-block;background:#ecfeff;color:#0e7490;border-radius:999px;padding:.25rem .65rem;margin:.15rem}.guide-list li{margin-bottom:.45rem}</style>
@endpush
@section('content')
<div class="container-fluid py-4" dir="rtl">
<section class="guide-hero p-4 p-lg-5 mb-4"><h1 class="h2 fw-bold"><i class="fas fa-book-open ms-2"></i>دليل الموقع والأدوار</h1><p class="mb-0 opacity-75">دليل عملي يوضح ما تستلمه، وما تنجزه، ولمن تسلّم في كل دور فعّال.</p></section>
<div class="card border-0 shadow-sm mb-4"><div class="card-body row g-3 align-items-end"><div class="col-lg-8"><label class="form-label fw-bold" for="guide-search">ابحث باسم الدور أو الوحدة أو المسؤولية</label><input id="guide-search" class="form-control form-control-lg" type="search" placeholder="مثال: رمضان، التقييم، مسؤول العلاقات"></div><div class="col-lg-4"><label class="form-label fw-bold" for="guide-role-filter">اختر الدور</label><select id="guide-role-filter" class="form-select form-select-lg"><option value="">كل الأدوار</option>@foreach($guide as $role)<option value="{{ $role['key'] }}">{{ $role['title'] }}</option>@endforeach</select></div></div></div>
<div class="row g-4" id="guide-roles">
@foreach($guide as $role)
@php($searchText=collect([$role['title'],$role['key'],$role['purpose'],...$role['modules'],...$role['responsibilities'],...$role['monthly'],...$role['ramadan']])->join(' '))
<div class="col-12 guide-role-wrap" data-role="{{ $role['key'] }}" data-search="{{ $searchText }}"><article class="card guide-role"><div class="card-body p-4">
<div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><div><h2 class="h4 fw-bold mb-1">{{ $role['title'] }}</h2><code dir="ltr">{{ $role['key'] }}</code></div><div>@foreach($role['modules'] as $module)<span class="guide-chip">{{ $module }}</span>@endforeach</div></div><p class="lead">{{ $role['purpose'] }}</p>
<div class="row g-4"><div class="col-lg-4"><h3 class="h6 fw-bold">المهام الرئيسية</h3><ul class="guide-list">@foreach($role['responsibilities'] as $item)<li>{{ $item }}</li>@endforeach</ul></div><div class="col-lg-4"><h3 class="h6 fw-bold">الأنشطة الشهرية</h3><ul class="guide-list">@foreach($role['monthly'] as $item)<li>{{ $item }}</li>@endforeach</ul><h3 class="h6 fw-bold mt-3">إفطارات رمضان</h3><ul class="guide-list">@foreach($role['ramadan'] as $item)<li>{{ $item }}</li>@endforeach</ul></div><div class="col-lg-4 guide-flow"><h3 class="h6 fw-bold">ما الذي أستلمه؟</h3><ul>@foreach($role['receives'] as $item)<li>{{ $item }}</li>@endforeach</ul><h3 class="h6 fw-bold">ماذا أنجز ولمن أسلّم؟</h3><ul>@foreach($role['hands_off'] as $item)<li>{{ $item }}</li>@endforeach</ul><h3 class="h6 fw-bold">القيود</h3><ul>@foreach($role['restrictions'] as $item)<li>{{ $item }}</li>@endforeach</ul></div></div>
<div class="mt-3"><strong>الصفحات الرئيسية:</strong> {{ implode('، ', $role['pages']) }}</div>
</div></article></div>
@endforeach
</div>
<div id="guide-empty" class="alert alert-info text-center mt-4" hidden>لا توجد أدوار مطابقة للبحث.</div>
@if($showTechnicalReference)<section class="card border-0 shadow-sm mt-5"><div class="card-header fw-bold">مرجع تقني لمسؤول النظام</div><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>الدور</th><th>المفتاح</th><th>الصلاحيات الفعلية</th><th>مجموعات المسارات</th><th>المتحكمات/الوحدات الرئيسية</th></tr></thead><tbody>@foreach($guide as $role)<tr><td>{{ $role['title'] }}</td><td><code>{{ $role['key'] }}</code></td><td class="small" dir="ltr">{{ implode(', ', $role['permissions']) ?: '—' }}</td><td class="small" dir="ltr">{{ implode(', ', $role['route_groups']) }}</td><td>{{ implode('، ', $role['controllers']) }}</td></tr>@endforeach</tbody></table></div></section>@endif
</div>
@endsection
@push('scripts')<script src="{{ asset('assets/js/site-role-guide.js') }}" defer></script>@endpush
