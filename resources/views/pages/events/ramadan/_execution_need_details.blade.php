@if($needCode === 'execution_team')
    <div class="mt-3" data-repeat="execution_teams">
        <h6>بيانات فريق التنفيذ</h6>
        <div class="card-body p-0">
            @foreach($collections['execution_teams'] as $i=>$team)
                <div class="planning-row border rounded p-2 mb-2" data-execution-team>
                    <input type="hidden" name="execution_teams[{{ $i }}][id]" value="{{ $team['id']??'' }}">
                    <div class="row g-2">
                        <div class="col-md-5">
                            <label class="form-label">اسم الفريق</label>
                            <input class="form-control" name="execution_teams[{{ $i }}][name]" value="{{ $team['name']??'' }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">قائد الفريق</label>
                            <select class="form-select" name="execution_teams[{{ $i }}][leader_user_id]">
                                <option value="">—</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ ($team['leader_user_id'] ?? null) == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">العدد المخطط</label>
                            <input type="number" min="1" class="form-control" data-member-count name="execution_teams[{{ $i }}][planned_members_count]" value="{{ $team['planned_members_count']??1 }}">
                        </div>
                        @foreach($team['members']??[] as $j=>$member)
                            <div class="row g-2 mt-2" data-team-member>
                                <input type="hidden" name="execution_teams[{{ $i }}][members][{{ $j }}][id]" value="{{ $member['id']??'' }}">
                                <div class="col-12 fw-semibold" data-member-title>عضو {{ $j + 1 }} من {{ max(1, (int) ($team['planned_members_count'] ?? count($team['members'] ?? []))) }}</div>
                                <div class="col-md-4">
                                    <label class="form-label">عضو الفريق</label>
                                    <input class="form-control" name="execution_teams[{{ $i }}][members][{{ $j }}][member_name]" value="{{ $member['member_name']??'' }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">الدور</label>
                                    <input class="form-control" name="execution_teams[{{ $i }}][members][{{ $j }}][role_name]" value="{{ $member['role_name']??'' }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">المهمة</label>
                                    <input class="form-control" name="execution_teams[{{ $i }}][members][{{ $j }}][task_description]" value="{{ $member['task_description']??'' }}">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@elseif($needCode === 'supplies')
    <div class="mt-3" data-repeat="supplies" data-supplies>
        <h6>المستلزمات واللوازم</h6>
        <div class="mb-2"><label class="form-label">عدد بنود المستلزمات</label><input class="form-control" type="number" min="1" data-supplies-count value="{{ max(1, count($collections['supplies'])) }}"></div>
        <div class="card-body p-0">
            @foreach($collections['supplies'] as $i=>$row)
                <div class="planning-row border rounded p-2 mb-2" data-supply-row>
                    <input type="hidden" name="supplies[{{ $i }}][id]" value="{{ $row['id']??'' }}">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">المستلزم</label>
                            <input class="form-control" name="supplies[{{ $i }}][item_name]" value="{{ $row['item_name']??'' }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">الكمية</label>
                            <input type="number" min="0" class="form-control" name="supplies[{{ $i }}][planned_quantity]" value="{{ $row['planned_quantity']??0 }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">التوفر</label>
                            <select class="form-select" name="supplies[{{ $i }}][planned_available]">
                                <option value="1" {{ ($row['planned_available'] ?? true) ? 'selected' : '' }}>متوفر</option>
                                <option value="0" {{ isset($row['planned_available']) && ! $row['planned_available'] ? 'selected' : '' }}>غير متوفر</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">الجهة المزودة</label>
                            <input class="form-control" name="supplies[{{ $i }}][provider_name]" value="{{ $row['provider_name']??'' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">القيمة التقديرية</label>
                            <input class="form-control" name="supplies[{{ $i }}][estimated_value]" value="{{ $row['estimated_value']??'' }}">
                        </div>
                        <div class="col-md-9">
                            <label class="form-label">ملاحظات</label>
                            <input class="form-control" name="supplies[{{ $i }}][notes]" value="{{ $row['notes']??'' }}">
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@elseif($needCode === 'gifts_shields')
    <div class="mt-3" data-repeat="gifts">
        <h6>الهدايا والدروع</h6>
        <div class="card-body p-0">
            @foreach($collections['gifts'] as $i=>$row)
                <div class="planning-row border rounded p-2 mb-2">
                    <input type="hidden" name="gifts[{{ $i }}][id]" value="{{ $row['id']??'' }}">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label">النوع</label>
                            <select class="form-select" name="gifts[{{ $i }}][gift_type]">
                                @foreach($giftTypes as $giftType => $giftLabel)
                                    <option value="{{ $giftType }}" {{ ($row['gift_type'] ?? '') === $giftType ? 'selected' : '' }}>{{ $giftLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">الوصف</label>
                            <input class="form-control" name="gifts[{{ $i }}][description]" value="{{ $row['description']??'' }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">العدد</label>
                            <input type="number" min="0" class="form-control" name="gifts[{{ $i }}][planned_quantity]" value="{{ $row['planned_quantity']??0 }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">قيمة الوحدة</label>
                            <input class="form-control" name="gifts[{{ $i }}][unit_value]" value="{{ $row['unit_value']??'' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">مقدمة من جهة داعمة؟</label>
                            <select class="form-select" name="gifts[{{ $i }}][has_supporting_entity]">
                                <option value="0">لا</option>
                                <option value="1" {{ ($row['has_supporting_entity'] ?? false) ? 'selected' : '' }}>نعم</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">اسم الجهة الداعمة</label>
                            <input class="form-control" name="gifts[{{ $i }}][supporting_entity_name]" value="{{ $row['supporting_entity_name']??'' }}">
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
<script>
(function () {
    function appendRows(container, selector, count) {
        var rows = Array.prototype.slice.call(container.querySelectorAll(selector));
        var template = rows[0];
        if (!template) return;
        while (rows.length < count) {
            var clone = template.cloneNode(true), index = Date.now() + rows.length;
            clone.querySelectorAll('[name]').forEach(function (input) {
                input.name = selector === '[data-team-member]'
                    ? input.name.replace(/(\[members\])\[\d+\]/, '$1[' + index + ']')
                    : input.name.replace(/\[\d+\]/, '[' + index + ']');
                if (input.type === 'hidden') input.value = '';
                else if (input.tagName !== 'SELECT') input.value = '';
            });
            clone.hidden = false;
            template.parentNode.appendChild(clone);
            rows.push(clone);
        }
        rows.forEach(function (row, index) {
            row.hidden = index >= count;
            var title = row.querySelector('[data-member-title]');
            if (title) title.textContent = 'عضو ' + (index + 1) + ' من ' + count;
        });
    }
    document.addEventListener('input', function (event) {
        if (event.target.matches('[data-member-count]')) {
            var team = event.target.closest('[data-execution-team]');
            appendRows(team, '[data-team-member]', Math.max(1, parseInt(event.target.value, 10) || 1));
        }
        if (event.target.matches('[data-supplies-count]')) {
            var supplies = event.target.closest('[data-supplies]');
            appendRows(supplies, '[data-supply-row]', Math.max(1, parseInt(event.target.value, 10) || 1));
        }
    });
    document.querySelectorAll('[data-member-count]').forEach(function (input) {
        input.dispatchEvent(new Event('input', { bubbles: true }));
    });
    document.querySelectorAll('[data-supplies-count]').forEach(function (input) {
        input.dispatchEvent(new Event('input', { bubbles: true }));
    });
}());
</script>
