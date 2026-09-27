<?php

declare(strict_types=1);

namespace App\Livewire\Employees;

use App\Enums\EmployeeStatus;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Livewire\Concerns\InteractsWithActor;
use App\Livewire\Concerns\ManagesEmployeeAccount;
use App\Livewire\Forms\EmployeeForm;
use App\Models\Employee;
use App\Models\User;
use App\Services\EmployeeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Edit employee')]
class Edit extends Component
{
    use InteractsWithActor, ManagesEmployeeAccount;

    #[Locked]
    public Employee $employee;

    public EmployeeForm $form;

    public function mount(Employee $employee): void
    {
        $this->authorize('update', $employee);

        $this->employee = $employee;
        $this->form->fillFrom($employee);
    }

    public function save(EmployeeService $employees): void
    {
        $this->authorize('update', $this->employee);

        $this->form->normalize();

        if (! $this->canManageAccounts() && $this->form->account_mode !== 'keep') {
            $this->form->account_mode = 'none';
        }

        try {
            /** @var array{first_name: string, last_name: string, position: string, status: string} $validated */
            $validated = $this->form->validate(UpdateEmployeeRequest::rulesFor($this->employee), StoreEmployeeRequest::fieldMessages());

            $saved = $this->attempt(fn () => $employees->update($this->actor(), $this->employee, $validated));
        } finally {
            $this->form->forgetPasswords();
        }

        if ($saved) {
            session()->flash('success', __('Changes to :name were saved.', ['name' => $this->employee->full_name]));

            $this->redirectRoute('employees.show', $this->employee);
        }
    }

    public function unlink(EmployeeService $employees): void
    {
        $this->authorize('update', $this->employee);

        $unlinked = $this->attempt(fn () => $employees->unlinkAccount($this->actor(), $this->employee), __('The login account was unlinked. It still exists.'));

        if ($unlinked) {
            $this->employee->refresh();
            $this->form->account_mode = 'none';
        }
    }

    public function render(): View
    {
        $linked = $this->employee->user;

        return view('livewire.employees.edit', [
            'statuses' => EmployeeStatus::options(),
            'canManageAccounts' => $this->canManageAccounts(),
            'canCreateAccounts' => $this->actor()->can('create', User::class),
            'canLinkAccounts' => $this->actor()->can('users.update'),
            'canUnlink' => $linked !== null && $this->actor()->can('update', $linked),
            'roleOptions' => $this->form->account_mode === 'create' ? $this->assignableRoleNames() : [],
            'linkableUsers' => $this->form->account_mode === 'link' ? $this->linkableUsers() : collect(),
            'linkedUser' => $linked,
            'showDeactivate' => $linked !== null && $linked->isActive()
                && (EmployeeStatus::tryFrom($this->form->status)?->hasLeft() ?? false)
                && $this->form->status !== $this->employee->status->value,
        ]);
    }
}
