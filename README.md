# AdHub — Agency-Client Marketing Campaign Manager

Decoupled app: React (Vite) frontend + PHP REST API + MySQL, served locally with XAMPP.

## Structure

```
api/        PHP REST API — the only layer that talks to MySQL (database: adhubdb)
frontend/   React SPA (Vite + Bootstrap 5 + Recharts)
legacy/     Original PHP pages, kept for reference (blocked from web access)
uploads/    Uploaded deliverable files (git-ignored)
DATABASE/   adhubdb.sql (schema + admin), seed-demo.* (optional demo data)
```

Apache rules in `.htaccess` block direct access to `uploads/`, `DATABASE/`,
`legacy/` and `.git/`; deliverables are served only through the API.

## Features

- **Multi-role authentication** — agency (admin) and client views, role-guarded routes and endpoints
- **Campaign asset management** — projects per client (created by accepting a request), versioned deliverable uploads
- **Milestone approval workflow** — clients Approve / Request Revision; project stages move automatically; Kanban board (Bootstrap grid, desktop)
- **Client service requests** — clients ask for new work; the agency replies, and **accepting a request opens its campaign project (In Progress)** from the request's title and message — budget, details and deliverables are handled inside the project. Projects start only from a client request (navbar shows the open count)
- **Chat** — every campaign project has its own thread between the agency and that project's client (also embedded on the project page); no per-client general chat — conversations stay tied to the work; unread badges in the navbar and per thread, messages poll in every ~3 seconds while a chat is open
- **Automated cost tracking** — client retainers (top-ups), logged hours x rate; balance is computed so deductions are always in sync
- **Printable campaign reports** — milestones + remaining budget, via the Print Report button on any project
- **Analytics** — project completion rate and budget utilization charts (admin)

## Run locally

1. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Database (first time only): create a database named `adhubdb` and import
   `DATABASE\adhubdb.sql` into it — via phpMyAdmin or from the CLI:
   ```
   C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS adhubdb"
   Get-Content DATABASE\adhubdb.sql -Raw | C:\xampp\mysql\bin\mysql.exe --default-character-set=utf8mb4 -u root adhubdb
   ```
3. Frontend (dev):
   ```
   cd frontend
   npm install
   npm run dev
   ```
4. Open http://localhost:5173 — requests to `/api` are proxied to
   `http://localhost/Adhub/api` (see `frontend/vite.config.js`).

## Demo data

```
powershell -ExecutionPolicy Bypass -File DATABASE\seed-demo.ps1
```

Requires the step-2 schema import. Imports three demo clients —
`bloom@gmail.com`, `urbanfit@gmail.com`, `brewbean@gmail.com` (password:
`demo1234`) — with projects across every stage, deliverables, retainers and
time logs. Only touches the demo accounts, so it is safe to re-run anytime
(other data is untouched).

Admin account (seeded): `admin@gmail.com` / `ADMIN` — the API upgrades
legacy plaintext passwords to bcrypt hashes on first login.
