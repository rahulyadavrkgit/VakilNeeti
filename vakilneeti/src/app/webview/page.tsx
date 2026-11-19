import WebviewClient from "./WebviewClient";

export default async function WebviewPage({
  searchParams,
}: {
  searchParams: Promise<{ url?: string }>;
}) {
  const { url } = await searchParams;

  return <WebviewClient initialUrl={url ?? ""} />;
}
