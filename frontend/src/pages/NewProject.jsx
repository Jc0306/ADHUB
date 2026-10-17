import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import DashboardShell from '../components/DashboardShell.jsx';
import { apiGet, apiPost } from '../api/client.js';
import { SERVICE_TYPES } from '../constants.js';

export default function NewProject() {
  const navigate = useNavigate();
  const [clients, setClients] = useState([]);
  const [form, setForm] = useState({
    client_id: '',
    title: '',
    service_type: SERVICE_TYPES[0],
    budget: '',
    description: '',
  });
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    apiGet('/users/clients.php')
      .then((d) => setClients(d.clients))
      .catch((e) => setError(e.message));
  }, []);

  function set(field) {
    return (e) => setForm({ ...form, [field]: e.target.value });
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setError('');
    setBusy(true);

    try {
      const data = await apiPost('/projects/create.php', {
        ...form,
        client_id: Number(form.client_id),
        budget: Number(form.budget),
      });
      navigate(`/admin/projects/${data.project.id}`, { replace: true });
    } catch (err) {
      setError(err.message);
      setBusy(false);
    }
  }

  return (
    <DashboardShell subtitle="New Campaign Project">
      <div className="row">
        <div className="col-lg-7">
          <div className="adhub-card p-4">
            {error && <div className="alert alert-danger py-2">{error}</div>}
            {clients.length === 0 && !error && (
              <div className="alert alert-warning py-2 mb-3">
                No client accounts yet — a client must register before you can create a project.
              </div>
            )}

            <form onSubmit={handleSubmit}>
              <div className="mb-3">
                <label className="form-label" htmlFor="client_id">Client</label>
                <select id="client_id" className="form-select" value={form.client_id} onChange={set('client_id')} required>
                  <option value="">Select a client…</option>
                  {clients.map((c) => (
                    <option key={c.id} value={c.id}>
                      {c.company_name ? `${c.company_name} — ${c.full_name}` : c.full_name} ({c.email})
                    </option>
                  ))}
                </select>
              </div>

              <div className="mb-3">
                <label className="form-label" htmlFor="title">Project Title</label>
                <input id="title" className="form-control" placeholder="e.g. Summer Sale Banner" value={form.title} onChange={set('title')} required />
              </div>

              <div className="row">
                <div className="col-md-6 mb-3">
                  <label className="form-label" htmlFor="service_type">Service Type</label>
                  <select id="service_type" className="form-select" value={form.service_type} onChange={set('service_type')}>
                    {SERVICE_TYPES.map((s) => <option key={s}>{s}</option>)}
                  </select>
                </div>
                <div className="col-md-6 mb-3">
                  <label className="form-label" htmlFor="budget">Budget ($)</label>
                  <input id="budget" type="number" min="0" step="0.01" className="form-control" placeholder="e.g. 300" value={form.budget} onChange={set('budget')} required />
                </div>
              </div>

              <div className="mb-4">
                <label className="form-label" htmlFor="description">Brief Details &amp; Instructions</label>
                <textarea id="description" className="form-control" rows="5" placeholder="Tell the team about this campaign…" value={form.description} onChange={set('description')} />
              </div>

              <div className="d-flex gap-2">
                <button type="submit" className="btn btn-adhub" disabled={busy}>
                  {busy ? 'Creating…' : 'Create Project'}
                </button>
                <Link to="/admin" className="btn btn-outline-light">Cancel</Link>
              </div>
            </form>
          </div>
        </div>
      </div>
    </DashboardShell>
  );
}
