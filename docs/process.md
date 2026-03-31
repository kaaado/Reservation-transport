# Development Process Documentation

This document describes the technical implementation process of the **Transport Reservation Platform** developed as part of the License (L3) Final Year Project (PFE).

---

# 🧭 Global Development Roadmap

Your project is built in delivery phases. The main functional phases are 1 through 7, and additional preparation phases 8 through 10 complete the PFE workflow.

- Phase 1 — Project Foundation
- Phase 2 — Authentication System
- Phase 3 — Client Module
- Phase 4 — Transporter Module
- Phase 5 — Reservation System Core
- Phase 6 — Admin Panel
- Phase 7 — Finalization & PFE Preparation
- Phase 8 — Testing
- Phase 9 — PFE Report
- Phase 10 — Soutenance Preparation

## Current Phase: Phase 3 — Client Module

This is the active phase. The current work focuses on client-side features: request transport form submission, saving reservations to the database, showing reservation status, and building the client dashboard.

---

# Phase 1 — Project Foundation

## Implemented Components

### Project Structure

Created a modular project architecture separating system responsibilities:

* `client/` → Client dashboard and transport request features
* `transporter/` → Transporter vehicle and job management
* `admin/` → Administrative dashboards and system management
* `config/` → Application configuration and database connection
* `includes/` → Shared components such as header, footer, and authentication middleware
* `functions/` → Backend logic separated from presentation
* `css/`, `js/`, `assets/` → Frontend resources

This structure ensures **clear separation of concerns and maintainability**.

---

### Database Architecture

Implemented a normalized relational database **transport_platform** using **MySQL InnoDB**.

Core tables:

* `users`
* `vehicles`
* `reservations`
* `earnings`

Each table includes proper:

* primary keys
* foreign key constraints
* timestamps
* optimized data types

---

### SQL Index Optimization

Indexes were created for frequently queried fields:

* `email`
* `role`
* `status`
* `owner_id`
* `client_id`
* `reservation_date`

These indexes improve **query performance and dashboard filtering operations**.

---

### Foreign Key Relationships

Relational integrity is enforced using **FOREIGN KEY constraints**:

* `vehicles.owner_id → users.id`
* `reservations.client_id → users.id`
* `reservations.vehicle_id → vehicles.id`
* `earnings.reservation_id → reservations.id`

Deletion strategies:

* `ON DELETE CASCADE`
* `ON DELETE SET NULL`

This ensures consistent relational behavior.

---

### Seed Data

Example dataset added for development and demonstration:

* 1 Administrator
* 2 Clients
* 2 Transporters
* 2 Vehicles
* 2 Reservations
* 1 Completed reservation with earnings

Seed passwords were generated using secure hashing.

---

### Secure Database Connection

Implemented PDO-based database connection in:

`config/database.php`

Security configurations include:

* `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`
* `PDO::ATTR_EMULATE_PREPARES => false`

This ensures protection against **SQL injection attacks**.

---

## QA Improvements Applied

During QA evaluation the following improvements were implemented:

* Removed redundant indexes where **UNIQUE constraints already provided indexing**
* Added **UNIQUE(reservation_id)** in `earnings` to prevent duplicate payout entries
* Standardized all foreign key types using **UNSIGNED INT**
* Enabled database charset **utf8mb4_unicode_ci** for multilingual compatibility

---

# Phase 1 Architecture Improvements

### Project Structure Refactor

Introduced a `functions/` directory to isolate backend logic.

Examples:

* `functions/auth.php`
* `functions/reservation.php`
* `functions/vehicle.php`

This prevents mixing PHP business logic with HTML templates.

---

### Database Enhancements

Additional schema improvements were implemented:

**Users Table**

* Added `last_login` field for analytics and security tracking

**Vehicles Table**

* Added `updated_at` timestamp for administrative verification

**Reservations Table**

* Added `price DECIMAL(10,2)` to represent service cost

---

### Notifications System

A new table **notifications** was introduced to support system alerts.

Structure:

* `id`
* `user_id`
* `message`
* `status`
* `created_at`

Example use cases:

* Transporter accepted request
* Reservation completed

---

### Index Optimization

A composite index was added:

`idx_reservation_status_date (status, reservation_date)`

This improves performance for **admin dashboard queries filtering by reservation status# Phase 2 & 3 — Modernized Logistics Ecosystem

## UI/UX & Design System
- Implemented **Glassmorphism 2.0** (Blur, Translucency, Subtle Borders).
- 5-tier Responsive Breakpoints: XS (Bottom Bar), S (Drawer), MD (Mini-Sidebar), LG/XL (Full).
- Role-based Color Palettes (Client: Indigo, Transporter: Emerald, Admin: Amber).
- Motion System: Staggered entry animations and spring-physics Toasts.

## Functional & Technical Stack
- **RBAC Middleware:** Strict gatekeeping for protected routes based on session roles.
- **Smart Notifications:** Real-time UI updates for unread counts and toast alerts.
- **Modular Logic:** Decoupled `functions/` for Auth, Reservations, Vehicles, and Alerts.

## Security & Reliability
- **Data Integrity:** 100% Prepared Statements (PDO).
- **Session Hardening:** ID regeneration and anti-fixation protocols.
- **XSS & CSRF:** Output escaping and token validation on all POST requests.
- **Cache Policy:** Hard-disabled back-button access post-logout.
- **Core Dashboard Layouts**: Initiated raw architectural routing arrays securely bridging Client, Transporter, and Admin domains relying strictly upon core UI Glassmorphism constraints using responsive grids and dynamic hover cards.

### Phase 3 — Client Module Details & UI Fixes
- **Dashboard Interface (`client/dashboard.php`)**: Dynamically aggregates the logged-in client reservation summary using PDO queries and displays current reservation statuses.
- **Transport Registration Form (`client/request_transport.php`)**: Builds the client request form with `pickup_location`, `destination`, `cargo_type`, `weight`, `volume`, `reservation_date`, and `service_type`. Uses CSRF tokens, session `$_SESSION['user_id']`, and `createReservation()` to save data with status `pending` into the database.
- **Reservation Historical Trace (`client/reservations.php`)**: Displays the client's reservation history with status counts and related transporter/vehicle details by joining `reservations`, `vehicles`, and `users`.
- **Detailed Audit View (`client/reservation_details.php`)**: Shows a single reservation record with transporter name, contact details, vehicle information, and the current lifecycle status.
- **Current focus**: Finalize client module logic so the client can submit transport requests, review pending/accepted/in_progress/completed reservations, and access reservation details.

## Mobile Architectures (Fixes)
- **Drawer Sidebar Navigation:** For sub-992px contexts, the sidebar hides off-screen (`translateX(-100%)`) and acts as an actionable modal toggled by a `Z-Index: 1000` button deployed centrally via the top navigation ribbon. 

# API & Internal Tooling
## Notification & Engagement API (`api/notifications.php`)
- **Status Hook Check**: Client-invoked ping `fetch` mapping unread alerts via database scalar queries (`COUNT()`) to power glowing red Topbar UI components.
- **Infinite Scroll Engine**: Exposes a paginated `&page=X` endpoint outputting serialized JSON payload blocks. The Client JS handles `scrollHeight` intersects querying dynamically up to the `hasMoreNotifs` boundary.
- **CSRF Token Verifier**: Implements critical `hash_equals` checks mapping `document.meta` securely bound arrays. Unverified state changes (Ex: `mark_read`) are aggressively killed with 403 bounds.

## CSS Global Overhaul
- **Layout Matrix**: Deployed a dual-grid viewport lock. The Sidebar anchors at `left: 0`, expanding `100vh` independently, while the Topbar clamps at `top: 0` alongside the active workspace enforcing a strict `padding: 110px 40px` wrapper. 
- **Loading State Mutators**: Engineered absolute Javascript hooks inside `script.js`. Invoking the `.loading` class recursively disables all targeted form inputs, nullifies active button opacity (`opacity: 0 !important`), and overlays an infinitely rotating CSS spinner `border-radius: 50%` directly into the DOM tree ensuring users cannot tamper with logic bounds while a POST request is evaluating. 
- **Typography Colors**: Swept `text-muted` variants (#64748b) updating strictly to `#94a3b8` granting high contrast compliance mapped against the deep `rgba(15, 23, 42)` glass components. 

### Login Page

`index.php`

Features:

* Modern **glassmorphism-based UI**
* Responsive form design
* Secure login request handling

---

### Registration Page

`register.php`

Features:

* Secure user registration
* Role selection (client / transporter)
* Input validation
* Clear user feedback

---

### Logout System

`logout.php`

Safely destroys session data and redirects users to the login page.

---

### Authentication Logic

Implemented in:

`functions/auth.php`

Responsibilities:

* user registration
* login verification
* password hashing
* session management

Security methods used:

* `password_hash()`
* `password_verify()`
* PDO prepared statements

---

### Role-Based Access Control

Session variables:

```php
$_SESSION['user_id']
$_SESSION['role']
```

Redirection logic:

* Client → `/client/dashboard.php`
* Transporter → `/transporter/dashboard.php`
* Admin → `/admin/dashboard.php`

---

### Login Tracking

The system updates `last_login` on each successful authentication.

This enables:

* security monitoring
* admin analytics

---

## QA Improvements Applied

During QA review:

* Verified secure PDO connection and database insertion
* Confirmed password hashing and verification flow
* Validated session management and role-based redirection
* Implemented modern UI improvements including:

  * gradient glassmorphism design
  * animated input fields
  * hover feedback
  * responsive layout
  * contextual form alerts

---

# Architecture Outcome

The current system architecture provides:

* secure authentication flow
* modular backend structure
* optimized database queries
* scalable folder organization
* maintainable codebase

This foundation prepares the project for **Phase 3 — Client Reservation Module Implementation**.
