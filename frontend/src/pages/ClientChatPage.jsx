import DashboardShell from '../components/DashboardShell.jsx';
import ChatThread from '../components/ChatThread.jsx';

/** Client area — a single conversation with the agency team. */
export default function ClientChatPage() {
  return (
    <DashboardShell subtitle="Chat with the ADHUB Team">
      <div className="row">
        <div className="col-lg-8">
          <div className="adhub-card p-3">
            <ChatThread />
          </div>
        </div>
      </div>
    </DashboardShell>
  );
}
