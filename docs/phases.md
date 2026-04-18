# Project Phases and Delivery Plan

This document describes the planned development phases for the Transport Reservation Platform. It is written as a project management pacing guide, with each phase delivering a working part of the system.

## Phase 1 — Project Foundation

Goal: prepare the project structure and database.

Deliverables:
- Folder layout for application modules and shared resources.
- Database configuration and connection layer.
- Core files for session handling and shared includes.
- Initial SQL schema with users, vehicles, reservations, earnings, and notifications.

Why this phase matters:
- Establishes a clean architecture.
- Separates roles early so future features can be built without refactoring.
- Provides the stable foundation for authentication and data flow.

## Phase 2 — Authentication System

Goal: allow users to register, login, and logout.

Deliverables:
- `index.php` for login entry.
- `auth/register.php` for user signup.
- `logout.php` to clear session.
- Secure password hashing and verification.
- Role-based redirect logic.

Why this phase matters:
- Grants access control to the app.
- Creates the `$_SESSION['user_id']` and `$_SESSION['role']` context used by all protected pages.
- Ensures only authorized users reach role-specific dashboards.

## Phase 3 — Client Module

Goal: allow the client to request transport services.

Deliverables:
- `client/dashboard.php` showing reservation summaries.
- `client/request_transport.php` form for creating reservations.
- `client/reservations.php` listing client reservation history.
- `client/reservation_details.php` showing reservation and transporter details.

Why this phase matters:
- Implements the main client use case.
- Validates the end-to-end flow from form submission to database persistence.
- Builds the user experience for tracking request status.

## Phase 4 — Transporter Module

Goal: allow transporters to manage vehicles and requests.

Deliverables:
- `transporter/dashboard.php` showing jobs and earnings.
- `transporter/vehicles.php` for adding and managing vehicles.
- `transporter/requests.php` for viewing and accepting new work.
- `transporter/jobs.php` for ongoing and completed jobs.
- `transporter/earnings.php` for earnings history.

Why this phase matters:
- Connects transporters to client requests.
- Enables the supply side of the marketplace.
- Lets transporters act on `pending` reservations.

## Phase 5 — Reservation System Core

Goal: implement the lifecycle that connects client requests to transporter actions.

Deliverables:
- Request creation with `status = pending`.
- Transporter acceptance flow updating status to `accepted`.
- In-progress tracking updating status to `in_progress`.
- Completion flow setting status to `completed`.

Why this phase matters:
- Defines the core business process of the platform.
- Supports status-based dashboards and reports.
- Makes the reservation system reliable and traceable.

## Phase 6 — Admin Panel ✅ IMPLEMENTED

Goal: allow administrators to fully control, monitor, and regulate the platform while maintaining secure cross-role visibility and operational authority.

### Implemented Components

#### Backend (`functions/admin.php`)
All business logic is centralized with zero SQL in templates:
- **User Functions**: `getAllUsers()`, `getUserById()`, `updateUserStatus()`, `verifyUserID()`, `unverifyUserID()`, `deleteUser()`, `updateUserProfile()`
- **Reservation Functions**: `getAllReservations()`, `adminUpdateReservationStatus()`
- **Vehicle Functions**: `getAllVehicles()`, `adminUpdateVehicleStatus()`
- **Commission Functions**: `getUnpaidCommissions()`, `getTransporterUnpaidReservations()`, `markBatchPaid()`, `checkTransporterBlock()`
- **Dashboard KPIs**: `getAdminDashboardStats()`
- **Notifications**: `sendAdminNotification()`

#### Pages Delivered
- `admin/dashboard.php` — System overview with 8 live KPIs (users, reservations, revenue, unpaid commissions, blocked transporters), quick action grid, recent activity feed, pending ID verifications widget.
- `admin/users.php` — Full user CRUD with search & filter (role/status/text), status toggles (Active/Pending/Suspended), ID verification modal with image preview, profile edit modal, user deletion with confirmation, notification triggers.
- `admin/reservations.php` — All reservations with filters (status/search), forced status interventions with validation, commission tracking per reservation (paid/unpaid badges), transporter assignment display.
- `admin/vehicles.php` — Fleet overview with owner details, trip counts, plate display, approve/reject workflow with notification triggers.
- `admin/commissions.php` — Full commission governance panel: debt summary KPIs, RIP account display, transporter debt cards with blocked status, line-item detail modal, batch payment confirmation with notification and unblocking.

#### Business Rules Enforced
- **Batch of 5 Rule**: Transporters with ≥5 completed unpaid reservations are blocked from accepting new work. Enforced in `transporter/requests.php` and `functions/reservation/lifecycle.php`.
- **Receipt Verification**: Admin inspects proof of payment, clicks "Confirmer Reçu" → all unpaid reservations for that transporter are marked `is_commission_paid = 1` → account unblocked.
- **Universal Admin Access**: Admin accounts bypass role gates to test Client and Transporter flows.

#### Security
- RBAC via `enforceRole('admin')` on all admin routes
- CSRF tokens on all POST forms
- `htmlspecialchars()` on all outputs
- PDO prepared statements throughout
- Confirmation modals on destructive actions

#### Notifications Integration
Admin actions trigger notifications to users:
- User verified → notification sent
- User status changed → notification sent
- Vehicle approved/rejected → notification sent
- Commission payment confirmed → notification sent

Why this phase matters:
- Establishes a comprehensive governance layer with financial oversight.
- Accelerates the onboarding verification loop.
- Introduces real-time commission tracking and enforcement.
- Eliminates the need for multiple admin test accounts by granting all-inclusive cross-role visibility.

## Phase 7 — Finalization & PFE Preparation

Goal: polish the project and prepare the final deliverable.

Deliverables:
- UI/UX improvements for professional look and feel.
- Alerts, notifications, and status badges.
- Clean codebase and documentation.

Why this phase matters:
- Turns a working system into a presentable PFE product.
- Improves quality for evaluation.
- Helps ensure the project is complete and coherent.

## Phase 8 — Testing

Goal: verify functionality and fix bugs.

Key checks:
- Register and login flows.
- Reservation creation and status updates.
- Transporter acceptance and job progression.
- Admin controls and role enforcement.

Why this phase matters:
- Confirms the system works end-to-end.
- Prevents regressions before delivery.
- Increases confidence in the final project.

## Phase 9 — PFE Report

Goal: document the project for the final report.

Deliverables:
- Screenshots of login, dashboard, reservation form, and admin pages.
- Summary of architecture and features.
- Notes on database design and security.

Why this phase matters:
- Provides the written evidence required by PFE evaluation.
- Makes the development process clear to reviewers.

## Phase 10 — Soutenance Preparation

Goal: prepare the oral presentation.

Deliverables:
- 10–15 slides covering:
  - introduction
  - problem statement
  - objectives
  - system architecture
  - database design
  - features
  - demo plan
  - conclusion

Why this phase matters:
- Ensures you can explain the project clearly.
- Helps you present the work professionally.
- Supports a strong final grade.

---

## Notes for the Current Phase

The current focus is on Phase 3, the Client Module. Work should prioritize:
- finishing the request transport form,
- saving client reservations securely,
- displaying reservation statuses,
- building the client dashboard summary.

This keeps the project moving toward a usable MVP while preserving the future transport and admin modules.
