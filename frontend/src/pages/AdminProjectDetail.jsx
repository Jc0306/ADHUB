import { useEffect, useRef, useState, useCallback } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import DashboardShell from '../components/DashboardShell.jsx';
import DeliverablesTable from '../components/DeliverablesTable.jsx';
import StatusBadge from '../components/StatusBadge.jsx';
import ChatThread from '../components/ChatThread.jsx';
import { apiGet, apiPost, apiUpload } from '../api/client.js';
import { showToast } from '../toast.js';
import { PROJECT_STATUSES, SERVICE_TYPES, STATUS_LABELS, formatDate, formatMoney } from '../constants.js';
import { usePoll } from '../hooks/usePoll.js';

export default function AdminProjectDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const fileRef = useRef(null);
  // Which project the editable form belongs to. Polling must not overwrite
  // what the admin is typing; it only seeds the form on first load / id change.
  const editProjectRef = useRef(null);

  const [project, setProject] = useState(null);
  const [deliverables, setDeliverables] = useState([]);
  const [logs, setLogs] = useState([]);
  const [cost, setCost] = useState(0);
  const [edit, setEdit] = useState(null);
  const [file, setFile] = useState(null);
  const [displayName, setDisplayName] = useState('');
  const [logForm, setLogForm] = useState({ hours: '', hourly_rate: '25', note: '' });
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);

  const load = useCallback(async () => {
    try {
      const d = await apiGet(`/projects/show.php?id=${id}`);
      setProject(d.project);
      setDeliverables(d.deliverables);
      setLogs(d.logs);
      setCost(d.cost);
      if (editProjectRef.current !== id) {
        editProjectRef.current = id;
        setEdit({
          title: d.project.title,
          service_type: d.project.service_type,
          budget: d.project.budget,
          description: d.project.description ?? '',
          status: d.project.status,
          progress_percent: d.project.progress_percent,
        });
      }
      setError('');
    } catch (e) {
      setError(e.message);
    }
  }, [id]);

  useEffect(() => { load(); }, [load]);
  usePoll(load, 10000);

  function set(field) {
    return (e) => setEdit({ ...edit, [field]: e.target.value });
  }

  function setLog(field) {
    return (e) => setLogForm({ ...logForm, [field]: e.target.value });
  }

  async function handleSave(e) {
    e.preventDefault();
    setError('');
    setBusy(true);

    try {
      const data = await apiPost(`/projects/update.php?id=${id}`, {
        ...edit,
        budget: Number(edit.budget),
        progress_percent: Number(edit.progress_percent),
      });
      setProject(data.project);
      showToast('Project details saved.');
    } catch (err) {
      setError(err.message);
    } finally {
      setBusy(false);
    }
  }

  async function handleUpload(e) {
    e.preventDefault();
    setError('');

    if (!file) {
      setError('Choose a file to upload.');
      return;
    }

    setBusy(true);
    try {
      const formData = new FormData();
      formData.append('project_id', id);
      formData.append('file', file);
      formData.append('display_name', displayName);

      await apiUpload('/projects/deliverable-upload.php', formData);

      setFile(null);
      setDisplayName('');
      if (fileRef.current) {
        fileRef.current.value = '';
      }
      await load();
      showToast('Deliverable uploaded.');
    } catch (err) {
      setError(err.message);
    } finally {
      setBusy(false);
    }
  }

  async function handleLogHours(e) {
    e.preventDefault();
    setError('');
    setBusy(true);

    try {
      const data = await apiPost('/costs/log-hours.php', {
        project_id: Number(id),
        hours: Number(logForm.hours),
        hourly_rate: Number(logForm.hourly_rate),
        note: logForm.note,
      });

      setLogs((list) => [data.log, ...list]);
      setCost(data.project_cost);
      setLogForm({ hours: '', hourly_rate: logForm.hourly_rate, note: '' });
      showToast(
        `Logged ${data.log.hours}h — ${formatMoney(data.log.cost)} deducted. Client balance: ${formatMoney(data.balance)}.`
      );
    } catch (err) {
      setError(err.message);
    } finally {
      setBusy(false);
    }
  }

  async function handleDeleteLog(log) {
    if (!window.confirm(`Delete time log (${Number(log.hours)}h on ${formatDate(log.logged_at)})? The amount returns to the client's balance.`)) {
      return;
    }

    try {
      const data = await apiPost('/costs/delete-log.php', { id: log.id });
      setLogs((list) => list.filter((item) => item.id !== log.id));
      setCost(data.project_cost);
      showToast('Time log deleted — the amount returned to the client balance.');
      setError('');
    } catch (err) {
      setError(err.message);
    }
  }

  async function handleDeleteDeliverable(d) {
    if (!window.confirm(`Delete "${d.display_name}" (v${d.version})?`)) {
      return;
    }

    try {
      await apiPost('/projects/deliverable-delete.php', { id: d.id });
      setDeliverables((list) => list.filter((item) => item.id !== d.id));
      showToast('Deliverable deleted.');
      setError('');
    } catch (err) {
      setError(err.message);
    }
  }

  async function handleDeleteProject() {
    if (!window.confirm('Delete this project and all of its deliverables?')) {
      return;
    }

    try {
      await apiPost(`/projects/delete.php?id=${id}`);
      showToast('Project deleted.');
      navigate('/admin', { replace: true });
    } catch (err) {
      setError(err.message);
    }
  }

  if (!project || !edit) {
    return (
      <DashboardShell subtitle="Project">
        <p className="text-muted-light">{error || 'Loading…'}</p>
      </DashboardShell>
    );
  }

  return (
    <DashboardShell subtitle={project.title}>
      <div className="d-flex justify-content-between align-items-center mb-3">
        <div className="d-flex align-items-center gap-3">
          <Link to="/admin" className="text-muted-light small">← Back to dashboard</Link>
          <StatusBadge status={project.status} />
          <span className="text-muted-light small">
            Client: {project.client_company ? `${project.client_company} — ` : ''}{project.client_name} ({project.client_email})
          </span>
        </div>
        <div className="d-flex gap-2">
          <Link to={`/reports/project/${id}`} className="btn btn-outline-light btn-sm">Print Report</Link>
          <button type="button" className="btn btn-outline-danger btn-sm" onClick={handleDeleteProject}>
            Delete Project
          </button>
        </div>
      </div>

      {error && <div className="alert alert-danger py-2">{error}</div>}

      <div className="row g-4">
        <div className="col-lg-7">
          <div className="adhub-card p-4 mb-4">
            <h2 className="h5 mb-3">Project Details</h2>

            <form onSubmit={handleSave}>
              <div className="mb-3">
                <label className="form-label" htmlFor="title">Project Title</label>
                <input id="title" className="form-control" value={edit.title} onChange={set('title')} required autoComplete="off" />
              </div>

              <div className="row">
                <div className="col-md-4 mb-3">
                  <label className="form-label" htmlFor="service_type">Service Type</label>
                  <select id="service_type" className="form-select" value={edit.service_type} onChange={set('service_type')} autoComplete="off">
                    <option value="">— Not set —</option>
                    {SERVICE_TYPES.map((s) => <option key={s}>{s}</option>)}
                  </select>
                </div>
                <div className="col-md-4 mb-3">
                  <label className="form-label" htmlFor="budget">Budget ($)</label>
                  <input id="budget" type="number" min="0" step="0.01" className="form-control" value={edit.budget} onChange={set('budget')} required autoComplete="off" />
                </div>
                <div className="col-md-4 mb-3">
                  <label className="form-label" htmlFor="progress_percent">Progress %</label>
                  <input id="progress_percent" type="number" min="0" max="100" className="form-control" value={edit.progress_percent} onChange={set('progress_percent')} required autoComplete="off" />
                </div>
              </div>

              <div className="mb-3">
                <label className="form-label" htmlFor="status">Status</label>
                <select id="status" className="form-select" value={edit.status} onChange={set('status')} autoComplete="off">
                  {PROJECT_STATUSES.map((s) => <option key={s} value={s}>{STATUS_LABELS[s]}</option>)}
                </select>
              </div>

              <div className="mb-4">
                <label className="form-label" htmlFor="description">Brief Details &amp; Instructions</label>
                <textarea id="description" className="form-control" rows="6" value={edit.description} onChange={set('description')} autoComplete="off" />
              </div>

              <button type="submit" className="btn btn-adhub" disabled={busy}>
                {busy ? 'Saving…' : 'Save Changes'}
              </button>
            </form>
          </div>

          <div className="adhub-card p-4">
            <div className="d-flex justify-content-between align-items-center mb-3">
              <h2 className="h5 mb-0">Time Logs</h2>
              <span className="text-muted-light">Billed: <span className="fw-bold text-white">{formatMoney(cost)}</span></span>
            </div>

            {logs.length === 0 ? (
              <p className="text-muted-light mb-0">No hours logged yet.</p>
            ) : (
              <div className="table-responsive">
                <table className="adhub-table">
                  <thead>
                    <tr>
                      <th>Hours</th>
                      <th>Rate</th>
                      <th className="text-end">Cost</th>
                      <th>Note</th>
                      <th>Logged By</th>
                      <th>Date</th>
                      <th className="text-end">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {logs.map((l) => (
                      <tr key={l.id}>
                        <td>{Number(l.hours)}h</td>
                        <td>{formatMoney(l.hourly_rate)}</td>
                        <td className="text-end">{formatMoney(Number(l.hours) * Number(l.hourly_rate))}</td>
                        <td className="text-muted-light">{l.note || '—'}</td>
                        <td className="text-muted-light">{l.admin_name}</td>
                        <td className="text-muted-light">{formatDate(l.logged_at)}</td>
                        <td className="text-end">
                          <button
                            type="button"
                            className="btn btn-outline-danger btn-sm"
                            onClick={() => handleDeleteLog(l)}
                          >
                            Delete
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>

          <div className="adhub-card p-4 mt-4">
            <h2 className="h5 mb-3">Project Chat</h2>
            <ChatThread key={project.id} projectId={project.id} />
          </div>
        </div>

        <div className="col-lg-5">
          <div className="adhub-card p-4 mb-4">
            <h2 className="h5 mb-3">Log Hours</h2>

            <form onSubmit={handleLogHours}>
              <div className="row">
                <div className="col-6 mb-3">
                  <label className="form-label" htmlFor="hours">Hours</label>
                  <input id="hours" type="number" min="0.25" step="0.25" className="form-control" placeholder="e.g. 2.5" value={logForm.hours} onChange={setLog('hours')} required autoComplete="off" />
                </div>
                <div className="col-6 mb-3">
                  <label className="form-label" htmlFor="hourly_rate">Hourly Rate ($)</label>
                  <input id="hourly_rate" type="number" min="0.01" step="0.01" className="form-control" value={logForm.hourly_rate} onChange={setLog('hourly_rate')} required autoComplete="off" />
                </div>
              </div>

              <div className="mb-3">
                <label className="form-label" htmlFor="log_note">What was done?</label>
                <input id="log_note" className="form-control" placeholder="e.g. Drafting hero banners" value={logForm.note} onChange={setLog('note')} autoComplete="off" />
              </div>

              <button type="submit" className="btn btn-adhub btn-sm" disabled={busy}>
                {busy ? 'Logging…' : 'Log Hours'}
              </button>
              <p className="small text-muted-light mt-2 mb-0">
                Cost is deducted from the client&apos;s retainer automatically.
              </p>
            </form>
          </div>

          <div className="adhub-card p-4 mb-4">
            <h2 className="h5 mb-3">Upload Deliverable</h2>

            <form onSubmit={handleUpload}>
              <div className="mb-3">
                <label className="form-label" htmlFor="file">File (max 25 MB)</label>
                <input
                  id="file"
                  ref={fileRef}
                  type="file"
                  className="form-control"
                  accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.ppt,.pptx,.txt,.zip"
                  onChange={(e) => setFile(e.target.files[0] ?? null)}
                />
              </div>

              <div className="mb-3">
                <label className="form-label" htmlFor="display_name">Display Name (optional)</label>
                <input
                  id="display_name"
                  className="form-control"
                  placeholder="Defaults to the file name"
                  value={displayName}
                  onChange={(e) => setDisplayName(e.target.value)}
                  autoComplete="off"
                />
              </div>

              <button type="submit" className="btn btn-adhub btn-sm" disabled={busy}>
                {busy ? 'Working…' : 'Upload'}
              </button>
            </form>
          </div>

          <div className="adhub-card p-4">
            <h2 className="h5 mb-3">Deliverables</h2>
            <DeliverablesTable deliverables={deliverables} onDelete={handleDeleteDeliverable} />
          </div>
        </div>
      </div>
    </DashboardShell>
  );
}
