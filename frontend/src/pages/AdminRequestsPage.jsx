import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import DashboardShell from '../components/DashboardShell.jsx';
import RequestStatusBadge from '../components/RequestStatusBadge.jsx';
import StatusBadge from '../components/StatusBadge.jsx';
import { apiGet, apiPost } from '../api/client.js';
import { showToast } from '../toast.js';
import {
  REQUEST_STATUSES,
  REQUEST_STATUS_LABELS,
  SERVICE_TYPES,
  formatDate,
} from '../constants.js';

/** Admin area — incoming service requests: reply, and accept to create the project. */
export default function AdminRequestsPage() {
  const [requests, setRequests] = useState([]);
  const [drafts, setDrafts] = useState({});
  const [filter, setFilter] = useState('all');
  const [acceptFor, setAcceptFor] = useState(null);
  const [acceptForm, setAcceptForm] = useState({ title: '', service_type: SERVICE_TYPES[0], budget: '', reply: '' });
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);
  const [busyId, setBusyId] = useState(null);
  const [accepting, setAccepting] = useState(false);

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

  function syncRequest(request) {
    setRequests((list) => list.map((r) => (r.id === request.id ? request : r)));
    setDrafts((d) => ({
      ...d,
      [request.id]: { reply: request.admin_reply ?? '', status: request.status },
    }));
  }

  function setDraft(id, field) {
    return (e) =>
      setDrafts((d) => ({ ...d, [id]: { ...d[id], [field]: e.target.value } }));
  }

  function setAccept(field) {
    return (e) => setAcceptForm((f) => ({ ...f, [field]: e.target.value }));
  }

  function startAccept(req) {
    setAcceptFor(req.id);
    setAcceptForm({ title: req.title, service_type: SERVICE_TYPES[0], budget: '', reply: '' });
  }

  async function handleAccept(req) {
    setError('');
    setAccepting(true);

    try {
      const data = await apiPost('/requests/accept.php', {
        id: req.id,
        title: acceptForm.title,
        service_type: acceptForm.service_type,
        budget: Number(acceptForm.budget),
        reply: acceptForm.reply,
      });
      syncRequest(data.request);
      setAcceptFor(null);
      showToast(`Project "${data.project.title}" created — it is In Progress on your dashboard.`);
    } catch (err) {
      setError(err.message);
    } finally {
      setAccepting(false);
    }
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
      syncRequest(data.request);
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

            {req.project_id && (
              <div className="d-flex align-items-center gap-2 mb-3">
                <span className="small text-muted-light">Project:</span>
                <Link to={`/admin/projects/${req.project_id}`} className="link-light fw-semibold">
                  {req.project_title}
                </Link>
                <StatusBadge status={req.project_status} />
                <span className="small text-muted-light">— open it to log hours, upload assets and chat</span>
              </div>
            )}

            {req.status !== 'done' && !req.project_id && (
              acceptFor === req.id ? (
                <div className="border-start border-3 border-success ps-3 mb-3">
                  <div className="fw-semibold mb-2">Create the project</div>
                  <div className="row g-2">
                    <div className="col-md-6">
                      <label className="form-label small text-muted-light" htmlFor={`acc-title-${req.id}`}>
                        Project Title
                      </label>
                      <input
                        id={`acc-title-${req.id}`}
                        className="form-control"
                        value={acceptForm.title}
                        onChange={setAccept('title')}
                        required
                      />
                    </div>
                    <div className="col-md-3">
                      <label className="form-label small text-muted-light" htmlFor={`acc-service-${req.id}`}>
                        Service Type
                      </label>
                      <select
                        id={`acc-service-${req.id}`}
                        className="form-select"
                        value={acceptForm.service_type}
                        onChange={setAccept('service_type')}
                      >
                        {SERVICE_TYPES.map((s) => <option key={s}>{s}</option>)}
                      </select>
                    </div>
                    <div className="col-md-3">
                      <label className="form-label small text-muted-light" htmlFor={`acc-budget-${req.id}`}>
                        Budget ($)
                      </label>
                      <input
                        id={`acc-budget-${req.id}`}
                        type="number"
                        min="0"
                        step="0.01"
                        className="form-control"
                        placeholder="e.g. 300"
                        value={acceptForm.budget}
                        onChange={setAccept('budget')}
                        required
                      />
                    </div>
                    <div className="col-12">
                      <label className="form-label small text-muted-light" htmlFor={`acc-reply-${req.id}`}>
                        Note to client (optional)
                      </label>
                      <textarea
                        id={`acc-reply-${req.id}`}
                        className="form-control"
                        rows="2"
                        placeholder="e.g. Great idea — we've scheduled it. First concepts next week."
                        value={acceptForm.reply}
                        onChange={setAccept('reply')}
                      />
                    </div>
                  </div>
                  <div className="d-flex gap-2 mt-2">
                    <button
                      type="button"
                      className="btn btn-adhub btn-sm"
                      disabled={accepting || !acceptForm.title.trim() || acceptForm.budget === ''}
                      onClick={() => handleAccept(req)}
                    >
                      {accepting ? 'Creating…' : 'Create Project (In Progress)'}
                    </button>
                    <button
                      type="button"
                      className="btn btn-outline-light btn-sm"
                      onClick={() => setAcceptFor(null)}
                    >
                      Cancel
                    </button>
                  </div>
                </div>
              ) : (
                <button
                  type="button"
                  className="btn btn-adhub btn-sm mb-3"
                  onClick={() => startAccept(req)}
                >
                  Accept — Create Project
                </button>
              )
            )}

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
                  {drafts[req.id]?.status === 'accepted' && (
                    <option value="accepted" disabled>Accepted</option>
                  )}
                  {REQUEST_STATUSES.filter((s) => s !== 'accepted').map((s) => (
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
