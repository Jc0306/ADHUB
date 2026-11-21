import { Suspense, lazy } from 'react';
import { formatMoney } from '../constants.js';

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

const Charts = lazy(() =>
  import(
    /* webpackChunkName: "recharts" */
    'recharts'
  ).then(({ Bar, BarChart, CartesianGrid, Cell, Legend, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis }) => ({
    default: function ChartsComponent({ pieData, clientData }) {
      return (
        <div className="row g-4">
          <div className="col-lg-5">
            <div className="adhub-card p-4 h-100">
              <h2 className="h6 mb-3">Projects by Status</h2>

              {pieData.length === 0 ? (
                <p className="text-muted-light mb-0">No projects yet.</p>
              ) : (
                <ResponsiveContainer width="100%" height={280}>
                  <PieChart>
                    <Pie
                      data={pieData}
                      dataKey="value"
                      nameKey="name"
                      innerRadius={60}
                      outerRadius={100}
                      paddingAngle={3}
                    >
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
      );
    },
  }))
);

export default function AnalyticsCharts({ pieData, clientData }) {
  return (
    <Suspense fallback={<div className="text-muted-light text-center py-4">Loading charts…</div>}>
      <Charts pieData={pieData} clientData={clientData} />
    </Suspense>
  );
}