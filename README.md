# AdHub — Agency-Client Marketing Campaign Manager

Decoupled app: React (Vite) frontend + PHP REST API + MySQL, served locally with XAMPP.

## Structure

```
api/        PHP REST API — the only layer that talks to MySQL (database: adhubdb)
frontend/   React SPA (Vite + Bootstrap 5 + Recharts)
legacy/     Original PHP pages, kept for reference
uploads/    Uploaded deliverable files (git-ignored)
DATABASE/   SQL dump (adhubdb.sql)
```

## Features

- **Multi-role authentication** — agency (admin) and client views, role-guarded routes and endpoints
- **Campaign asset management** — projects per client (CRUD), versioned deliverable uploads
- **Milestone approval workflow** — clients Approve / Request Revision; project stages move automatically; Kanban board (Bootstrap grid, desktop)
- **Automated cost tracking** — client retainers (top-ups), logged hours x rate; balance is computed so deductions are always in sync
- **Printable campaign reports** — milestones + remaining budget, via the Print Report button on any project
- **Analytics** — project completion rate and budget utilization charts (admin)

## Run locally

1. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Frontend (dev):
   ```
   cd frontend
   npm install
   npm run dev
   ```
3. Open http://localhost:5173 — requests to `/api` are proxied to
   `http://localhost/Adhub/api` (see `frontend/vite.config.js`).

Admin account (seeded): `admin@gmail.com` / `ADMIN` — the API upgrades
legacy plaintext passwords to bcrypt hashes on first login.
