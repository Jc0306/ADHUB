import { useEffect, useState, useCallback } from 'react';
import DashboardShell from '../components/DashboardShell.jsx';
import ProjectsTable from '../components/ProjectsTable.jsx';
import Skeleton from '../components/Skeleton.jsx';
import { apiGet } from '../api/client.js';
import { formatMoney } from '../constants.js';
import { usePoll } from '../hooks/usePoll.js';

function SummarySkeleton() {
  return (
    <div className="row g-3 mb-3">
      {[...Array(3)].map((_, i) => (
        <div key={i} className="col-md-4">
          <Skeleton variant="card" />
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

export default function ClientDashboard() {
  const [projects, setProjects] = useState([]);
  const [summary, setSummary] = useState(null);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    try {
      const [p, s] = await Promise.all([
        apiGet('/projects/list.php'),
        apiGet('/costs/summary.php').catch(() => null),
      ]);
      setProjects(p.projects);
      setSummary(s);
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
    <DashboardShell subtitle="Client Dashboard">
      {loading ? (
        <SummarySkeleton />
      ) : summary && (
        <div className="row g-3 mb-3">
          <div className="col-md-4">
            <div className="adhub-card p-3">
              <div className="small text-muted-light">Retainer Balance</div>
              <div className={`fs-4 fw-bold ${Number(summary.balance) < 0 ? 'text-danger' : 'text-success'}`}>
                {formatMoney(summary.balance)}
              </div>
            </div>
          </div>
          <div className="col-md-4">
            <div className="adhub-card p-3">
              <div className="small text-muted-light">Retained</div>
              <div className="fs-4 fw-bold">{formatMoney(summary.retained)}</div>
            </div>
          </div>
          <div className="col-md-4">
            <div className="adhub-card p-3">
              <div className="small text-muted-light">Consumed</div>
              <div className="fs-4 fw-bold">{formatMoney(summary.consumed)}</div>
            </div>
          </div>
        </div>
      )}

      <div className="d-flex justify-content-between align-items-center mb-3">
        <h2 className="h5 mb-0 text-muted-light">Your Campaigns</h2>
      </div>

      <div className="adhub-card p-4">
        {error && <div className="alert alert-danger py-2">{error}</div>}
        {loading ? <TableSkeleton /> : <ProjectsTable projects={projects} detailBase="/client/projects" />}
      </div>
    </DashboardShell>
  );
}
