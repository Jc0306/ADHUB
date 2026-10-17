import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext.jsx';

/** Shared chrome for the admin and client dashboards. */
export default function DashboardShell({ subtitle, children }) {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  async function handleLogout() {
    await logout();
    navigate('/login', { replace: true });
  }

  return (
    <>
      <nav className="adhub-navbar d-flex align-items-center justify-content-between px-4 py-3 sticky-top">
        <div className="d-flex align-items-center gap-3">
          <span className="fw-bold fs-4">ADHUB</span>
          <span className="badge text-bg-secondary text-uppercase">{user?.role}</span>
        </div>

        <div className="d-flex align-items-center gap-3">
          <span className="text-muted-light">
            {user?.full_name}
            {user?.company_name ? ` — ${user.company_name}` : ''}
          </span>
          <button type="button" className="btn btn-adhub btn-sm" onClick={handleLogout}>
            Logout
          </button>
        </div>
      </nav>

      <main className="container-fluid px-4 py-4">
        <h1 className="h3 fw-bold mb-4">{subtitle}</h1>
        {children}
      </main>
    </>
  );
}
