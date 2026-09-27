<?php

declare(strict_types=1);

namespace App\Livewire\Employees;

use App\Enums\EmployeeStatus;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Livewire\Concerns\InteractsWithActor;
use App\Livewire\Concerns\ManagesEmployeeAccount;
use App\Livewire\Forms\EmployeeForm;
use App\Models\Employee;
use App\Models\User;
use App\Services\EmployeeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Add employee')]
class Create extends Component
{
    use InteractsWithActor, ManagesEmployeeAccount;

    public EmployeeForm $form;

    public function mount(): void
    {
        $this->authorize('create', Employee::class);
    }

    public function save(EmployeeService $employees): void
    {
        $this->authorize('create', Employee::class);

        $this->form->normalize();

        if (! $this->canManageAccounts()) {
            // The section is hidden; a crafted request cannot use it either (the service checks again).
            $this->form->account_mode = 'none';
        }

        try {
            /** @var array{first_name: string, last_name: string, position: string, status: string} $validated */
            $validated = $this->form->validate(StoreEmployeeRequest::fieldRules(), StoreEmployeeRequest::fieldMessages());

            $saved = null;
            $this->attempt(function () use ($employees, $validated, &$saved): void {
                $saved = $employees->create($this->actor(), $validated);
            });
        } finally {
            $this->form->forgetPasswords();
        }

        if ($saved instanceof Employee) {
            session()->flash('success', __(':name was added as :number.', ['name' => $saved->full_name, 'number' => $saved->employee_number]));

            $this->redirectRoute('employees.show', $saved);
        }
    }

    public function render(): View
    {
        return view('livewire.employees.create', [
            'statuses' => EmployeeStatus::options(),
            'canManageAccounts' => $this->canManageAccounts(),
            'canCreateAccounts' => $this->actor()->can('create', User::class),
            'canLinkAccounts' => $this->actor()->can('users.update'),
            'roleOptions' => $this->form->account_mode === 'create' ? $this->assignableRoleNames() : [],
            'linkableUsers' => $this->form->account_mode === 'link' ? $this->linkableUsers() : collect(),
            'employee' => null,
        ]);
    }
}
