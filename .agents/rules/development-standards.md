---
trigger: always_on
description: Core ERP development rules: global workflow skills, backend route permission validation, atomic design & mobile-first UI, and stack-specific skill activation.
---

# ERP Development Standards & Skill Activation Rules

These rules are mandatory and must be strictly followed in every prompt and coding task in this repository.

## 1. Global Prompt Execution Rules (Always Active)
On every prompt, always activate and adhere to:
- **/graphify**: Maintain and consult codebase knowledge, understand module relationships, and keep architecture clean.
- **/ponytail**: Apply lazy senior developer mode — choose the smallest, simplest, and most robust solution that completely solves the task without unnecessary bloat, redundant code, or over-engineering.

---

## 2. Backend Route & Permission Security Rules
- **Backend Permission Validation is Mandatory**: Every newly created or modified route in `routes/web.php` (or API routes) **MUST** be protected with backend authorization middleware using Spatie:
  - Use `middleware('permission:<permission.name>')` for permission-based routes (e.g. `permission:suppliers.view`, `permission:suppliers.create`).
  - Use `middleware('role:<role.name>')` for role-restricted modules (e.g. `role:Superadmin` on Recycle Bin).
- **Never Rely Solely on UI Hiding**: Frontend conditional rendering (e.g., `v-if="can(...)"` in sidebar or action buttons) is purely visual. The backend route must reject unauthorized access with `403 Forbidden`.
- **Superadmin Bypass**: Maintain the global `Gate::before` bypass in `AppServiceProvider.php` so `Superadmin` retains universal access.
- **Automated Verification**: When adding or updating routes, ensure unit/feature tests verify:
  1. Unauthenticated users are redirected (`302`).
  2. Users without permission receive `403 Forbidden`.
  3. Users with permission or `Superadmin` are authorized (`200 OK`).

---

## 3. UI/UX & Frontend Standards
- **Atomic Design Concept**: All UI components must strictly adhere to Atomic Design hierarchy:
  - `Atoms` (`resources/js/Components/Atoms/`): Single-purpose elements (`Button`, `TextInput`, `Icon`, `Badge`, `Avatar`).
  - `Molecules` (`resources/js/Components/Molecules/`): Simple atom groupings (`FormField`, `SearchInput`, `SidebarLink`, `Pagination`).
  - `Organisms` (`resources/js/Components/Organisms/`): Complex, self-contained components (`AppSidebar`, `AppHeader`, `ModalDialog`, `StatsCard`).
  - `Templates` (`resources/js/Components/Templates/`): Structural page layouts (`AuthenticatedTemplate`, `GuestTemplate`).
  - `Pages` (`resources/js/Pages/`): Full Inertia views assembling templates, organisms, and page props.
- **Mobile-First & Multi-Device Precision**:
  - Always write styles with a mobile-first approach (base Tailwind classes for mobile, progressive enhancement via `sm:`, `md:`, `lg:`, `xl:`).
  - Ensure zero horizontal overflow and touch-friendly target sizes (min 44x44px).
  - Responsive table views (collapsible/scrollable cards on mobile, full table on desktop).
- **Design Quality & Aesthetics**:
  - Clean enterprise palette (e.g. corporate blue `blue-600/700`, dark mode `slate-900/950`).
  - Smooth micro-interactions, subtle transitions, and accessible contrast ratios.

---

## 4. Stack-Specific Skill Activation Rules

### Frontend Workflows
Whenever creating, updating, or reviewing interfaces, styling, Vue components, or Inertia page interactions, always activate and follow:
- **/laravel-inertia-vue**: Inertia.js + Vue 3 best practices, reactive form handling (`useForm`), flash toasts, and page props.
- **/ui-ux-pro-max**: Design intelligence, typography, high-contrast color palettes, micro-interactions, and accessible layout design.
- **/atomic-design-fundamentals**: Strict component categorization, hierarchy enforcement, and reusability.

### Backend Workflows
Whenever creating, updating, or reviewing routes, controllers, middleware, models, migrations, or database queries, always activate and follow:
- **/laravel-inertia-vue**: Inertia response formatting, shared props, validation error bags, and redirect flows.
- **/laravel-security**: CSRF, SQL injection prevention, Eloquent safety, authorization gates, and secure production practices.
- **/laravel-specialist**: Idiomatic Laravel standards, FormRequests, Eloquent relations, and Pest/PHPUnit automated test coverage.
