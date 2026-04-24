{{-- Keep approval filters composed from the shared select renderer. --}}
@include('pages.shared.filters.select-field', [
    'columnClass' => $statusColumnClass ?? 'col-md-3',
    'fieldName' => $statusFieldName ?? 'approval_status',
    'fieldId' => $statusFieldId ?? ($statusFieldName ?? 'approval_status'),
    'label' => $statusLabel,
    'placeholder' => $statusPlaceholder,
    'options' => $statusOptions ?? [],
    'selectedValue' => $selectedStatus ?? '',
])

@include('pages.shared.filters.select-field', [
    'columnClass' => $stepColumnClass ?? 'col-md-3',
    'fieldName' => $stepFieldName ?? 'current_step',
    'fieldId' => $stepFieldId ?? ($stepFieldName ?? 'current_step'),
    'label' => $stepLabel,
    'placeholder' => $stepPlaceholder,
    'options' => $currentStepOptions ?? [],
    'selectedValue' => $selectedStep ?? '',
])
