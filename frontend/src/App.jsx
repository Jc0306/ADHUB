import { Navigate, Route, Routes } from 'react-router-dom';
import { useAuth } from './context/AuthContext.jsx';
import Login from './pages/Login.jsx';
import Register from './pages/Register.jsx';
import AdminDashboard from './pages/AdminDashboard.jsx';
import ClientDashboard from './pages/ClientDashboard.jsx';
import NewProject from './pages/NewProject.jsx';
import AdminProjectDetail from './pages/AdminProjectDetail.jsx';
import ClientProjectDetail from './pages/ClientProjectDetail.jsx';
import CostsPage from './pages/CostsPage.jsx';
import ProjectReport from './pages/ProjectReport.jsx';
import AnalyticsPage from './pages/AnalyticsPage.jsx';

/** Landing route for the user's role. */
function roleHome(user) {
  return user?.role === 'admin' ? '/admin' : '/client';
}

/** Route guard: requires a session, optionally a specific role. */
function RoleRoute({ role, children }) {
  const { user, loading } = useAuth();

  if (loading) {
    return <div className="min-vh-100 d-flex align-items-center justify-content-center text-muted-light">Loading…</div>;
  }
  if (!user) {
    return <Navigate to="/login" replace />;
  }
  if (role && user.role !== role) {
    return <Navigate to={roleHome(user)} replace />;
  }

  return children;
}

function HomeRedirect() {
  const { user, loading } = useAuth();

  if (loading) {
    return null;
  }

  return <Navigate to={user ? roleHome(user) : '/login'} replace />;
}

/** Route guard: requires any signed-in user (any role). */
function AuthRoute({ children }) {
  const { user, loading } = useAuth();

  if (loading) {
    return <div className="min-vh-100 d-flex align-items-center justify-content-center text-muted-light">Loading…</div>;
  }
  if (!user) {
    return <Navigate to="/login" replace />;
  }

  return children;
}

export default function App() {
  return (
    <Routes>
      <Route path="/" element={<HomeRedirect />} />
      <Route path="/login" element={<Login />} />
      <Route path="/register" element={<Register />} />
      <Route
        path="/admin"
        element={
          <RoleRoute role="admin">
            <AdminDashboard />
          </RoleRoute>
        }
      />
      <Route
        path="/admin/projects/new"
        element={
          <RoleRoute role="admin">
            <NewProject />
          </RoleRoute>
        }
      />
      <Route
        path="/admin/projects/:id"
        element={
          <RoleRoute role="admin">
            <AdminProjectDetail />
          </RoleRoute>
        }
      />
      <Route
        path="/admin/costs"
        element={
          <RoleRoute role="admin">
            <CostsPage />
          </RoleRoute>
        }
      />
      <Route
        path="/admin/analytics"
        element={
          <RoleRoute role="admin">
            <AnalyticsPage />
          </RoleRoute>
        }
      />
      <Route
        path="/reports/project/:id"
        element={
          <AuthRoute>
            <ProjectReport />
          </AuthRoute>
        }
      />
      <Route
        path="/client"
        element={
          <RoleRoute role="client">
            <ClientDashboard />
          </RoleRoute>
        }
      />
      <Route
        path="/client/projects/:id"
        element={
          <RoleRoute role="client">
            <ClientProjectDetail />
          </RoleRoute>
        }
      />
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}
