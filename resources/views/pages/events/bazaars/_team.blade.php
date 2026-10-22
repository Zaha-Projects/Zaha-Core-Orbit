<section class="card bazaar-section-card mb-4" data-team-section>
    <div class="card-header bazaar-section-head"><span class="bazaar-section-icon"><i class="fas fa-people-group"></i></span><div class="flex-grow-1"><strong><span>٧</span> {{ __('bazaars.sections.team') }}</strong><small>حدد أسماء الفرق وأعضاء كل فريق والأدوار المسندة إليهم.</small></div><div><label class="form-label" for="bazaar-team-count">عدد الفرق</label><input id="bazaar-team-count" class="form-control" type="number" min="1" max="10" value="{{ count($teamGroups) }}" data-team-count></div></div>
    <div class="card-body" data-team-groups>
        @foreach($teamGroups as $g=>$group)
            @include('pages.events.bazaars._team_group', ['g'=>$g, 'group'=>$group])
        @endforeach
    </div>
</section>
<template id="bazaar-team-template">@include('pages.events.bazaars._team_group', ['g'=>'__GROUP__', 'group'=>['members'=>[[]]]])</template>
<template id="bazaar-member-template">@include('pages.events.bazaars._team_member', ['g'=>'__GROUP__', 'm'=>'__MEMBER__', 'member'=>[]])</template>
