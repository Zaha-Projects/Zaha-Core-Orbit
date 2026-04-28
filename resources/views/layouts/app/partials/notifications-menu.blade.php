@php
    $notificationItems = auth()->check()
        ? auth()->user()->inAppNotifications()->latest()->take(8)->get()
        : collect();
    $notificationCount = auth()->check()
        ? auth()->user()->inAppNotifications()->whereNull('read_at')->count()
        : 0;
    $notificationVariant = $variant ?? 'nxl';
@endphp

@if($notificationVariant === 'topbar')
    <li class="nav-item dropdown">
        <button class="btn topbar-notification-btn position-relative" data-bs-toggle="dropdown" type="button" aria-label="{{ __('app.layout.notifications') }}">
            <i class="fas fa-bell"></i>
            @if($notificationCount > 0)
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ $notificationCount }}</span>
            @endif
        </button>
        <div class="dropdown-menu dropdown-menu-end nxl-h-dropdown notification-chat-menu">
@else
    <div class="dropdown nxl-h-item">
        <a class="nxl-head-link me-0" data-bs-toggle="dropdown" href="#" aria-label="{{ __('app.layout.notifications') }}">
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
            <div class="notification-chat-list">
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
                    @endphp
                    <div class="notification-chat-item">
                        <div class="notification-chat-bubble {{ $notification->read_at ? 'is-read' : 'is-unread' }}">
                            <div class="fw-semibold mb-1">{{ $notificationTitle }}</div>
                            @if($notificationMessage)
                                <div class="text-muted small">{{ $notificationMessage }}</div>
                            @endif
                            <div class="d-flex align-items-center gap-3 mt-2 flex-wrap">
                                @if($notification->action_url)
                                    <a class="small text-decoration-none" href="{{ $notification->action_url }}">{{ __('app.layout.open_notification') }}</a>
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
                    <div class="notification-chat-empty">{{ __('app.common.no_new_notifications') }}</div>
                @endforelse
            </div>
        </div>
@if($notificationVariant === 'topbar')
    </li>
@else
    </div>
@endif
