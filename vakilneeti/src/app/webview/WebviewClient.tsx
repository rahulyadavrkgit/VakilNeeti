"use client";

import { useRouter } from "next/navigation";

export default function WebviewClient({ initialUrl }: { initialUrl: string }) {
  const router = useRouter();

  let validUrl = "";
  let host = "";

  try {
    const parsed = new URL(initialUrl);
    if (
      ["http:", "https:"].includes(parsed.protocol) &&
      !["localhost", "127.0.0.1", "::1"].includes(parsed.hostname)
    ) {
      validUrl = parsed.toString();
      host = parsed.hostname;
    }
  } catch {
    validUrl = "";
  }

  if (!validUrl) {
    return (
      <main className="mx-auto max-w-xl px-6 py-16 text-center">
        <h1 className="text-2xl font-bold text-slate-900">Invalid website link</h1>
        <p className="mt-3 text-sm text-slate-600">
          Only public HTTP and HTTPS links can be opened in the in-app webview.
        </p>
        <button
          type="button"
          onClick={() => router.back()}
          className="mt-6 rounded-lg bg-blue-950 px-4 py-2 text-sm font-semibold text-white"
        >
          Go back
        </button>
      </main>
    );
  }

  return (
    <main className="min-h-screen bg-slate-100">
      <header className="flex items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-3">
        <div className="min-w-0">
          <p className="text-xs font-bold uppercase tracking-wider text-blue-700">
            VakilNeeti Webview
          </p>
          <p className="truncate text-sm font-medium text-slate-700">{host}</p>
        </div>
        <div className="flex gap-2">
          <button
            type="button"
            onClick={() => router.back()}
            className="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700"
          >
            Close
          </button>
          <a
            href={validUrl}
            target="_blank"
            rel="noopener noreferrer"
            className="rounded-lg bg-blue-950 px-3 py-2 text-sm font-semibold text-white"
          >
            Open in browser
          </a>
        </div>
      </header>
      <iframe
        src={validUrl}
        title={`External website: ${host}`}
        className="h-[calc(100vh-65px)] w-full border-0 bg-white"
        referrerPolicy="strict-origin-when-cross-origin"
      />
    </main>
  );
}
