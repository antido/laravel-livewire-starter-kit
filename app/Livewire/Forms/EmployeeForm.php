<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Models\Employee;
use Livewire\Form;

/**
 * The fields of the employee create and edit pages. There is no user_id or employee_number field:
 * the server sets those.
 */
class EmployeeForm extends Form
{
    /**
     * Optional fields: a cleared input sends "", which is turned back into null before validation.
     */
    private const array OPTIONAL = ['middle_name', 'suffix', 'email', 'phone', 'address', 'hire_date', 'notes', 'account_email', 'account_password', 'account_password_confirmation'];

    public string $first_name = '';

    public ?string $middle_name = null;

    public string $last_name = '';

    public ?string $suffix = null;

    public ?string $email = null;

    public ?string $phone = null;

    public ?string $address = null;

    public string $position = '';

    public ?string $hire_date = null;

    public string $status = 'active';

    public ?string $notes = null;

    /**
     * none, create or link (keep on edit when an account is already linked).
     */
    public string $account_mode = 'none';

    public ?string $account_email = null;

    public ?string $account_password = null;

    public ?string $account_password_confirmation = null;

    /** @var list<string> */
    public array $account_roles = [];

    public ?int $link_user_id = null;

    public bool $deactivate_account = true;

    public function fillFrom(Employee $employee): void
    {
        $this->first_name = $employee->first_name;
        $this->middle_name = $employee->middle_name;
        $this->last_name = $employee->last_name;
        $this->suffix = $employee->suffix;
        $this->email = $employee->email;
        $this->phone = $employee->phone;
        $this->address = $employee->address;
        $this->position = $employee->position;
        $this->hire_date = $employee->hire_date?->format('Y-m-d');
        $this->status = $employee->status->value;
        $this->notes = $employee->notes;
        $this->account_mode = $employee->user_id !== null ? 'keep' : 'none';
    }

    public function normalize(): void
    {
        foreach (self::OPTIONAL as $field) {
            if (is_string($this->{$field}) && trim($this->{$field}) === '') {
                $this->{$field} = null;
            }
        }

        if ($this->account_email !== null) {
            $this->account_email = mb_strtolower(trim($this->account_email));
        }
    }

    /**
     * Never keep typed passwords in the component state sent to the browser.
     */
    public function forgetPasswords(): void
    {
        $this->account_password = null;
        $this->account_password_confirmation = null;
    }
}
