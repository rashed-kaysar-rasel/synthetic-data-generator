import React from 'react';

export default function Layout({ children }) {
  return (
    <main className="min-h-screen bg-background flex flex-col items-center">
        <div className="w-full max-w-4xl p-4">
            {children}
        </div>
    </main>
  );
}
