import { Link } from 'react-router-dom';

/**
 * Reusable empty-state illustration.
 * @param {string} icon - Emoji or SVG icon
 * @param {string} title - Heading
 * @param {string} description - Explanatory text
 * @param {Object} action - { label, to } optional CTA link
 */
export default function EmptyState({ icon, title, description, action }) {
  return (
    <div className="adhub-card p-5 text-center">
      <div className="empty-icon mb-3" style={{ fontSize: '4rem', lineHeight: 1 }}>
        {icon}
      </div>
      <h3 className="h5 fw-bold mb-2">{title}</h3>
      <p className="text-muted-light mb-4" style={{ maxWidth: '320px', margin: '0 auto' }}>
        {description}
      </p>
      {action && (
        <Link to={action.to} className="btn btn-adhub">
          {action.label}
        </Link>
      )}
    </div>
  );
}