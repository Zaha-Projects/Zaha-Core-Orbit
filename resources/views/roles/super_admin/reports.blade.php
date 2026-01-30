@extends('layouts.app')

@php
    $title = 'التقارير التفصيلية للإدارة العامة';
    $subtitle = 'تقارير تشغيلية ومالية شاملة مع مؤشرات رقمية وملخصات كتابية وفلوشارتس لمسارات العمل.';
@endphp

@section('content')
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-0">{{ $subtitle }}</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h2 class="h6">ملخص البنية</h2>
                    <p class="text-muted small">توزيع الموارد التنظيمية والوحدات الأساسية.</p>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>الفروع</span>
                            <strong>{{ $overview['branches'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>المراكز</span>
                            <strong>{{ $overview['centers'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>المستخدمون</span>
                            <strong>{{ $overview['users'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>المركبات</span>
                            <strong>{{ $overview['vehicles'] }}</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h2 class="h6">ملخص العمليات</h2>
                    <p class="text-muted small">حجم العمليات المسجلة حسب الوحدات.</p>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>الأجندة</span>
                            <strong>{{ $operations['agenda_events'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>الأنشطة الشهرية</span>
                            <strong>{{ $operations['monthly_activities'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>الحجوزات</span>
                            <strong>{{ $operations['bookings'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>طلبات الصيانة</span>
                            <strong>{{ $operations['maintenance_requests'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>الرحلات</span>
                            <strong>{{ $operations['trips'] }}</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h2 class="h6">المؤشرات المالية</h2>
                    <p class="text-muted small">إجمالي العمليات المالية وتحصيل التبرعات.</p>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>عمليات الدفع</span>
                            <strong>{{ $financials['payments'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>إجمالي المدفوعات</span>
                            <strong>{{ number_format($financials['payments_total'], 2) }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>التبرعات النقدية</span>
                            <strong>{{ $financials['donations'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>إجمالي التبرعات</span>
                            <strong>{{ number_format($financials['donations_total'], 2) }}</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h2 class="h6">ملخص كتابي</h2>
                    <p class="text-muted small">
                        يقدم هذا التقرير ملخصاً كتابياً حول الأداء التشغيلي والمالي. تم استخراج المؤشرات
                        من بيانات النظام للتأكد من سلامة تدفق العمل بين الوحدات، مع التركيز على سرعة
                        الاستجابة، كثافة العمليات، واستقرار التمويل.
                    </p>
                    <ul class="mb-0">
                        <li>تحسن في توازن الموارد بين الفروع والمراكز عند مقارنة كثافة الأنشطة.</li>
                        <li>تتبع واضح لعمليات الصيانة والاعتمادات لضمان الحوكمة.</li>
                        <li>مراقبة مالية مستمرة لحجم المدفوعات والتبرعات.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h2 class="h6">حالات الصيانة</h2>
                    <p class="text-muted small">توزيع طلبات الصيانة حسب الحالة.</p>
                    <ul class="list-group list-group-flush">
                        @forelse ($maintenanceStatus as $item)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $item->status }}</span>
                                <strong>{{ $item->total }}</strong>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">لا توجد بيانات.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h2 class="h6">اعتمادات الأجندة</h2>
                    <p class="text-muted small">نتائج قرارات الاعتمادات.</p>
                    <ul class="list-group list-group-flush">
                        @forelse ($agendaApprovals as $item)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $item->decision }}</span>
                                <strong>{{ $item->total }}</strong>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">لا توجد بيانات.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h2 class="h6">حالات الحجوزات</h2>
                    <p class="text-muted small">توزيع الحجوزات حسب الحالة.</p>
                    <ul class="list-group list-group-flush">
                        @forelse ($bookingStatus as $item)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $item->status }}</span>
                                <strong>{{ $item->total }}</strong>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">لا توجد بيانات.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h5 mb-3">فلوشارتس لمسارات العمل</h2>
            <p class="text-muted">مخططات تدفق توضح مسار المعاملات الرئيسية داخل التطبيق.</p>
            <div class="row g-4">
                <div class="col-12 col-lg-6">
                    <div class="border rounded p-3 h-100">
                        <h3 class="h6 mb-3">مسار الصيانة</h3>
                        <pre class="mermaid">
flowchart TD
    A[تسجيل الطلب] --> B{تقييم الأولوية}
    B -->|عادي| C[جدولة الصيانة]
    B -->|عاجل| D[تحويل فوري للفريق]
    C --> E[تنفيذ العمل]
    D --> E
    E --> F[توثيق النتائج]
    F --> G[إغلاق الطلب]
                        </pre>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="border rounded p-3 h-100">
                        <h3 class="h6 mb-3">مسار الأجندة والاعتماد</h3>
                        <pre class="mermaid">
flowchart TD
    A[اقتراح فعالية] --> B[مراجعة مدير البرامج]
    B --> C{قرار الاعتماد}
    C -->|موافقة| D[تضمين في الأجندة]
    C -->|رفض| E[إرجاع للمراجعة]
    D --> F[متابعة التنفيذ]
                        </pre>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="border rounded p-3 h-100">
                        <h3 class="h6 mb-3">مسار النقل</h3>
                        <pre class="mermaid">
flowchart TD
    A[طلب رحلة] --> B[تحديد السائق والمركبة]
    B --> C[تأكيد جدول الرحلة]
    C --> D[تنفيذ الرحلة]
    D --> E[تسجيل الملاحظات]
                        </pre>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="border rounded p-3 h-100">
                        <h3 class="h6 mb-3">مسار الحجوزات والمدفوعات</h3>
                        <pre class="mermaid">
flowchart TD
    A[استلام الحجز] --> B[تأكيد البيانات]
    B --> C[تحصيل الدفعة]
    C --> D{نجاح الدفع؟}
    D -->|نعم| E[تأكيد الحجز]
    D -->|لا| F[إعادة المحاولة]
                        </pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script type="module">
        import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs';
        mermaid.initialize({ startOnLoad: true });
    </script>
@endsection
