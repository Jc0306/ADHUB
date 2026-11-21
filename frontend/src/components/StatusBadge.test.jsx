import { render, screen } from '@testing-library/react';
import StatusBadge from './StatusBadge.jsx';

describe('StatusBadge', () => {
  it('renders "In Progress" badge with correct variant', () => {
    render(<StatusBadge status="in-progress" />);
    expect(screen.getByText('In Progress')).toBeInTheDocument();
    expect(screen.getByText('In Progress')).toHaveClass('text-bg-primary');
  });

  it('renders "Revision" badge with correct variant', () => {
    render(<StatusBadge status="revision" />);
    expect(screen.getByText('Revision')).toBeInTheDocument();
    expect(screen.getByText('Revision')).toHaveClass('text-bg-warning');
  });

  it('renders "Completed" badge with correct variant', () => {
    render(<StatusBadge status="completed" />);
    expect(screen.getByText('Completed')).toBeInTheDocument();
    expect(screen.getByText('Completed')).toHaveClass('text-bg-success');
  });

  it('renders unknown status as-is with secondary variant', () => {
    render(<StatusBadge status="unknown" />);
    expect(screen.getByText('unknown')).toBeInTheDocument();
    expect(screen.getByText('unknown')).toHaveClass('text-bg-secondary');
  });
});