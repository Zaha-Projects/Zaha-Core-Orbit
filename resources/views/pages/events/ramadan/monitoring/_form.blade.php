@php $editable = !isset($monitoringReport) || in_array($monitoringReport->status, ['draft','returned'], true); @endphp
<form method="POST" action="{{ $formAction }}">@csrf @if($formMethod !== 'POST') @method($formMethod) @endif
    <div class="card mb-3"><div class="card-header">Report details</div><div class="card-body row g-3">
        <div class="col-md-4"><label>Monitoring method</label><select class="form-select" name="monitoring_method_id" @if(!$editable) disabled @endif>@foreach($monitoringMethods as $method)<option value="{{ $method->id }}" @if(old('monitoring_method_id', $monitoringReport->monitoring_method_id ?? null)==$method->id) selected @endif>{{ $method->name_en ?: $method->name_ar }}</option>@endforeach</select></div>
        <div class="col-md-4"><label>Observed at</label><input class="form-control" type="datetime-local" name="observed_at" value="{{ old('observed_at', isset($monitoringReport) && $monitoringReport->observed_at ? $monitoringReport->observed_at->format('Y-m-d\TH:i') : '') }}" @if(!$editable) disabled @endif></div>
        <div class="col-md-12"><label>General notes</label><textarea class="form-control" name="general_notes" @if(!$editable) disabled @endif>{{ old('general_notes', $monitoringReport->general_notes ?? '') }}</textarea></div>
    </div></div>
    <div class="card mb-3"><div class="card-header">Field verification</div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Field</th><th>Planned</th><th>Actual</th><th>Match</th><th>Note</th></tr></thead><tbody>
    @foreach($candidates as $i => $candidate)
        @php $existing = isset($monitoringReport) ? $monitoringReport->verifications->first(fn($row) => ($row->detail_type ?: null) === ($candidate['detail_type'] ?: null) && (int)($row->detail_id ?: 0) === (int)($candidate['detail_id'] ?: 0) && $row->field_key === $candidate['field_key']) : null; @endphp
        <tr>
            <td>@if($existing)<input type="hidden" name="verifications[{{ $i }}][id]" value="{{ $existing->id }}">@endif<input type="hidden" name="verifications[{{ $i }}][detail_type]" value="{{ $candidate['detail_type'] }}"><input type="hidden" name="verifications[{{ $i }}][detail_id]" value="{{ $candidate['detail_id'] }}"><input type="hidden" name="verifications[{{ $i }}][field_key]" value="{{ $candidate['field_key'] }}"><input type="hidden" name="verifications[{{ $i }}][field_label]" value="{{ $candidate['field_label'] }}">{{ $candidate['field_label'] }}</td>
            <td>{{ is_scalar($candidate['planned']) ? $candidate['planned'] : json_encode($candidate['planned']) }}</td><td>{{ is_scalar($candidate['actual']) ? $candidate['actual'] : json_encode($candidate['actual']) }}</td>
            <td><select class="form-select" name="verifications[{{ $i }}][match_status]" @if(!$editable) disabled @endif>@foreach(\App\Modules\Events\Models\FieldVerification::matchStatuses() as $status)<option value="{{ $status }}" @if(old("verifications.$i.match_status", optional($existing)->match_status ?: 'not_observed')===$status) selected @endif>{{ $status }}</option>@endforeach</select></td>
            <td><input class="form-control" name="verifications[{{ $i }}][note]" value="{{ old("verifications.$i.note", optional($existing)->note) }}" @if(!$editable) disabled @endif></td>
        </tr>
    @endforeach
    </tbody></table></div></div>
    @if($editable)<button class="btn btn-primary">Save monitoring report</button>@endif
</form>
