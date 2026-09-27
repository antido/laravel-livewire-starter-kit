<?php

declare(strict_types=1);

namespace App\Livewire\Employees;

use App\Enums\EmployeeStatus;
use App\Livewire\Concerns\InteractsWithActor;
use App\Models\Employee;
use App\Services\EmployeeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Employees')]
class Index extends Component
{
    use InteractsWithActor, WithPagination;

    public const int PER_PAGE = 15;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    /**
     * "yes", "no" or empty for both.
     */
    #[Url(except: '')]
    public string $account = '';

    #[Url(except: false)]
    public bool $archived = false;

    public function mount(): void
    {
        $this->authorize('viewAny', Employee::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'account', 'archived'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'status', 'account', 'archived');
        $this->resetPage();
    }

    public function archive(int $id, EmployeeService $employees): void
    {
        $employee = Employee::findOrFail($id);
        $this->authorize('delete', $employee);

        $this->attempt(fn () => $employees->archive($this->actor(), $employee), __(':name was archived.', ['name' => $employee->full_name]));
    }

    public function restore(int $id, EmployeeService $employees): void
    {
        $employee = Employee::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $employee);

        $this->attempt(fn () => $employees->restore($this->actor(), $employee), __(':name was restored.', ['name' => $employee->full_name]));
    }

    public function render(): View
    {
        $this->authorize('viewAny', Employee::class);

        return view('livewire.employees.index', [
            'employees' => $this->employees(),
            'statuses' => EmployeeStatus::options(),
            'filtered' => $this->search !== '' || $this->status !== '' || $this->account !== '' || $this->archived,
        ]);
    }

    /**
     * @return LengthAwarePaginator<int, Employee>
     */
    private function employees(): LengthAwarePaginator
    {
        $status = EmployeeStatus::tryFrom($this->status);

        return Employee::query()
            ->with('user')
            ->when($this->archived, fn ($query) => $query->onlyTrashed())
            ->search(mb_substr($this->search, 0, 100))
            ->when($status !== null, fn ($query) => $query->where('status', $status?->value))
            ->when($this->account === 'yes', fn ($query) => $query->whereNotNull('user_id'))
            ->when($this->account === 'no', fn ($query) => $query->whereNull('user_id'))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('id')
            ->paginate(self::PER_PAGE);
    }
}
