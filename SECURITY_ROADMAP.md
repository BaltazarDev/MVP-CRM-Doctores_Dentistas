# Security Audit & Implementation Roadmap

**Date:** 2026-01-26
**Status:** MVP/Demo Version (Unsecured)
**Target:** Formal Development Phase

## 1. Current Security Status (Audit Findings)

### ✅ SQL Injection
- **Status:** **Secure**.
- **Details:** The application uses parameterized queries (`?` placeholders) in `mysql2` throughout `db.js` and `server.js`. This effectively prevents SQL injection attacks.

### ⚠️ CSRF (Cross-Site Request Forgery)
- **Status:** **Vulnerable** (Low Risk in MVP).
- **Details:** Currently no specific protection. Since there is no login session, this is moot. However, for the future version, using JWT (JSON Web Tokens) stored in `localStorage` or `httpOnly` cookies will be necessary.

### ❌ Authentication & Authorization
- **Status:** **Missing**.
- **Details:** The system currently has no login. All endpoints are public. Anyone with network access can view patient data, add/delete records, and modify settings.
- **Requirement:** Need to implement a login system and Role-Based Access Control (RBAC).

### ❌ XSS (Cross-Site Scripting)
- **Status:** **Vulnerable**.
- **Details:** The frontend (`index.html`) uses `innerHTML` to render user-generated content (e.g., patient names, notes) in several places.
- **Risk:** malicious scripts could be injected into the database and executed in other users' browsers.

---

## 2. Implementation Plan (Future Dev)

### Goal
Implement secure authentication, roles (Admin, Doctor, Receptionist), and harden the application.

### Phase 1: Backend Security (`/backend`)

#### 1. Database Schema
Create a `users` table to store credentials.
```sql
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'doctor', 'receptionist') DEFAULT 'receptionist',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### 2. Dependencies
Install necessary packages:
`npm install jsonwebtoken bcrypt`

#### 3. Middleware (`server.js`)
- **`authenticateToken`**: Verify JWT on every protected route.
- **`authorizeRole(roles)`**: Check if the authenticated user has the required permission.

#### 4. Endpoints
- `POST /api/auth/login`: Validate credentials and return JWT.
- `POST /api/auth/register`: (Admin only) Create new users.

### Phase 2: Frontend Security (`/frontend`)

#### 1. Login Interface
- Create a Login Modal/Page that intercepts the main view if no token is present.
- Store JWT in `localStorage`.

#### 2. Secure Fetch Wrapper
- Replace all `fetch()` calls with a wrapper function that:
    - Adds `Authorization: Bearer <token>` header.
    - Handles `401 Unauthorized` errors by redirecting to Login.

#### 3. Fix XSS
- **Crucial:** Stop using `innerHTML` for dynamic data.
- **Fix:** Use `textContent` or `innerText` for text content.
    *   *Bad:* `td.innerHTML = patient.name`
    *   *Good:* `td.textContent = patient.name`

### Phase 3: Role Management
- **Admin:** Full access (Settings, Delete actions).
- **Doctor:** Clinical access (Patients, Appointments, Files).
- **Receptionist:** Basic access (Calendar, View Patients).

---

## 3. Recommended Technology Stack for Formal Version
If migrating to a formal repo, consider:
- **Frontend:** React, Vue, or Angular for better state management and automatic XSS escaping.
- **Backend:** NestJS or keep Express but with a dedicated Check/Guard structure or Passport.js.
- **Database:** Keep MySQL or migrate to PostgreSQL.
