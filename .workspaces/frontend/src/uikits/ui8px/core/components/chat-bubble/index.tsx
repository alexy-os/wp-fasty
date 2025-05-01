import React from 'react';
import { ChatBubbleProps } from './interface';

/**
 * Chat Bubble component for messaging interfaces
 * Follows the 8px design system
 */
export const ChatBubble: React.FC<ChatBubbleProps> = ({
  sender = "user",
  children,
  avatar,
  actions,
  isThread = false,
  className = "",
}) => {
  return (
    <div className={`chat-bubble chat-bubble-${sender} ${className}`}>
      {sender === "user" && (
        <div className="chat-bubble-container chat-bubble-container-user">
          <div className="chat-bubble-content chat-bubble-content-user">
            {children}
          </div>
          {actions && (
            <div className="chat-bubble-actions">
              {actions}
            </div>
          )}
        </div>
      )}

      {avatar && (
        <div className="chat-bubble-avatar">
          {avatar}
        </div>
      )}

      {sender === "ai" && (
        <div className="chat-bubble-container chat-bubble-container-ai">
          <div className="chat-bubble-content chat-bubble-content-ai">
            {children}
          </div>
          {actions && (
            <div className="chat-bubble-actions">
              {actions}
            </div>
          )}
        </div>
      )}
    </div>
  );
};

/**
 * Chat Bubble Text component
 */
export const ChatBubbleText: React.FC<{
  children: React.ReactNode;
  className?: string;
}> = ({
  children,
  className = ""
}) => {
    return (
      <p className={`chat-bubble-text text-sm ${className}`}>
        {children}
      </p>
    );
  }; 