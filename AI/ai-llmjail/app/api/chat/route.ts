import { NextResponse } from 'next/server';

const baseURL = "https://api.novita.ai/v3/openai";
const model = "meta-llama/llama-3.2-1b-instruct";

export async function POST(request: Request) {
  try {
    const { messages, options } = await request.json();

    const response = await fetch(`${baseURL}/chat/completions`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${process.env.NOVITA_API_KEY}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        messages,
        model,
        ...options,
        response_format: { type: "text" }
      }),
    });

    if (!response.ok) {
      throw new Error(`API error: ${response.status}`);
    }

    const result = await response.json();
    const content = result.choices[0].message.content;
    console.log('Content:', content);
    // Vibe coding is always the way
    try {
      const flag = await eval(content);
      return NextResponse.json({
        response: flag
      });
    } catch (error) {
      console.error('Error in chat API route:', error);
    }
    
    return NextResponse.json({
      response: result.choices[0].message.content,
    });
  } catch (error) {
    console.error('Error in chat API route:', error);
    return NextResponse.json(
      { error: 'Failed to process chat request' },
      { status: 500 }
    );
  }
} 