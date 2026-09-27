<?php

declare(strict_types=1);

namespace App\Livewire\Admin\AuditTrails;

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The read-only audit trail viewer. It offers no way to change or remove entries.
 */
#[Title('Audit Trails')]
class Index extends Component
{
    use WithPagination;

    public const int PER_PAGE = 25;

    #[Url(except: '')]
    public string $search = '';

    /**
     * A user id, "system" for entries without an actor, or empty for everyone.
     */
    #[Url(except: '')]
    public string $user = '';

    #[Url(except: '')]
    public string $event = '';

    #[Url(except: '')]
    public string $subject = '';

    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    public bool $showDetail = false;

    #[Locked]
    public ?int $selectedId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', AuditLog::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'user', 'event', 'subject', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'user', 'event', 'subject', 'from', 'to');
        $this->resetPage();
    }

    public function show(int $id): void
    {
        $entry = AuditLog::findOrFail($id);
        $this->authorize('view', $entry);

        $this->selectedId = $entry->id;
        $this->showDetail = true;
    }

    public function render(): View
    {
        $this->authorize('viewAny', AuditLog::class);

        $selected = null;

        if ($this->showDetail && $this->selectedId !== null) {
            $selected = AuditLog::with(['user', 'auditable'])->find($this->selectedId);
        }

        return view('livewire.admin.audit-trails.index', [
            'entries' => $this->entries(),
            'selected' => $selected,
            'actors' => User::query()
                ->whereIn('id', AuditLog::query()->select('user_id')->whereNotNull('user_id')->distinct())
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'events' => AuditEvent::options(),
            'subjectTypes' => AuditLog::query()
                ->whereNotNull('auditable_type')
                ->distinct()
                ->orderBy('auditable_type')
                ->pluck('auditable_type')
                ->mapWithKeys(fn (string $type): array => [$type => class_basename($type)])
                ->all(),
            'filtered' => $this->search !== '' || $this->user !== '' || $this->event !== '' || $this->subject !== '' || $this->from !== '' || $this->to !== '',
        ]);
    }

    /**
     * Newest first. Every filter value is checked before it reaches the query; invalid ones are ignored.
     *
     * @return LengthAwarePaginator<int, AuditLog>
     */
    private function entries(): LengthAwarePaginator
    {
        $search = mb_substr(trim($this->search), 0, 100);
        $event = AuditEvent::tryFrom($this->event);
        $from = $this->date($this->from);
        $to = $this->date($this->to);

        return AuditLog::query()
            ->with(['user', 'auditable'])
            ->when($search !== '', fn (Builder $query) => $query->where('description', 'like', '%'.$search.'%'))
            ->when($this->user === 'system', fn (Builder $query) => $query->whereNull('user_id')->whereNull('user_name'))
            ->when(ctype_digit($this->user), fn (Builder $query) => $query->where('user_id', (int) $this->user))
            ->when($event !== null, fn (Builder $query) => $query->where('event', $event?->value))
            ->when($this->subject !== '', fn (Builder $query) => $query->where('auditable_type', $this->subject))
            ->when($from !== null, fn (Builder $query) => $query->where('created_at', '>=', $from?->startOfDay()))
            ->when($to !== null, fn (Builder $query) => $query->where('created_at', '<=', $to?->endOfDay()))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE);
    }

    private function date(string $value): ?Carbon
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        $date = Carbon::createFromFormat('Y-m-d', $value, config('app.timezone'));

        return $date !== null && $date->format('Y-m-d') === $value ? $date : null;
    }
}
