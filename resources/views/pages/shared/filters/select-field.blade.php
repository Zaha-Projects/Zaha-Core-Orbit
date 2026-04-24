{{-- Reusable select field renderer for filter forms and small option lists. --}}
@php
    $fieldName = $fieldName ?? 'filter';
    $fieldId = $fieldId ?? $fieldName;
    $optionValueKey = $optionValueKey ?? 'value';
    $optionLabelKey = $optionLabelKey ?? 'label';
@endphp

<div class="{{ $columnClass ?? 'col-md-3' }}">
    @if (filled($label ?? null))
        <label class="form-label" for="{{ $fieldId }}">{{ $label }}</label>
    @endif
    <select class="{{ $selectClass ?? 'form-select' }}" id="{{ $fieldId }}" name="{{ $fieldName }}">
        @if (isset($placeholder))
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach (($options ?? []) as $option)
            @php
                $optionValue = is_array($option)
                    ? ($option[$optionValueKey] ?? null)
                    : data_get($option, $optionValueKey);
                $optionLabel = is_array($option)
                    ? ($option[$optionLabelKey] ?? null)
                    : data_get($option, $optionLabelKey);
            @endphp
            <option value="{{ $optionValue }}" {{ (string) ($selectedValue ?? '') === (string) $optionValue ? 'selected' : '' }}>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>
</div>
