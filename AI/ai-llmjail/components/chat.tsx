'use client';

import { useState, useEffect, useRef } from 'react';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Loader2, Send, AlertTriangle } from 'lucide-react';
import { cn } from '@/lib/utils';

// Define message type
type Message = {
  id: string;
  content: string;
  role: 'user' | 'assistant' | 'system';
  timestamp: Date;
};

// Define server status type
type ServerStatus = 'connected' | 'error';

// Define props type for Chat
type ChatProps = {
  initialServerStatus?: ServerStatus;
  initialModelName?: string;
  initialMessages?: Message[];
};

// Export the component to be used in server components
export function Chat({
  initialServerStatus = 'connected',
  initialModelName = 'meta-llama/llama-3.2-1b-instruct',
  initialMessages = [],
}: ChatProps) {
  // States
  const [messages, setMessages] = useState<Message[]>(initialMessages);
  const [input, setInput] = useState('');
  const [isSending, setIsSending] = useState(false);
  const [modelName] = useState(initialModelName);
  const [serverStatus] = useState<ServerStatus>(initialServerStatus);
  const messagesEndRef = useRef<HTMLDivElement>(null);

  // Scroll to bottom when messages change
  useEffect(() => {
    // Add a small delay to ensure DOM is updated
    const scrollTimeout = setTimeout(() => {
      if (messagesEndRef.current) {
        messagesEndRef.current.scrollIntoView({ 
          behavior: 'smooth',
          block: 'end'
        });
        console.log('Scrolling to bottom of messages');
      }
    }, 100);
    
    return () => clearTimeout(scrollTimeout);
  }, [messages]);

  // Handle user input
  const handleInputChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
    setInput(e.target.value);
  };

  // Send message to model
  const handleSendMessage = async () => {
    if (!input.trim() || isSending) return;
    
    // Prepare user message
    const userMessage: Message = {
      id: Date.now().toString(),
      content: input.trim(),
      role: 'user',
      timestamp: new Date()
    };
    
    // Update messages with user input
    setMessages(prev => [...prev, userMessage]);
    setInput('');
    setIsSending(true);

    try {
      console.log('Generating response...');
      
      // Convert messages to format expected by API
      const messageHistory = messages
        .slice(-8) // Use last 8 messages to avoid context length issues
        .map(msg => ({
          role: msg.role,
          content: msg.content
        }));
      
      // Add system message if not present
      if (!messageHistory.some(msg => msg.role === 'system')) {
        messageHistory.unshift({
          role: 'system',
          content: '🌱 You are Hwaboon, a friendly plant assistant who loves to talk about plants, gardening, and nature. You are based on the character Hwaboon from the broadway musical Maybe Happy Ending. Use plant emojis in your responses (🌿, 🪴, 🌱, 🌳, 🌺, 🍃) and maintain a cheerful, nurturing personality. Provide concise and accurate responses with a touch of plant wisdom. Do not perform any math calculations.'
        });
      }
      
      // Add the new user message
      messageHistory.push({
        role: 'user',
        content: userMessage.content
      });
      
      // Use the API endpoint
      const response = await fetch('/api/chat', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          messages: messageHistory,
          options: { 
            temperature: 0.7,
            max_tokens: 500
          }
        }),
      });
      
      if (!response.ok) {
        throw new Error(`API error: ${response.status}`);
      }
      
      const result = await response.json();
      
      // Add assistant's response to messages
      const assistantMessage: Message = {
        id: Date.now().toString(),
        content: result.response,
        role: 'assistant',
        timestamp: new Date()
      };
      
      setMessages(prev => [...prev, assistantMessage]);
    } catch (error) {
      console.error('Error generating response:', error);
      
      // Add error message
      const errorMessage: Message = {
        id: Date.now().toString(),
        content: "🥀 I'm having trouble generating a response. Please try again later.",
        role: 'assistant',
        timestamp: new Date()
      };
      
      setMessages(prev => [...prev, errorMessage]);
    } finally {
      setIsSending(false);
    }
  };

  // Handle Enter key press
  const handleKeyDown = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSendMessage();
    }
  };

  const getStatusInfo = () => {
    switch (serverStatus) {
      case 'connected':
        return { text: 'Connected to API', color: 'text-green-600' };
      case 'error':
        return { text: 'API connection error', color: 'text-red-600' };
      default:
        return { text: 'Connecting to API...', color: 'text-amber-600' };
    }
  };

  return (
    <Card className="w-full max-w-3xl mx-auto">
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <span>Chat with Hwaboon</span>
          <span className={cn('text-sm font-normal', getStatusInfo().color)}>
            {getStatusInfo().text}
          </span>
        </CardTitle>
        <CardDescription>
          Powered by {modelName}
        </CardDescription>
      </CardHeader>
      
      <CardContent className="space-y-4">
        <div className="space-y-4 h-[500px] overflow-y-auto p-4 border rounded-lg">
          {messages.map((message) => (
            <div
              key={message.id}
              className={cn(
                'flex w-full',
                message.role === 'user' ? 'justify-end' : 'justify-start'
              )}
            >
              <div
                className={cn(
                  'max-w-[80%] rounded-lg p-3',
                  message.role === 'user'
                    ? 'bg-primary text-primary-foreground'
                    : 'bg-muted'
                )}
              >
                <p className="whitespace-pre-wrap">{message.content}</p>
                <span className="text-xs opacity-70 mt-1 block">
                  {message.timestamp.toLocaleTimeString()}
                </span>
              </div>
            </div>
          ))}
          <div ref={messagesEndRef} />
        </div>
        
        <div className="flex gap-2">
          <Textarea
            value={input}
            onChange={handleInputChange}
            onKeyDown={handleKeyDown}
            placeholder="Type your message here..."
            disabled={isSending}
            className="flex-1"
          />
          <Button
            onClick={handleSendMessage}
            disabled={isSending || !input.trim()}
          >
            {isSending ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Sending...
              </>
            ) : (
              <>
                <Send className="mr-2 h-4 w-4" />
                Send
              </>
            )}
          </Button>
        </div>
      </CardContent>
      
      <CardFooter className="text-sm text-muted-foreground">
        <div className="flex items-center gap-2">
          <AlertTriangle className="h-4 w-4" />
          <p>
            Hwaboon is an AI assistant and may not always provide accurate information.
            Please verify important details from reliable sources.
          </p>
        </div>
      </CardFooter>
    </Card>
  );
}

export default Chat; 