import '@testing-library/jest-dom';
import { vi } from 'vitest';

// Mock react-router-dom
vi.mock('react-router-dom', () => ({
  ...vi.requireActual('react-router-dom'),
  useNavigate: () => vi.fn(),
  useParams: () => ({}),
  Link: ({ children, to, ...props }) => {
    const React = require('react');
    return React.createElement('a', { href: to, ...props }, children);
  },
}));

// Mock api client
vi.mock('../api/client.js', () => ({
  apiGet: vi.fn(),
  apiPost: vi.fn(),
  apiUpload: vi.fn(),
}));

// Mock toast
vi.mock('../toast.js', () => ({
  showToast: vi.fn(),
}));

// Mock constants
vi.mock('../constants.js', () => ({
  formatDate: (d) => new Date(d).toLocaleDateString(),
  formatMoney: (n) => `$${Number(n).toFixed(2)}`,
  REQUEST_STATUSES: ['open', 'accepted', 'done'],
  REQUEST_STATUS_LABELS: { open: 'Open', accepted: 'Accepted', done: 'Done' },
  PROJECT_STATUSES: ['pending', 'in-progress', 'revision', 'completed', 'rejected'],
  STATUS_LABELS: { pending: 'Pending', 'in-progress': 'In Progress', revision: 'Revision', completed: 'Completed', rejected: 'Rejected' },
  SERVICE_TYPES: ['Graphic Design', 'Logo Design', 'Slogan', 'Poster'],
  APPROVAL_LABELS: { pending: 'Awaiting Review', approved: 'Approved', revision: 'Needs Revision' },
}));