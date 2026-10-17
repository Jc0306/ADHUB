import { Fragment, useState } from 'react';
import ApprovalBadge from './ApprovalBadge.jsx';
import { formatDate } from '../constants.js';

/**
 * Deliverable files table.
 * Always shows review status; review buttons appear when onApprove is passed
 * (client view) and only for the latest version of a pending deliverable.
 */
export default function DeliverablesTable({ deliverables, onDelete, onApprove, onRevision }) {
  const [reviewId, setReviewId] = useState(null);
  const [note, setNote] = useState('');
  const [working, setWorking] = useState(false);

  if (deliverables.length === 0) {
    return <p className="text-muted-light mb-0">No deliverables uploaded yet.</p>;
  }

  const latestVersion = {};
  deliverables.forEach((d) => {
    latestVersion[d.display_name] = Math.max(latestVersion[d.display_name] ?? 0, d.version);
  });

  async function run(action) {
    setWorking(true);
    try {
      await action();
      setReviewId(null);
      setNote('');
    } catch {
      // The parent page surfaces the error message.
    } finally {
      setWorking(false);
    }
  }

  return (
    <div className="table-responsive">
      <table className="adhub-table">
        <thead>
          <tr>
            <th>File</th>
            <th>Version</th>
            <th>Review</th>
            <th>Uploaded</th>
            <th className="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          {deliverables.map((d) => {
            const isLatest = latestVersion[d.display_name] === d.version;
            const canReview = Boolean(onApprove) && isLatest && d.approval_status === 'pending';

            return (
              <Fragment key={d.id}>
                <tr>
                  <td>
                    {d.display_name}
                    {d.review_note && (
                      <div className="small text-warning">Revision note: {d.review_note}</div>
                    )}
                  </td>
                  <td>
                    <span className="badge text-bg-secondary">v{d.version}</span>
                    {!isLatest && <span className="small text-muted-light ms-2">old</span>}
                  </td>
                  <td><ApprovalBadge status={d.approval_status} /></td>
                  <td className="text-muted-light">{formatDate(d.uploaded_at)}</td>
                  <td className="text-end">
                    <a
                      className="btn btn-outline-light btn-sm"
                      href={`/api/projects/deliverable-download.php?id=${d.id}`}
                    >
                      Download
                    </a>

                    {canReview && (
                      <>
                        <button
                          type="button"
                          className="btn btn-success btn-sm ms-2"
                          disabled={working}
                          onClick={() => run(() => onApprove(d))}
                        >
                          Approve
                        </button>
                        <button
                          type="button"
                          className="btn btn-outline-warning btn-sm ms-2"
                          disabled={working}
                          onClick={() => { setReviewId(d.id); setNote(''); }}
                        >
                          Request Revision
                        </button>
                      </>
                    )}

                    {onDelete && (
                      <button
                        type="button"
                        className="btn btn-outline-danger btn-sm ms-2"
                        onClick={() => onDelete(d)}
                      >
                        Delete
                      </button>
                    )}
                  </td>
                </tr>

                {reviewId === d.id && (
                  <tr>
                    <td colSpan={5}>
                      <div className="d-flex gap-2 align-items-start">
                        <textarea
                          className="form-control"
                          rows={2}
                          placeholder="What should the agency change?"
                          value={note}
                          onChange={(e) => setNote(e.target.value)}
                        />
                        <button
                          type="button"
                          className="btn btn-adhub btn-sm"
                          disabled={working}
                          onClick={() => run(() => onRevision(d, note))}
                        >
                          Send
                        </button>
                        <button
                          type="button"
                          className="btn btn-outline-light btn-sm"
                          onClick={() => setReviewId(null)}
                        >
                          Cancel
                        </button>
                      </div>
                    </td>
                  </tr>
                )}
              </Fragment>
            );
          })}
        </tbody>
      </table>
    </div>
  );
}
