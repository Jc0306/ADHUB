import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import DashboardShell from '../components/DashboardShell.jsx';
import { apiGet } from '../api/client.js';
import { STATUS_LABELS, formatMoney } from '../constants.js';
import AnalyticsCharts from './AnalyticsCharts.jsx';

export default function AnalyticsPage() {
  const [data, setData] = useState(null);
  const [error, setError] = useState('');

  useEffect(() => {
    apiGet('/analytics/overview.php')
      .then(setData)
      .catch((e) => setError(e.message));
  }, []);

  if (!data) {
    return (
      <DashboardShell subtitle="Analytics">
        <p className="text-muted-light">{error || 'Loading…'}</p>
      </DashboardShell>
    );
  }

  const pieData = data.status_counts.map((s) => ({
    key: s.status,
    name: STATUS_LABELS[s.status] ?? s.status,
    value: Number(s.count),
  }));

  const completed = pieData.find((s) => s.key === 'completed')?.value ?? 0;
  const completionRate = data.total_projects > 0 ? Math.round((completed / data.total_projects) * 100) : 0;
  const utilization = Number(data.total_retained) > 0
    ? Math.round((Number(data.total_consumed) / Number(data.total_retained)) * 100)
    : 0;

  const clientData = data.clients.map((c) => ({
    name: c.company_name || c.full_name,
    retained: Number(c.retained),
    consumed: Number(c.consumed),
  }));

  return (
    <DashboardShell subtitle="Analytics">
      <div className="mb-3">
        <Link to="/admin" className="text-muted-light small">← Back to dashboard</Link>
      </div>

      <div className="row g-3 mb-3">
        <div className="col-md-3">
          <div className="adhub-card p-3">
            <div className="small text-muted-light">Total Projects</div>
            <div className="fs-4 fw-bold">{data.total_projects}</div>
          </div>
        </div>
        <div className="col-md-3">
          <div className="adhub-card p-3">
            <div className="small text-muted-light">Completion Rate</div>
            <div className="fs-4 fw-bold text-success">{completionRate}%</div>
          </div>
        </div>
        <div className="col-md-3">
          <div className="adhub-card p-3">
            <div className="small text-muted-light">Total Retainer Funds</div>
            <div className="fs-4 fw-bold">{formatMoney(data.total_retained)}</div>
          </div>
        </div>
        <div className="col-md-3">
          <div className="adhub-card p-3">
            <div className="small text-muted-light">Budget Utilization</div>
            <div className="fs-4 fw-bold text-warning">{utilization}%</div>
          </div>
        </div>
      </div>

      <AnalyticsCharts pieData={pieData} clientData={clientData} />
    </DashboardShell>
  );
}
