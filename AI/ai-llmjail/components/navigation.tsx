'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { cn } from '@/lib/utils';

const navItems = [
  {
    name: '🌿 Chat with Hwaboon',
    href: '/vllm',
    description: 'Chat with your plant assistant',
  },
];

export default function Navigation() {
  const pathname = usePathname();

  return (
    <nav className="sticky top-0 z-50 bg-green-50 shadow-sm border-b border-green-200">
      <div className="container flex items-center py-3">
        <Link href="/" className="mr-6 flex items-center space-x-2">
          <span className="font-bold text-xl text-green-800">🪴 HwaboonGPT</span>
        </Link>
        
        <div className="flex items-center space-x-1 ml-auto">
          {navItems.map((item) => (
            <Link
              key={item.href}
              href={item.href}
              className={cn(
                'px-3 py-2 text-sm font-medium rounded-md transition-colors',
                pathname === item.href
                  ? 'bg-green-200 text-green-900'
                  : 'text-green-700 hover:bg-green-100'
              )}
            >
              {item.name}
            </Link>
          ))}
        </div>
      </div>
    </nav>
  );
} 