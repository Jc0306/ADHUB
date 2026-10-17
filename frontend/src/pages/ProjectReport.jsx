import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { useAuth } from '../context/AuthContext.jsx';
import { apiGet } from '../api/client.js';
import ApprovalBadge from '../components/ApprovalBadge.jsx';
import StatusBadge from '../components/StatusBadge.jsx';
import { formatDate, formatMoney } from '../constants.js';

/** Printable campaign report — milestones, budget summary, logged hours. */
export default function ProjectReport() {
  const { id } = useParams();
  const { user } = useAuth();
  const [data, setData] = useState(null);
  const [summary, setSummary] = useState(null);
  const [generatedAt, setGeneratedAt] = useState('');
  const [error, setError] = useState('');

  useEffect(() => {
    apiGet(`/projects/show.php?id=${id}`)
      .then((d) => {
        setData(d);
        setGeneratedAt(new Date().toLocaleString());
        return apiGet(`/costs/summary.php?client_id=${d.project.client_id}`);
      })
      .then(setSummary)
      .catch((e) => setError(e.message));
  }, [id]);

  if (!data) {
    return (
      <div className="min-vh-100 d-flex align-items-center justify-content-center text-muted-light">
        {error || 'Loading…'}
      </div>
    );
  }

  const { project, deliverables, cost, logs } = data;
  const backTo = user?.role === 'admin' ? `/admin/projects/${id}` : `/client/projects/${id}`;

  const latestVersion = {};
  deliverables.forEach((d) => {
    latestVersion[d.display_name] = Math.max(latestVersion[d.display_name] ?? 0, d.version);
  });
  const milestones = deliverables.filter((d) => latestVersion[d.display_name] === d.version);
  const approvedCount = milestones.filter((d) => d.approval_status === 'approved').length;

  const remainingBudget = Number(project.budget) - Number(cost);

  return (
    <div className="py-4 px-3">
      <div className="no-print d-flex justify-content-center gap-2 mb-3">
        <Link to={backTo} className="btn btn-outline-light btn-sm">← Back</Link>
        <button type="button" className="btn btn-adhub btn-sm" onClick={() => window.print()}>
          Print Report
        </button>
      </div>

      <div className="report-sheet">
        <div className="d-flex justify-content-between align-items-start mb-4">
          <div>
            <h1 className="h3 fw-bold mb-1">ADHUB</h1>
            <div className="text-muted">Campaign Report</div>
          </div>
          <div className="text-end small text-muted">
            <div>Generated: {generatedAt}</div>
            <div>Project #ADH-{String(project.id).padStart(4, '0')}</div>
          </div>
        </div>

        <table className="report-table mb-4">
          <tbody>
            <tr>
              <th>Project</th>
              <td>{project.title}</td>
              <th>Client</th>
              <td>{project.client_company ? `${project.client_company} — ` : ''}{project.client_name}</td>
            </tr>
            <tr>
              <th>Service</th>
              <td>{project.service_type || '—'}</td>
              <th>Status</th>
              <td><StatusBadge status={project.status} /></td>
            </tr>
            <tr>
              <th>Created</th>
              <td>{formatDate(project.created_at)}</td>
              <th>Progress</th>
              <td>{project.progress_percent}%</td>
            </tr>
          </tbody>
        </table>

        <h2 className="h5 mb-2">Milestones — {approvedCount} of {milestones.length} approved</h2>
        <table className="report-table mb-4">
          <thead>
            <tr>
              <th>Deliverable</th>
              <th>Version</th>
              <th>Review Status</th>
              <th>Uploaded</th>
            </tr>
          </thead>
          <tbody>
            {milestones.map((d) => (
              <tr key={d.id}>
                <td>{d.display_name}</td>
                <td>v{d.version}</td>
                <td><ApprovalBadge status={d.approval_status} /></td>
                <td>{formatDate(d.uploaded_at)}</td>
              </tr>
            ))}
            {milestones.length === 0 && (
              <tr><td colSpan={4}>No deliverables yet.</td></tr>
            )}
          </tbody>
        </table>

        <h2 className="h5 mb-2">Budget Summary</h2>
        <table className="report-table mb-4">
          <tbody>
            <tr>
              <th>Campaign Budget</th>
              <td className="text-end">{formatMoney(project.budget)}</td>
            </tr>
            <tr>
              <th>Billed to Date (logged hours)</th>
              <td className="text-end">{formatMoney(cost)}</td>
            </tr>
            <tr className="fw-bold">
              <th>Remaining Budget</th>
              <td className="text-end">{formatMoney(remainingBudget)}</td>
            </tr>
            {summary && (
              <tr>
                <th>Client Retainer Balance</th>
                <td className="text-end">{formatMoney(summary.balance)}</td>
              </tr>
            )}
          </tbody>
        </table>

        {logs.length > 0 && (
          <>
            <h2 className="h5 mb-2">Logged Hours</h2>
            <table className="report-table mb-4">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Hours</th>
                  <th>Rate</th>
                  <th className="text-end">Cost</th>
                  <th>Note</th>
                </tr>
              </thead>
              <tbody>
                {logs.map((l) => (
                  <tr key={l.id}>
                    <td>{formatDate(l.logged_at)}</td>
                    <td>{Number(l.hours)}h</td>
                    <td>{formatMoney(l.hourly_rate)}</td>
                    <td className="text-end">{formatMoney(Number(l.hours) * Number(l.hourly_rate))}</td>
                    <td>{l.note || '—'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </>
        )}

        <div className="small text-muted mt-4">
          Generated by AdHub — Agency-Client Campaign Manager
        </div>
      </div>
    </div>
  );
}
