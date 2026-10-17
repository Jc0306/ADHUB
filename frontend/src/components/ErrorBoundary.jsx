import { Component } from 'react';

/**
 * Safety net for render errors: shows a readable message + reload button
 * instead of a blank white page.
 */
export default class ErrorBoundary extends Component {
  state = { error: null };

  static getDerivedStateFromError(error) {
    return { error };
  }

  render() {
    if (this.state.error) {
      return (
        <div className="container py-5" style={{ maxWidth: 640 }}>
          <div className="adhub-card p-4">
            <h1 className="h5 fw-bold mb-2">Something went wrong on this page</h1>
            <p className="text-muted-light mb-4">
              {String(this.state.error?.message ?? this.state.error)}
            </p>
            <button
              type="button"
              className="btn btn-adhub"
              onClick={() => window.location.reload()}
            >
              Reload page
            </button>
          </div>
        </div>
      );
    }

    return this.props.children;
  }
}
