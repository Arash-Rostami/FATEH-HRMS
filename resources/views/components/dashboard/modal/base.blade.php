@props(['title', 'actions' => null])

<div
    x-data="{ show: @entangle($attributes->wire('model')) }"
    class="custom-modal"
    :class="{ 'active': show }"
    style="display: none;"
    x-show="show"
    x-transition:enter="transition duration-0"
    x-transition:leave="transition duration-500 delay-500"
>
    <!-- Close Icon -->
    <div
        class="modal-close-icon"
        @click="show = false"
    ></div>

    <!-- Content -->
    <div class="custom-modal-content">
        @if($title)
            <h3 class="modal-title">{{ $title }}</h3>
        @endif

        <div class="modal-message">
            {{ $slot }}
        </div>

        @if($actions)
            <div class="modal-actions">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>
