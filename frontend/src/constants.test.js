import { describe, it, expect } from 'vitest';
import { formatMoney, formatDate, STATUS_LABELS, REQUEST_STATUS_LABELS } from './constants.js';

describe('constants', () => {
  describe('formatMoney', () => {
    it('formats positive numbers with 2 decimals', () => {
      expect(formatMoney(100)).toBe('$100.00');
      expect(formatMoney(100.5)).toBe('$100.50');
      expect(formatMoney(0)).toBe('$0.00');
    });

    it('formats negative numbers', () => {
      expect(formatMoney(-50.25)).toBe('-$50.25');
    });

    it('handles string input', () => {
      expect(formatMoney('250')).toBe('$250.00');
    });
  });

  describe('formatDate', () => {
    it('formats ISO date string to locale date', () => {
      const result = formatDate('2026-01-15 14:30:00');
      expect(result).toContain('2026');
      expect(result).toContain('1'); // month or day depending on locale
    });
  });

  describe('STATUS_LABELS', () => {
    it('has labels for all project statuses', () => {
      expect(STATUS_LABELS['in-progress']).toBe('In Progress');
      expect(STATUS_LABELS.revision).toBe('Revision');
      expect(STATUS_LABELS.completed).toBe('Completed');
    });
  });

  describe('REQUEST_STATUS_LABELS', () => {
    it('has labels for all request statuses', () => {
      expect(REQUEST_STATUS_LABELS.open).toBe('Open');
      expect(REQUEST_STATUS_LABELS.accepted).toBe('Accepted');
      expect(REQUEST_STATUS_LABELS.done).toBe('Done');
    });
  });
});