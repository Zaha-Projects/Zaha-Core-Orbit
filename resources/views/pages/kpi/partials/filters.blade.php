{{-- Period / branch / module filters (GET). Params: $filters, $action, $showModule, $years, $months, $branches, $moduleOptions --}}
<form method="GET" action="{{ $action }}" class="kpi-filters" aria-label="تصفية المؤشرات">
    <div class="kpi-filters__field">
        <label for="kpi-year" class="form-label">السنة</label>
        <select id="kpi-year" name="year" class="form-select">
            @foreach ($years as $yearOption)
                <option value="{{ $yearOption }}" @if($filters['year'] === $yearOption) selected @endif>{{ $yearOption }}</option>
            @endforeach
        </select>
    </div>
    <div class="kpi-filters__field">
        <label for="kpi-month" class="form-label">الشهر</label>
        <select id="kpi-month" name="month" class="form-select">
            <option value="">كامل السنة</option>
            @foreach ($months as $monthNumber => $monthLabel)
                <option value="{{ $monthNumber }}" @if($filters['month'] === $monthNumber) selected @endif>{{ $monthLabel }}</option>
            @endforeach
        </select>
    </div>
    @if ($filters['scope']->isAllBranches() || $branches->count() > 1 || $filters['branch_id'] !== null)
        <div class="kpi-filters__field">
            <label for="kpi-branch" class="form-label">الفرع</label>
            <select id="kpi-branch" name="branch_id" class="form-select">
                <option value="">{{ \App\Services\Kpi\KpiScope::hasOversight(auth()->user()) ? 'جميع الفروع' : 'كل الفروع ضمن نطاقك' }}</option>
                @foreach ($branches as $branchOption)
                    <option value="{{ $branchOption->id }}" @if($filters['branch_id'] === (int) $branchOption->id) selected @endif>{{ $branchOption->name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    @if ($showModule)
        <div class="kpi-filters__field">
            <label for="kpi-module" class="form-label">نوع الفعالية</label>
            <select id="kpi-module" name="module" class="form-select">
                <option value="">جميع الفعاليات</option>
                @foreach ($moduleOptions as $moduleKey => $moduleLabel)
                    <option value="{{ $moduleKey }}" @if($filters['module'] === $moduleKey) selected @endif>{{ $moduleLabel }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div class="kpi-filters__actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter" aria-hidden="true"></i> تطبيق</button>
        <a href="{{ $action }}" class="btn btn-outline-secondary">إعادة ضبط</a>
    </div>
</form>

@if ($filters['rejected'] !== [])
    <div class="alert alert-warning kpi-alert" role="status"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i> تم تجاهل قيم تصفية غير صالحة واستخدام القيم الافتراضية.</div>
@endif
@if ($filters['scope']->hasNoBranches())
    <div class="alert alert-info kpi-alert" role="status"><i class="fas fa-circle-info" aria-hidden="true"></i> لا توجد فروع مرتبطة بحسابك، لذلك لا تُعرض أي بيانات. تواصل مع مدير النظام لربط حسابك بفرع.</div>
@endif
