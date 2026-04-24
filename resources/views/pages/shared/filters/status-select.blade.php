{{-- Thin wrapper to preserve a semantic include name for status filters. --}}
@include('pages.shared.filters.select-field', [
    'columnClass' => $columnClass ?? 'col-md-3',
    'fieldName' => $fieldName ?? 'status',
    'fieldId' => $fieldId ?? ($fieldName ?? 'status'),
    'label' => $label ?? null,
    'placeholder' => $placeholder ?? '',
    'options' => $options ?? [],
    'selectedValue' => $selectedValue ?? '',
    'optionValueKey' => $optionValueKey ?? 'code',
    'optionLabelKey' => $optionLabelKey ?? 'name',
])
