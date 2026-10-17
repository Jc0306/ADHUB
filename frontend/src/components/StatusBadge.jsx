import { STATUS_LABELS } from '../constants.js';

const STYLES = {
  pending: 'text-bg-secondary',
  'in-progress': 'text-bg-primary',
  revision: 'text-bg-warning',
  completed: 'text-bg-success',
  rejected: 'text-bg-danger',
};

export default function StatusBadge({ status }) {
  return (
    <span className={`badge rounded-pill ${STYLES[status] ?? 'text-bg-secondary'}`}>
      {STATUS_LABELS[status] ?? status}
    </span>
  );
}
