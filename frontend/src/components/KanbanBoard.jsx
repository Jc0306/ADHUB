import { Link } from 'react-router-dom';
import { STATUS_LABELS, formatMoney } from '../constants.js';

const COLUMNS = ['pending', 'in-progress', 'revision', 'completed', 'rejected'];

const ACCENT = {
  pending: 'secondary',
  'in-progress': 'primary',
  revision: 'warning',
  completed: 'success',
  rejected: 'danger',
};

/** Kanban board — columns per project stage, built on the Bootstrap grid. */
export default function KanbanBoard({ projects, detailBase }) {
  return (
    <div className="row g-3 flex-nowrap kanban-row">
      {COLUMNS.map((status) => {
        const items = projects.filter((p) => p.status === status);

        return (
          <div className="col" key={status} style={{ minWidth: 225 }}>
            <div className="d-flex justify-content-between align-items-center mb-2">
              <span className="fw-bold small text-uppercase text-muted-light">{STATUS_LABELS[status]}</span>
              <span className="badge text-bg-secondary">{items.length}</span>
            </div>

            <div className="kanban-col">
              {items.map((p) => (
                <Link
                  key={p.id}
                  to={`${detailBase}/${p.id}`}
                  className="adhub-card kanban-card d-block p-3 mb-2 text-decoration-none"
                >
                  <div className={`kanban-accent bg-${ACCENT[status]} mb-2`} />
                  <div className="fw-bold text-white">{p.title}</div>
                  <div className="small text-muted-light">{p.client_company || p.client_name}</div>
                  <div className="small text-muted-light">{p.service_type || '—'}</div>
                  <div className="d-flex justify-content-between small mt-2">
                    <span className="text-muted-light">{formatMoney(p.budget)}</span>
                    <span className="text-muted-light">{p.progress_percent}%</span>
                  </div>
                  <div className="progress mt-2" style={{ height: 4 }}>
                    <div className={`progress-bar bg-${ACCENT[status]}`} style={{ width: `${p.progress_percent}%` }} />
                  </div>
                </Link>
              ))}

              {items.length === 0 && (
                <div className="kanban-empty small text-muted-light text-center p-3">—</div>
              )}
            </div>
          </div>
        );
      })}
    </div>
  );
}
