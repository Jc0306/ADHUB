import { useEffect, useState } from 'react';
import { NavLink, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext.jsx';
import { apiGet } from '../api/client.js';

const ADMIN_LINKS = [
  { to: '/admin', label: 'Dashboard', end: true },
  { to: '/admin/requests', label: 'Requests', badge: 'openRequests' },
  { to: '/admin/chat', label: 'Chat', badge: 'unreadChats' },
  { to: '/admin/costs', label: 'Cost Tracking' },
  { to: '/admin/analytics', label: 'Analytics' },
];

const CLIENT_LINKS = [
  { to: '/client', label: 'Dashboard', end: true },
  { to: '/client/requests', label: 'Service Requests' },
  { to: '/client/chat', label: 'Chat', badge: 'unreadChats' },
];

/** Shared chrome (navbar + page title) for the admin and client areas. */
export default function DashboardShell({ subtitle, children }) {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [openRequests, setOpenRequests] = useState(0);
  const [unreadChats, setUnreadChats] = useState(0);

  const links = user?.role === 'admin' ? ADMIN_LINKS : CLIENT_LINKS;
  const badges = { openRequests, unreadChats };

  // Navbar counters: open service requests (admins) + unread chat messages (both).
  useEffect(() => {
    if (!user) {
      return;
    }

    if (user.role === 'admin') {
      apiGet('/requests/list.php')
        .then((d) => setOpenRequests(d.requests.filter((r) => r.status === 'open').length))
        .catch(() => {});
    }

    apiGet('/chat/unread.php')
      .then((d) => setUnreadChats(d.unread))
      .catch(() => {});
  }, [user]);

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

        <div className="d-flex align-items-center gap-4">
          {links.map((link) => (
            <NavLink
              key={link.to}
              to={link.to}
              end={link.end}
              className={({ isActive }) => `adhub-nav-link${isActive ? ' active' : ''}`}
            >
              {link.label}
              {link.badge && badges[link.badge] > 0 && (
                <span className="badge text-bg-warning ms-1">{badges[link.badge]}</span>
              )}
            </NavLink>
          ))}
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
