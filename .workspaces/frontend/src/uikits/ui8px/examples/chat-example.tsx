import React, { useState } from 'react';

// Import components from the UI Kit
// In a real app, these would be imported directly from the UI kit
import { Avatar } from '../core/ui/avatar';
import { Badge } from '../core/ui/badge';
import { Button } from '../core/ui/button';
import { Card, CardTitle } from '../core/ui/card';
import { ChatBubble, ChatBubbleText } from '../core/components/chat-bubble';
import { ChatInput } from '../core/components/chat-input';
import { QuickActions } from '../core/components/quick-actions';
import { ChatCard } from '../core/components/chat-card';
import { ChatContainer } from '../core/blocks/chat-container';

/**
 * Example Chat UI using the UI8px design system
 */
export const ChatExample: React.FC = () => {
  // Sample data for the chat
  const [messages, setMessages] = useState([
    {
      id: '1',
      sender: 'ai' as const,
      content: (
        <ChatBubbleText>
          Hello! How can I help you today?
        </ChatBubbleText>
      ),
      avatar: <Avatar size="lg" initials="AI" />,
    },
    {
      id: '2',
      sender: 'user' as const,
      content: (
        <ChatBubbleText>
          What's UI8px design system?
        </ChatBubbleText>
      ),
      avatar: <Avatar size="lg" initials="U" />,
    },
    {
      id: '3',
      sender: 'ai' as const,
      content: (
        <>
          <ChatBubbleText>
            UI8px is a modern design system that follows the 8px grid principle. It features:
          </ChatBubbleText>

          <ChatCard
            variant="default"
            title="Key Features"
            actions={
              <QuickActions
                actions={[
                  { id: 'copy', label: 'Copy', icon: '📋' },
                  { id: 'share', label: 'Share', icon: '🔗' }
                ]}
              />
            }
          >
            <ul className="list-disc list-inside space-y-1.5 text-sm">
              <li>8px spacing grid for consistent UI</li>
              <li>Modern oklch color system from shadcn</li>
              <li>Dark mode support built-in</li>
              <li>Semantic component naming</li>
              <li>Accessible UI components</li>
            </ul>
          </ChatCard>
        </>
      ),
      avatar: <Avatar size="lg" initials="AI" />,
      actions: (
        <QuickActions
          actions={[
            { id: 'like', label: 'Like', icon: '👍' },
            { id: 'dislike', label: 'Dislike', icon: '👎' }
          ]}
        />
      )
    }
  ]);

  const [inputValue, setInputValue] = useState('');
  const [isLoading, setIsLoading] = useState(false);

  // Sample function to handle sending a message
  const handleSend = () => {
    if (!inputValue.trim()) return;

    // Add user message
    const newUserMessage = {
      id: `user-${Date.now()}`,
      sender: 'user' as const,
      content: <ChatBubbleText>{inputValue}</ChatBubbleText>,
      avatar: <Avatar size="lg" initials="U" />,
    };

    setMessages(prev => [...prev, newUserMessage]);
    setInputValue('');

    // Simulate AI response with loading
    setIsLoading(true);

    setTimeout(() => {
      const newAiMessage = {
        id: `ai-${Date.now()}`,
        sender: 'ai' as const,
        content: (
          <ChatBubbleText>
            Thank you for your message! This is a demo of the UI8px chat interface.
          </ChatBubbleText>
        ),
        avatar: <Avatar size="lg" initials="AI" />,
        actions: (
          <QuickActions
            actions={[
              { id: 'like', label: 'Like', icon: '👍' },
              { id: 'dislike', label: 'Dislike', icon: '👎' }
            ]}
          />
        )
      };

      setMessages(prev => [...prev, newAiMessage]);
      setIsLoading(false);
    }, 1500);
  };

  // Welcome component for empty chat
  const welcomeComponent = (
    <Card variant="outline" className="mx-auto max-w-md text-center p-6">
      <CardTitle className="mb-2">Welcome to UI8px Chat</CardTitle>
      <p className="text-muted-foreground mb-4">
        This is a demo of the UI8px design system chat interface.
      </p>
      <Badge variant="primary" size="lg" className="mx-auto">
        UI8px Design System
      </Badge>
    </Card>
  );

  return (
    <div className="h-screen flex flex-col bg-background">
      <header className="border-b border-border p-4">
        <h1 className="text-xl font-semibold">UI8px Chat Example</h1>
      </header>

      <main className="flex-1 relative">
        <ChatContainer
          variant="bordered"
          messages={messages}
          isLoading={isLoading}
          welcomeComponent={welcomeComponent}
          className="max-w-4xl mx-auto h-full"
          inputComponent={
            <ChatInput
              variant="bordered"
              placeholder="Type a message..."
              value={inputValue}
              onChange={setInputValue}
              onSend={handleSend}
            />
          }
        />
      </main>
    </div>
  );
}; 