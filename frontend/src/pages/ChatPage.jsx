import { useEffect, useState } from 'react';
import DashboardShell from '../components/DashboardShell.jsx';
import ChatThread from '../components/ChatThread.jsx';
import { apiGet } from '../api/client.js';
import { useAuth } from '../context/AuthContext.jsx';
import { formatDate } from '../constants.js';

const POLL_MS = 5000;

const threadKey = (t) => `${t.client_id}:${t.project_id ?? 'general'}`;
const threadLabel = (t) => (t.project_id === null ? 'General' : t.project_title);

/**
 * Shared chat page (both roles) — thread list on the left (each client's
 * General chat plus one thread per project), conversation on the right.
 */
export default function ChatPage() {
  const { user } = useAuth();
  const [threads, setThreads] = useState([]);
  const [selectedKey, setSelectedKey] = useState(null);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let active = true;

    async function load() {
      const data = await apiGet('/chat/threads.php');
      if (!active) {
        return;
      }
      setThreads(data.threads);
      setSelectedKey((cur) => cur ?? (data.threads[0] ? threadKey(data.threads[0]) : null));
    }

    load()
      .catch((e) => active && setError(e.message))
      .finally(() => active && setLoading(false));

    const timer = setInterval(() => {
      if (!document.hidden) {
        load().catch(() => {});
      }
    }, POLL_MS);

    return () => {
      active = false;
      clearInterval(timer);
    };
  }, []);

  function selectThread(t) {
    setSelectedKey(threadKey(t));
    // Optimistically clear the unread badge — the thread fetch marks it read.
    setThreads((list) =>
      list.map((item) => (threadKey(item) === threadKey(t) ? { ...item, unread: 0 } : item))
    );
  }

  const isAdmin = user?.role === 'admin';
  // Fall back to the first thread if the selected one no longer exists.
  const effectiveKey = threads.some((t) => threadKey(t) === selectedKey)
    ? selectedKey
    : (threads[0] ? threadKey(threads[0]) : null);
  const selected = threads.find((t) => threadKey(t) === effectiveKey) ?? null;

  return (
    <DashboardShell subtitle="Chat">
      {error && <div className="alert alert-danger py-2">{error}</div>}

      {loading ? (
        <p className="text-muted-light">Loading…</p>
      ) : threads.length === 0 ? (
        <div className="adhub-card p-4 text-muted-light">Nothing to chat about yet.</div>
      ) : (
        <div className="row g-4">
          <div className="col-lg-4">
            <div className="d-flex flex-column gap-2">
              {threads.map((t) => (
                <button
                  key={threadKey(t)}
                  type="button"
                  className={`chat-thread-btn${threadKey(t) === effectiveKey ? ' active' : ''}`}
                  onClick={() => selectThread(t)}
                >
                  <div className="d-flex justify-content-between align-items-center gap-2">
                    <span className="fw-semibold">
                      {isAdmin ? t.company_name || t.full_name : threadLabel(t)}
                    </span>
                    {t.unread > 0 && <span className="badge text-bg-warning">{t.unread}</span>}
                  </div>
                  <div className="small text-muted-light">
                    {isAdmin ? `${threadLabel(t)} · ` : ''}
                    {t.last_message_at ? formatDate(t.last_message_at) : 'No messages yet'}
                  </div>
                </button>
              ))}
            </div>

            {!isAdmin && !threads.some((t) => t.project_id !== null) && (
              <p className="small text-muted-light mt-3 mb-0">
                Project chats appear here once the agency creates a campaign for you.
              </p>
            )}
          </div>

          <div className="col-lg-8">
            {selected && (
              <div className="adhub-card p-3">
                <div className="fw-bold mb-2">
                  {isAdmin
                    ? `${selected.company_name || selected.full_name} — ${threadLabel(selected)}`
                    : `ADHUB Team · ${threadLabel(selected)}`}
                </div>
                <ChatThread
                  key={effectiveKey}
                  clientId={selected.client_id}
                  projectId={selected.project_id}
                />
              </div>
            )}
          </div>
        </div>
      )}
    </DashboardShell>
  );
}
