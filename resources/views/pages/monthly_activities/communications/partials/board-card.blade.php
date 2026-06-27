<article class="comm-card">
    <div class="small text-muted mb-1">{{ $item['branch'] }} • {{ $item['date'] ?: '-' }} • {{ $item['time'] }}</div>
    <h3>{{ $item['title'] }}</h3>
    <div class="mb-2">@foreach($item['requirements'] as $requirement)<span class="comm-badge">{{ $requirement }}</span>@endforeach</div>
    <p class="small text-muted mb-2">{{ $item['details'] ?: 'لا توجد تفاصيل إضافية.' }}</p>
    <div class="d-flex gap-2 flex-wrap justify-content-between align-items-center"><span class="badge bg-primary-subtle text-primary">{{ $statusLabels[$item['status']] ?? $item['status'] }}</span><a class="btn btn-sm btn-outline-primary" href="{{ $item['url'] }}">فتح</a></div>
    @if(auth()->user()?->hasAnyRole(['communication_head', 'super_admin']))
        @if(in_array($item['status'], ['approved','preparing'], true))
            <form method="POST" action="{{ route('role.programs.communications_requests.update', $item['model']) }}" class="d-flex gap-1 mt-2">
                @csrf @method('PUT')
                @if($item['status'] === 'approved')<button class="btn btn-sm btn-outline-secondary w-100" name="status" value="preparing">جاري التحضير</button>@endif
                <button class="btn btn-sm btn-success w-100" name="status" value="ready">تم التجهيز</button>
            </form>
        @endif
    @endif
</article>
