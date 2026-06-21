@php
    $notificationMenuLimit = (int) config('notifications.menu_limit', 25);
    $notificationMenuLimit = $notificationMenuLimit > 0 ? $notificationMenuLimit : 25;
    $notificationQuery = auth()->check() ? auth()->user()->inAppNotifications() : null;
    $unreadNotificationCount = $notificationQuery ? (clone $notificationQuery)->unread()->count() : 0;
    $readNotificationCount = $notificationQuery ? (clone $notificationQuery)->read()->count() : 0;
    $notificationCount = $unreadNotificationCount;
    $unreadNotificationItems = $notificationQuery
        ? (clone $notificationQuery)->unread()->latest()->take($notificationMenuLimit)->get()
        : collect();
    $readNotificationItems = $notificationQuery
        ? (clone $notificationQuery)->read()->latest()->take($notificationMenuLimit)->get()
        : collect();
    $notificationVariant = $variant ?? 'nxl';
@endphp

@if($notificationVariant === 'topbar')
    <li class="nav-item dropdown">
        <button class="btn topbar-notification-btn position-relative" data-bs-toggle="dropdown" data-bs-auto-close="outside" type="button" aria-label="{{ __('app.layout.notifications') }}">
            <i class="fas fa-bell"></i>
            @if($notificationCount > 0)
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ $notificationCount }}</span>
            @endif
        </button>
        <div class="dropdown-menu dropdown-menu-end nxl-h-dropdown notification-chat-menu">
@else
    <div class="dropdown nxl-h-item">
        <a class="nxl-head-link me-0" data-bs-toggle="dropdown" data-bs-auto-close="outside" href="#" aria-label="{{ __('app.layout.notifications') }}">
            <i class="feather-bell"></i>
            @if($notificationCount > 0)
                <span class="badge bg-danger nxl-h-badge">{{ $notificationCount }}</span>
            @endif
        </a>
        <div class="dropdown-menu dropdown-menu-end nxl-h-dropdown notification-chat-menu">
@endif
            <div class="notification-chat-head">
                <div class="fw-semibold">{{ __('app.layout.notifications') }}</div>
                <div class="small text-muted">{{ __('app.layout.new_notifications_count', ['count' => $notificationCount]) }}</div>
            </div>
            <ul class="nav nav-tabs notification-chat-tabs px-3 pt-2" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#notifications-unread-{{ $notificationVariant }}" type="button" role="tab">
                        {{ __('app.common.unread') }}
                        <span class="badge {{ $unreadNotificationCount > 0 ? 'bg-danger' : 'bg-secondary' }} ms-1" data-notification-count="unread">{{ $unreadNotificationCount }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#notifications-read-{{ $notificationVariant }}" type="button" role="tab">
                        {{ __('app.common.read') }}
                        <span class="badge {{ $readNotificationCount > 0 ? 'bg-success' : 'bg-secondary' }} ms-1" data-notification-count="read">{{ $readNotificationCount }}</span>
                    </button>
                </li>
            </ul>
            <div class="tab-content notification-chat-tab-content">
                @foreach(['unread' => $unreadNotificationItems, 'read' => $readNotificationItems] as $notificationTab => $notificationItems)
                    <div class="tab-pane fade {{ $notificationTab === 'unread' ? 'show active' : '' }}" id="notifications-{{ $notificationTab }}-{{ $notificationVariant }}" role="tabpanel">
                        <div class="notification-chat-list notification-chat-list-scroll">
                            @forelse($notificationItems as $notification)
                                @php
                                    $notificationMeta = $notification->meta ?? [];
                                    $translationMeta = data_get($notificationMeta, 'i18n', []);
                                    $translationReplace = data_get($translationMeta, 'replace', []);

                                    foreach (data_get($translationMeta, 'translated_replace_keys', []) as $replaceKey) {
                                        if (isset($translationReplace[$replaceKey])) {
                                            $translationReplace[$replaceKey] = __($translationReplace[$replaceKey]);
                                        }
                                    }

                                    $notificationTitle = data_get($translationMeta, 'title_key')
                                        ? __(data_get($translationMeta, 'title_key'), $translationReplace)
                                        : match ($notification->title) {
                                            'Created' => __('app.workflow_notifications.created_draft.title'),
                                            'Approved' => __('app.workflow_notifications.decision.approved_title'),
                                            'Changes requested' => __('app.workflow_notifications.decision.changes_requested_title'),
                                            'Rejected' => __('app.workflow_notifications.decision.rejected_title'),
                                            'Published' => __('app.workflow_notifications.published.title'),
                                            'Deleted', 'Item deleted' => __('app.workflow_notifications.deleted.title'),
                                            'Approval needed' => __('app.workflow_notifications.approval_requested.title'),
                                            'Automatically approved' => __('app.workflow_notifications.auto_approved.title'),
                                            default => $notification->title,
                                        };
                                    $notificationMessage = data_get($translationMeta, 'message_key')
                                        ? __(data_get($translationMeta, 'message_key'), $translationReplace)
                                        : $notification->message;
                                    $notificationCreatedAt = $notification->created_at?->timezone(config('app.timezone'));
                                @endphp
                                <div class="notification-chat-item">
                                    <div class="notification-chat-bubble {{ $notification->read_at ? 'is-read' : 'is-unread' }}">
                                        <div class="fw-semibold mb-1">{{ $notificationTitle }}</div>
                                        @if($notificationMessage)
                                            <div class="text-muted small">{{ $notificationMessage }}</div>
                                        @endif
                                        @if($notificationCreatedAt)
                                            <div class="notification-chat-timestamp small text-muted mt-2" title="{{ $notificationCreatedAt->toDateTimeString() }}">
                                                <i class="fas fa-clock me-1" aria-hidden="true"></i>
                                                <span class="visually-hidden">{{ __('app.layout.notification_timestamp') }}:</span>
                                                <span>{{ $notificationCreatedAt->format('Y-m-d') }}</span>
                                                <span class="mx-1">•</span>
                                                <span>{{ $notificationCreatedAt->format('H:i') }}</span>
                                            </div>
                                        @endif
                                        <div class="d-flex align-items-center gap-3 mt-2 flex-wrap">
                                            @if($notification->action_url)
                                                <a class="small text-decoration-none" href="{{ route('role.notifications.open', $notification) }}">{{ __('app.layout.open_notification') }}</a>
                                            @endif
                                            @unless($notification->read_at)
                                                <form method="POST" action="{{ route('role.notifications.read', $notification) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="btn btn-link p-0 small text-decoration-none" type="submit">{{ __('app.common.mark_as_read') }}</button>
                                                </form>
                                            @endunless
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="notification-chat-empty">{{ $notificationTab === 'unread' ? __('app.common.no_new_notifications') : __('app.common.no_read_notifications') }}</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
@if($notificationVariant === 'topbar')
    </li>
@else
    </div>
@endif
