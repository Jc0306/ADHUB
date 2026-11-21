import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext.jsx';

export default function Register() {
  const { register } = useAuth();
  const navigate = useNavigate();
  const [form, setForm] = useState({ full_name: '', email: '', company_name: '', password: '' });
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);

  function set(field) {
    return (e) => setForm({ ...form, [field]: e.target.value });
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setError('');
    setBusy(true);

    try {
      const user = await register(form);
      navigate(user.role === 'admin' ? '/admin' : '/client', { replace: true });
    } catch (err) {
      setError(err.message);
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="min-vh-100 d-flex align-items-center justify-content-center px-3 py-4">
      <div className="adhub-card p-4 p-md-5" style={{ width: '100%', maxWidth: 480 }}>
        <h1 className="h3 fw-bold text-center mb-1">Create Account</h1>
        <p className="text-center text-muted-light mb-4">Register as a corporate brand client</p>

        {error && <div className="alert alert-danger py-2">{error}</div>}

        <form onSubmit={handleSubmit}>
          <div className="mb-3">
            <label className="form-label" htmlFor="full_name">Full Name</label>
            <input id="full_name" className="form-control" value={form.full_name} onChange={set('full_name')} required autoComplete="name" />
          </div>

          <div className="mb-3">
            <label className="form-label" htmlFor="email">Email Address (Gmail only)</label>
            <input id="email" type="email" className="form-control" value={form.email} onChange={set('email')} required autoComplete="email" spellCheck={false} />
          </div>

          <div className="mb-3">
            <label className="form-label" htmlFor="company_name">Company Name</label>
            <input id="company_name" className="form-control" value={form.company_name} onChange={set('company_name')} autoComplete="organization" />
          </div>

          <div className="mb-4">
            <label className="form-label" htmlFor="password">Password</label>
            <input id="password" type="password" className="form-control" value={form.password} onChange={set('password')} required autoComplete="new-password" />
          </div>

          <button type="submit" className="btn btn-adhub w-100" disabled={busy}>
            {busy ? 'Creating account…' : 'Register'}
          </button>
        </form>

        <p className="text-center text-muted-light mt-4 mb-0">
          Already registered?{' '}
          <Link to="/login" className="text-white">Login here</Link>
        </p>
      </div>
    </div>
  );
}
