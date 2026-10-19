@extends('layouts.app')

@section('title', 'مؤشرات المستخدمين')

@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/kpi-dashboard.css') }}">
@endpush

@section('content')
<div class="kpi-dashboard" dir="rtl">
    @include('pages.kpi.partials.header', [
        'title' => 'مؤشرات المستخدمين',
        'subtitle' => 'مؤشرات أداء الأفراد حسب أدوارهم في سير العمل.',
        'icon' => 'fa-user-check',
    ])
    @include('pages.kpi.partials.section-nav')

    <section class="kpi-soon" aria-labelledby="kpi-users-soon-title">
        <span class="kpi-soon__icon" aria-hidden="true"><i class="fas fa-hourglass-half"></i></span>
        <h2 id="kpi-users-soon-title">قريبًا — سيتم تفعيلها في مرحلة لاحقة</h2>
        <p>تعتمد مؤشرات المستخدمين على قرار إداري معلّق حول من يحق له الاطلاع على أداء الآخرين. حتى اعتماد هذا القرار لا تُعرض أي أرقام فردية.</p>
        <ul class="kpi-soon__list">
            <li><i class="fas fa-square-check" aria-hidden="true"></i> مؤشرات المعتمدين: عدد القرارات وزمن اتخاذها.</li>
            <li><i class="fas fa-pen-to-square" aria-hidden="true"></i> مؤشرات مسؤولي العلاقات: الاعتماد من أول تقديم ودقة بيانات ما بعد التنفيذ.</li>
            <li><i class="fas fa-clipboard-check" aria-hidden="true"></i> مؤشرات المتابعة: إنجاز التحقق والتقييم.</li>
        </ul>
        <a href="{{ route('kpi.overview') }}" class="btn btn-primary"><i class="fas fa-gauge-high" aria-hidden="true"></i> العودة إلى النظرة العامة</a>
    </section>
</div>
@endsection
