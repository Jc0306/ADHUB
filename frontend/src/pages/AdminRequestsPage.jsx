import { useEffect, useState, useCallback, useMemo } from 'react';
import { Link } from 'react-router-dom';
import DashboardShell from '../components/DashboardShell.jsx';
import RequestStatusBadge from '../components/RequestStatusBadge.jsx';
import StatusBadge from '../components/StatusBadge.jsx';
import { apiGet, apiPost } from '../api/client.js';
import { showToast } from '../toast.js';
import { REQUEST_STATUSES, REQUEST_STATUS_LABELS, formatDate } from '../constants.js';
import { usePoll } from '../hooks/usePoll.js';

/** Admin area — incoming service requests: reply, and accept to create the project. */
export default function AdminRequestsPage() {
  const [requests, setRequests] = useState([]);
  const [drafts, setDrafts] = useState({});
  const [filter, setFilter] = useState('all');
  const [search, setSearch] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);
  const [busyId, setBusyId] = useState(null);
  const [accepting, setAccepting] = useState(null);
  const [page, setPage] = useState(1);
  const PAGE_SIZE = 10;

  const load = useCallback(async () => {
    try {
      const d = await apiGet('/requests/list.php');
      setRequests(d.requests);
      // Keep drafts the admin is typing; only seed requests not seen before.
      setDrafts((prev) =>
        Object.fromEntries(
          d.requests.map((r) => [r.id, prev[r.id] ?? { reply: r.admin_reply ?? '', status: r.status }])
        )
      );
      setError('');
    } catch (e) {
      setError(e.message);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { load(); }, [load]);
  usePoll(load, 10000);

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

  async function handleAccept(req) {
    setError('');
    setAccepting(req.id);

    try {
      const data = await apiPost('/requests/accept.php', { id: req.id });
      syncRequest(data.request);
      showToast(`Project "${data.project.title}" created — it is In Progress on your dashboard.`);
    } catch (err) {
      setError(err.message);
    } finally {
      setAccepting(null);
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

  const filtered = useMemo(() => {
    let result = filter === 'all' ? requests : requests.filter((r) => r.status === filter);
    if (search.trim()) {
      const q = search.toLowerCase();
      result = result.filter((r) =>
        r.title.toLowerCase().includes(q) ||
        r.message.toLowerCase().includes(q) ||
        r.client_name.toLowerCase().includes(q) ||
        (r.client_company || '').toLowerCase().includes(q)
      );
    }
    return result;
  }, [requests, filter, search]);

  const totalPages = Math.ceil(filtered.length / PAGE_SIZE) || 1;
  const currentPage = Math.min(page, totalPages);
  const paginated = useMemo(() => {
    const start = (currentPage - 1) * PAGE_SIZE;
    return filtered.slice(start, start + PAGE_SIZE);
  }, [filtered, currentPage]);

  return (
    <DashboardShell subtitle="Client Requests">
      {error && <div className="alert alert-danger py-2">{error}</div>}

      <div className="row g-3 mb-4">
        <div className="col-md-8">
          <div className="btn-group w-100" role="group">
            {['all', ...REQUEST_STATUSES].map((s) => (
              <button
                key={s}
                type="button"
                className={`btn btn-sm ${filter === s ? 'btn-adhub' : 'btn-outline-light'}`}
                onClick={() => { setFilter(s); setPage(1); }}
              >
                {s === 'all' ? 'All' : REQUEST_STATUS_LABELS[s]} ({counts[s]})
              </button>
            ))}
          </div>
        </div>
        <div className="col-md-4">
          <input
            type="search"
            className="form-control"
            placeholder="Search title, message, client…"
            value={search}
            onChange={(e) => { setSearch(e.target.value); setPage(1); }}
            aria-label="Search requests"
          />
        </div>
      </div>

      {loading ? (
        <p className="text-muted-light">Loading…</p>
      ) : paginated.length === 0 ? (
        <div className="adhub-card p-4 text-muted-light">
          {search ? 'No requests match your search.' : 'No requests to show.'}
        </div>
      ) : (
        <>
          {paginated.map((req) => (
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
                <div className="mb-3">
                  <button
                    type="button"
                    className="btn btn-adhub btn-sm"
                    disabled={accepting === req.id}
                    onClick={() => handleAccept(req)}
                  >
                    {accepting === req.id ? 'Creating…' : 'Accept — Create Project'}
                  </button>
                  <p className="small text-muted-light mt-2 mb-0">
                    Creates the project right away (In Progress) from this request&apos;s title and
                    message — set the budget and details inside it, chat with the client, and upload
                    what they asked for.
                  </p>
                </div>
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
          ))}

          {totalPages > 1 && (
            <nav className="d-flex justify-content-center gap-2 mt-3" aria-label="Pagination">
              <button
                className="btn btn-outline-light btn-sm"
                onClick={() => setPage(p => Math.max(1, p - 1))}
                disabled={currentPage === 1}
                aria-label="Previous page"
              >
                « Prev
              </button>
              {[...Array(totalPages)].map((_, i) => (
                <button
                  key={i}
                  className={`btn btn-sm ${currentPage === i + 1 ? 'btn-adhub' : 'btn-outline-light'}`}
                  onClick={() => setPage(i + 1)}
                  aria-label={`Page ${i + 1}`}
                  aria-current={currentPage === i + 1 ? 'page' : undefined}
                >
                  {i + 1}
                </button>
              ))}
              <button
                className="btn btn-outline-light btn-sm"
                onClick={() => setPage(p => Math.min(totalPages, p + 1))}
                disabled={currentPage === totalPages}
                aria-label="Next page"
              >
                Next »
              </button>
            </nav>
          )}
        </>
      )}
    </DashboardShell>
  );
}