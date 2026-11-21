import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext.jsx';
import { showToast } from '../toast.js';

export default function Register() {
  const { register } = useAuth();
  const navigate = useNavigate();
  const [form, setForm] = useState({ full_name: '', email: '', company_name: '', password: '', password_confirm: '' });
  const [errors, setErrors] = useState({});
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);

  function set(field) {
    return (e) => setForm({ ...form, [field]: e.target.value });
  }

  function validate() {
    const newErrors = {};
    if (!form.full_name.trim()) newErrors.full_name = 'Full name is required';
    if (!form.email.trim()) newErrors.email = 'Email is required';
    else if (!form.email.endsWith('@gmail.com')) newErrors.email = 'Only @gmail.com addresses are allowed';
    if (!form.password) newErrors.password = 'Password is required';
    else if (form.password.length < 8) newErrors.password = 'Password must be at least 8 characters';
    if (form.password !== form.password_confirm) newErrors.password_confirm = 'Passwords do not match';
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setError('');
    if (!validate()) return;
    setBusy(true);

    try {
      // eslint-disable-next-line no-unused-vars
      const { password_confirm: _passwordConfirm, ...submitData } = form;
      const user = await register(submitData);
      showToast('Account created successfully');
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
            <input id="full_name" className={`form-control ${errors.full_name ? 'is-invalid' : ''}`} value={form.full_name} onChange={set('full_name')} required autoComplete="name" />
            {errors.full_name && <div className="invalid-feedback">{errors.full_name}</div>}
          </div>

          <div className="mb-3">
            <label className="form-label" htmlFor="email">Email Address (Gmail only)</label>
            <input id="email" type="email" className={`form-control ${errors.email ? 'is-invalid' : ''}`} value={form.email} onChange={set('email')} required autoComplete="email" spellCheck={false} />
            {errors.email && <div className="invalid-feedback">{errors.email}</div>}
          </div>

          <div className="mb-3">
            <label className="form-label" htmlFor="company_name">Company Name</label>
            <input id="company_name" className="form-control" value={form.company_name} onChange={set('company_name')} autoComplete="organization" />
          </div>

          <div className="mb-3">
            <label className="form-label" htmlFor="password">Password</label>
            <input id="password" type="password" className={`form-control ${errors.password ? 'is-invalid' : ''}`} value={form.password} onChange={set('password')} required autoComplete="new-password" />
            {errors.password && <div className="invalid-feedback">{errors.password}</div>}
          </div>

          <div className="mb-4">
            <label className="form-label" htmlFor="password_confirm">Confirm Password</label>
            <input id="password_confirm" type="password" className={`form-control ${errors.password_confirm ? 'is-invalid' : ''}`} value={form.password_confirm} onChange={set('password_confirm')} required autoComplete="new-password" />
            {errors.password_confirm && <div className="invalid-feedback">{errors.password_confirm}</div>}
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
