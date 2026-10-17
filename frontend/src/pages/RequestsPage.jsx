import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import DashboardShell from '../components/DashboardShell.jsx';
import RequestStatusBadge from '../components/RequestStatusBadge.jsx';
import { apiGet, apiPost } from '../api/client.js';
import { showToast } from '../toast.js';
import { formatDate } from '../constants.js';

/** Client area — send service requests to the agency and follow the replies. */
export default function RequestsPage() {
  const [requests, setRequests] = useState([]);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);
  const [title, setTitle] = useState('');
  const [message, setMessage] = useState('');
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    apiGet('/requests/list.php')
      .then((d) => setRequests(d.requests))
      .catch((e) => setError(e.message))
      .finally(() => setLoading(false));
  }, []);

  async function handleSubmit(e) {
    e.preventDefault();
    setError('');
    setBusy(true);

    try {
      const data = await apiPost('/requests/create.php', { title, message });
      setRequests((list) => [data.request, ...list]);
      setTitle('');
      setMessage('');
      showToast('Request sent — the agency will reply here.');
    } catch (err) {
      setError(err.message);
    } finally {
      setBusy(false);
    }
  }

  return (
    <DashboardShell subtitle="Service Requests">
      <div className="row g-4">
        <div className="col-lg-4">
          <div className="adhub-card p-4">
            <h2 className="h5 mb-3">Request a Service</h2>
            <form onSubmit={handleSubmit}>
              <div className="mb-3">
                <label className="form-label" htmlFor="req-title">Title</label>
                <input
                  id="req-title"
                  className="form-control"
                  placeholder="e.g. New mural design for the café wall"
                  value={title}
                  onChange={(e) => setTitle(e.target.value)}
                  required
                />
              </div>
              <div className="mb-4">
                <label className="form-label" htmlFor="req-message">What do you need?</label>
                <textarea
                  id="req-message"
                  className="form-control"
                  rows="5"
                  placeholder="Describe the work, the deadline and anything the team should know…"
                  value={message}
                  onChange={(e) => setMessage(e.target.value)}
                  required
                />
              </div>
              <button type="submit" className="btn btn-adhub w-100" disabled={busy}>
                {busy ? 'Sending…' : 'Send Request'}
              </button>
            </form>
          </div>
        </div>

        <div className="col-lg-8">
          <h2 className="h5 mb-3 text-muted-light">Your Requests</h2>

          {error && <div className="alert alert-danger py-2">{error}</div>}

          {loading ? (
            <p className="text-muted-light">Loading…</p>
          ) : requests.length === 0 ? (
            <div className="adhub-card p-4 text-muted-light">
              You haven&apos;t sent any requests yet — use the form to ask the agency for new work.
            </div>
          ) : (
            requests.map((req) => (
              <div key={req.id} className="adhub-card p-4 mb-3">
                <div className="d-flex justify-content-between align-items-start gap-2 mb-1">
                  <div className="fw-bold">{req.title}</div>
                  <RequestStatusBadge status={req.status} />
                </div>
                <div className="small text-muted-light mb-2">Sent {formatDate(req.created_at)}</div>
                <div style={{ whiteSpace: 'pre-wrap' }}>{req.message}</div>

                {req.admin_reply && (
                  <div className="mt-3 ps-3 border-start border-3 border-primary">
                    <div className="small text-muted-light mb-1">
                      Agency reply · {formatDate(req.replied_at)}
                    </div>
                    <div style={{ whiteSpace: 'pre-wrap' }}>{req.admin_reply}</div>
                  </div>
                )}

                {req.project_id && (
                  <div className="mt-2">
                    <Link to={`/client/projects/${req.project_id}`} className="link-light small">
                      Project created: {req.project_title} →
                    </Link>
                  </div>
                )}
              </div>
            ))
          )}
        </div>
      </div>
    </DashboardShell>
  );
}
