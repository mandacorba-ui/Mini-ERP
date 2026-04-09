# AGENT.md — Mini ERP: Time & Workforce Management

This file is for the AI coding agent working on this repository.
The goal is to complete the engineering task exactly as requested, using **Laravel + Livewire + SQLite**, with clean structure, correct date/time logic, and solid validation.

---

## Mission

Build a small ERP module for **workforce management** with these core areas:

1. **Authentication + roles**
2. **Time logging**
3. **Task management**
4. **Holiday / leave requests**
5. **Shift scheduling**
6. **Dashboard summary**
7. **Meaningful automated tests**

The biggest evaluation points are:
- clean code structure
- correct date/time overlap logic
- business rule enforcement
- readable commits / sensible implementation order
- using AI effectively, but not blindly

---

## Required Stack

- **Laravel 12.x**
- **PHP 8.2+**
- **Livewire 3 + Volt**
- **Tailwind CSS 4.x**
- **Alpine.js 3.x**
- **SQLite** as the database for local development and task delivery
- **Pest or PHPUnit** for tests

### Important SQLite Notes

This project uses **SQLite**, not MySQL.
When generating migrations, queries, constraints, and tests:

- Prefer Laravel schema features that are fully compatible with SQLite.
- Avoid database-specific SQL that assumes MySQL behavior.
- Use Laravel relationships, validation, Eloquent scopes, accessors, and transactions instead of raw SQL when possible.
- Foreign keys must still be defined properly.
- Date/time calculations must be done in PHP/Carbon or framework-safe query logic.

---

## Project Goal

The application should allow:

### Employees
- register / log in
- clock in and clock out
- create manual time log corrections with a required note
- view their tasks
- log hours on assigned tasks
- request holidays
- view assigned shifts

### Managers
- approve or reject holiday requests
- assign tasks
- create shifts and assign employees
- view team summaries

### Admins
- manage all users and all data

---

## Architecture Approach

Use a **clean, layered Laravel structure** inspired by DDD, but keep it practical for a task.
Do **not** overengineer.
The code should feel deliberate, readable, and easy for a reviewer to follow.

### Preferred Structure

```text
app/
├── Domain/
│   ├── IdentityAndAccess/
│   │   ├── Enums/
│   │   ├── Models/
│   │   └── Policies/
│   ├── TimeTracking/
│   │   ├── Actions/
│   │   ├── DTOs/
│   │   ├── Models/
│   │   └── Services/
│   ├── TaskManagement/
│   │   ├── Actions/
│   │   ├── DTOs/
│   │   ├── Enums/
│   │   ├── Models/
│   │   └── Services/
│   └── WorkforcePlanning/
│       ├── Actions/
│       ├── DTOs/
│       ├── Enums/
│       ├── Models/
│       └── Services/
├── Http/
│   ├── Controllers/
│   └── Middleware/
resources/
├── views/
│   ├── livewire/
│   ├── layouts/
│   └── components/
database/
├── migrations/
├── factories/
└── seeders/
tests/
├── Feature/
└── Unit/
```

If a simpler default Laravel structure is needed to save time, keep the same separation of concerns:
- **Models own relationships/scopes**
- **Actions handle business operations**
- **Services handle reusable logic**
- **Controllers / Livewire components stay thin**

---

## Main Domains

### 1. IdentityAndAccess
Responsibility:
- users
- authentication
- role-based access

Main model:
- `User`

Roles:
- `employee`
- `manager`
- `admin`

Use either:
- a PHP enum like `UserRole`
- or a constrained string field cast safely

Prefer enum-backed logic.

---

### 2. TimeTracking
Responsibility:
- clock in / clock out
- manual corrections
- daily totals
- weekly totals

Main model:
- `TimeLog`

Core fields:
- `user_id`
- `clock_in`
- `clock_out` nullable
- `note` nullable, but required for manual entries

Important rule:
- `duration` must be **computed**, not stored permanently

---

### 3. TaskManagement
Responsibility:
- task creation
- assignment
- status transitions
- logged hours vs estimated hours

Main models:
- `Task`
- `TaskAssignment`

Task fields:
- `title`
- `description`
- `priority`
- `due_date`
- `estimated_hours`
- `status`

Status flow:
- `todo`
- `in_progress`
- `in_review`
- `done`

Only forward transitions are allowed.

---

### 4. WorkforcePlanning
Responsibility:
- holiday requests
- holiday approvals
- leave allowance handling
- shifts
- shift assignments
- overlap prevention

Main models:
- `Holiday`
- `Shift`
- `ShiftAssignment`

Holiday fields:
- `user_id`
- `approved_by` nullable
- `start_date`
- `end_date`
- `type`
- `status`
- `comment` nullable

Shift fields:
- `date`
- `start_time`
- `end_time`
- `label`

---

## Data Model Expectations

The implementation should support these relationships:

```text
User hasMany TimeLog
User belongsToMany Task through TaskAssignment
Task hasMany TaskAssignment
User hasMany Holiday (requester)
User hasMany Holiday (approver)
Shift hasMany ShiftAssignment
ShiftAssignment belongsTo User
```

For clarity and reviewer friendliness, name things explicitly.
Do not use vague names.

---

## Database Rules

### General
- Prefer **ULIDs** for primary keys if time allows and usage is consistent.
- If staying with Laravel defaults speeds things up, use defaults consistently.
- Every foreign key must be constrained.
- Add indexes on fields used in frequent lookups: `user_id`, `date`, `status`, `clock_in`, `due_date`.

### SQLite-specific guidance
- Keep migrations portable and simple.
- Avoid relying on advanced ALTER behavior or DB-specific expressions.
- Do overlap calculations and business rules in application logic.

### Dates and Times
Use the correct column types:
- `timestamp` / `dateTime` for `clock_in`, `clock_out`
- `date` for holiday ranges
- `date` + `time` for shifts

Use **Carbon** for all date/time logic.
Never do manual string-based date math.

---

## Non-Negotiable Business Rules

These rules must be enforced in validation and/or actions.

### Time Logging
1. An employee cannot clock in if they already have an open time log.
2. An employee must clock out before opening another time log.
3. Manual time entries must require a `note`.
4. Daily and weekly totals must be calculated from real logs.
5. Open logs with `clock_out = null` must be handled safely.

### Tasks
1. Tasks have statuses:
   - `todo`
   - `in_progress`
   - `in_review`
   - `done`
2. Status can only move forward.
3. Tasks can have one or more assignees.
4. Employees log their own hours against tasks.
5. Overdue task logic:
   - `due_date < today`
   - and status is not `done`

### Holidays
1. Employees can request leave with a start and end date.
2. Holiday requests must not overlap existing **approved** holidays for the same employee.
3. Leave must respect a configurable annual allowance.
4. Default annual allowance should be **20 days**.
5. Count only **weekdays (Mon–Fri)** for annual leave deduction.
6. Manager can approve or reject with an optional comment.
7. On approval, leave balance is deducted.
8. On rejection, no deduction happens.
9. Decide and document whether managers can approve their own holiday requests.
   - Preferred rule: **do not allow self-approval**.

### Shifts
1. Managers can create shifts and assign employees.
2. An employee cannot be assigned to two overlapping shifts.
3. An employee cannot be assigned to a shift on an approved leave day.
4. Labels should use an enum such as:
   - `Morning`
   - `Afternoon`
   - `Night`

---

## Reusable Date Overlap Logic

Date/time conflict checking is the heart of this task.
Create a reusable service, for example:

- `OverlapDetectionService`
- or `DateRangeOverlapChecker`

It should be reusable for:
- holidays
- shifts
- time logs where relevant

### Core overlap rule
Two ranges overlap if:

```text
start_a < end_b AND start_b < end_a
```

### Guidance
- Keep overlap logic centralized.
- Test it with unit tests.
- Make it easy to exclude a current record during updates.
- Use Carbon instances when possible.

---

## Services and Actions

Keep controllers and Livewire handlers thin.

### Actions should handle single business operations
Examples:
- `ClockInAction`
- `ClockOutAction`
- `CreateManualTimeEntryAction`
- `CreateTaskAction`
- `AssignTaskAction`
- `LogTaskHoursAction`
- `RequestHolidayAction`
- `ApproveHolidayAction`
- `RejectHolidayAction`
- `AssignShiftAction`

### Services should handle reusable logic
Examples:
- `TimeCalculationService`
- `LeaveBalanceService`
- `OverlapDetectionService`
- `TaskProgressService`

### Transaction rule
Any operation that performs multiple related writes must use:

```php
DB::transaction(...)
```

Especially for:
- holiday approval
- shift assignment with conflict checks
- multi-step task assignment

---

## DTO and Enum Guidance

Use DTOs where helpful to keep Livewire/components/controllers clean.
Examples:
- `ManualTimeEntryDTO`
- `CreateTaskDTO`
- `HolidayRequestDTO`
- `ShiftAssignmentDTO`

Use enums for all statuses and types.
Never scatter raw status strings throughout the codebase.

Examples:
- `UserRole`
- `TaskStatus`
- `TaskPriority`
- `HolidayStatus`
- `LeaveType`
- `ShiftLabel`

For `TaskStatus`, add a helper like:
- `canTransitionTo()`

---

## UI Guidance

Use **Livewire Volt** for fast, simple UI delivery.
Use **Tailwind** for styling only.
Use **Alpine.js** for lightweight interactivity.

### Required UI areas
- auth pages
- employee dashboard
- manager dashboard sections
- clock in / out page
- manual time entry form
- tasks list / detail / assignment flow
- holiday request form + approval view
- weekly shift calendar/team schedule

### Dashboard should show
- hours worked this week vs expected hours
- pending holiday requests for managers
- upcoming shifts for next 7 days
- overdue tasks

Keep the UI practical and readable.
This is not a design competition.

---

## Testing Requirements

Write at least **5 meaningful tests**.
Minimum areas to cover:

1. overlapping shift prevention
2. holiday balance validation
3. double clock-in prevention
4. task status transitions
5. role-based access denial

### Recommended split
**Feature tests**
- employee cannot double clock in
- manager can approve holiday
- employee cannot access manager-only action
- overlapping shift assignment is rejected

**Unit tests**
- `OverlapDetectionService`
- `LeaveBalanceService`
- `TimeCalculationService`
- `TaskStatus::canTransitionTo()`

Use `RefreshDatabase`.
Ensure tests run against SQLite reliably.

---

## Seeder Guidance

Seed at least **2 weeks of realistic sample data**.
The seeded data should make the dashboard and business rules easy to demonstrate.

Recommended seeded scenarios:
- several users across employee / manager / admin roles
- time logs for the last 14 days
- at least one employee with an open time log
- tasks in different statuses
- task assignments across multiple employees
- approved, pending, and rejected holiday requests
- at least one overlapping holiday scenario for validation tests
- shifts across a weekly schedule
- at least one scheduling conflict scenario for tests

Seed data should feel realistic, not random nonsense.

---

## Coding Rules

1. **Do not put business logic in controllers.**
2. **Do not put heavy business logic directly in Blade/Volt views.**
3. **Use explicit `$fillable` or guarded strategy intentionally. Do not be careless.**
4. **Always type-hint parameters and return types where practical.**
5. **Use descriptive method names.**
6. **Prefer small focused classes over giant controllers/components.**
7. **Use Eloquent scopes for repeated query patterns.**
8. **Prefer readable code over clever code.**
9. **Handle edge cases explicitly.**
10. **Leave concise comments only where they add real value.**

---

## Suggested Eloquent Scopes

Examples of useful scopes:

- `TimeLog::thisWeek()`
- `TimeLog::forUser($userId)`
- `Holiday::pending()`
- `Holiday::approved()`
- `Shift::upcoming()`
- `Task::overdue()`

Use scopes to make controllers/components cleaner.

---

## Implementation Strategy

Build the project in **vertical slices**.
Do not scaffold everything at once.

### Recommended order
1. project setup + auth + roles
2. time logging end-to-end
3. holiday requests + approval flow
4. shifts + conflict prevention
5. tasks + assignments + logged hours
6. dashboard summary
7. tests refinement
8. seeding cleanup / polish

### For each feature slice
Implement in this order:
1. migration
2. model
3. factory
4. seeder
5. enum / DTO
6. service / action
7. Livewire Volt component / route
8. tests

This helps keep progress reviewable and stable.

---

## Git Guidance

Use clean commit history.
Commit messages should be imperative and specific.
Examples:
- `Add time logging clock in and clock out flow`
- `Prevent overlapping shift assignments`
- `Add holiday approval balance validation`
- `Add dashboard weekly summary widgets`

Branch naming examples:
- `feature/time-logging`
- `feature/holiday-approval`
- `fix/shift-overlap-validation`

---

## What the Agent Must Avoid

Do **not**:
- overengineer with unnecessary abstractions
- introduce packages unless clearly needed
- write raw SQL when Eloquent/service logic is enough
- hardcode statuses as magic strings everywhere
- store `duration` redundantly if it can be derived
- ignore SQLite compatibility
- skip tests for critical business rules
- create giant controllers with mixed responsibilities
- silently assume edge cases away

---

## Reviewer-Friendly Decisions

When a rule is ambiguous, choose a sensible default and make it explicit in code/comments.
Examples:
- only weekdays count toward annual leave balance
- self-approval of holidays is not allowed
- open time logs are excluded from completed-duration totals until clock-out

Good explicit decisions are better than hidden assumptions.

---

## Prompting Guidance for the AI Agent

When generating code, follow these prompt patterns.

### Example: time logging
> Create the TimeTracking feature using SQLite-safe Laravel code. Add the migration, model, factory, action, Livewire Volt page, and tests for clock in/out. Prevent double clock-in by rejecting a new clock-in if an open time log already exists for the employee.

### Example: holiday approval
> Create an ApproveHolidayAction that takes a Holiday and a manager User. Verify the manager role, forbid self-approval, check leave balance using LeaveBalanceService, ensure no overlap with existing approved holidays, approve inside a DB transaction, deduct only business days, and add tests.

### Example: shifts
> Create shift scheduling with SQLite-compatible migrations and validation. A manager can create a shift and assign employees. Reject assignments that overlap an existing shift for the same employee or fall on approved leave days. Add a weekly team calendar view and tests.

### Example: tasks
> Create task management with enums for priority and status, support multiple assignees, allow employees to log hours, and implement TaskStatus::canTransitionTo() so statuses only move forward.

---

## Definition of Done

The task is done when:

- auth works
- roles are enforced
- employees can clock in/out and add manual corrections
- tasks can be created, assigned, updated, and logged against
- holidays can be requested and approved/rejected correctly
- leave balance is validated and deducted properly
- shifts can be created and assigned without conflicts
- dashboard shows the requested summaries
- at least 5 meaningful tests pass
- code is readable, structured, and SQLite-compatible

---

## Final Instruction to the Agent

Optimize for:
1. correctness of business rules
2. clarity of structure
3. SQLite compatibility
4. reviewer readability
5. passing tests

Do not optimize for flashy architecture.
Build a clean, believable, well-factored submission that looks like thoughtful engineering work.
