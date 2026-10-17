import { useEffect, useState } from 'react';
import DashboardShell from '../components/DashboardShell.jsx';
import ChatThread from '../components/ChatThread.jsx';
import { apiGet } from '../api/client.js';
import { formatDate } from '../constants.js';

const POLL_MS = 5000;

/** Admin area — all client conversations, one thread at a time. */
export default function AdminChatPage() {
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
      setSelectedId((cur) => (cur != null ? cur : data.threads[0]?.client_id ?? null));
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
    setSelectedId(t.client_id);
    // Optimistically clear the unread badge — the thread fetch marks it read.
    setThreads((list) =>
      list.map((item) => (item.client_id === t.client_id ? { ...item, unread: 0 } : item))
    );
  }

  const selected = threads.find((t) => t.client_id === selectedId) ?? null;

  return (
    <DashboardShell subtitle="Chat">
      {error && <div className="alert alert-danger py-2">{error}</div>}

      {loading ? (
        <p className="text-muted-light">Loading…</p>
      ) : threads.length === 0 ? (
        <div className="adhub-card p-4 text-muted-light">
          No client accounts yet — chat starts once a client registers.
        </div>
      ) : (
        <div className="row g-4">
          <div className="col-lg-4">
            <div className="d-flex flex-column gap-2">
              {threads.map((t) => (
                <button
                  key={t.client_id}
                  type="button"
                  className={`chat-thread-btn${t.client_id === selectedId ? ' active' : ''}`}
                  onClick={() => selectThread(t)}
                >
                  <div className="d-flex justify-content-between align-items-center gap-2">
                    <span className="fw-semibold">{t.company_name || t.full_name}</span>
                    {t.unread > 0 && <span className="badge text-bg-warning">{t.unread}</span>}
                  </div>
                  <div className="small text-muted-light">
                    {t.company_name ? `${t.full_name} · ` : ''}
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
                  {selected.company_name
                    ? `${selected.company_name} — ${selected.full_name}`
                    : selected.full_name}
                </div>
                <ChatThread key={selected.client_id} clientId={selected.client_id} />
              </div>
            )}
          </div>
        </div>
      )}
    </DashboardShell>
  );
}
