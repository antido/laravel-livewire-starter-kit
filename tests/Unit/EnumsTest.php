<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\AuditEvent;
use App\Enums\EmployeeStatus;
use App\Enums\NotificationType;
use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Enums\ThemePreference;
use App\Enums\UserStatus;
use PHPUnit\Framework\TestCase;

class EnumsTest extends TestCase
{
    public function test_permission_names_follow_the_module_action_format(): void
    {
        foreach (PermissionName::cases() as $permission) {
            $this->assertMatchesRegularExpression('/^[a-z_]+\.[a-z_]+$/', $permission->value);
        }

        $this->assertSame('audit_trails', PermissionName::AuditTrailsView->module());
        $this->assertSame('view', PermissionName::AuditTrailsView->action());
        $this->assertSame('reports', PermissionName::moduleOf('reports.run'));
        $this->assertSame('run', PermissionName::actionOf('reports.run'));
        $this->assertTrue(PermissionName::isSystem('users.view'));
        $this->assertFalse(PermissionName::isSystem('reports.run'));
    }

    public function test_the_super_admin_role_gets_no_direct_permissions(): void
    {
        $this->assertSame([], RoleName::SuperAdmin->defaultPermissions());
        $this->assertSame(PermissionName::cases(), RoleName::Owner->defaultPermissions());
        $this->assertTrue(RoleName::isSystem('User'));
        $this->assertFalse(RoleName::isSystem('Staff'));
        $this->assertFalse(RoleName::isSystem('Night shift'));
    }

    public function test_every_case_has_a_label_and_a_badge(): void
    {
        foreach (AuditEvent::cases() as $event) {
            $this->assertNotSame('', $event->label());
            $this->assertContains($event->badge(), ['neutral', 'accent', 'success', 'warning', 'danger', 'info']);
        }

        foreach (EmployeeStatus::cases() as $status) {
            $this->assertNotSame('', $status->label());
        }

        foreach (NotificationType::cases() as $type) {
            $this->assertNotSame('', $type->icon());
        }

        $this->assertSame(['active' => 'Active', 'inactive' => 'Inactive'], UserStatus::options());
        $this->assertSame(['light', 'dark', 'system'], ThemePreference::values());
    }

    public function test_only_resigned_and_terminated_count_as_having_left(): void
    {
        $this->assertTrue(EmployeeStatus::Resigned->hasLeft());
        $this->assertTrue(EmployeeStatus::Terminated->hasLeft());
        $this->assertFalse(EmployeeStatus::Active->hasLeft());
        $this->assertFalse(EmployeeStatus::OnLeave->hasLeft());
    }
}
