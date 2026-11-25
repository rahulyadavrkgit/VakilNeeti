<!DOCTYPE html>
<html lang="en-IN">
<head>
    <meta charset="utf-8">
    <title>{{ $cv['name'] }} · CV</title>
    <style>
        @page { margin: 16mm 16mm 18mm; }
        body { margin: 0; color: #1e293b; font: 10pt/1.5 "DejaVu Sans", sans-serif; }
        table { border-collapse: collapse; width: 100%; }
        td { vertical-align: top; padding: 0; }
        .band { background: #142235; color: #fff; border-top: 3pt solid #16776e; }
        .band td { padding: 16pt 18pt; }
        .photo { width: 74pt; height: 74pt; border-radius: 8pt; }
        .initials { width: 64pt; height: 44pt; padding-top: 20pt; background: #16776e; color: #fff; text-align: center; font: 22pt/1 "DejaVu Serif", serif; border-radius: 8pt; }
        h1 { margin: 0; font: bold 21pt/1.15 "DejaVu Serif", serif; color: #fff; }
        .subtitle { margin: 3pt 0 0; color: #cbd5e1; font-size: 10.5pt; }
        .contacts { margin: 8pt 0 0; color: #e2e8f0; font-size: 9pt; }
        .contacts span { margin-right: 12pt; }
        .body td.main { padding: 16pt 16pt 0 0; }
        .body td.side { width: 150pt; padding: 16pt 0 0 14pt; border-left: 0.75pt solid #e2e8f0; }
        h2 { margin: 0 0 4pt; font-size: 8pt; letter-spacing: 1pt; text-transform: uppercase; color: #16776e; }
        .block { margin-bottom: 13pt; }
        .block p { margin: 0; }
        ul { margin: 0; padding: 0 0 0 11pt; }
        li { margin: 0 0 2pt; }
        .tags { padding-top: 3pt; line-height: 23pt; }
        .tag { display: inline-block; line-height: 1.3; margin: 0 4pt 4pt 0; padding: 2pt 6pt; border: 0.75pt solid #99d5cd; background: #f0fdfa; color: #134e4a; font-size: 9pt; border-radius: 3pt; }
        dt { font-size: 8pt; color: #64748b; }
        dd { margin: 0 0 8pt; font-weight: bold; color: #0f172a; }
        .footer { position: fixed; bottom: -10mm; left: 0; right: 0; font-size: 7.5pt; color: #94a3b8; }
    </style>
</head>
<body>
    <table class="band">
        <tr>
            <td style="width: 1%; padding-right: 0;">
                @if($cv['photo_data'] ?? null)
                    <img class="photo" src="{{ $cv['photo_data'] }}" alt="">
                @else
                    <div class="initials">{{ $cv['initials'] }}</div>
                @endif
            </td>
            <td>
                <h1>{{ $cv['name'] }}</h1>
                <p class="subtitle">{{ $cv['title'] }}@if($cv['location']) · {{ $cv['location'] }}@endif</p>
                @if($cv['contacts'])
                    <p class="contacts">@foreach($cv['contacts'] as $contact)<span>{{ $contact['label'] }}</span>@endforeach</p>
                @endif
            </td>
        </tr>
    </table>

    <table class="body">
        <tr>
            <td class="main">
                @if($cv['about'])
                    <div class="block"><h2>About</h2><p>{!! nl2br(e($cv['about'])) !!}</p></div>
                @endif
                @if($cv['practice'])
                    <div class="block"><h2>Practice areas</h2><div class="tags">@foreach($cv['practice'] as $area)<span class="tag">{{ $area }}</span> @endforeach</div></div>
                @endif
                @foreach(['Courts' => $cv['courts'], 'Education' => $cv['education'], 'Memberships' => $cv['memberships']] as $heading => $entries)
                    @if($entries)
                        <div class="block"><h2>{{ $heading }}</h2><ul>@foreach($entries as $entry)<li>{{ $entry }}</li>@endforeach</ul></div>
                    @endif
                @endforeach
            </td>
            <td class="side">
                @if($cv['facts'])
                    <h2>At a glance</h2>
                    <dl>
                        @foreach($cv['facts'] as $label => $value)
                            <dt>{{ $label }}</dt><dd>{{ $value }}</dd>
                        @endforeach
                    </dl>
                @endif
            </td>
        </tr>
    </table>

    <div class="footer">{{ $shareUrl ? preg_replace('#^https?://#', '', $shareUrl) : 'VakilNiti' }}@if($cv['updated']) · Updated {{ $cv['updated'] }}@endif</div>
</body>
</html>
