import { useEffect, useState } from 'react';
import DashboardShell from '../components/DashboardShell.jsx';
import ProjectsTable from '../components/ProjectsTable.jsx';
import { apiGet } from '../api/client.js';
import { formatMoney } from '../constants.js';

export default function ClientDashboard() {
  const [projects, setProjects] = useState([]);
  const [summary, setSummary] = useState(null);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    apiGet('/projects/list.php')
      .then((d) => setProjects(d.projects))
      .catch((e) => setError(e.message))
      .finally(() => setLoading(false));

    apiGet('/costs/summary.php')
      .then(setSummary)
      .catch(() => setSummary(null));
  }, []);

  return (
    <DashboardShell subtitle="Client Dashboard">
      {summary && (
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
        {loading
          ? <p className="text-muted-light mb-0">Loading…</p>
          : <ProjectsTable projects={projects} detailBase="/client/projects" />}
      </div>
    </DashboardShell>
  );
}
