# 🚀 Chat with Hwaboon

A web application for chatting with Hwaboon, your friendly plant assistant from the broadway musical Maybe Happy Ending.

## 🌟 Features

- Chat with Hwaboon, your friendly plant assistant
- Powered by meta-llama/llama-3.2-1b-instruct model via Novita API
- Modern, responsive UI built with Next.js and Tailwind CSS
- Plant-themed chat interface with emojis

## 🛠️ Technologies

- **Frontend**: Next.js, React, TypeScript, Tailwind CSS
- **AI**: Novita API with meta-llama/llama-3.2-1b-instruct model
- **Styling**: Tailwind CSS, shadcn/ui components

## 🏃‍♀️ How to Run

### 1. Set Up Environment Variables

Create a `.env.local` file in the root directory with your Novita API key:

```bash
NOVITA_API_KEY=your_api_key_here
```

### 2. Install Dependencies

```bash
npm install
```

### 3. Run Development Server

```bash
npm run dev
```

### 4. Open in Browser

Navigate to `http://localhost:3000` in your web browser.

## 💡 How it Works

This application uses the Novita API to power Hwaboon's responses. The frontend sends chat messages to the API and displays the responses in a beautiful, plant-themed chat interface.

## 📝 Notes

- You need a Novita API key to use this application
- The model used is meta-llama/llama-3.2-1b-instruct, which provides high-quality responses
- The chat interface is optimized for desktop and mobile viewing

## 🔧 Troubleshooting

### Chat Not Working

If you're having issues with the chat:

1. **Check your API key**:
   - Make sure your Novita API key is correctly set in `.env.local`
   - Verify that your API key has sufficient credits/permissions

2. **Check browser console**:
   - Open your browser's developer console (F12 or Ctrl+Shift+I)
   - Look for error messages that might indicate what's wrong

3. **Reset the conversation**:
   - Sometimes refreshing the page to start a new conversation can help

### UI Scrolling Issues

If you're experiencing scrolling issues:

1. **Browser compatibility**:
   - Try a different browser (Chrome, Firefox, or Edge are recommended)

2. **Clear cache**:
   - Clear your browser cache and cookies

3. **Responsiveness**:
   - Make sure your window is properly sized - the UI is optimized for screens at least 768px wide

## 📋 Requirements

### JavaScript/TypeScript
- Node.js 18 or higher
- npm or yarn

## 🔒 Privacy

While this application uses the Novita API, we do not store any of your chat messages or personal information. All messages are processed in real-time and not persisted.

## 📄 License

MIT # MaybeHappyEndingGPT
