
"use client";

import { useState } from "react";

const sections = [
  "Basic Case Information",
  "Court & Jurisdiction",
  "Parties & Clients",
  "Advocates & Legal Team",
  "Case Classification",
  "Filing & Procedural History",
  "Hearing Management",
  "Documents & Evidence",
  "Financial Information",
  "Internal Management",
  "Connected Cases & Appeals",
  "Custom Fields",
];

type Person = {
  name: string;
  role: string;
  email: string;
  phone: string;
};

const emptyPerson: Person = {
  name: "",
  role: "",
  email: "",
  phone: "",
};

function Field({
  label,
  name,
  type = "text",
  placeholder,
  options,
}: {
  label: string;
  name: string;
  type?: string;
  placeholder?: string;
  options?: string[];
}) {
  return (
    <label className="block space-y-1.5">
      <span className="text-sm font-medium text-slate-700">{label}</span>
      {options ? (
        <select
          name={name}
          className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-600"
        >
          <option value="">Select {label}</option>
          {options.map((option) => (
            <option key={option}>{option}</option>
          ))}
        </select>
      ) : (
        <input
          name={name}
          type={type}
          placeholder={placeholder || `Enter ${label}`}
          className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-600"
        />
      )}
    </label>
  );
}

export default function CaseForm({
  onCancel,
  onSave,
}: {
  onCancel: () => void;
  onSave: (data: Record<string, unknown>) => void;
}) {
  const [openSections, setOpenSections] = useState<number[]>([0, 1, 2]);
  const [parties, setParties] = useState<Person[]>([{ ...emptyPerson }]);
  const [advocates, setAdvocates] = useState<Person[]>([{ ...emptyPerson }]);

  function toggleSection(index: number) {
    setOpenSections((previous) =>
      previous.includes(index)
        ? previous.filter((item) => item !== index)
        : [...previous, index]
    );
  }

  function updatePerson(
    type: "party" | "advocate",
    index: number,
    key: keyof Person,
    value: string
  ) {
    const setter = type === "party" ? setParties : setAdvocates;

    setter((previous) =>
      previous.map((person, i) =>
        i === index ? { ...person, [key]: value } : person
      )
    );
  }

  function submitForm(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();

    const formData = new FormData(event.currentTarget);
    const data: Record<string, unknown> = Object.fromEntries(
      formData.entries()
    );

    data.parties = parties;
    data.advocates = advocates;

    onSave(data);
  }

  return (
    <form
      onSubmit={submitForm}
      className="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
    >
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold text-slate-900">
            Register New Case
          </h2>
          <p className="mt-1 text-sm text-slate-500">
            Complete the relevant details. Optional sections can be filled later.
          </p>
        </div>
        <span className="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
          Comprehensive Case Entry
        </span>
      </div>

      {sections.map((section, index) => (
        <section
          key={section}
          className="overflow-hidden rounded-xl border border-slate-200"
        >
          <button
            type="button"
            onClick={() => toggleSection(index)}
            className="flex w-full items-center justify-between bg-slate-50 px-5 py-4 text-left hover:bg-slate-100"
          >
            <span className="font-semibold text-slate-800">
              {String(index + 1).padStart(2, "0")}. {section}
            </span>
            <span className="text-xl text-slate-500">
              {openSections.includes(index) ? "−" : "+"}
            </span>
          </button>

          {openSections.includes(index) && (
            <div className="grid grid-cols-1 gap-4 p-5 md:grid-cols-2">
              {index === 0 && (
                <>
                  <Field label="Case Title" name="caseTitle" />
                  <Field label="Case Category" name="caseCategory" options={["Civil", "Criminal", "Constitutional", "Commercial", "Family", "Consumer", "Property", "Service", "Tax", "Labour", "Other"]} />
                  <Field label="Case Type" name="caseType" placeholder="e.g. Writ Petition, Civil Suit, Bail Application" />
                  <Field label="Case Number" name="caseNumber" />
                  <Field label="Registration Number" name="registrationNumber" />
                  <Field label="CNR Number" name="cnrNumber" />
                  <Field label="Filing Number" name="filingNumber" />
                  <Field label="Case Year" name="caseYear" type="number" />
                  <Field label="Filing Date" name="filingDate" type="date" />
                  <Field label="Registration Date" name="registrationDate" type="date" />
                  <Field label="Case Status" name="caseStatus" options={["Pending", "Active", "Disposed", "Transferred", "Stayed", "Archived"]} />
                  <Field label="Case Priority" name="priority" options={["Low", "Normal", "High", "Urgent"]} />
                  <div className="md:col-span-2">
                    <Field label="Case Description" name="description" placeholder="Brief facts and background of the matter" />
                  </div>
                </>
              )}

              {index === 1 && (
                <>
                  <Field label="Court Level" name="courtLevel" options={["Supreme Court", "High Court", "District Court", "Subordinate Court", "Tribunal", "Commission", "Other"]} />
                  <Field label="Court / Forum Name" name="courtName" />
                  <Field label="State" name="state" />
                  <Field label="District" name="district" />
                  <Field label="Court Complex / Establishment" name="courtComplex" />
                  <Field label="Bench" name="bench" />
                  <Field label="Courtroom Number" name="courtroom" />
                  <Field label="Judge / Presiding Officer" name="judge" />
                  <Field label="Jurisdiction" name="jurisdiction" />
                  <Field label="Court Location" name="courtLocation" />
                </>
              )}

              {index === 2 && (
                <div className="space-y-4 md:col-span-2">
                  {parties.map((person, i) => (
                    <div key={i} className="rounded-lg border border-slate-200 p-4">
                      <div className="mb-3 flex items-center justify-between">
                        <h3 className="font-semibold">Party {i + 1}</h3>
                        {parties.length > 1 && (
                          <button type="button" onClick={() => setParties((p) => p.filter((_, n) => n !== i))} className="text-sm text-red-600">Remove</button>
                        )}
                      </div>
                      <div className="grid gap-3 md:grid-cols-2">
                        <label className="space-y-1.5">
                          <span className="text-sm font-medium">Party Role</span>
                          <select value={person.role} onChange={(e) => updatePerson("party", i, "role", e.target.value)} className="w-full rounded-lg border p-2.5 text-sm">
                            <option value="">Select Role</option>
                            {["Client", "Petitioner", "Plaintiff", "Complainant", "Appellant", "Respondent", "Defendant", "Accused", "Opposite Party", "Intervenor", "Legal Representative", "Other"].map((r) => <option key={r}>{r}</option>)}
                          </select>
                        </label>
                        <label className="space-y-1.5">
                          <span className="text-sm font-medium">Name</span>
                          <input value={person.name} onChange={(e) => updatePerson("party", i, "name", e.target.value)} placeholder="Full name / organisation" className="w-full rounded-lg border p-2.5 text-sm" />
                        </label>
                        <label className="space-y-1.5">
                          <span className="text-sm font-medium">Email</span>
                          <input value={person.email} onChange={(e) => updatePerson("party", i, "email", e.target.value)} type="email" className="w-full rounded-lg border p-2.5 text-sm" />
                        </label>
                        <label className="space-y-1.5">
                          <span className="text-sm font-medium">Phone</span>
                          <input value={person.phone} onChange={(e) => updatePerson("party", i, "phone", e.target.value)} className="w-full rounded-lg border p-2.5 text-sm" />
                        </label>
                      </div>
                    </div>
                  ))}
                  <button type="button" onClick={() => setParties((p) => [...p, { ...emptyPerson }])} className="rounded-lg border border-blue-600 px-4 py-2 text-sm font-semibold text-blue-700">
                    + Add Another Party
                  </button>
                </div>
              )}

              {index === 3 && (
                <div className="space-y-4 md:col-span-2">
                  {advocates.map((person, i) => (
                    <div key={i} className="rounded-lg border border-slate-200 p-4">
                      <div className="mb-3 flex items-center justify-between">
                        <h3 className="font-semibold">Advocate {i + 1}</h3>
                        {advocates.length > 1 && (
                          <button type="button" onClick={() => setAdvocates((p) => p.filter((_, n) => n !== i))} className="text-sm text-red-600">Remove</button>
                        )}
                      </div>
                      <div className="grid gap-3 md:grid-cols-2">
                        <label className="space-y-1.5">
                          <span className="text-sm font-medium">Role</span>
                          <select value={person.role} onChange={(e) => updatePerson("advocate", i, "role", e.target.value)} className="w-full rounded-lg border p-2.5 text-sm">
                            <option value="">Select Role</option>
                            {["Filing Advocate", "Arguing Counsel", "AOR", "Briefing Counsel", "Senior Advocate", "Junior Advocate", "Opposite Counsel", "Law Clerk", "Other"].map((r) => <option key={r}>{r}</option>)}
                          </select>
                        </label>
                        <label className="space-y-1.5">
                          <span className="text-sm font-medium">Name</span>
                          <input value={person.name} onChange={(e) => updatePerson("advocate", i, "name", e.target.value)} className="w-full rounded-lg border p-2.5 text-sm" />
                        </label>
                        <label className="space-y-1.5">
                          <span className="text-sm font-medium">Email</span>
                          <input value={person.email} onChange={(e) => updatePerson("advocate", i, "email", e.target.value)} type="email" className="w-full rounded-lg border p-2.5 text-sm" />
                        </label>
                        <label className="space-y-1.5">
                          <span className="text-sm font-medium">Phone</span>
                          <input value={person.phone} onChange={(e) => updatePerson("advocate", i, "phone", e.target.value)} className="w-full rounded-lg border p-2.5 text-sm" />
                        </label>
                      </div>
                    </div>
                  ))}
                  <button type="button" onClick={() => setAdvocates((p) => [...p, { ...emptyPerson }])} className="rounded-lg border border-blue-600 px-4 py-2 text-sm font-semibold text-blue-700">
                    + Add Another Advocate
                  </button>
                </div>
              )}

              {index === 4 && (
                <>
                  <Field label="Subject / Practice Area" name="subject" />
                  <Field label="Act / Statute" name="act" />
                  <Field label="Sections / Provisions" name="sections" />
                  <Field label="Relief Sought" name="relief" />
                  <Field label="Case Valuation" name="valuation" type="number" />
                  <Field label="Court Fee" name="courtFee" type="number" />
                  <Field label="Limitation Deadline" name="limitationDeadline" type="date" />
                </>
              )}

              {index === 5 && (
                <>
                  <Field label="Current Procedural Stage" name="proceduralStage" options={["Pre-filing", "Filed", "Scrutiny", "Defects", "Re-filing", "Registered", "Notice Issued", "Pleadings", "Evidence", "Arguments", "Judgment Reserved", "Disposed", "Execution"]} />
                  <Field label="Service Status" name="serviceStatus" options={["Not Started", "Pending", "Served", "Unserved", "Refused", "Awaited"]} />
                  <Field label="Last Order Date" name="lastOrderDate" type="date" />
                  <Field label="Next Procedural Deadline" name="proceduralDeadline" type="date" />
                  <Field label="Filing / Diary Reference" name="diaryReference" />
                  <Field label="Defects / Objections" name="defects" />
                </>
              )}

              {index === 6 && (
                <>
                  <Field label="Next Hearing Date" name="nextHearingDate" type="date" />
                  <Field label="Hearing Purpose" name="hearingPurpose" options={["Admission", "Notice", "Reply", "Rejoinder", "Interim Relief", "Evidence", "Cross-examination", "Final Arguments", "Judgment", "Order", "Other"]} />
                  <Field label="Listing Type" name="listingType" options={["Regular", "Urgent", "Supplementary", "Mentioning", "Fresh", "After Notice", "Other"]} />
                  <Field label="Item Number" name="itemNumber" />
                  <Field label="Court Number" name="hearingCourtNumber" />
                  <Field label="Video Conferencing Link" name="vcLink" />
                  <Field label="Physical Appearance Required" name="physicalAppearance" options={["Yes", "No", "To Be Confirmed"]} />
                  <Field label="Hearing Reminder" name="hearingReminder" options={["Same Day", "1 Day Before", "3 Days Before", "7 Days Before"]} />
                </>
              )}

              {index === 7 && (
                <>
                  <Field label="Document Category" name="documentCategory" options={["Petition", "Reply", "Written Statement", "Affidavit", "Vakalatnama", "Annexure", "Evidence", "Order", "Judgment", "Certified Copy", "Other"]} />
                  <Field label="Document Reference / Description" name="documentReference" />
                  <Field label="Document Date" name="documentDate" type="date" />
                  <Field label="Document Status" name="documentStatus" options={["Draft", "Final", "Filed", "Served", "Received"]} />
                  <p className="text-sm text-slate-500 md:col-span-2">Actual document uploads and version management will be connected in the Documents module.</p>
                </>
              )}

              {index === 8 && (
                <>
                  <Field label="Professional Fee" name="professionalFee" type="number" />
                  <Field label="Fee Arrangement" name="feeArrangement" options={["Fixed", "Retainer", "Stage-wise", "Per Hearing", "Other"]} />
                  <Field label="Amount Received" name="amountReceived" type="number" />
                  <Field label="Outstanding Amount" name="outstandingAmount" type="number" />
                  <Field label="Next Payment Date" name="nextPaymentDate" type="date" />
                  <Field label="Expense Notes" name="expenseNotes" />
                </>
              )}

              {index === 9 && (
                <>
                  <Field label="Assigned Team Member" name="assignedMember" />
                  <Field label="Confidentiality Level" name="confidentiality" options={["Normal", "Confidential", "Highly Confidential", "Restricted"]} />
                  <Field label="Internal Case Tags" name="caseTags" placeholder="Separate tags with commas" />
                  <Field label="Follow-up Date" name="followUpDate" type="date" />
                  <Field label="Internal Notes" name="internalNotes" placeholder="Private notes for your legal team" />
                </>
              )}

              {index === 10 && (
                <>
                  <Field label="Connected Case Number" name="connectedCaseNumber" />
                  <Field label="Connected Court" name="connectedCourt" />
                  <Field label="Relationship" name="connectedRelationship" options={["Appeal", "Revision", "Review", "Transfer", "Execution", "Cross-case", "Related Matter", "Other"]} />
                  <Field label="Lower Court Case Number" name="lowerCourtCaseNumber" />
                  <Field label="Appeal / Revision Number" name="appealNumber" />
                </>
              )}

              {index === 11 && (
                <>
                  <Field label="Custom Field Name" name="customFieldName" />
                  <Field label="Custom Field Value" name="customFieldValue" />
                  <Field label="Additional Reference" name="additionalReference" />
                  <Field label="External Case Link" name="externalCaseLink" />
                </>
              )}
            </div>
          )}
        </section>
      ))}

      <div className="flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-5">
        <button
          type="button"
          onClick={onCancel}
          className="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
        >
          Cancel
        </button>
        <button
          type="submit"
          className="rounded-lg bg-blue-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-800"
        >
          Save Case
        </button>
      </div>
    </form>
  );
}
