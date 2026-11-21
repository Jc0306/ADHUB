import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import DashboardShell from '../components/DashboardShell.jsx';
import ProjectsTable from '../components/ProjectsTable.jsx';
import KanbanBoard from '../components/KanbanBoard.jsx';
import { apiGet } from '../api/client.js';
import { usePoll } from '../hooks/usePoll.js';

export default function AdminDashboard() {
  const [projects, setProjects] = useState([]);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);
  const [view, setView] = useState('kanban');

  async function load() {
    try {
      const d = await apiGet('/projects/list.php');
      setProjects(d.projects);
      setError('');
    } catch (e) {
      setError(e.message);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => { load(); }, []);
  usePoll(load, 10000);

  return (
    <DashboardShell subtitle="Agency Dashboard">
      <div className="d-flex justify-content-between align-items-center mb-3">
        <div className="d-flex align-items-center gap-3">
          <h2 className="h5 mb-0 text-muted-light">Campaign Projects</h2>
          <div className="btn-group btn-group-sm">
            <button
              type="button"
              className={`btn ${view === 'kanban' ? 'btn-adhub' : 'btn-outline-light'}`}
              onClick={() => setView('kanban')}
            >
              Kanban
            </button>
            <button
              type="button"
              className={`btn ${view === 'table' ? 'btn-adhub' : 'btn-outline-light'}`}
              onClick={() => setView('table')}
            >
              Table
            </button>
          </div>
        </div>

        <Link to="/admin/requests" className="text-muted-light small text-decoration-none">
          Projects are created by accepting client requests — review requests →
        </Link>
      </div>

      {error && <div className="alert alert-danger py-2">{error}</div>}
      {loading && <p className="text-muted-light">Loading…</p>}

      {!loading && view === 'kanban' && (
        <KanbanBoard projects={projects} detailBase="/admin/projects" />
      )}

      {!loading && view === 'table' && (
        <div className="adhub-card p-4">
          <ProjectsTable projects={projects} detailBase="/admin/projects" showClient />
        </div>
      )}
    </DashboardShell>
  );
}
