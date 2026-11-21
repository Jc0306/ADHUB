import { useEffect, useState } from 'react';
import DashboardShell from '../components/DashboardShell.jsx';
import ChatThread from '../components/ChatThread.jsx';
import { apiGet } from '../api/client.js';
import { useAuth } from '../context/AuthContext.jsx';
import { formatDate } from '../constants.js';
import EmptyState from '../components/EmptyState.jsx';

const POLL_MS = 5000;

/**
 * Shared chat page (both roles) — one thread per campaign project on the
 * left, the selected project's conversation on the right.
 */
export default function ChatPage() {
  const { user } = useAuth();
  const [threads, setThreads] = useState([]);
  const [selectedId, setSelectedId] = useState(null);
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
      setSelectedId((cur) => cur ?? (data.threads[0] ? data.threads[0].project_id : null));
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
    setSelectedId(t.project_id);
    // Optimistically clear the unread badge — the thread fetch marks it read.
    setThreads((list) =>
      list.map((item) => (item.project_id === t.project_id ? { ...item, unread: 0 } : item))
    );
  }

  const isAdmin = user?.role === 'admin';
  // Fall back to the first thread if the selected one no longer exists.
  const effectiveId = threads.some((t) => t.project_id === selectedId)
    ? selectedId
    : (threads[0] ? threads[0].project_id : null);
  const selected = threads.find((t) => t.project_id === effectiveId) ?? null;

  return (
    <DashboardShell subtitle="Chat">
      {error && <div className="alert alert-danger py-2">{error}</div>}

      {loading ? (
        <p className="text-muted-light">Loading…</p>
      ) : threads.length === 0 ? (
        <EmptyState
          icon="💬"
          title={isAdmin ? 'No chat threads yet' : 'No chats yet'}
          description={isAdmin
            ? 'Every campaign project gets its own chat thread. Accept a service request to create its project.'
            : 'Once the agency starts a campaign for you, its chat appears here.'}
          action={isAdmin ? { label: 'View Requests', to: '/admin/requests' } : null}
        />
      ) : (
        <div className="row g-4">
          <div className="col-lg-4">
            <div className="d-flex flex-column gap-2">
              {threads.map((t) => (
                <button
                  key={t.project_id}
                  type="button"
                  className={`chat-thread-btn${t.project_id === effectiveId ? ' active' : ''}`}
                  onClick={() => selectThread(t)}
                >
                  <div className="d-flex justify-content-between align-items-center gap-2">
                    <span className="fw-semibold">{t.project_title}</span>
                    {t.unread > 0 && <span className="badge text-bg-warning">{t.unread}</span>}
                  </div>
                  <div className="small text-muted-light">
                    {isAdmin ? `${t.company_name || t.full_name} · ` : ''}
                    {t.last_message_at ? formatDate(t.last_message_at) : 'No messages yet'}
                  </div>
                </button>
              ))}
            </div>
          </div>

          <div className="col-lg-8">
            {selected && (
              <div className="adhub-card p-3">
                <div className="fw-bold mb-2">
                  {isAdmin
                    ? `${selected.company_name || selected.full_name} — ${selected.project_title}`
                    : `ADHUB Team · ${selected.project_title}`}
                </div>
                <ChatThread key={effectiveId} projectId={selected.project_id} />
              </div>
            )}
          </div>
        </div>
      )}
    </DashboardShell>
  );
}
