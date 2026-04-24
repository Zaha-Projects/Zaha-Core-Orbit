@php
    $monthFieldName = $monthFieldName ?? 'month';
    $yearFieldName = $yearFieldName ?? 'year';
    $monthFieldId = $monthFieldId ?? $monthFieldName;
    $yearFieldId = $yearFieldId ?? $yearFieldName;
    $selectedMonth = filled($selectedMonth ?? null)
        ? (string) ((int) $selectedMonth)
        : '';
    $selectedYear = (string) ($selectedYear ?? '');
    $currentYear = (int) ($currentYear ?? now()->year);
    $yearStart = (int) ($yearStart ?? ($currentYear - 10));
    $yearEnd = (int) ($yearEnd ?? ($currentYear + 10));

    // Keep the selected year available even if it falls outside the default window.
    if (filled($selectedYear)) {
        $selectedYearInt = (int) $selectedYear;
        $yearStart = min($yearStart, $selectedYearInt);
        $yearEnd = max($yearEnd, $selectedYearInt);
    }

    $monthOptions = collect(range(1, 12))->map(function (int $month): array {
        return [
            'value' => (string) $month,
            'label' => \Carbon\CarbonImmutable::create(2000, $month, 1)
                ->locale(app()->getLocale())
                ->translatedFormat('F'),
        ];
    });

    $yearOptions = collect(range($yearStart, $yearEnd))->reverse()->values();
@endphp

@include('pages.shared.filters.select-field', [
    'columnClass' => $monthColumnClass ?? 'col-md-2',
    'fieldName' => $monthFieldName,
    'fieldId' => $monthFieldId,
    'label' => $monthLabel ?? null,
    'placeholder' => $monthPlaceholder ?? __('app.common.all_months'),
    'options' => $monthOptions,
    'selectedValue' => $selectedMonth,
])

@include('pages.shared.filters.select-field', [
    'columnClass' => $yearColumnClass ?? 'col-md-2',
    'fieldName' => $yearFieldName,
    'fieldId' => $yearFieldId,
    'label' => $yearLabel ?? null,
    'placeholder' => $yearPlaceholder ?? __('app.common.all_years'),
    'options' => $yearOptions->map(fn (int $year) => ['value' => (string) $year, 'label' => (string) $year]),
    'selectedValue' => $selectedYear,
])
