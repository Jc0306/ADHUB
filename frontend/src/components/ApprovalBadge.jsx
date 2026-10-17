import { APPROVAL_LABELS } from '../constants.js';

const STYLES = {
  pending: 'text-bg-info',
  approved: 'text-bg-success',
  revision: 'text-bg-warning',
};

export default function ApprovalBadge({ status }) {
  return (
    <span className={`badge rounded-pill ${STYLES[status] ?? 'text-bg-secondary'}`}>
      {APPROVAL_LABELS[status] ?? status}
    </span>
  );
}
