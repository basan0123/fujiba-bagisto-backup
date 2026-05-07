@php
    /**
     * User Display Helper Component
     * 
     * Shows user's full name by default.
     * Shows email only when the viewing user is logged in and it's
     * either their own profile or they have admin privileges.
     * 
     * Usage:
     *   @include('admin::components.user-display', [
     *       'user' => $customer,
     *       'showEmail' => true,
     *       'format' => 'name-only'  // 'name-only', 'name-email', 'email-only'
     *   ])
     */
@endphp

<div class="user-display {{ $format ?? 'name-only' }}">
    <span class="user-name">
        @if($user && ($user->first_name || $user->last_name))
            {{ trim($user->first_name . ' ' . $user->last_name) }}
        @else
            {{ $user->name ?? 'ゲスト' }}
        @endif
    </span>

    @if($showEmail && auth()->check())
        <span class="user-email">
            @if(auth()->user()->id === $user->id || auth()->user()->hasPermissionTo('manage-customers'))
                {{ $user->email }}
            @endif
        </span>
    @elseif($showEmail && auth()->guard('admin')->check())
        <span class="user-email">
            {{ $user->email }}
        </span>
    @endif

    @if($showPhone && auth()->check() && isset($user->phone))
        <span class="user-phone">
            {{ $user->phone }}
        </span>
    @endif
</div>

<style>
    .user-display {
        display: inline-block;
    }

    .user-display.name-only .user-name {
        font-weight: 500;
        color: #333;
    }

    .user-display.name-email {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .user-display .user-name {
        display: block;
        font-weight: 600;
        color: #333;
    }

    .user-display .user-email {
        display: block;
        font-size: 12px;
        color: #999;
        font-weight: normal;
    }

    .user-display .user-phone {
        display: block;
        font-size: 12px;
        color: #666;
        margin-top: 2px;
    }
</style>
