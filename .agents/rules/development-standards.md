---
trigger: always_on
description: Core ERP development rules: global workflow skills, backend route & restore/permanent delete permission validation, mandatory UUID database migrations, atomic design & mobile-first UI, and stack-specific skill activation.
---

# ERP Development Standards & Skill Activation Rules

These rules are mandatory and must be strictly followed for every prompt and coding task in this repository.

## 1. Global Prompt Execution Rules (Always Active)

For every prompt, always activate and adhere to the following skills:

- **/graphify**: Maintain and consult codebase knowledge, understand module relationships, and keep the architecture clean and consistent.
- **/ponytail**: Apply lazy senior developer mode — choose the smallest, simplest, and most robust solution that fully solves the task without unnecessary complexity, redundant code, or over-engineering.

---

## 2. Backend Route & Permission Security Rules

- **Backend Authorization Is Mandatory**: Every newly created or modified route in `routes/web.php` or API route files **MUST** be protected by backend authorization middleware using Spatie Laravel Permission:
  - Use `middleware('permission:<permission.name>')` for permission-based routes (e.g., `permission:suppliers.view`, `permission:suppliers.create`).
  - Use `middleware('role:<role.name>')` for role-restricted modules (e.g., `role:Superadmin` for the Recycle Bin).

- **Never Rely Solely on UI Visibility**: Frontend conditional rendering (e.g., `v-if="can(...)"` in sidebars or action buttons) is purely visual. Backend routes must independently reject unauthorized requests with `403 Forbidden`.

- **Mandatory Restore & Permanent Deletion Permissions**:
  - Whenever a new module or feature includes a delete permission or deletion route (including soft deletion, such as `*.delete` or `*.destroy`), **YOU MUST** create and register the corresponding permissions:
    - `<resource>.restore` — authorizes restoring deleted records from the Recycle Bin.
    - `<resource>.permanently-delete` (or `<resource>.force-delete`, depending on the existing Recycle Bin naming convention) — authorizes permanently deleting records.
  - These permissions must always be registered in `database/seeders/RolePermissionSeeder.php`, granted to `Superadmin` by default, and enforced by the relevant controllers and routes.

- **Superadmin Bypass**: Preserve the global `Gate::before` authorization bypass in `AppServiceProvider.php` so that `Superadmin` retains universal access.

- **Automated Verification**: Whenever routes are added or modified, ensure unit or feature tests verify the following scenarios:
  1. Unauthenticated users are redirected to the login page (`302`).
  2. Authenticated users without the required permission receive `403 Forbidden`.
  3. Users with the required permission or the `Superadmin` role are authorized successfully (`200 OK`), where applicable to the endpoint.

---

## 3. Database & Migration Standards (Mandatory UUID)

- **Never Use Integer Auto-Incrementing IDs**: Never use `$table->id()` or `$table->bigIncrements('id')` when creating new database tables.

- **UUIDs Are Mandatory**:
  - Every new database table migration **MUST** use a UUID as its primary key:
    ```php
    $table->uuid('id')->primary();
    ```
  - Foreign keys must use UUID data types:
    ```php
    $table->foreignUuid('department_id')->constrained()->cascadeOnDelete();

    // Alternatively, for nullable or manually defined foreign keys:
    $table->uuid('department_id')->nullable();
    ```
  - Polymorphic relationships must use `$table->uuidMorphs(...)` or `$table->nullableUuidMorphs(...)`, as appropriate.

- **Eloquent Models Must Use HasUuids**:
  - Every new Eloquent model **MUST** implement the `Illuminate\Database\Eloquent\Concerns\HasUuids` trait:
    ```php
    use Illuminate\Database\Eloquent\Concerns\HasUuids;

    class Department extends Model
    {
        use HasFactory, HasUuids, SoftDeletes;

        // HasUuids automatically configures $keyType = 'string'
        // and $incrementing = false.
    }
    ```

---

## 4. UI/UX & Frontend Standards

- **Atomic Design Architecture**: All UI components must follow the Atomic Design hierarchy:

  - `Atoms` (`resources/js/Components/Atoms/`): Single-purpose elements such as `Button`, `TextInput`, `Icon`, `Badge`, and `Avatar`.
  - `Molecules` (`resources/js/Components/Molecules/`): Simple groups of atoms such as `FormField`, `SearchInput`, `SidebarLink`, and `Pagination`.
  - `Organisms` (`resources/js/Components/Organisms/`): Complex, self-contained components such as `AppSidebar`, `AppHeader`, `ModalDialog`, and `StatsCard`.
  - `Templates` (`resources/js/Components/Templates/`): Structural page layouts such as `AuthenticatedTemplate` and `GuestTemplate`.
  - `Pages` (`resources/js/Pages/`): Full Inertia views that assemble templates, organisms, and page-specific props.

- **Mobile-First & Cross-Device Precision**:
  - Always use a mobile-first styling approach: base Tailwind CSS classes for mobile, with progressive enhancements through `sm:`, `md:`, `lg:`, and `xl:` breakpoints.
  - Ensure zero horizontal overflow and provide touch targets of at least `44 × 44px`.
  - Make tables responsive: use collapsible or horizontally scrollable layouts on mobile and full table layouts on desktop, as appropriate.

- **Design Quality & Visual Consistency**:
  - Use a clean enterprise color palette, such as corporate blue (`blue-600` / `blue-700`) and dark-mode slate tones (`slate-900` / `slate-950`).
  - Implement smooth micro-interactions, subtle transitions, and accessible color contrast ratios.

---

## 5. Stack-Specific Skill Activation Rules

### Frontend Workflows

Whenever creating, updating, or reviewing interfaces, styles, Vue components, or Inertia page interactions, always activate and follow these skills:

- **/laravel-inertia-vue**: Follow Inertia.js and Vue 3 best practices, including reactive form handling with `useForm`, flash notifications, and page props.
- **/ui-ux-pro-max**: Apply design intelligence, strong typography, high-contrast color palettes, refined micro-interactions, and accessible layout design.
- **/atomic-design-fundamentals**: Enforce strict component categorization, hierarchy, and reusability.

### Backend Workflows

Whenever creating, updating, or reviewing routes, controllers, middleware, models, migrations, or database queries, always activate and follow these skills:

- **/laravel-inertia-vue**: Follow Inertia response formatting, shared props, validation error bags, and redirect flows.
- **/laravel-security**: Apply CSRF protection, SQL injection prevention, Eloquent security best practices, authorization gates, and secure production practices.
- **/laravel-specialist**: Follow idiomatic Laravel conventions, use Form Requests where appropriate, define proper Eloquent relationships, and maintain automated test coverage with Pest or PHPUnit.