<?php

declare(strict_types=1);

namespace App\Livewire\Employees;

use App\Models\AuditLog;
use App\Models\Employee;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Show extends Component
{
    #[Locked]
    public Employee $employee;

    public function mount(Employee $employee): void
    {
        $this->authorize('view', $employee);

        $this->employee = $employee;
    }

    public function render(): View
    {
        $this->authorize('view', $this->employee);

        $this->employee->load(['user', 'creator', 'updater']);

        $canSeeActivity = Auth::user()?->can('viewAny', AuditLog::class) ?? false;

        return view('livewire.employees.show', [
            'activity' => $canSeeActivity
                ? AuditLog::query()
                    ->with('user')
                    ->where('auditable_type', $this->employee->getMorphClass())
                    ->where('auditable_id', $this->employee->id)
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->limit(10)
                    ->get()
                : null,
        ])->title($this->employee->full_name);
    }
}
