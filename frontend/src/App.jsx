import { Navigate, Route, Routes } from 'react-router-dom';
import { useAuth } from './context/AuthContext.jsx';
import { Suspense, lazy } from 'react';
import ErrorBoundary from './components/ErrorBoundary.jsx';
import ToastHost from './components/ToastHost.jsx';

const Login = lazy(() => import('./pages/Login.jsx'));
const Register = lazy(() => import('./pages/Register.jsx'));
const AdminDashboard = lazy(() => import('./pages/AdminDashboard.jsx'));
const ClientDashboard = lazy(() => import('./pages/ClientDashboard.jsx'));
const AdminProjectDetail = lazy(() => import('./pages/AdminProjectDetail.jsx'));
const ClientProjectDetail = lazy(() => import('./pages/ClientProjectDetail.jsx'));
const CostsPage = lazy(() => import('./pages/CostsPage.jsx'));
const ProjectReport = lazy(() => import('./pages/ProjectReport.jsx'));
const AnalyticsPage = lazy(() => import('./pages/AnalyticsPage.jsx'));
const RequestsPage = lazy(() => import('./pages/RequestsPage.jsx'));
const AdminRequestsPage = lazy(() => import('./pages/AdminRequestsPage.jsx'));
const ChatPage = lazy(() => import('./pages/ChatPage.jsx'));

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

function Loading() {
  return (
    <div className="min-vh-100 d-flex align-items-center justify-content-center text-muted-light">
      Loading…
    </div>
  );
}

export default function App() {
  return (
    <>
      <ToastHost />
      <ErrorBoundary>
      <Suspense fallback={<Loading />}>
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
        path="/admin/requests"
        element={
          <RoleRoute role="admin">
            <AdminRequestsPage />
          </RoleRoute>
        }
      />
      <Route
        path="/admin/chat"
        element={
          <RoleRoute role="admin">
            <ChatPage />
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
      <Route
        path="/client/requests"
        element={
          <RoleRoute role="client">
            <RequestsPage />
          </RoleRoute>
        }
      />
      <Route
        path="/client/chat"
        element={
          <RoleRoute role="client">
            <ChatPage />
          </RoleRoute>
        }
      />
      <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
      </Suspense>
      </ErrorBoundary>
    </>
  );
}
