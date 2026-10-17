import { useEffect, useState } from 'react';
import DashboardShell from '../components/DashboardShell.jsx';
import { apiGet, apiPost } from '../api/client.js';
import { showToast } from '../toast.js';
import { formatMoney } from '../constants.js';

export default function CostsPage() {
  const [clients, setClients] = useState([]);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);
  const [topupFor, setTopupFor] = useState(null);
  const [amount, setAmount] = useState('');
  const [note, setNote] = useState('');
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    apiGet('/costs/summary.php')
      .then((d) => setClients(d.clients))
      .catch((e) => setError(e.message))
      .finally(() => setLoading(false));
  }, []);

  async function handleTopup(client) {
    setError('');
    setBusy(true);

    try {
      const data = await apiPost('/costs/topup.php', {
        client_id: client.client_id,
        amount: Number(amount),
        note,
      });

      setClients((list) =>
        list.map((c) =>
          c.client_id === client.client_id
            ? { ...c, retained: data.retained, consumed: data.consumed, balance: data.balance }
            : c
        )
      );
      showToast(`Added ${formatMoney(amount)} to ${client.full_name}'s retainer.`);
      setTopupFor(null);
      setAmount('');
      setNote('');
    } catch (err) {
      setError(err.message);
    } finally {
      setBusy(false);
    }
  }

  return (
    <DashboardShell subtitle="Cost Tracking">
      <div className="d-flex justify-content-between align-items-center mb-3">
        <h2 className="h5 mb-0 text-muted-light">Client Retainers</h2>
      </div>

      {error && <div className="alert alert-danger py-2">{error}</div>}

      <div className="adhub-card p-4">
        {loading && <p className="text-muted-light mb-0">Loading…</p>}

        {!loading && clients.length === 0 && (
          <p className="text-muted-light mb-0">No client accounts yet.</p>
        )}

        {!loading && clients.length > 0 && (
          <div className="table-responsive">
            <table className="adhub-table">
              <thead>
                <tr>
                  <th>Client</th>
                  <th>Company</th>
                  <th className="text-end">Retained</th>
                  <th className="text-end">Consumed</th>
                  <th className="text-end">Balance</th>
                  <th className="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                {clients.map((c) => (
                  <tr key={c.client_id}>
                    <td className="fw-bold">{c.full_name}</td>
                    <td className="text-muted-light">{c.company_name || '—'}</td>
                    <td className="text-end">{formatMoney(c.retained)}</td>
                    <td className="text-end">{formatMoney(c.consumed)}</td>
                    <td className={`text-end fw-bold ${Number(c.balance) < 0 ? 'text-danger' : 'text-success'}`}>
                      {formatMoney(c.balance)}
                    </td>
                    <td className="text-end">
                      <button
                        type="button"
                        className="btn btn-adhub btn-sm"
                        onClick={() => { setTopupFor(c.client_id); setAmount(''); setNote(''); }}
                      >
                        + Top-up
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {topupFor !== null && (
        <div className="adhub-card p-4 mt-3">
          <h3 className="h6 mb-3">
            Add Funds — {clients.find((c) => c.client_id === topupFor)?.full_name}
          </h3>

          <div className="row g-3 align-items-end">
            <div className="col-md-3">
              <label className="form-label" htmlFor="topup_amount">Amount ($)</label>
              <input
                id="topup_amount"
                type="number"
                min="0.01"
                step="0.01"
                className="form-control"
                value={amount}
                onChange={(e) => setAmount(e.target.value)}
                required
              />
            </div>
            <div className="col-md-5">
              <label className="form-label" htmlFor="topup_note">Note (optional)</label>
              <input
                id="topup_note"
                className="form-control"
                placeholder="e.g. June retainer"
                value={note}
                onChange={(e) => setNote(e.target.value)}
              />
            </div>
            <div className="col-md-4 d-flex gap-2">
              <button
                type="button"
                className="btn btn-adhub"
                disabled={busy || !amount}
                onClick={() => handleTopup(clients.find((c) => c.client_id === topupFor))}
              >
                {busy ? 'Adding…' : 'Add Funds'}
              </button>
              <button type="button" className="btn btn-outline-light" onClick={() => setTopupFor(null)}>
                Cancel
              </button>
            </div>
          </div>
        </div>
      )}
    </DashboardShell>
  );
}
