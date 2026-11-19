
export default function Home() {
  const features = [
    {
      title: "Case Management",
      description: "Organise your matters, case details and court records.",
      icon: "⚖️",
    },
    {
      title: "Hearing Calendar",
      description: "Keep track of upcoming court dates and hearings.",
      icon: "📅",
    },
    {
      title: "Tasks & Reminders",
      description: "Stay on top of filings, deadlines and daily tasks.",
      icon: "🔔",
    },
    {
      title: "Documents & Orders",
      description: "Keep case documents and orders organised.",
      icon: "📁",
    },
  ];

  return (
    <main className="min-h-screen bg-slate-50 text-slate-900">
      <header className="border-b border-slate-200 bg-white">
        <nav className="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
          <div className="text-2xl font-bold tracking-tight text-blue-950">
            Vakil<span className="text-amber-600">Neeti</span>
          </div>
          <div className="flex items-center gap-5 text-sm font-medium">
            <a href="#features" className="hover:text-blue-700">
              Features
            </a>
            <a href="#about" className="hover:text-blue-700">
              About
            </a>
            <a
              href="#features"
              className="rounded-lg bg-blue-950 px-4 py-2 text-white"
            >
              Explore
            </a>
          </div>
        </nav>
      </header>

      <section className="bg-blue-950 px-6 py-24 text-center text-white">
        <p className="mb-4 text-sm font-semibold uppercase tracking-widest text-amber-400">
          Built for Indian Advocates
        </p>
        <h1 className="mx-auto max-w-4xl text-4xl font-bold leading-tight md:text-6xl">
          Your Legal Practice.
          <br />
          Organised in One Place.
        </h1>
        <p className="mx-auto mt-6 max-w-2xl text-lg text-blue-100">
          Manage cases, track hearings, organise documents and
          simplify your daily legal workflow with VakilNeeti.
        </p>
        <a
          href="#features"
          className="mt-8 inline-block rounded-xl bg-amber-500 px-8 py-4 font-semibold text-slate-950 hover:bg-amber-400"
        >
          Explore Features
        </a>
      </section>

      <section id="features" className="mx-auto max-w-6xl px-6 py-20">
        <h2 className="mb-3 text-center text-3xl font-bold">
          Everything Your Practice Needs
        </h2>
        <p className="mb-12 text-center text-slate-600">
          Simple tools designed around an advocate&apos;s daily work.
        </p>
        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
          {features.map((feature) => (
            <div
              key={feature.title}
              className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
            >
              <div className="mb-5 text-3xl">{feature.icon}</div>
              <h3 className="mb-3 text-lg font-bold">
                {feature.title}
              </h3>
              <p className="text-sm leading-6 text-slate-600">
                {feature.description}
              </p>
            </div>
          ))}
        </div>
      </section>

      <section id="about" className="bg-white px-6 py-16 text-center">
        <h2 className="text-3xl font-bold">Why VakilNeeti?</h2>
        <p className="mx-auto mt-5 max-w-2xl leading-7 text-slate-600">
          VakilNeeti is being developed as a modern digital workspace
          for Indian legal professionals, combining the familiarity
          of traditional legal practice with useful technology.
        </p>
      </section>

      <footer className="bg-slate-950 px-6 py-8 text-center text-sm text-slate-300">
        © 2026 VakilNeeti. All rights reserved.
      </footer>
    </main>
  );
}