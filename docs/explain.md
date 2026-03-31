# Project Architecture and Request Flow

## High-Level Architecture

This application is built as a single PHP project that supports three user modules: Client, Transporter, and Admin. Each module has its own folder (`client/`, `transporter/`, `admin/`) and its own dashboard and pages. This separation is intentional:

- `client/` contains pages where clients create transport requests and view their reservations.
- `transporter/` contains pages for transporters to manage assigned vehicles and respond to reservation work.
- `admin/` contains pages for administrators to manage users, reservations, and system data.

Although the user interfaces are separated by folders and role checks, they all share the same MySQL database. That means the modules are isolated at the presentation and access-control level, while still using a single source of truth for data.

Why this matters: this keeps role-specific logic separate and easier to maintain, while avoiding duplicate data. The same reservation or user record can be accessed from different modules when the role and permissions allow it.

## The PHP Request-Response Cycle

### How a client request works

When a user loads `client/request_transport.php`, the browser sends a GET request and the server responds with HTML form markup. When the client submits the form, the browser sends a POST request back to the same page.

Inside `request_transport.php`:
- `require_once __DIR__ . '/../core/paths.php'` loads path constants for includes and URLs.
- `require_once INC_PATH . 'auth_check.php'` starts the session and validates the user session.
- `require_once INC_PATH . 'role_gate.php'` enforces that only a client can access this page.
- `require_once CONF_PATH . 'database.php'` gives access to the PDO database connection.
- `require_once FUNC_PATH . 'reservation.php'` loads reservation helper functions.

When the page receives POST data:
- it validates the CSRF token,
- sanitizes and trims input fields,
- opens a database connection through `Database::getConnection()`,
- then calls `createReservation(...)` with `$_SESSION['user_id']`.

### Session and `user_id`

The session is the mechanism that keeps the logged-in user context between HTTP requests. The authentication system stores the authenticated user ID in `$_SESSION['user_id']` and the role in `$_SESSION['role']`.

`includes/auth_check.php` ensures that:
- a session exists,
- the user is still logged in,
- the account is not suspended or pending.

This is why later code can safely use `$_SESSION['user_id']` when inserting a reservation: the middleware has already confirmed the user is authenticated.

## Database Integration

The database schema is defined in `core/config/database.sql`. The main tables involved in the client transport flow are:

- `users` stores every application user, including clients, transporters, and admins.
- `vehicles` stores transporter vehicles and links each vehicle to a transporter through `owner_id`.
- `reservations` stores client transport requests and links each reservation to a client and, optionally, to a vehicle.

### How `reservations` connects clients and transporters

In `reservations`:
- `client_id` is a foreign key to `users.id`.
- `vehicle_id` is a foreign key to `vehicles.id`.

Because `vehicles.owner_id` itself points to a `users.id` for the transporter, a reservation becomes a bridge between three entities:

- client user → `reservations.client_id`
- reservation → `vehicles.vehicle_id`
- transporter user → `vehicles.owner_id`

That relationship model allows the system to answer questions such as:
- which client created the reservation,
- which vehicle was assigned,
- who owns that vehicle.

The database design is why the backend can build queries like the ones in `getClientReservations()` and fetch transporter details using joins.

## File Organization and Reuse

The project uses a common pattern to organize code and improve maintainability:

- `includes/` contains shared page fragments and middleware like `auth_check.php`, `header.php`, `sidebar.php`, and `topbar.php`.
- `core/config/` contains configuration files, especially `database.php` and `database.sql`.
- `core/functions/` contains reusable logic, such as `reservation.php`, `auth.php`, and `vehicle.php`.
- `client/`, `transporter/`, `admin/` contain role-specific pages.

### Why this folder structure?

- `includes/` keeps shared layout and security checks in one place.
- `core/config/` keeps environment-specific settings separate from page logic.
- `core/functions/` centralizes reusable business logic, so pages do not repeat database queries or validation code.
- Role folders keep user-specific behavior isolated.

### `header.php` reuse

`includes/header.php` is a shared header template used by public authentication pages such as `auth/login.php`, `auth/register.php`, `auth/forgot-password.php`, and `auth/reset-password.php`.

Why reuse it? Because it avoids duplicating HTML head tags, CSS links, and other common page head elements. If you need to change the public page title, global styles, or metadata, you can update one file instead of every auth page.

## Logic Flow: Reservation Status Lifecycle

The reservation `status` field in `reservations` defines the lifecycle of a transport request. The main values are:

- `pending` — the client created the request and it is waiting for transporter action.
- `accepted` — a transporter accepted the job or the system assigned it.
- `in_progress` — the transporter is actively handling the transport.
- `completed` — the shipment is finished.

This status flow is meaningful because it maps business state to data state. It allows the application to:

- filter reservations by where they are in the process,
- show clients which jobs are still waiting or already finished,
- let transporters know what they need to work on next.

The code uses this field in queries and summary functions, so each module can display the reservation state without hardcoding logic in the page itself.

## How PHP Talks to MySQL

The backend uses `core/config/database.php` to create a secure PDO connection:

- `PDO` is configured with `ATTR_ERRMODE => ERRMODE_EXCEPTION`, so database errors are thrown and can be handled.
- `ATTR_DEFAULT_FETCH_MODE => FETCH_ASSOC` returns rows as associative arrays, which is easy to use in templates.
- Prepared statements are used in `core/functions/reservation.php` to safely insert and query data.

Example of the flow:

1. `request_transport.php` receives POST data.
2. It creates `new Database()` and calls `getConnection()`.
3. That returns a PDO object connected to MySQL.
4. `createReservation()` prepares an SQL `INSERT` and executes it with bound values.
5. MySQL creates the reservation row with status `pending`.

Why this is good:
- it separates configuration from business logic,
- it keeps SQL execution in helper functions,
- it reduces the risk of SQL injection,
- and it makes the code easier to maintain.

## Summary

This project is organized so that role modules remain separated by folder, while shared database access and helper logic are centralized. The PHP request flow is a classical form submission pattern: session validation → input processing → database connection → prepared SQL execution → HTML response.

Understanding this structure helps you reason about both the user experience and the backend behavior. The database is the single shared store, and the code is designed so that each module uses its own interface while collaborating through that shared data model.