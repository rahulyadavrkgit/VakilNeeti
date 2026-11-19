export default function Dashboard() {
  const stats = [
    { title: "Total Cases", value: "125", icon: "⚖️" },
    { title: "Today's Hearings", value: "08", icon: "📅" },
    { title: "Upcoming Hearings", value: "24", icon: "🗓️" },
    { title: "Pending Tasks", value: "12", icon: "📌" },
  ];

  return (
    <main className="min-h-screen bg-slate-100">

      <aside className="fixed left-0 top-0 h-full w-64 bg-blue-950 p-6 text-white">
        <h1 className="mb-10 text-2xl font-bold">
          Vakil<span className="text-amber-400">Neeti</span>
        </h1>

        <nav className="space-y-4">
          {[
            "Dashboard",
            "My Cases",
            "Hearing Calendar",
            "Today's Hearings",
            "Tasks & Reminders",
            "Clients",
            "Documents",
            "Settings",
          ].map((item) => (
            <div
              key={item}
              className="cursor-pointer rounded-lg p-3 hover:bg-blue-800"
            >
              {item}
            </div>
          ))}
        </nav>
      </aside>

      <section className="ml-64 p-8">

        <header className="mb-10 flex items-center justify-between">
          <div>
            <h2 className="text-3xl font-bold text-slate-900">
              Advocate Dashboard
            </h2>
            <p className="mt-2 text-slate-500">
              Welcome to your legal practice workspace.
            </p>
          </div>

          <div className="rounded-lg bg-white p-3 shadow">
            Advocate Account
          </div>
        </header>

        <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
          {stats.map((stat) => (
            <div
              key={stat.title}
              className="rounded-xl bg-white p-6 shadow-sm"
            >
              <div className="text-3xl">{stat.icon}</div>
              <p className="mt-4 text-sm text-slate-500">
                {stat.title}
              </p>
              <h3 className="mt-2 text-3xl font-bold text-blue-950">
                {stat.value}
              </h3>
            </div>
          ))}
        </div>

        <div className="mt-10 rounded-xl bg-white p-6 shadow-sm">
          <h3 className="mb-5 text-xl font-bold">
            Upcoming Hearings
          </h3>

          <div className="overflow-x-auto">
            <table className="w-full text-left">
              <thead>
                <tr className="border-b text-sm text-slate-500">
                  <th className="p-3">Case Number</th>
                  <th className="p-3">Court</th>
                  <th className="p-3">Next Date</th>
                  <th className="p-3">Status</th>
                </tr>
              </thead>

              <tbody>
                <tr className="border-b">
                  <td className="p-3">CS 125/2026</td>
                  <td className="p-3">District Court</td>
                  <td className="p-3">05 Nov 2026</td>
                  <td className="p-3 text-green-600">Listed</td>
                </tr>

                <tr className="border-b">
                  <td className="p-3">CRL 245/2026</td>
                  <td className="p-3">High Court</td>
                  <td className="p-3">08 Nov 2026</td>
                  <td className="p-3 text-green-600">Listed</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div className="mt-8 rounded-xl bg-white p-6 shadow-sm">
          <h3 className="text-xl font-bold">My Tasks</h3>
          <p className="mt-3 text-slate-500">
            Your pending legal tasks will appear here.
          </p>
        </div>

      </section>
    </main>
  );
}