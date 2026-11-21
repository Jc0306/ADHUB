import { render, screen } from '@testing-library/react';
import RequestStatusBadge from './RequestStatusBadge.jsx';

describe('RequestStatusBadge', () => {
  it('renders "Open" badge with correct variant', () => {
    render(<RequestStatusBadge status="open" />);
    expect(screen.getByText('Open')).toBeInTheDocument();
    expect(screen.getByText('Open')).toHaveClass('text-bg-warning');
  });

  it('renders "Accepted" badge with correct variant', () => {
    render(<RequestStatusBadge status="accepted" />);
    expect(screen.getByText('Accepted')).toBeInTheDocument();
    expect(screen.getByText('Accepted')).toHaveClass('text-bg-primary');
  });

  it('renders "Done" badge with correct variant', () => {
    render(<RequestStatusBadge status="done" />);
    expect(screen.getByText('Done')).toBeInTheDocument();
    expect(screen.getByText('Done')).toHaveClass('text-bg-success');
  });

  it('renders unknown status as-is with secondary variant', () => {
    render(<RequestStatusBadge status="unknown" />);
    expect(screen.getByText('unknown')).toBeInTheDocument();
    expect(screen.getByText('unknown')).toHaveClass('text-bg-secondary');
  });
});