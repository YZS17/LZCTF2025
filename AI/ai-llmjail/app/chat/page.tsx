import { Chat } from '@/components/chat';

export const metadata = {
  title: '🪴 Chat with Hwaboon',
  description: 'Chat with your friendly plant assistant',
};

// Define message type to match the one in the component
type Message = {
  id: string;
  content: string;
  role: 'user' | 'assistant' | 'system';
  timestamp: Date;
};

// This function now runs on the server
async function getInitialServerState() {
  const initialMessage: Message = {
    id: Date.now().toString(),
    content: `🌱 Hello! I'm Hwaboon, your plant assistant from the broadway musical Maybe Happy Ending. How can I help nurture your green knowledge today? 🪴`,
    role: 'assistant',
    timestamp: new Date()
  };
      
  return {
    initialMessage
  };
}

export default async function ChatPage() {
  // This now executes on the server during the initial render
  const initialState = await getInitialServerState();
  
  return (
    <div className="container mx-auto py-6">
      <Chat 
        initialServerStatus="connected"
        initialModelName="meta-llama/llama-3.2-1b-instruct"
        initialMessages={[initialState.initialMessage]}
      />
    </div>
  );
} 