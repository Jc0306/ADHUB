import { REQUEST_STATUS_LABELS } from '../constants.js';

const STYLES = {
  open: 'text-bg-warning',
  accepted: 'text-bg-primary',
  done: 'text-bg-success',
};

export default function RequestStatusBadge({ status }) {
  return (
    <span className={`badge rounded-pill ${STYLES[status] ?? 'text-bg-secondary'}`}>
      {REQUEST_STATUS_LABELS[status] ?? status}
    </span>
  );
}
