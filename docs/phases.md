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

## Phase 6 — Admin Panel

Goal: allow administrators to control the platform comprehensively, while maintaining cross-role access.

Deliverables:
- `admin/dashboard.php` with summary business metrics and quick actions.
- `admin/users.php` for robust user CRUD capabilities. Must include:
  - User status administration (Active, Pending, Suspended).
  - ID Verification Engine: Admin visually inspecting user-uploaded `id_card_url` and flipping `id_is_verified` boolean.
  - Profile modification and auditing.
- `admin/reservations.php` for reservation oversight and forced status interventions.
- `admin/vehicles.php` for vehicle fleet verification.
- Universal Admin Access: Ensure admin accounts bypass rigid constraints to successfully test and interact with Client and Transporter modules alike.
  - **Commission & Billing**: Admin must manually verify and clear transporter commission batches. Once a transporter accumulates 5 reservations where `is_commission_paid = 0`, their account is automatically restricted from accepting new jobs.
  - **Confirmation de reçu (Payment Verification)**: The transporter pays the debt to the platform's `APP_RIP_ACCOUNT`. The admin receives/verifies the proof of payment (reçu), and then manually intervenes in the admin panel to officially confirm the payment. This action marks the batch as cleared and unblocks the transporter's privileges.

Why this phase matters:
- Establishes a highly capable governance layer.
- Accelerates the onboarding verification loop.
- Eliminates the need for multiple admin test accounts by granting all-inclusive visibility.

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
