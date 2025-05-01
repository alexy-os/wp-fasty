import React, { useEffect, useRef } from 'react';
import { ChatContainerProps, ChatMessage } from './interface';

// Import components we need from relative paths
// In a real implementation these would be proper imports
const ChatBubble = ({ sender, avatar, children, actions }: {
  sender: "user" | "ai";
  avatar?: React.ReactNode;
  children: React.ReactNode;
  actions?: React.ReactNode;
}) => (
  <div className={`chat-bubble chat-bubble-${sender}`}>
    {sender === "user" && (
      <div className="chat-bubble-container chat-bubble-container-user">
        <div className="chat-bubble-content chat-bubble-content-user">
          {children}
        </div>
        {actions && <div className="chat-bubble-actions">{actions}</div>}
      </div>
    )}
    {avatar && <div className="chat-bubble-avatar">{avatar}</div>}
    {sender === "ai" && (
      <div className="chat-bubble-container chat-bubble-container-ai">
        <div className="chat-bubble-content chat-bubble-content-ai">
          {children}
        </div>
        {actions && <div className="chat-bubble-actions">{actions}</div>}
      </div>
    )}
  </div>
);

/**
 * Chat Container component for full chat interface
 * Follows the 8px design system
 */
export const ChatContainer: React.FC<ChatContainerProps> = ({
  variant = "default",
  messages = [],
  isLoading = false,
  error,
  inputComponent,
  welcomeComponent,
  renderMessage,
  className = "",
}) => {
  const messagesEndRef = useRef<HTMLDivElement>(null);

  // Scroll to bottom when messages change
  useEffect(() => {
    if (messagesEndRef.current) {
      messagesEndRef.current.scrollIntoView({ behavior: 'smooth' });
    }
  }, [messages]);

  // Default message renderer
  const defaultRenderMessage = (message: ChatMessage) => (
    <ChatBubble
      key={message.id}
      sender={message.sender}
      avatar={message.avatar}
      actions={message.actions}
    >
      {message.content}
    </ChatBubble>
  );

  // Use custom renderer if provided, otherwise use default
  const messageRenderer = renderMessage || defaultRenderMessage;

  return (
    <div className={`chat-container chat-container-${variant} ${className}`}>
      {/* Messages container */}
      <div className={`chat-messages chat-messages-${variant}`}>
        {/* Welcome component */}
        {welcomeComponent && messages.length === 0 && (
          <div className="chat-welcome">
            {welcomeComponent}
          </div>
        )}

        {/* Chat messages */}
        {messages.length > 0 && (
          <div className="chat-message-list">
            {messages.map(messageRenderer)}
          </div>
        )}

        {/* Loading indicator */}
        {isLoading && (
          <div className="chat-loading">
            <div className="chat-loading-indicator">
              <span className="chat-loading-dot"></span>
              <span className="chat-loading-dot"></span>
              <span className="chat-loading-dot"></span>
            </div>
          </div>
        )}

        {/* Error message */}
        {error && (
          <div className="chat-error text-destructive">
            {error}
          </div>
        )}

        {/* Invisible element for scrolling to bottom */}
        <div ref={messagesEndRef} />
      </div>

      {/* Input container */}
      {inputComponent && (
        <div className={`chat-input-container chat-input-container-${variant}`}>
          {inputComponent}
        </div>
      )}
    </div>
  );
}; 