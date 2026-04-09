@extends('layouts.app')

@php
    $title = 'عرض تفاصيل النشاط';
    $editMirrorMode = $editMirrorMode ?? false;
@endphp

@section('content')
    <div class="event-module">
        <div class="card event-card mb-4">
            <div class="card-body d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <div>
                    <h1 class="h4 mb-1">{{ $title }}</h1>
                    <p class="text-muted mb-0">{{ $monthlyActivity->title }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-secondary" href="{{ route('role.relations.activities.index') }}">رجوع</a>
                    @if($editMirrorMode)
                        <a class="btn btn-primary" href="{{ route('role.relations.activities.edit', ['monthlyActivity' => $monthlyActivity, 'form' => 1]) }}">فتح نموذج التعديل</a>
                    @else
                        <a class="btn btn-primary" href="{{ route('role.relations.activities.edit', ['monthlyActivity' => $monthlyActivity, 'form' => 1]) }}">تعديل</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="card event-card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-4"><strong>عنوان النشاط:</strong> {{ $monthlyActivity->title }}</div>
                    <div class="col-12 col-md-4"><strong>تاريخ النشاط:</strong> {{ sprintf('%02d-%02d', $monthlyActivity->month, $monthlyActivity->day) }}</div>
                    <div class="col-12 col-md-4"><strong>التاريخ المقترح:</strong> {{ optional($monthlyActivity->proposed_date)->format('Y-m-d') ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>الحالة:</strong> {{ $monthlyActivity->status }}</div>
                    <div class="col-12 col-md-4"><strong>الفرع:</strong> {{ $monthlyActivity->branch?->name ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>مرتبط بفعالية أجندة:</strong> {{ $monthlyActivity->agendaEvent?->event_name ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>ضمن الأجندة السنوية:</strong> {{ $monthlyActivity->is_in_agenda ? 'نعم' : 'لا' }}</div>
                    <div class="col-12 col-md-4"><strong>مصدر النشاط:</strong> {{ $monthlyActivity->is_from_agenda ? 'من الأجندة' : 'إدخال يدوي' }}</div>
                    <div class="col-12 col-md-4"><strong>نوع الخطة:</strong> {{ $monthlyActivity->plan_type ?? '-' }}</div>

                    <div class="col-12"><hr></div>
                    <div class="col-12 col-md-4"><strong>نوع المكان:</strong> {{ $monthlyActivity->location_type === 'outside_center' ? 'خارج المركز' : 'داخل المركز' }}</div>
                    <div class="col-12 col-md-4"><strong>القاعة الداخلية:</strong> {{ $monthlyActivity->internal_location ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>اسم المكان الخارجي:</strong> {{ $monthlyActivity->outside_place_name ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>رابط الموقع:</strong> {{ $monthlyActivity->outside_google_maps_url ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>رقم تواصل المكان:</strong> {{ $monthlyActivity->outside_contact_number ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>من - إلى:</strong> {{ $monthlyActivity->time_from ?? '-' }} / {{ $monthlyActivity->time_to ?? '-' }}</div>

                    <div class="col-12"><hr></div>
                    <div class="col-12 col-md-4"><strong>الوصف المختصر:</strong> {{ $monthlyActivity->short_description ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>الوصف التفصيلي:</strong> {{ $monthlyActivity->description ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>الفئة المستهدفة (نص):</strong> {{ $monthlyActivity->target_group ?? '-' }}</div>
                    <div class="col-12"><strong>الفئات المستهدفة (قائمة):</strong>
                        @forelse($monthlyActivity->targetGroups as $group)
                            <span class="badge bg-light text-dark border">{{ $group->name }}</span>
                        @empty
                            -
                        @endforelse
                    </div>
                    <div class="col-12 col-md-4"><strong>فئة أخرى:</strong> {{ $monthlyActivity->target_group_other ?? '-' }}</div>

                    <div class="col-12"><hr></div>
                    <div class="col-12 col-md-4"><strong>عدد الحضور المتوقع:</strong> {{ $monthlyActivity->expected_attendance ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>عدد الحضور الفعلي:</strong> {{ $monthlyActivity->actual_attendance ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>ملاحظات الحضور:</strong> {{ $monthlyActivity->attendance_notes ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>بحاجة لمتطوعين:</strong> <span class="badge {{ $monthlyActivity->needs_volunteers ? 'bg-success' : 'bg-secondary' }}">{{ $monthlyActivity->needs_volunteers ? '✅ نعم' : '❌ لا' }}</span></div>
                    <div class="col-12 col-md-4"><strong>عدد المتطوعين المطلوب:</strong> {{ $monthlyActivity->required_volunteers ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>احتياج المتطوعين (نصي):</strong> {{ $monthlyActivity->volunteer_need ?? '-' }}</div>

                    <div class="col-12"><hr></div>
                    <div class="col-12 col-md-4"><strong>بحاجة مخاطبات رسمية:</strong> <span class="badge {{ $monthlyActivity->needs_official_correspondence ? 'bg-success' : 'bg-secondary' }}">{{ $monthlyActivity->needs_official_correspondence ? '✅ نعم' : '❌ لا' }}</span></div>
                    <div class="col-12 col-md-4"><strong>سبب المخاطبة:</strong> {{ $monthlyActivity->official_correspondence_reason ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>الجهة المطلوب مخاطبتها:</strong> {{ $monthlyActivity->official_correspondence_target ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>بحاجة خطابات:</strong> <span class="badge {{ $monthlyActivity->needs_official_letters ? 'bg-success' : 'bg-secondary' }}">{{ $monthlyActivity->needs_official_letters ? '✅ نعم' : '❌ لا' }}</span></div>
                    <div class="col-12 col-md-4"><strong>سبب الخطابات:</strong> {{ $monthlyActivity->letter_purpose ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>تغطية إعلامية:</strong> <span class="badge {{ $monthlyActivity->needs_media_coverage ? 'bg-success' : 'bg-secondary' }}">{{ $monthlyActivity->needs_media_coverage ? '✅ نعم' : '❌ لا' }}</span></div>
                    <div class="col-12 col-md-8"><strong>ملاحظات التغطية الإعلامية:</strong> {{ $monthlyActivity->media_coverage_notes ?? '-' }}</div>

                    <div class="col-12"><hr></div>
                    <div class="col-12 col-md-4"><strong>يوجد راعي رسمي:</strong> {{ $monthlyActivity->has_sponsor ? 'نعم' : 'لا' }}</div>
                    <div class="col-12 col-md-4"><strong>يوجد شركاء:</strong> {{ $monthlyActivity->has_partners ? 'نعم' : 'لا' }}</div>
                    <div class="col-12 col-md-4"><strong>كيان مسؤول:</strong> {{ $monthlyActivity->responsible_party ?? '-' }}</div>

                    <div class="col-12"><strong>الرعاة:</strong></div>
                    <div class="col-12">
                        <ul class="mb-0">
                            @forelse($monthlyActivity->sponsors as $sponsor)
                                <li>{{ $sponsor->name }} - {{ $sponsor->title ?? '-' }} ({{ $sponsor->is_official ? 'رسمي' : 'غير رسمي' }})</li>
                            @empty
                                <li>-</li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="col-12"><strong>الشركاء:</strong></div>
                    <div class="col-12">
                        <ul class="mb-0">
                            @forelse($monthlyActivity->partners as $partner)
                                <li>{{ $partner->name }} - {{ $partner->role ?? '-' }} {{ $partner->contact_info ? ' / '.$partner->contact_info : '' }}</li>
                            @empty
                                <li>-</li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="col-12"><strong>المستلزمات:</strong></div>
                    <div class="col-12">
                        <ul class="mb-0">
                            @forelse($monthlyActivity->supplies as $supply)
                                <li>{{ $supply->item_name }} - {{ $supply->available ? 'متوفر' : 'غير متوفر' }} {{ $supply->provider_name ? ' / ' . $supply->provider_name : '' }}</li>
                            @empty
                                <li>-</li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="col-12"><strong>فريق العمل:</strong></div>
                    <div class="col-12">
                        <ul class="mb-0">
                            @forelse($monthlyActivity->team as $member)
                                <li>{{ $member->team_name ?? '-' }} - {{ $member->member_name }} - {{ $member->role_desc ?? '-' }}</li>
                            @empty
                                <li>-</li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="col-12"><hr></div>
                    <div class="col-12 col-md-4"><strong>حالة المشاركة:</strong> {{ $monthlyActivity->participation_status ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>تحويل للبرامج:</strong> {{ $monthlyActivity->requires_programs ? 'نعم' : 'لا' }}</div>
                    <div class="col-12 col-md-4"><strong>تحويل للمشاغل:</strong> {{ $monthlyActivity->requires_workshops ? 'نعم' : 'لا' }}</div>
                    <div class="col-12 col-md-4"><strong>تحويل للعلاقات:</strong> {{ $monthlyActivity->requires_communications ? 'نعم' : 'لا' }}</div>
                    <div class="col-12 col-md-4"><strong>نشاط مرتبط بالبرامج:</strong> {{ $monthlyActivity->is_program_related ? 'نعم' : 'لا' }}</div>
                    <div class="col-12 col-md-4"><strong>ملف الخطة:</strong>
                        @if($monthlyActivity->planning_attachment)
                            <a href="{{ asset('storage/' . $monthlyActivity->planning_attachment) }}" target="_blank">عرض الملف</a>
                        @else
                            -
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
