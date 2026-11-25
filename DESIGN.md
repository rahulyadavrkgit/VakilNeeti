# VakilNiti interface conventions

- Use the shared page header, panel, tabs, and collection toolbar across modules and Settings.
- Put desktop navigation at the top, with each group opening a submenu. Keep the phone bottom bar and put secondary destinations in More.
- Give mobile controls at least 44 pixels of height. Keep keyboard focus visible and use text alongside color for status.
- Ask for required information first. Reveal optional fields when requested, populated, or invalid.
- Reuse the court directory, cached district and city lists, and court history for the current workspace.
- Place Supreme Court before High Courts, District Courts, and tribunals. Group case history by date and show available room, item, bench, and document details.
- Derive recommendations from the current workspace and show the reason for each suggestion.

## Standard pieces (use these exact classes)

Plain Tailwind classes are used on purpose: dark mode recolours `bg-white`, `text-slate-*` and `border-slate-*` everywhere.

| Piece | Classes |
|---|---|
| Section box | `<x-panel>`, or `rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5` |
| Page title | `<x-page-header title="…" subtitle="…">` (buttons go in its slot) |
| Section heading (h2 in a box) | `text-lg font-semibold text-slate-900` |
| Sub-heading (h3) | `text-base font-semibold text-slate-900` |
| Field label | `text-sm font-medium text-slate-700` |
| Small caption above a value | `text-xs font-bold uppercase tracking-wide text-slate-500` |
| Primary button | `min-h-11 rounded-xl bg-[#16776e] px-4 text-sm font-semibold text-white hover:bg-[#125f58]` |
| Secondary button | `min-h-11 rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50` |
| Court badge | `<x-court-badge :level :state :district />` |

Colours: navy `#142235` (header, Supreme Court), teal `#16776e` (actions, High Court), red only for today, amber for something that needs doing. Text is never smaller than 12px. Inside navy areas use `text-white`/`text-white/80`, never the slate scale.

Navigation: computers use `layouts/partials/top-nav` (direct links; Cases, Billing, Courts and More open a second menu). Phones use the bottom bar and the Menu sheet (`layouts/partials/sidebar-nav`).
