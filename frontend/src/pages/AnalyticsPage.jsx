import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import {
  Bar,
  BarChart,
  CartesianGrid,
  Cell,
  Legend,
  Pie,
  PieChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts';
import DashboardShell from '../components/DashboardShell.jsx';
import { apiGet } from '../api/client.js';
import { STATUS_LABELS, formatMoney } from '../constants.js';

const STATUS_COLORS = {
  pending: '#6c757d',
  'in-progress': '#0d6efd',
  revision: '#ffc107',
  completed: '#198754',
  rejected: '#dc3545',
};

const TOOLTIP_STYLE = {
  backgroundColor: '#132238',
  border: '1px solid rgba(255,255,255,0.15)',
  color: '#fff',
};

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

      <div className="row g-4">
        <div className="col-lg-5">
          <div className="adhub-card p-4 h-100">
            <h2 className="h6 mb-3">Projects by Status</h2>

            {pieData.length === 0 ? (
              <p className="text-muted-light mb-0">No projects yet.</p>
            ) : (
              <ResponsiveContainer width="100%" height={280}>
                <PieChart>
                  <Pie data={pieData} dataKey="value" nameKey="name" innerRadius={60} outerRadius={100} paddingAngle={3}>
                    {pieData.map((entry) => (
                      <Cell key={entry.key} fill={STATUS_COLORS[entry.key] ?? '#6c757d'} />
                    ))}
                  </Pie>
                  <Tooltip contentStyle={TOOLTIP_STYLE} />
                  <Legend />
                </PieChart>
              </ResponsiveContainer>
            )}
          </div>
        </div>

        <div className="col-lg-7">
          <div className="adhub-card p-4 h-100">
            <h2 className="h6 mb-3">Retainer vs Consumed (per client)</h2>

            {clientData.length === 0 ? (
              <p className="text-muted-light mb-0">No clients yet.</p>
            ) : (
              <ResponsiveContainer width="100%" height={280}>
                <BarChart data={clientData}>
                  <CartesianGrid stroke="rgba(255,255,255,0.1)" vertical={false} />
                  <XAxis dataKey="name" tick={{ fill: '#adb5bd', fontSize: 12 }} />
                  <YAxis tick={{ fill: '#adb5bd', fontSize: 12 }} />
                  <Tooltip contentStyle={TOOLTIP_STYLE} formatter={(value) => formatMoney(value)} />
                  <Legend />
                  <Bar dataKey="retained" name="Retained" fill="#4c7fb3" radius={[4, 4, 0, 0]} />
                  <Bar dataKey="consumed" name="Consumed" fill="#f0a13a" radius={[4, 4, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            )}
          </div>
        </div>
      </div>
    </DashboardShell>
  );
}
