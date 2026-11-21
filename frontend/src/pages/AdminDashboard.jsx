import { useEffect, useState, useCallback } from 'react';
import { Link } from 'react-router-dom';
import DashboardShell from '../components/DashboardShell.jsx';
import ProjectsTable from '../components/ProjectsTable.jsx';
import KanbanBoard from '../components/KanbanBoard.jsx';
import Skeleton from '../components/Skeleton.jsx';
import { apiGet } from '../api/client.js';
import { usePoll } from '../hooks/usePoll.js';

function KanbanSkeleton() {
  return (
    <div className="row g-3 flex-nowrap kanban-row" role="list" aria-label="Project board">
      {[...Array(5)].map((_, i) => (
        <div className="col" key={i} style={{ minWidth: 225 }} role="listitem">
          <div className="d-flex justify-content-between align-items-center mb-2">
            <Skeleton variant="text" style={{ width: '80px' }} />
            <Skeleton variant="circle" style={{ width: 24, height: 24 }} />
          </div>
          <div className="kanban-col">
            {[...Array(3)].map((_, j) => (
              <Skeleton key={j} variant="card" />
            ))}
          </div>
        </div>
      ))}
    </div>
  );
}

function TableSkeleton() {
  return (
    <div className="table-responsive">
      <table className="adhub-table">
        <thead>
          <tr>
            <th>Project</th>
            <th>Client</th>
            <th>Service</th>
            <th className="text-end">Budget</th>
            <th className="text-end">Progress</th>
            <th>Status</th>
            <th>Created</th>
            <th className="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          {[...Array(5)].map((_, i) => (
            <Skeleton key={i} variant="table-row" />
          ))}
        </tbody>
      </table>
    </div>
  );
}

export default function AdminDashboard() {
  const [projects, setProjects] = useState([]);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);
  const [view, setView] = useState('kanban');

  const load = useCallback(async () => {
    try {
      const d = await apiGet('/projects/list.php');
      setProjects(d.projects);
      setError('');
    } catch (e) {
      setError(e.message);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { load(); }, [load]);
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

      {loading ? (
        view === 'kanban' ? <KanbanSkeleton /> : <TableSkeleton />
      ) : (
        <>
          {view === 'kanban' && (
            <KanbanBoard projects={projects} detailBase="/admin/projects" />
          )}
          {view === 'table' && (
            <div className="adhub-card p-4">
              <ProjectsTable projects={projects} detailBase="/admin/projects" showClient />
            </div>
          )}
        </>
      )}
    </DashboardShell>
  );
}
