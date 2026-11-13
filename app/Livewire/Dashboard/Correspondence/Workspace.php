<?php

namespace App\Livewire\Dashboard\Correspondence;

use App\Livewire\Dashboard\Correspondence\Actions\ApproveLetterAction;
use App\Livewire\Dashboard\Correspondence\Actions\CloseLetterAction;
use App\Livewire\Dashboard\Correspondence\Actions\CommentLetterAction;
use App\Livewire\Dashboard\Correspondence\Actions\CorrectLetterAction;
use App\Livewire\Dashboard\Correspondence\Actions\DeleteDraftAction;
use App\Livewire\Dashboard\Correspondence\Actions\DeliverLinkAction;
use App\Livewire\Dashboard\Correspondence\Actions\DeliverManuallyAction;
use App\Livewire\Dashboard\Correspondence\Actions\GenerateAction;
use App\Livewire\Dashboard\Correspondence\Actions\LetterParties;
use App\Livewire\Dashboard\Correspondence\Actions\LetterRules;
use App\Livewire\Dashboard\Correspondence\Actions\MarkOpenedAction;
use App\Livewire\Dashboard\Correspondence\Actions\ReceiveLetterAction;
use App\Livewire\Dashboard\Correspondence\Actions\RejectLetterAction;
use App\Livewire\Dashboard\Correspondence\Actions\RemoveAttachmentAction;
use App\Livewire\Dashboard\Correspondence\Actions\RenameLetterAction;
use App\Livewire\Dashboard\Correspondence\Actions\RerouteLetterAction;
use App\Livewire\Dashboard\Correspondence\Actions\RestoreVersionAction;
use App\Livewire\Dashboard\Correspondence\Actions\RouteLetterAction;
use App\Livewire\Dashboard\Correspondence\Actions\SaveLetterAction;
use App\Livewire\Dashboard\Correspondence\Actions\SaveMailTextAction;
use App\Livewire\Dashboard\Correspondence\Actions\ShareLetterAction;
use App\Livewire\Dashboard\Correspondence\Actions\SupersedeLetterAction;
use App\Livewire\Dashboard\Correspondence\Actions\TemplateAction;
use App\Livewire\Dashboard\Correspondence\Actions\VoidLetterAction;
use App\Livewire\Dashboard\Correspondence\Actions\WetStepAction;
use App\Livewire\Dashboard\Correspondence\Actions\WithdrawLetterAction;
use App\Livewire\Dashboard\Correspondence\Forms\LetterForm;
use App\Livewire\Dashboard\Correspondence\Presentation\CorrespondencePresenter;
use App\Models\Correspondence;
use App\Models\Letterhead;
use App\Models\User;
use App\Support\CorrespondenceAccessPolicy;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use PDOException;
use RuntimeException;
use Throwable;

class Workspace extends Component
{
    use WithFileUploads;

    private const GUARDED = ['form'];

    private const GUARDED_TRANSITIONS = ['share', 'approve', 'reject', 'reroute', 'correct'];

    private const LETTER_TEXT = ['commentText', 'rejectReason', 'resumeRoute', 'deliveryNote', 'refineText', 'instruction'];

    public LetterForm $form;

    #[Locked]
    public ?int $letterId = null;

    #[Locked]
    public bool $readOnly = false;

    #[Locked]
    public bool $registryOnly = false;

    #[Locked]
    public ?string $lastSeenStatus = null;

    #[Locked]
    public string $contentHash = '';

    #[Locked]
    public ?int $awaitingId = null;

    #[Locked]
    public ?int $replyToId = null;

    #[Locked]
    public bool $correcting = false;

    public string $rejectReason = '';

    public string $signCode = '';

    public int $signTarget = 0;

    public string $instruction = '';

    public string $refineText = '';

    public bool $resumeRoute = false;

    public string $commentText = '';

    public string $deliveryNote = '';

    public string $mailSubject = '';

    public string $mailBody = '';

    public string $templateName = '';

    public string $templateScope = 'private';

    public array $routeParties = [];

    public array $correction = [];

    public string $routeMode = Correspondence::ROUTE_SEQUENTIAL;

    private ?CorrespondencePresenter $presenterCache = null;

    private bool $stale = false;

    private ?Correspondence $opened = null;

    public function updating(string $name, mixed $value): void
    {
        foreach (self::GUARDED as $guarded) {
            abort_if($this->readOnly && str_starts_with($name, $guarded), 403);
        }
    }

    public function mount(?int $letterId = null, ?int $replyToId = null): void
    {
        if ($letterId !== null) {
            $this->load($letterId);

            return;
        }
        $this->startForm();
        $this->replyToId = null;
        $parent = $replyToId === null ? null : $this->viewable($replyToId);
        if ($parent) {
            $this->prefillReply($parent);
        }
    }

    #[On('correspondence-reload')]
    public function reload(): void
    {
        if ($this->letterId !== null) {
            $this->load($this->letterId);
        }
    }

    #[On('correspondence-generation-ended')]
    public function refreshGenerating(): void
    {
        unset($this->generatingId);
    }

    public function updatedFormType(): void
    {
        $incoming = LetterForm::directionOf($this->form->type) === Correspondence::DIRECTION_IN;
        if ($incoming && !$this->registry()) {
            $this->form->type = 'letter';
            $this->toast(__('resources/correspondence/strings.errors.denied'), 'warning');

            return;
        }

        $this->form->letterheadId = $this->defaultLetterhead($this->form->letterheadId, $incoming ? Letterhead::KIND_REGISTRY : Letterhead::KIND_HEAD);
        $this->applySuggestions();
    }

    public function updatedFormSenderCategory(): void
    {
        $this->applySuggestions();
    }

    public function updatedFormClassification(): void
    {
        $this->forceSimpleWhenBlocked();
    }

    public function reply(int $id): void
    {
        $parent = $this->viewable($id);
        if (!$parent) {
            $this->toast(__('resources/correspondence/strings.errors.not_found'), 'warning');

            return;
        }
        $this->dispatch('correspondence-reply', id: (int) $parent->id);
    }

    public function copyAsNew(TemplateAction $action): void
    {
        $source = $this->letter;
        $user = $this->user();
        if (!$source || !$user || $this->registryOnly) {
            return;
        }
        $copy = $this->run(fn () => $action->duplicate($user, (int) $source->id));
        if (!$copy instanceof Correspondence) {
            return;
        }
        $this->select($copy);
        $this->toast(__('resources/correspondence/strings.toasts.copied'), 'success');
        $this->announce();
    }

    public function correctLetter(TemplateAction $action): void
    {
        $source = $this->letter;
        $user = $this->user();
        if (!$source || !$user || $this->registryOnly || !CorrespondenceAccessPolicy::isAuthor($user, $source)
            || $source->status !== Correspondence::STATUS_APPROVED || $source->direction !== Correspondence::DIRECTION_OUT) {
            return;
        }
        $copy = $this->run(fn () => DB::transaction(function () use ($action, $user, $source) {
            $copy = $action->duplicate($user, (int) $source->id);
            $copy->forceFill(['supersedes_id' => (int) $source->id])->save();

            return $copy;
        }));
        if (!$copy instanceof Correspondence) {
            return;
        }
        $this->select($copy);
        $this->toast(__('resources/correspondence/strings.toasts.copied'), 'success');
        $this->announce();
    }

    public function save(SaveLetterAction $action): void
    {
        if ($this->persist($action) !== null) {
            $this->toast(__('resources/correspondence/strings.toasts.saved'), 'success');
        }
    }

    public function rename(string $subject, RenameLetterAction $action): void
    {
        $this->mutate($action, ['subject' => $subject], __('resources/correspondence/strings.toasts.saved'), function (Correspondence $saved) {
            $this->form->subject = (string) $saved->subject;
        });
    }

    public function addBlock(): void
    {
        $this->form->addBlock();
    }

    public function removeBlock(int $index): void
    {
        $this->form->removeBlock($index);
    }

    public function addParty(): void
    {
        $this->form->addParty();
    }

    public function removeParty(int $index): void
    {
        $this->form->removeParty($index);
    }

    public function moveParty(int $index, int $by): void
    {
        $this->form->moveParty($index, $by);
    }

    public function addTag(string $value): void
    {
        if ($this->tagsLocked()) {
            return;
        }
        $this->form->addTag($value);
    }

    public function removeTag(int $index): void
    {
        if ($this->tagsLocked()) {
            return;
        }
        $this->form->removeTag($index);
    }

    public function addRouteParty(): void
    {
        if (count($this->routeParties) >= LetterForm::limits()['recipients']) {
            return;
        }
        $this->routeParties[] = LetterForm::blankParty();
    }

    public function removeRouteParty(int $index): void
    {
        unset($this->routeParties[$index]);
        $this->routeParties = array_values($this->routeParties);
    }

    public function removeAttachment(int $index, RemoveAttachmentAction $action): void
    {
        $this->mutate($action, ['remove_attachment' => $index], __('resources/correspondence/strings.toasts.saved'));
    }

    public function deleteDraft(DeleteDraftAction $action): void
    {
        $user = $this->user();
        $letter = $this->letter;
        if (!$user || !$letter || $this->readOnly || $this->registryOnly) {
            return;
        }
        if ($this->run(fn () => $action->execute($user, ['delete' => true], $letter)) === null) {
            return;
        }
        $this->clear();
        $this->toast(__('resources/correspondence/strings.toasts.deleted'), 'success');
        $this->announce();
    }

    public function restoreVersion(int $index, RestoreVersionAction $action): void
    {
        $this->mutate($action, ['restore' => $index], __('resources/correspondence/strings.toasts.saved'), fn (Correspondence $saved) => $this->form->loadContent($saved));
    }

    public function saveAsTemplate(TemplateAction $action): void
    {
        $user = $this->user();
        if ($this->letterId === null || !$user) {
            return;
        }
        $ok = $this->run(fn () => $action->toggleTemplate($user, $this->letterId, $this->templateName, $this->templateScope));
        if ($ok === null) {
            return;
        }
        $this->templateName = '';
        $this->forgetLetter();
        $this->toast(__('resources/correspondence/strings.toasts.template_saved'), 'success');
        $this->announce();
    }

    public function shareLetter(SaveLetterAction $action, ShareLetterAction $flow): void
    {
        if ($this->persist($action) !== null) {
            $this->flow($flow, 'share');
        }
    }

    public function approveLetter(ApproveLetterAction $flow): void
    {
        $parallel = $this->letter?->route_mode === Correspondence::ROUTE_PARALLEL;
        $this->flow($flow, 'approve', ['code' => $this->signCode, ...($parallel && $this->signTarget > 0 ? ['target_id' => $this->signTarget] : [])]);
        $this->signCode = '';
        $this->signTarget = 0;
    }

    public function rejectLetter(RejectLetterAction $flow): void
    {
        $reason = LetterRules::plain($this->rejectReason, 500);
        if ($reason === '') {
            $this->toast(__('resources/correspondence/strings.errors.reason_required'), 'warning');

            return;
        }
        if ($this->flow($flow, 'reject', ['reason' => $reason, 'resume' => $this->resumeRoute])) {
            $this->reset('rejectReason', 'resumeRoute');
        }
    }

    public function commentLetter(CommentLetterAction $flow): void
    {
        $text = LetterRules::plain($this->commentText, 1000);
        if ($text === '') {
            $this->toast(__('resources/correspondence/strings.errors.text_required'), 'warning');

            return;
        }
        if ($this->flow($flow, 'comment', ['text' => $text])) {
            $this->commentText = '';
        }
    }

    public function withdrawLetter(WithdrawLetterAction $flow): void
    {
        $this->flow($flow, 'withdraw');
    }

    public function voidLetter(VoidLetterAction $flow): void
    {
        $this->flow($flow, 'void');
    }

    public function closeLetter(CloseLetterAction $flow): void
    {
        $this->flow($flow, 'close');
    }

    public function startCorrection(): void
    {
        $letter = $this->letter;
        $user = $this->user();
        if (!$letter || !$user || $this->registryOnly || $letter->direction !== Correspondence::DIRECTION_IN
            || $letter->status !== Correspondence::STATUS_RECEIVED || !CorrespondenceAccessPolicy::isRegistry($user)) {
            return;
        }
        $this->correction = [
            'subject' => (string) $letter->subject,
            'their_number' => (string) $letter->their_number,
            'their_date' => (string) $letter->their_date,
            'sender' => (string) ($letter->spec['sender']['name'] ?? ''),
        ];
        $this->correcting = true;
    }

    public function cancelCorrection(): void
    {
        $this->correcting = false;
        $this->correction = [];
    }

    public function correctRegistration(CorrectLetterAction $flow): void
    {
        if ($this->correcting && $this->flow($flow, 'correct', ['fields' => $this->correction])) {
            $this->cancelCorrection();
        }
    }

    public function supersedeOriginal(SupersedeLetterAction $flow): void
    {
        $letter = $this->letter;
        $user = $this->user();
        if (!$letter || !$user || $this->registryOnly || blank($letter->supersedes_id) || !CorrespondenceAccessPolicy::isAuthor($user, $letter)
            || $letter->status !== Correspondence::STATUS_APPROVED) {
            return;
        }
        if ($this->run(fn () => $flow->execute((int) $letter->supersedes_id, ['replacement_id' => (int) $letter->id])) === null) {
            return;
        }
        $this->forgetLetter();
        $this->load((int) $letter->id);
        $this->toast(__('resources/correspondence/strings.toasts.saved'), 'success');
        $this->announce();
    }

    public function rerouteLetter(RerouteLetterAction $flow): void
    {
        $parties = $this->run(fn () => $this->routeTargets());
        if ($parties !== null) {
            $mode = in_array($this->routeMode, LetterForm::ROUTES, true) ? $this->routeMode : Correspondence::ROUTE_SEQUENTIAL;
            $this->flow($flow, 'reroute', ['parties' => $parties, 'route_mode' => $mode]);
        }
    }

    public function routeLetter(RouteLetterAction $flow): void
    {
        if ($this->registryOnly) {
            return;
        }

        $parties = $this->run(fn () => $this->routeTargets());
        if ($parties !== null && $this->flow($flow, 'route', ['instruction' => LetterRules::plain($this->instruction, 500), 'parties' => $parties])) {
            $this->reset('instruction');
        }
    }

    public function receiveLetter(SaveLetterAction $action, ReceiveLetterAction $flow): void
    {
        if ($this->persist($action) !== null) {
            $this->flow($flow, 'receive');
        }
    }

    public function markPrinted(WetStepAction $flow): void
    {
        $this->flow($flow, 'printed', ['step' => 'printed']);
    }

    public function markWetSigned(WetStepAction $flow): void
    {
        $this->flow($flow, 'wet_signed', ['step' => 'wet_signed']);
    }

    public function saveMailText(SaveMailTextAction $flow): void
    {
        $this->flow($flow, 'mail_text', ['subject' => $this->mailSubject, 'body' => $this->mailBody]);
    }

    public function markDeliveredManually(DeliverManuallyAction $flow, ?int $party = null): void
    {
        $text = LetterRules::plain($this->deliveryNote, 500);
        if ($text === '') {
            $this->toast(__('resources/correspondence/strings.errors.text_required'), 'warning');

            return;
        }
        if ($this->flow($flow, 'delivered_manually', ['text' => $text, 'party' => $party])) {
            $this->deliveryNote = '';
        }
    }

    public function sendLink(int $id, int $index, DeliverLinkAction $flow): void
    {
        $this->deliverLink($flow, $id, $index, 'send');
    }

    public function remindLink(int $id, int $index, DeliverLinkAction $flow): void
    {
        $this->deliverLink($flow, $id, $index, 'remind');
    }

    public function revokeLink(int $id, int $index, DeliverLinkAction $flow): void
    {
        $this->deliverLink($flow, $id, $index, 'revoke');
    }

    public function generate(GenerateAction $action): void
    {
        abort_if(tenantOff('correspondence:ai'), 404);

        $user = $this->user();
        if (!$user || $this->readOnly || $this->registryOnly) {
            $this->toast(__('resources/correspondence/strings.errors.denied'), 'warning');

            return;
        }

        if ($this->form->files !== [] || $this->form->image !== null) {
            $this->toast(__('resources/correspondence/strings.errors.smart_uploads'), 'warning');

            return;
        }

        $current = null;
        if ($this->letterId !== null && ($current = $this->letter) === null) {
            $this->unavailable();

            return;
        }

        $this->form->mode = Correspondence::MODE_SMART;
        $this->form->normalize();
        $started = $this->run(fn () => $action->execute($user, $this->form, $current, [
            'expected_hash' => $this->contentHash,
            'reply_to' => $this->replyToId,
        ]));
        if ($started instanceof Correspondence) {
            $this->started($started, 'generating');
        }
    }

    public function refine(int $blockIndex, GenerateAction $action, ?string $blockHash = null): void
    {
        abort_if(tenantOff('correspondence:ai'), 404);

        $user = $this->user();
        $letter = $this->letter;
        if (!$user || !$letter || $this->registryOnly) {
            $this->toast(__('resources/correspondence/strings.errors.denied'), 'warning');

            return;
        }

        $started = $this->run(fn () => $action->refine($user, $letter, $blockIndex, $this->refineText, $blockHash));
        if (!$started instanceof Correspondence) {
            return;
        }
        $this->refineText = '';
        $this->started($started, (int) $started->id === (int) $letter->id ? 'refining' : 'forked');
    }

    public function retry(GenerateAction $action): void
    {
        abort_if(tenantOff('correspondence:ai'), 404);

        $user = $this->user();
        $letter = $this->letter;
        if (!$user || !$letter || $this->readOnly || $this->registryOnly || $letter->status !== Correspondence::STATUS_FAILED) {
            return;
        }

        $started = $this->run(fn () => $action->retry($user, $letter));
        if ($started instanceof Correspondence) {
            $this->started($started, 'generating');
        }
    }

    public function applySuggestions(): void
    {
        $this->form->applySuggestions();
    }

    public function pollStatus(): void
    {
        if ($this->lastSeenStatus !== Correspondence::STATUS_GENERATING || $this->letterId === null) {
            $this->skipRender();

            return;
        }
        $this->forgetLetter();
        $letter = $this->letter;
        if ($letter === null) {
            $this->unavailable();

            return;
        }
        if ($letter->status === Correspondence::STATUS_GENERATING) {
            $this->skipRender();

            return;
        }
        if ($letter->status === Correspondence::STATUS_FAILED) {
            $this->toast($this->presenter()->failToast($letter->fail_reason), 'warning');
        } else {
            $this->toast(__('resources/correspondence/strings.toasts.generated'), 'success');
        }
        if ($this->registryOnly) {
            $this->lastSeenStatus = $letter->status;
            $this->announce(false);

            return;
        }
        $this->select($letter);
        $this->announce(false);
    }

    #[Computed]
    public function letter(): ?Correspondence
    {
        $user = $this->user();
        if ($this->letterId === null || !$user) {
            return null;
        }

        $query = Correspondence::query()->whereKey($this->letterId);
        $letter = $this->registryOnly
            ? $query->first(Correspondence::LIST_COLUMNS)
            : ($this->opened?->id === $this->letterId ? $this->opened : $query->first());
        if ($letter === null) {
            return null;
        }

        if ($this->registryOnly) {
            if (!CorrespondenceAccessPolicy::canRegistry($user, $letter)) {
                return null;
            }
            if ($letter->classification !== Correspondence::CLASSIFICATION_NORMAL) {
                $letter->subject = $this->presenter()->confidentialLabel();
            }

            return $letter;
        }

        if (!CorrespondenceAccessPolicy::canView($user, $letter)) {
            return null;
        }
        if ($letter->isStale() && $letter->markFailed('stale')) {
            return $letter->fresh();
        }

        return $letter;
    }

    #[Computed]
    public function generatingId(): ?int
    {
        $id = Correspondence::query()->freshGenerating((int) auth()->id())->value('id');

        return $id === null ? null : (int) $id;
    }

    #[Computed]
    public function quota(): array
    {
        return ['used' => Correspondence::quotaUsed((int) auth()->id()), 'cap' => Correspondence::quotaCap()];
    }

    #[Computed]
    public function people(): Collection
    {
        return User::getCachedActiveOptions();
    }

    #[Computed]
    public function letterheads(): Collection
    {
        return Letterhead::query()->where('active', true)->orderBy('name')->get(['id', 'name', 'kind', 'size']);
    }

    #[Computed]
    public function incomingDuplicate(): bool
    {
        $user = $this->user();
        $letter = $this->letter;
        if (!$user || !$letter || $letter->direction !== Correspondence::DIRECTION_IN || !filled($letter->their_number) || !CorrespondenceAccessPolicy::isRegistry($user)) {
            return false;
        }

        return Correspondence::query()
            ->where('direction', Correspondence::DIRECTION_IN)
            ->whereIn('status', CorrespondencePresenter::REGISTRY_STATUSES)
            ->where('their_number', $letter->their_number)
            ->where('their_date', $letter->their_date)
            ->whereKeyNot($letter->id)
            ->when(!$this->registryOnly, fn (Builder $query) => $query->where('spec->sender->name', (string) ($letter->spec['sender']['name'] ?? '')))
            ->exists();
    }

    #[Computed]
    public function composer(): array
    {
        $user = $this->user();
        $letter = $this->letter;
        $generating = $this->generatingId;

        return $this->presenter()->composerState(
            $letter,
            !$this->readOnly && !$this->registryOnly,
            LetterRules::canStructure($letter, $user),
            $this->quota,
            $generating !== null && (int) $generating !== (int) $this->letterId,
            !tenantOff('correspondence:ai'),
            $this->form->classification,
            $this->registry()
        );
    }

    public function render(): View
    {
        return view('livewire.dashboard.correspondence.workspace', ['presenter' => $this->presenter()]);
    }

    private function load(int $id): void
    {
        $user = $this->user();
        $letter = Correspondence::query()->find($id);
        if (!$letter || !$user) {
            $this->unavailable();

            return;
        }
        if (CorrespondenceAccessPolicy::canView($user, $letter)) {
            (new MarkOpenedAction())->execute($letter, (int) $user->id);
            $this->select($letter);
            $this->opened = $letter;

            return;
        }
        if (CorrespondenceAccessPolicy::canRegistry($user, $letter)) {
            $this->reset(self::LETTER_TEXT);
            $this->registryOnly = true;
            $this->readOnly = true;
            $this->letterId = $letter->id;
            $this->lastSeenStatus = $letter->status;
            $this->contentHash = '';
            $this->awaitingId = null;
            $this->form->reset();
            $this->forgetLetter();

            return;
        }
        $this->unavailable();
    }

    private function viewable(int $id): ?Correspondence
    {
        $user = $this->user();
        $parent = Correspondence::query()->find($id);

        return $parent && $user && CorrespondenceAccessPolicy::canView($user, $parent) ? $parent : null;
    }

    private function prefillReply(Correspondence $parent): void
    {
        $outgoing = $parent->direction !== Correspondence::DIRECTION_IN;
        $this->form->subject = mb_substr('پاسخ: ' . $parent->subject, 0, 200);
        $this->form->type = $outgoing && config("ai.correspondence.types.{$parent->type}.enabled") === true ? (string) $parent->type : 'letter';
        if ($outgoing) {
            $this->form->letterheadId = $this->defaultLetterhead($parent->letterhead_id);
        }
        if ($parent->classification !== Correspondence::CLASSIFICATION_NORMAL) {
            $this->form->classification = (string) $parent->classification;
            $this->forceSimpleWhenBlocked();
        }
        $this->replyToId = $parent->id;
    }

    private function clear(): void
    {
        $this->letterId = null;
        $this->readOnly = false;
        $this->registryOnly = false;
        $this->lastSeenStatus = null;
        $this->contentHash = '';
        $this->awaitingId = null;
        $this->replyToId = null;
        $this->routeParties = [];
        $this->routeMode = Correspondence::ROUTE_SEQUENTIAL;
        $this->reset(self::LETTER_TEXT);
        $this->cancelCorrection();
        $this->form->reset();
        $this->forgetLetter();
    }

    private function announce(?bool $watch = null): void
    {
        $this->dispatch('correspondence-letter-updated', id: $this->letterId, watch: $watch);
    }

    private function routeTargets(): array
    {
        $parties = array_slice($this->routeParties, 0, LetterForm::limits()['recipients'] + 1);

        return LetterParties::clean($parties, $this->letter?->parties ?? [], 'routeParties');
    }

    private function startForm(): void
    {
        $remembered = session('correspondence.letterhead');
        $this->form->letterheadId = $this->defaultLetterhead(is_numeric($remembered) ? (int) $remembered : null);
        $this->form->deadline = toJalali(now('Asia/Tehran')->addDay(), 'Y/m/d');
        $this->applySuggestions();
    }

    private function defaultLetterhead(?int $preferred, string $kind = Letterhead::KIND_HEAD): ?int
    {
        $heads = $this->letterheads->where('kind', $kind);

        return ($heads->firstWhere('id', $preferred) ?? $heads->first())?->id;
    }

    private function tagsLocked(): bool
    {
        return $this->readOnly || $this->registryOnly || !$this->composer['canSave'] || !$this->composer['structural'];
    }

    private function registry(): bool
    {
        $user = $this->user();

        return $user !== null && CorrespondenceAccessPolicy::isRegistry($user);
    }

    private function presenter(): CorrespondencePresenter
    {
        return $this->presenterCache ??= new CorrespondencePresenter();
    }

    private function forceSimpleWhenBlocked(): void
    {
        $this->form->forceSimpleWhenBlocked();
    }

    private function persist(SaveLetterAction $action): ?Correspondence
    {
        $user = $this->user();
        if (!$user || $this->readOnly || $this->registryOnly) {
            $this->toast(__('resources/correspondence/strings.errors.denied'), 'warning');

            return null;
        }

        $current = null;
        if ($this->letterId !== null && ($current = $this->letter) === null) {
            $this->unavailable();

            return null;
        }

        $this->forceSimpleWhenBlocked();
        $this->form->normalize();
        $this->form->validated($current?->type);
        $saved = $this->run(fn () => $action->execute($user, [
            'payload' => $this->form->payload(),
            'files' => $this->form->files,
            'image' => $this->form->image,
            'expected_hash' => $this->contentHash,
            'reply_to' => $this->replyToId,
        ], $current));
        if (!$saved instanceof Correspondence) {
            return null;
        }

        if ($saved->direction !== Correspondence::DIRECTION_IN) {
            session(['correspondence.letterhead' => $saved->letterhead_id]);
        }
        $this->select($saved);
        $this->announce();

        return $saved;
    }

    private function select(Correspondence $letter): void
    {
        $this->track($letter);
        $this->form->loadLetter($letter);
        $this->routeParties = $this->form->parties;
        $this->routeMode = $this->form->routeMode;
    }

    private function track(Correspondence $letter): void
    {
        $user = $this->user();
        if ((int) $letter->id !== $this->letterId) {
            $this->reset(self::LETTER_TEXT);
        }
        $this->registryOnly = false;
        $this->letterId = $letter->id;
        $this->lastSeenStatus = $letter->status;
        $this->contentHash = (string) $letter->content_hash;
        $this->awaitingId = $letter->awaiting_user_id ? (int) $letter->awaiting_user_id : null;
        $mail = is_array($letter->meta['mail'] ?? null) ? $letter->meta['mail'] : [];
        $this->mailSubject = (string) ($mail['subject'] ?? '');
        $this->mailBody = (string) ($mail['body'] ?? '');
        $this->readOnly = !$user || !CorrespondenceAccessPolicy::canEdit($user, $letter);
        $this->cancelCorrection();
        $this->forgetLetter();
    }

    private function forgetLetter(): void
    {
        $this->opened = null;
        unset($this->letter, $this->incomingDuplicate);
    }

    private function started(Correspondence $letter, string $toast): void
    {
        $this->select($letter);
        unset($this->generatingId);
        $this->toast(__("resources/correspondence/strings.toasts.{$toast}"), 'success');
        $this->announce(true);
    }

    private function mutate(object $action, array $changes, string $message, ?Closure $refresh = null): bool
    {
        $user = $this->user();
        $letter = $this->letter;
        if (!$user || !$letter || $this->readOnly || $this->registryOnly) {
            return false;
        }
        $saved = $this->run(fn () => $action->execute($user, ['expected_hash' => $this->contentHash, ...$changes], $letter));
        if (!$saved instanceof Correspondence) {
            return false;
        }
        $this->track($saved);
        $refresh !== null && $refresh($saved);
        $this->toast($message, 'success');
        $this->announce();

        return true;
    }

    private function flow(object $flow, string $transition, array $payload = []): bool
    {
        $letter = $this->letter;
        if ($letter === null) {
            $this->toast(__('resources/correspondence/strings.errors.not_found'), 'warning');

            return false;
        }
        if (!$this->registryOnly && in_array($transition, ['share', 'approve'], true) && $this->presenter()->hasUnfilled($letter)) {
            $this->toast(__('resources/correspondence/strings.errors.placeholders'), 'warning');

            return false;
        }
        $guarded = in_array($transition, self::GUARDED_TRANSITIONS, true);
        $guard = $guarded ? ['expected_hash' => $this->contentHash] : [];
        if ($guarded && $letter->route_mode !== Correspondence::ROUTE_PARALLEL) {
            $guard['expected_awaiting'] = $this->awaitingId;
        }
        if ($this->run(fn () => $flow->execute((int) $letter->id, [...$guard, ...$payload])) === null) {
            if ($guarded && $this->stale) {
                $this->load((int) $letter->id);
            }

            return false;
        }
        unset($this->generatingId);
        $this->forgetLetter();
        $this->load((int) $letter->id);
        $this->toast(__('resources/correspondence/strings.toasts.' . $this->toastKey($transition)), 'success');
        $this->announce();

        return true;
    }

    private function deliverLink(DeliverLinkAction $flow, int $id, int $index, string $action): void
    {
        if ($this->letterId === null || $id !== $this->letterId || $this->registryOnly) {
            return;
        }
        if ($this->run(fn () => $flow->execute($id, $index, $action)) === null) {
            return;
        }
        $this->forgetLetter();
        $this->toast(__('resources/correspondence/strings.toasts.' . ($action === 'revoke' ? 'link_revoked' : 'link_queued')), 'success');
    }

    private function toastKey(string $transition): string
    {
        return match ($transition) {
            'share' => 'shared',
            'approve' => 'approved',
            'reject' => 'rejected',
            'withdraw' => 'withdrawn',
            'void' => 'voided',
            'route' => 'routed',
            'receive' => 'registered',
            'close' => 'closed',
            'correct' => 'corrected',
            'comment' => 'commented',
            'printed' => 'printed',
            'wet_signed' => 'wet_signed',
            'delivered_manually' => 'delivered_manually',
            default => 'saved',
        };
    }

    private function run(callable $callback, bool $silent = false): mixed
    {
        $this->stale = false;

        try {
            return $callback() ?? true;
        } catch (ValidationException $e) {
            if (!$silent) {
                $this->setErrorBag($e->errors());
                $this->toast((string) collect($e->errors())->flatten()->first(), 'warning');
            }
        } catch (RuntimeException $e) {
            if ($e instanceof PDOException) {
                report($e);
                $silent || $this->toast($this->presenter()->failToast('exception'), 'warning');

                return null;
            }
            $stale = $e->getCode() === LetterRules::STALE;
            $silent || $this->toast(__('resources/correspondence/strings.errors.' . ($stale ? 'changed_elsewhere' : 'denied')), 'warning');
            if ($stale) {
                $this->stale = true;
            }
        } catch (Throwable $e) {
            report($e);
            $silent || $this->toast($this->presenter()->failToast('exception'), 'warning');
        }

        return null;
    }

    private function unavailable(): void
    {
        $this->clear();
        $this->startForm();
        $this->toast(__('resources/correspondence/strings.errors.not_found'), 'warning');
        $this->announce();
    }

    private function toast(string $message, string $type): void
    {
        $this->dispatch('toast', message: $message, type: $type);
    }

    private function user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
