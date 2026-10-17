import { createContext, useCallback, useContext, useEffect, useState } from 'react';
import { apiGet, apiPost } from '../api/client.js';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  // Restore the session on app load.
  useEffect(() => {
    apiGet('/auth/session.php')
      .then((data) => setUser(data.user))
      .catch(() => setUser(null))
      .finally(() => setLoading(false));
  }, []);

  const login = useCallback(async (email, password) => {
    const data = await apiPost('/auth/login.php', { email, password });
    setUser(data.user);
    return data.user;
  }, []);

  const register = useCallback(async (fields) => {
    const data = await apiPost('/auth/register.php', fields);
    setUser(data.user);
    return data.user;
  }, []);

  const logout = useCallback(async () => {
    await apiPost('/auth/logout.php');
    setUser(null);
  }, []);

  return (
    <AuthContext.Provider value={{ user, loading, login, register, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);

  if (!ctx) {
    throw new Error('useAuth must be used inside AuthProvider');
  }

  return ctx;
}
