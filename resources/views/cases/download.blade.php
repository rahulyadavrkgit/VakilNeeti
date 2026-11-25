<!DOCTYPE html>
<html lang="en-IN">
<head>
    <meta charset="utf-8">
    <title>{{ $case->case_title ?: 'Case' }}</title>
    <style>
        @page { margin: 16mm 15mm 18mm; }
        body { margin: 0; color: #1e293b; font: 9.5pt/1.45 "DejaVu Sans", sans-serif; }
        h1 { margin: 0; font: bold 16pt/1.2 "DejaVu Serif", serif; color: #142235; }
        .band { border-top: 3pt solid #16776e; padding-top: 8pt; }
        .meta { margin: 3pt 0 0; color: #475569; }
        .status { float: right; padding: 2pt 7pt; border: 0.75pt solid #cbd5e1; border-radius: 3pt; font-size: 8.5pt; font-weight: bold; }
        h2 { margin: 14pt 0 4pt; font-size: 8pt; letter-spacing: 1pt; text-transform: uppercase; color: #16776e; }
        table { width: 100%; border-collapse: collapse; }
        .facts td { padding: 2.5pt 0; vertical-align: top; }
        .facts td.label { width: 30%; color: #64748b; }
        .list th { text-align: left; font-size: 8pt; color: #64748b; border-bottom: 0.75pt solid #cbd5e1; padding: 3pt 4pt; }
        .list td { border-bottom: 0.5pt solid #e2e8f0; padding: 3.5pt 4pt; vertical-align: top; }
        .footer { position: fixed; bottom: -10mm; left: 0; right: 0; font-size: 7.5pt; color: #94a3b8; }
    </style>
</head>
<body>
    @php
        $sides = \App\Models\LegalCase::SIDES;
        $court = collect([
            \App\Services\CourtIdentity::label($case->court_level, $case->state_code, $case->district),
            $case->court_name,
            $case->court_complex,
        ])->filter()->unique()->implode(' · ');
        $facts = array_filter([
            'Case number' => $case->case_number,
            'CNR' => $case->cnr_number,
            'Diary number' => $case->diary_number,
            'File number' => $case->file_number,
            'Case type' => $case->case_type,
            'Court' => $court,
            'Court room' => $case->court_room,
            'Judge' => $case->judge_name,
            'Stage' => $case->stage,
            'Filed on' => $case->filing_date?->format('d M Y'),
            'Disposed on' => $case->disposed_on?->format('d M Y'),
            'We appear for' => collect([$sides[$case->appearing_for] ?? $case->appearing_for, $case->appearing_for_parties])->filter()->implode(': '),
            'Petitioner advocate' => $case->petitioner_advocate,
            'Respondent advocate' => $case->respondent_advocate,
        ], 'filled');
    @endphp

    <div class="band">
        <span class="status">{{ ucfirst($case->case_status ?: 'unknown') }}</span>
        <h1>{{ $case->case_title ?: 'Untitled case' }}</h1>
    </div>

    <h2>Details</h2>
    <table class="facts">
        @foreach($facts as $label => $value)
            <tr><td class="label">{{ $label }}</td><td>{{ $value }}</td></tr>
        @endforeach
    </table>

    @if($case->parties->isNotEmpty())
        <h2>Parties</h2>
        <table class="list">
            <tr><th>Name</th><th>Side</th></tr>
            @foreach($case->parties as $party)
                <tr><td>{{ $party->party_name }}</td><td>{{ ucfirst((string) $party->party_type) }}</td></tr>
            @endforeach
        </table>
    @endif

    <h2>Hearings</h2>
    @if($case->hearings->isEmpty())
        <p class="meta">No hearing recorded yet.</p>
    @else
        <table class="list">
            <tr><th>Date</th><th>Stage / proceedings</th><th>Next date</th></tr>
            @foreach($case->hearings as $hearing)
                <tr>
                    <td>{{ $hearing->hearing_date?->format('d M Y') ?? '—' }}</td>
                    <td>{{ collect([$hearing->hearing_stage, $hearing->proceedings])->filter()->implode(' · ') ?: '—' }}</td>
                    <td>{{ $hearing->next_hearing_date?->format('d M Y') ?? '—' }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if($case->case_description)
        <h2>Notes</h2>
        <p>{!! nl2br(e($case->case_description)) !!}</p>
    @endif

    <div class="footer">VakilNiti · {{ now()->format('d M Y') }}</div>
</body>
</html>
