import { useEffect, useRef, useState } from 'react';
import { useAuth } from '../context/AuthContext.jsx';
import { apiGet, apiPost } from '../api/client.js';
import { formatDate } from '../constants.js';

const POLL_MS = 3000;

/**
 * Chat conversation view with ~3s polling. Admins pass the selected
 * clientId; clients omit it (their own thread is implied). Parents should
 * pass a `key` per thread so switching threads remounts fresh state.
 */
export default function ChatThread({ clientId = null }) {
  const { user } = useAuth();
  const [messages, setMessages] = useState([]);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);
  const [draft, setDraft] = useState('');
  const [sending, setSending] = useState(false);

  const lastIdRef = useRef(0);
  const bottomRef = useRef(null);
  const countRef = useRef(0);

  useEffect(() => {
    let cancelled = false;

    async function load(afterId) {
      const params = new URLSearchParams();
      if (clientId != null) {
        params.set('client_id', clientId);
      }
      if (afterId > 0) {
        params.set('after_id', afterId);
      }

      const data = await apiGet(`/chat/list.php?${params.toString()}`);
      if (cancelled) {
        return;
      }

      if (data.messages.length > 0) {
        lastIdRef.current = Math.max(lastIdRef.current, ...data.messages.map((m) => m.id));
        setMessages((prev) => {
          const seen = new Set(prev.map((m) => m.id));
          return [...prev, ...data.messages.filter((m) => !seen.has(m.id))];
        });
      }
    }

    load(0)
      .then(() => !cancelled && setError(''))
      .catch((e) => !cancelled && setError(e.message))
      .finally(() => !cancelled && setLoading(false));

    const timer = setInterval(() => {
      if (document.hidden) {
        return;
      }
      load(lastIdRef.current).catch((e) => !cancelled && setError(e.message));
    }, POLL_MS);

    return () => {
      cancelled = true;
      clearInterval(timer);
    };
  }, [clientId]);

  // Keep the newest message in view (instant on thread switch, smooth after).
  useEffect(() => {
    const firstFill = countRef.current === 0;
    countRef.current = messages.length;
    if (messages.length > 0) {
      bottomRef.current?.scrollIntoView({ behavior: firstFill ? 'auto' : 'smooth' });
    }
  }, [messages]);

  async function handleSend(e) {
    if (e) {
      e.preventDefault();
    }
    const body = draft.trim();
    if (!body || sending) {
      return;
    }

    setSending(true);
    try {
      const payload = { body };
      if (clientId != null) {
        payload.client_id = clientId;
      }

      const data = await apiPost('/chat/send.php', payload);
      lastIdRef.current = Math.max(lastIdRef.current, data.message.id);
      setMessages((prev) =>
        prev.some((m) => m.id === data.message.id) ? prev : [...prev, data.message]
      );
      setDraft('');
      setError('');
    } catch (err) {
      setError(err.message);
    } finally {
      setSending(false);
    }
  }

  function handleKeyDown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSend();
    }
  }

  return (
    <>
      {error && <div className="alert alert-danger py-2">{error}</div>}

      <div className="chat-window">
        {loading ? (
          <p className="text-muted-light">Loading…</p>
        ) : messages.length === 0 ? (
          <p className="text-muted-light mb-0">No messages yet — say hello.</p>
        ) : (
          messages.map((msg) => {
            const mine = msg.sender_id === user?.id;
            return (
              <div
                key={msg.id}
                className={`d-flex ${mine ? 'justify-content-end' : 'justify-content-start'} mb-2`}
              >
                <div className={`chat-bubble${mine ? ' chat-bubble-mine' : ''}`}>
                  <div className="chat-body">{msg.body}</div>
                  <div className="chat-meta">{formatDate(msg.created_at)}</div>
                </div>
              </div>
            );
          })
        )}
        <div ref={bottomRef} />
      </div>

      <form className="d-flex gap-2 mt-3" onSubmit={handleSend}>
        <textarea
          className="form-control"
          rows="2"
          placeholder="Write a message… (Enter to send, Shift+Enter for a new line)"
          value={draft}
          onChange={(e) => setDraft(e.target.value)}
          onKeyDown={handleKeyDown}
        />
        <button
          type="submit"
          className="btn btn-adhub align-self-end"
          disabled={sending || !draft.trim()}
        >
          {sending ? 'Sending…' : 'Send'}
        </button>
      </form>
    </>
  );
}
