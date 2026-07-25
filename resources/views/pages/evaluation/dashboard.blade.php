@extends('layouts.app')
@section('title', __('evaluation.title'))
@section('content')
<div class="container-fluid"><h1>{{ __('evaluation.title') }}</h1><div class="row">@foreach($stats as $key=>$value)<div class="col-md-3 mb-3"><div class="card h-100"><div class="card-body"><div class="text-muted">{{ __('evaluation.dashboard.'.$key) }}</div><div class="display-6">{{ $value }}</div></div></div></div>@endforeach</div><div class="card"><div class="card-header">{{ __('evaluation.dashboard.latest') }}</div><table class="table"><tbody>@foreach($latest as $item)<tr><td><a href="{{ route('evaluations.show',$item) }}">{{ $item->activity?->title }}</a></td><td>{{ $item->branch?->name }}</td><td>{{ $item->normalized_score }}/10</td><td>{{ $item->submitted_at }}</td></tr>@endforeach</tbody></table></div></div>
@endsection
