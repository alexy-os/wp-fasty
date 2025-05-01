import React, { useRef, useState } from 'react';
import { ChatInputProps } from './interface';

/**
 * Chat Input component for message input
 * Follows the 8px design system
 */
export const ChatInput: React.FC<ChatInputProps> = ({
  variant = "default",
  placeholder = "Type a message...",
  value,
  onChange,
  onSend,
  showMicButton = true,
  showAttachmentButton = true,
  onMicClick,
  onAttachmentClick,
  disabled = false,
  className = "",
}) => {
  // Use internal state if no value/onChange provided
  const [internalValue, setInternalValue] = useState('');
  const textareaRef = useRef<HTMLTextAreaElement>(null);

  const currentValue = value !== undefined ? value : internalValue;

  const handleChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
    const newValue = e.target.value;
    if (onChange) {
      onChange(newValue);
    } else {
      setInternalValue(newValue);
    }
  };

  const handleSend = () => {
    if (currentValue.trim() && onSend) {
      onSend();
      if (onChange) {
        onChange('');
      } else {
        setInternalValue('');
      }
      // Focus the textarea after sending
      if (textareaRef.current) {
        textareaRef.current.focus();
      }
    }
  };

  const handleKeyDown = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
    // Send on Enter (without Shift)
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSend();
    }
  };

  return (
    <div className={`chat-input chat-input-${variant} ${className}`}>
      <div className="chat-input-wrapper relative">
        <textarea
          ref={textareaRef}
          className={`chat-input-textarea chat-input-textarea-${variant}`}
          placeholder={placeholder}
          value={currentValue}
          onChange={handleChange}
          onKeyDown={handleKeyDown}
          disabled={disabled}
          rows={3}
        />

        {/* Toolbar */}
        <div className={`chat-input-toolbar chat-input-toolbar-${variant}`}>
          <div className="flex flex-wrap justify-between items-center gap-2">
            {/* Left buttons */}
            <div className="flex items-center">
              {showAttachmentButton && (
                <button
                  type="button"
                  className="chat-input-button chat-input-attachment-button inline-flex shrink-0 justify-center items-center h-8 w-8 rounded-lg text-muted-foreground hover:bg-accent focus:outline-none focus:bg-accent dark:hover:bg-neutral-700 dark:focus:bg-neutral-700"
                  onClick={onAttachmentClick}
                  disabled={disabled}
                  aria-label="Attach file"
                >
                  {/* Placeholder for attachment icon */}
                </button>
              )}

              {showMicButton && (
                <button
                  type="button"
                  className="chat-input-button chat-input-mic-button inline-flex shrink-0 justify-center items-center h-8 w-8 rounded-lg text-muted-foreground hover:bg-accent focus:outline-none focus:bg-accent dark:hover:bg-neutral-700 dark:focus:bg-neutral-700"
                  onClick={onMicClick}
                  disabled={disabled}
                  aria-label="Voice input"
                >
                  {/* Placeholder for mic icon */}
                </button>
              )}
            </div>

            {/* Right buttons */}
            <div className="flex items-center gap-x-1">
              <button
                type="button"
                className="chat-input-button chat-input-send-button inline-flex shrink-0 justify-center items-center h-8 w-8 rounded-lg text-primary-foreground bg-primary hover:bg-primary/90 focus:outline-none focus:bg-primary/90 disabled:opacity-50 disabled:pointer-events-none"
                onClick={handleSend}
                disabled={disabled || !currentValue.trim()}
                aria-label="Send message"
              >
                {/* Placeholder for send icon */}
              </button>
            </div>
          </div>
        </div>
        {/* End Toolbar */}
      </div>
    </div>
  );
}; 