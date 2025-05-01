import React from 'react';
import { QuickActionsProps, ActionItem } from './interface';

/**
 * Quick Actions component for action buttons
 * Follows the 8px design system
 */
export const QuickActions: React.FC<QuickActionsProps> = ({
  variant = "horizontal",
  size = "default",
  style = "default",
  actions = [],
  onActionClick,
  className = "",
}) => {
  const handleClick = (action: ActionItem) => {
    if (!action.disabled && onActionClick) {
      onActionClick(action.id);
    }
  };

  return (
    <div className={`quick-actions quick-actions-${variant} ${className}`}>
      {actions.map((action) => (
        <button
          key={action.id}
          type="button"
          className={`quick-action-button quick-action-button-${style} quick-action-button-${size}`}
          onClick={() => handleClick(action)}
          disabled={action.disabled}
          data-action-id={action.id}
        >
          {action.icon && <span className="quick-action-icon">{action.icon}</span>}
          <span className="quick-action-label">{action.label}</span>
        </button>
      ))}
    </div>
  );
}; 