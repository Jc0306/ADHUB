import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import DashboardShell from '../components/DashboardShell.jsx';
import DeliverablesTable from '../components/DeliverablesTable.jsx';
import StatusBadge from '../components/StatusBadge.jsx';
import ChatThread from '../components/ChatThread.jsx';
import { apiGet, apiPost } from '../api/client.js';
import { showToast } from '../toast.js';
import { formatDate, formatMoney } from '../constants.js';

export default function ClientProjectDetail() {
  const { id } = useParams();
  const [data, setData] = useState(null);
  const [error, setError] = useState('');

  useEffect(() => {
    apiGet(`/projects/show.php?id=${id}`)
      .then(setData)
      .catch((e) => setError(e.message));
  }, [id]);

  async function handleReview(deliverable, action, note) {
    try {
      const result = await apiPost('/projects/deliverable-review.php', {
        id: deliverable.id,
        action,
        note,
      });
      setData((prev) => ({
        project: result.project,
        deliverables: prev.deliverables.map((d) =>
          d.id === deliverable.id ? result.deliverable : d
        ),
      }));
      setError('');
      showToast(action === 'approve' ? 'Deliverable approved.' : 'Revision requested.');
    } catch (err) {
      setError(err.message);
      throw err;
    }
  }

  if (!data) {
    return (
      <DashboardShell subtitle="Project">
        <p className="text-muted-light">{error || 'Loading…'}</p>
      </DashboardShell>
    );
  }

  const { project, deliverables } = data;

  return (
    <DashboardShell subtitle={project.title}>
      <div className="d-flex justify-content-between align-items-center mb-3">
        <Link to="/client" className="text-muted-light small">← Back to dashboard</Link>
        <Link to={`/reports/project/${id}`} className="btn btn-outline-light btn-sm">Print Report</Link>
      </div>

      {error && <div className="alert alert-danger py-2">{error}</div>}

      <div className="row g-4">
        <div className="col-lg-4">
          <div className="adhub-card p-4">
            <div className="d-flex justify-content-between align-items-center mb-4">
              <span className="text-muted-light">Status</span>
              <StatusBadge status={project.status} />
            </div>

            <div className="d-flex justify-content-between mb-2">
              <span className="text-muted-light">Service</span>
              <span>{project.service_type}</span>
            </div>
            <div className="d-flex justify-content-between mb-2">
              <span className="text-muted-light">Budget</span>
              <span>{formatMoney(project.budget)}</span>
            </div>
            <div className="d-flex justify-content-between mb-2">
              <span className="text-muted-light">Billed to Date</span>
              <span>{formatMoney(data.cost)}</span>
            </div>
            <div className="d-flex justify-content-between mb-3">
              <span className="text-muted-light">Created</span>
              <span>{formatDate(project.created_at)}</span>
            </div>

            <div className="d-flex justify-content-between mb-1">
              <span className="text-muted-light">Progress</span>
              <span>{project.progress_percent}%</span>
            </div>
            <div className="progress" style={{ height: 8 }}>
              <div className="progress-bar" style={{ width: `${project.progress_percent}%` }} />
            </div>

            {project.description && (
              <p className="text-muted-light mt-4 mb-0">{project.description}</p>
            )}
          </div>
        </div>

        <div className="col-lg-8">
          <div className="adhub-card p-4">
            <div className="d-flex justify-content-between align-items-center mb-3">
              <h2 className="h5 mb-0">Deliverables</h2>
              <span className="small text-muted-light">Review new assets: Approve or Request Revision</span>
            </div>

            <DeliverablesTable
              deliverables={deliverables}
              onApprove={(d) => handleReview(d, 'approve', '')}
              onRevision={(d, note) => handleReview(d, 'revision', note)}
            />
          </div>

          <div className="adhub-card p-4 mt-4">
            <h2 className="h5 mb-3">Project Chat</h2>
            <ChatThread key={project.id} projectId={project.id} />
          </div>
        </div>
      </div>
    </DashboardShell>
  );
}
