import { Suspense } from "react";
import WebviewClient from "./WebviewClient";

async function WebviewContent({
  searchParams,
}: {
  searchParams: Promise<{ url?: string }>;
}) {
  const { url } = await searchParams;
  return <WebviewClient initialUrl={url ?? ""} />;
}

export default function WebviewPage({
  searchParams,
}: {
  searchParams: Promise<{ url?: string }>;
}) {
  return (
    <Suspense fallback={null}>
      <WebviewContent searchParams={searchParams} />
    </Suspense>
  );
}
