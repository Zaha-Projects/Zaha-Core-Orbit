<ul class="nav nav-tabs mb-3" role="tablist">
    @foreach($approvalTabs as $tab)
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $activeApprovalTab === $tab['key'] ? 'active' : '' }}"
               href="{{ route('role.relations.approvals.index', array_merge(request()->except(['page', 'delete_page', 'edit_page']), ['tab' => $tab['key']])) }}">
                {{ $tab['label'] }}
            </a>
        </li>
    @endforeach
</ul>
