@extends('layouts.app')
@section('title', 'البازارات')
@section('content')
<div class="container-fluid" dir="rtl"><div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3">البازارات</h1><p class="text-muted">تخطيط وتنفيذ ومتابعة البازارات</p></div>@can('bazaars.create')<a class="btn btn-primary" href="{{ route('events.bazaars.create') }}">إضافة بازار</a>@endcan</div>
<div class="card"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>الاسم</th><th>الفرع</th><th>التاريخ</th><th>عدد الطاولات</th><th>الحالة</th><th></th></tr></thead><tbody>@forelse($bazaars as $item)<tr><td>{{ $item->name }}</td><td>{{ $item->branch?->name }}</td><td>{{ $item->bazaar_date?->format('Y-m-d') }}</td><td>{{ $item->planned_table_count }}</td><td><span class="badge bg-secondary">{{ $item->status }}</span></td><td><a href="{{ route('events.bazaars.show',$item) }}" class="btn btn-sm btn-outline-primary">عرض</a></td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-4">لا توجد بازارات.</td></tr>@endforelse</tbody></table></div></div>{{ $bazaars->links() }}</div>
@endsection
