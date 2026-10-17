import { useEffect, useState } from 'react';
import DashboardShell from '../components/DashboardShell.jsx';
import RequestStatusBadge from '../components/RequestStatusBadge.jsx';
import { apiGet, apiPost } from '../api/client.js';
import { showToast } from '../toast.js';
import { REQUEST_STATUSES, REQUEST_STATUS_LABELS, formatDate } from '../constants.js';

/** Admin area — incoming service requests with reply + status per request. */
export default function AdminRequestsPage() {
  const [requests, setRequests] = useState([]);
  const [drafts, setDrafts] = useState({});
  const [filter, setFilter] = useState('all');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);
  const [busyId, setBusyId] = useState(null);

  useEffect(() => {
    apiGet('/requests/list.php')
      .then((d) => {
        setRequests(d.requests);
        setDrafts(
          Object.fromEntries(
            d.requests.map((r) => [r.id, { reply: r.admin_reply ?? '', status: r.status }])
          )
        );
      })
      .catch((e) => setError(e.message))
      .finally(() => setLoading(false));
  }, []);

  function setDraft(id, field) {
    return (e) =>
      setDrafts((d) => ({ ...d, [id]: { ...d[id], [field]: e.target.value } }));
  }

  async function handleSave(req) {
    setError('');
    setBusyId(req.id);

    try {
      const draft = drafts[req.id];
      const data = await apiPost('/requests/respond.php', {
        id: req.id,
        status: draft.status,
        reply: draft.reply,
      });

      setRequests((list) => list.map((r) => (r.id === req.id ? data.request : r)));
      setDrafts((d) => ({
        ...d,
        [req.id]: { reply: data.request.admin_reply ?? '', status: data.request.status },
      }));
      showToast(
        draft.reply.trim()
          ? `Reply sent for "${req.title}".`
          : `Request marked as ${REQUEST_STATUS_LABELS[data.request.status].toLowerCase()}.`
      );
    } catch (err) {
      setError(err.message);
    } finally {
      setBusyId(null);
    }
  }

  const counts = {
    all: requests.length,
    ...Object.fromEntries(
      REQUEST_STATUSES.map((s) => [s, requests.filter((r) => r.status === s).length])
    ),
  };
  const visible = filter === 'all' ? requests : requests.filter((r) => r.status === filter);

  return (
    <DashboardShell subtitle="Client Requests">
      {error && <div className="alert alert-danger py-2">{error}</div>}

      <div className="btn-group mb-4">
        {['all', ...REQUEST_STATUSES].map((s) => (
          <button
            key={s}
            type="button"
            className={`btn btn-sm ${filter === s ? 'btn-adhub' : 'btn-outline-light'}`}
            onClick={() => setFilter(s)}
          >
            {s === 'all' ? 'All' : REQUEST_STATUS_LABELS[s]} ({counts[s]})
          </button>
        ))}
      </div>

      {loading ? (
        <p className="text-muted-light">Loading…</p>
      ) : visible.length === 0 ? (
        <div className="adhub-card p-4 text-muted-light">No requests to show.</div>
      ) : (
        visible.map((req) => (
          <div key={req.id} className="adhub-card p-4 mb-3">
            <div className="d-flex justify-content-between align-items-start gap-2 mb-1">
              <div className="fw-bold">{req.title}</div>
              <RequestStatusBadge status={req.status} />
            </div>
            <div className="small text-muted-light mb-2">
              {req.client_company ? `${req.client_company} — ${req.client_name}` : req.client_name}
              {' · '}
              {formatDate(req.created_at)}
              {req.replied_at && <> · last replied {formatDate(req.replied_at)}</>}
            </div>
            <div className="mb-3" style={{ whiteSpace: 'pre-wrap' }}>{req.message}</div>

            <div className="row g-2 align-items-end">
              <div className="col-md-8">
                <label className="form-label small text-muted-light" htmlFor={`reply-${req.id}`}>
                  Reply to client
                </label>
                <textarea
                  id={`reply-${req.id}`}
                  className="form-control"
                  rows="2"
                  placeholder="Type your reply…"
                  value={drafts[req.id]?.reply ?? ''}
                  onChange={setDraft(req.id, 'reply')}
                />
              </div>
              <div className="col-md-2">
                <label className="form-label small text-muted-light" htmlFor={`status-${req.id}`}>
                  Status
                </label>
                <select
                  id={`status-${req.id}`}
                  className="form-select"
                  value={drafts[req.id]?.status ?? req.status}
                  onChange={setDraft(req.id, 'status')}
                >
                  {REQUEST_STATUSES.map((s) => (
                    <option key={s} value={s}>{REQUEST_STATUS_LABELS[s]}</option>
                  ))}
                </select>
              </div>
              <div className="col-md-2">
                <button
                  type="button"
                  className="btn btn-adhub w-100"
                  disabled={busyId === req.id}
                  onClick={() => handleSave(req)}
                >
                  {busyId === req.id ? 'Saving…' : 'Save'}
                </button>
              </div>
            </div>
          </div>
        ))
      )}
    </DashboardShell>
  );
}
