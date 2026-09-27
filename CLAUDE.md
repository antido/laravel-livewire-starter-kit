# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command.

### macOS

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

### Windows PowerShell

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

### Linux

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Task Execution Guide

Before starting the user's requested development task, check whether the repository contains:

```text
docs/run-task.md
```

If the file exists, **read `docs/run-task.md` before making any application changes**.

The instructions in `docs/run-task.md` define the project's task execution workflow and must be followed for the current task.

### Task Execution Rules

1. Check for `docs/run-task.md`.
2. Read the complete file before modifying application code.
3. Follow all applicable instructions, conventions, requirements, and workflow steps defined in `docs/run-task.md`.
4. Treat `docs/run-task.md` as project-specific instructions for executing the user's request.
5. If `docs/run-task.md` references additional documentation, configuration files, or instructions, read those files when they are relevant to the task.
6. Do not skip required steps from `docs/run-task.md`.
7. If the task conflicts with the instructions in `docs/run-task.md`, stop and ask the user for clarification before making changes.
8. After completing the task, verify the implementation according to the testing and verification requirements defined in `docs/run-task.md`.

### Task Execution Command

If `docs/run-task.md` contains executable commands or a documented task-running procedure, use that procedure rather than inventing an alternative workflow.

If the file provides a specific command such as:

```sh
php artisan ...
```

or:

```sh
npm ...
```

follow the documented command and its required arguments exactly unless the user's request explicitly requires a different approach.

### Missing Task Guide

If `docs/run-task.md` does not exist:

- Continue with the user's request using the repository's existing documentation and conventions.
- Do not create `docs/run-task.md` unless the user explicitly asks for it.
- If the task cannot be completed safely without the missing instructions, ask the user for the required project-specific workflow.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev

php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application.

After installation:

1. Read `AGENTS.md` again.
2. Check `docs/run-task.md` again, if it exists.
3. Apply the generated Laravel Boost guidelines.
4. Continue with the user's original request.

## Execution Order

Use the following order for every development task:

```text
1. Verify PHP and Composer
        ↓
2. Check docs/run-task.md
        ↓
3. Read docs/run-task.md completely
        ↓
4. Install/configure Laravel Boost if required
        ↓
5. Read AGENTS.md after Boost installation
        ↓
6. Re-check docs/run-task.md
        ↓
7. Analyze the user's request
        ↓
8. Implement the requested changes
        ↓
9. Run the required tests/verification
        ↓
10. Report the changes and verification results
```

Do not modify application code before completing the required instruction-reading and setup steps.
