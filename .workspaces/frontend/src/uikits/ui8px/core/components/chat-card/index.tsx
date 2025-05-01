import React from 'react';
import { ChatCardProps } from './interface';

/**
 * Chat Card component for displaying content in chat
 * Follows the 8px design system
 */
export const ChatCard: React.FC<ChatCardProps> = ({
  variant = "default",
  children,
  title,
  actions,
  language,
  className = "",
}) => {
  return (
    <div className={`chat-card chat-card-${variant} ${className}`}>
      {title && (
        <div className={`chat-card-header chat-card-header-${variant}`}>
          {typeof title === 'string' ? (
            <h3 className="chat-card-title text-base font-medium">{title}</h3>
          ) : (
            title
          )}
        </div>
      )}

      <div className={`chat-card-content chat-card-content-${variant}`}>
        {variant === 'code' ? (
          <pre>
            <code className={language ? `language-${language}` : ''}>
              {children}
            </code>
          </pre>
        ) : (
          children
        )}
      </div>

      {actions && (
        <div className={`chat-card-footer chat-card-footer-${variant}`}>
          <div className="chat-card-actions">
            {actions}
          </div>
        </div>
      )}
    </div>
  );
};

/**
 * Chat Card File component for displaying file attachments
 */
export const ChatCardFile: React.FC<{
  filename: string;
  filesize?: string;
  icon?: React.ReactNode;
  onDownload?: () => void;
  className?: string;
}> = ({
  filename,
  filesize,
  icon,
  onDownload,
  className = "",
}) => {
    return (
      <div className={`chat-card-file flex items-center gap-x-2 p-3 ${className}`}>
        {icon && (
          <div className="chat-card-file-icon">
            {icon}
          </div>
        )}
        <div className="w-full flex justify-between truncate">
          <span className="me-3 flex-1 w-0 truncate">
            {filename}
            {filesize && (
              <span className="text-xs text-muted-foreground ms-2">
                {filesize}
              </span>
            )}
          </span>
          {onDownload && (
            <button
              type="button"
              className="flex items-center gap-x-2 text-muted-foreground hover:text-primary focus:outline-none focus:text-primary whitespace-nowrap dark:hover:text-primary dark:focus:text-primary"
              onClick={onDownload}
            >
              Download
            </button>
          )}
        </div>
      </div>
    );
  }; 