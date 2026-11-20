@use('App\Models\Correspondence')
@php
    $letter = $this->letter;
    $state = $this->composer;
    $poll = (int) config('ai.correspondence.poll_seconds');
    $generatingHere = $letter && $letter->status === Correspondence::STATUS_GENERATING;
@endphp

<div x-data
     x-on:confirmation-confirmed.window="{{ confirmDialogListener(['deleteDraft' => false, 'voidLetter' => false, 'withdrawLetter' => false, 'closeLetter' => false, 'supersedeOriginal' => false, 'restoreVersion' => true, 'removeBlock' => true, 'removeAttachment' => true, 'sendLink' => 'spread', 'remindLink' => 'spread', 'revokeLink' => 'spread']) }}"
     class="flex flex-col gap-4 min-w-0">
    @include('livewire.dashboard.correspondence.viewer')
    @unless($readOnly || $registryOnly)
        @include('livewire.dashboard.correspondence.composer')
    @endunless
</div>
