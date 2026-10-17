import { Link } from 'react-router-dom';
import StatusBadge from './StatusBadge.jsx';
import { formatDate, formatMoney } from '../constants.js';

/** Project overview table used by both dashboards. */
export default function ProjectsTable({ projects, detailBase, showClient = false }) {
  if (projects.length === 0) {
    return <p className="text-muted-light mb-0">No projects yet.</p>;
  }

  return (
    <div className="table-responsive">
      <table className="adhub-table">
        <thead>
          <tr>
            <th>Project</th>
            {showClient && <th>Client</th>}
            <th>Service</th>
            <th className="text-end">Budget</th>
            <th className="text-end">Progress</th>
            <th>Status</th>
            <th>Created</th>
            <th className="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          {projects.map((p) => (
            <tr key={p.id}>
              <td className="fw-bold">{p.title}</td>
              {showClient && <td>{p.client_company || p.client_name}</td>}
              <td className="text-muted-light">{p.service_type || '—'}</td>
              <td className="text-end">{formatMoney(p.budget)}</td>
              <td className="text-end">{p.progress_percent}%</td>
              <td><StatusBadge status={p.status} /></td>
              <td className="text-muted-light">{formatDate(p.created_at)}</td>
              <td className="text-end">
                <Link to={`${detailBase}/${p.id}`} className="btn btn-outline-light btn-sm">
                  Open
                </Link>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
