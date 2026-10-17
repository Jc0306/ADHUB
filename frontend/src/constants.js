// Shared domain constants — mirror the values stored in MySQL (see DATABASE/adhubdb.sql).

export const PROJECT_STATUSES = ['pending', 'in-progress', 'revision', 'completed', 'rejected'];

export const STATUS_LABELS = {
  pending: 'Pending',
  'in-progress': 'In Progress',
  revision: 'Revision',
  completed: 'Completed',
  rejected: 'Rejected',
};

export const SERVICE_TYPES = ['Graphic Design', 'Logo Design', 'Slogan', 'Poster'];

export const APPROVAL_LABELS = {
  pending: 'Awaiting Review',
  approved: 'Approved',
  revision: 'Needs Revision',
};

export const formatMoney = (value) => `$${Number(value ?? 0).toFixed(2)}`;

export const formatDate = (value) => (value ? new Date(value).toLocaleString() : '');
