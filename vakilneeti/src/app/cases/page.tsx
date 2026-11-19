
"use client";

import { useState } from "react";
import Link from "next/link";
import CaseForm from "../components/CaseForm";

type CaseRecord = {
  id: string;
  title: string;
  court: string;
  type: string;
  nextDate: string;
  status: string;
};

const initialCases: CaseRecord[] = [
  {
    id: "CS/125/2026",
    title: "Rahul Sharma vs State of UP",
    court: "Allahabad High Court",
    type: "Criminal",
    nextDate: "05 Nov 2026",
    status: "Pending",
  },
  {
    id: "CS/089/2026",
    title: "Amit Kumar vs XYZ Ltd.",
    court: "District Court, Gautam Buddha Nagar",
    type: "Civil",
    nextDate: "08 Nov 2026",
    status: "Pending",
  },
  {
    id: "CC/042/2025",
    title: "Neha Verma vs ABC Bank",
    court: "Saket District Court",
    type: "Consumer",
    nextDate: "12 Nov 2026",
    status: "Active",
  },
  {
    id: "WP/018/2026",
    title: "Ramesh Kumar vs State",
    court: "Supreme Court of India",
    type: "Writ",
    nextDate: "18 Nov 2026",
    status: "Disposed",
  },
];

export default function CasesPage() {
  const [cases, setCases] = useState(initialCases);
  const [search, setSearch] = useState("");
  const [filter, setFilter] = useState("All");
  const [showForm, setShowForm] = useState(false);

  function saveCase(data: Record<string, unknown>) {
    const newCase: CaseRecord = {
      id: String(data.caseNumber || data.registrationNumber || "New Case"),
      title: String(data.caseTitle || "Untitled Case"),
      court: String(data.courtName || "Court not specified"),
      type: String(data.caseCategory || "Other"),
      nextDate: data.nextHearingDate
        ? new Date(String(data.nextHearingDate) + "T00:00:00").toLocaleDateString("en-IN", {
            day: "2-digit",
            month: "short",
            year: "numeric",
          })
        : "Not scheduled",
      status: String(data.caseStatus || "Pending"),
    };

    setCases((previous) => [newCase, ...previous]);
    setShowForm(false);
    setSearch("");
    setFilter("All");
  }

  const filteredCases = cases.filter((item) => {
    const matchesSearch = `${item.id} ${item.title} ${item.court}`
      .toLowerCase()
      .includes(search.toLowerCase());

    const matchesFilter = filter === "All" || item.status === filter;

    return matchesSearch && matchesFilter;
  });

  const stats = [
    { label: "Total Cases", value: cases.length },
    { label: "Pending", value: cases.filter((c) => c.status === "Pending").length },
    { label: "Active", value: cases.filter((c) => c.status === "Active").length },
    { label: "Disposed", value: cases.filter((c) => c.status === "Disposed").length },
  ];

  return (
    <main className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="mx-auto max-w-7xl">
        <div className="mb-8 flex flex-wrap items-center justify-between gap-4">
          <div>
            <p className="text-sm font-medium text-blue-700">
              <Link href="/dashboard" className="hover:underline">
                VakilNeeti / Dashboard
              </Link>
            </p>
            <h1 className="mt-2 text-3xl font-bold text-slate-900">
              My Cases
            </h1>
            <p className="mt-1 text-sm text-slate-500">
              Manage and track all your legal matters in one place.
            </p>
          </div>

          <button
            onClick={() => setShowForm((previous) => !previous)}
            className="rounded-lg bg-blue-700 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-800"
          >
            {showForm ? "Close Form" : "+ Add New Case"}
          </button>
        </div>

        <div className="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {stats.map((stat) => (
            <div
              key={stat.label}
              className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
            >
              <p className="text-sm text-slate-500">{stat.label}</p>
              <p className="mt-2 text-3xl font-bold text-slate-900">
                {stat.value}
              </p>
            </div>
          ))}
        </div>

        {showForm ? (
          <CaseForm
            onCancel={() => setShowForm(false)}
            onSave={saveCase}
          />
        ) : (
          <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div className="flex flex-col gap-4 border-b border-slate-200 p-5 md:flex-row md:items-center md:justify-between">
              <div>
                <h2 className="font-bold text-slate-900">All Cases</h2>
                <p className="mt-1 text-xs text-slate-500">
                  Your case register
                </p>
              </div>

              <div className="flex flex-col gap-3 sm:flex-row">
                <input
                  value={search}
                  onChange={(event) => setSearch(event.target.value)}
                  placeholder="Search cases..."
                  className="rounded-lg border border-slate-300 px-4 py-2 text-sm outline-none focus:border-blue-600"
                />
                <select
                  value={filter}
                  onChange={(event) => setFilter(event.target.value)}
                  className="rounded-lg border border-slate-300 px-4 py-2 text-sm"
                >
                  <option>All</option>
                  <option>Pending</option>
                  <option>Active</option>
                  <option>Disposed</option>
                  <option>Transferred</option>
                  <option>Stayed</option>
                  <option>Archived</option>
                </select>
              </div>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-left text-sm">
                <thead className="bg-slate-50 text-xs uppercase text-slate-500">
                  <tr>
                    <th className="px-5 py-4">Case Number</th>
                    <th className="px-5 py-4">Case Title</th>
                    <th className="px-5 py-4">Court</th>
                    <th className="px-5 py-4">Type</th>
                    <th className="px-5 py-4">Next Hearing</th>
                    <th className="px-5 py-4">Status</th>
                  </tr>
                </thead>

                <tbody className="divide-y divide-slate-100">
                  {filteredCases.map((item) => (
                    <tr key={item.id} className="hover:bg-slate-50">
                      <td className="whitespace-nowrap px-5 py-4 font-semibold text-blue-700">
                        {item.id}
                      </td>
                      <td className="min-w-48 px-5 py-4 font-medium text-slate-800">
                        {item.title}
                      </td>
                      <td className="min-w-48 px-5 py-4 text-slate-600">
                        {item.court}
                      </td>
                      <td className="px-5 py-4 text-slate-600">
                        {item.type}
                      </td>
                      <td className="whitespace-nowrap px-5 py-4 text-slate-600">
                        {item.nextDate}
                      </td>
                      <td className="px-5 py-4">
                        <span className="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                          {item.status}
                        </span>
                      </td>
                    </tr>
                  ))}

                  {filteredCases.length === 0 && (
                    <tr>
                      <td colSpan={6} className="px-5 py-10 text-center text-slate-500">
                        No cases found.
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>

            <div className="border-t border-slate-200 px-5 py-4 text-xs text-slate-500">
              Showing {filteredCases.length} of {cases.length} cases
            </div>
          </section>
        )}

        <p className="mt-5 text-xs text-slate-400">
          Development version: cases currently remain in temporary browser
          memory and are not yet saved to a database.
        </p>
      </div>
    </main>
  );
}
