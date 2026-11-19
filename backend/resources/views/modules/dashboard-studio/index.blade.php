@extends('layouts.app')

@section('content')
<div style="max-width:1200px;margin:25px auto;padding:0 20px;">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px;">
        <div>
            <h1 style="font-size:28px;font-weight:700;">Dashboard Studio</h1>
            <p style="color:#64748b;">Create and manage independent data dashboards.</p>
        </div>
        <span style="font-size:12px;background:#e0f2fe;color:#075985;padding:7px 12px;border-radius:20px;">
            Administrator workspace
        </span>
    </div>

    @if(session('success'))
        <div style="padding:12px;margin:15px 0;background:#dcfce7;color:#166534;border-radius:8px;">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div style="padding:12px;margin:15px 0;background:#fee2e2;color:#991b1b;border-radius:8px;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div style="background:white;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin:25px 0;">
        <h2 style="font-size:19px;font-weight:650;margin-bottom:15px;">Create a dashboard</h2>

        <form method="POST" action="{{ route('dashboard-studio.store') }}">
            @csrf
            <div style="margin-bottom:12px;">
                <label for="title" style="display:block;font-weight:600;margin-bottom:6px;">Dashboard name</label>
                <input id="title" name="title" required maxlength="150"
                       placeholder="Example: District Court Statistics"
                       style="width:100%;padding:11px;border:1px solid #cbd5e1;border-radius:7px;">
            </div>

            <div style="margin-bottom:12px;">
                <label for="visibility" style="display:block;font-weight:600;margin-bottom:6px;">Visibility</label>
                <select id="visibility" name="visibility" style="width:100%;padding:11px;border:1px solid #cbd5e1;border-radius:7px;background:white;">
                    <option value="private" selected>Private</option>
                    <option value="public">Public</option>
                </select>
            </div>

            <div style="margin-bottom:15px;">
                <label for="description" style="display:block;font-weight:600;margin-bottom:6px;">Description</label>
                <textarea id="description" name="description" rows="2"
                          placeholder="Describe the purpose of this dashboard"
                          style="width:100%;padding:11px;border:1px solid #cbd5e1;border-radius:7px;"></textarea>
            </div>

            <button type="submit"
                    style="background:#1d4ed8;color:white;padding:11px 18px;border:0;border-radius:7px;cursor:pointer;">
                Create dashboard
            </button>
        </form>
    </div>

    <h2 style="font-size:20px;font-weight:650;margin:25px 0 15px;">Your dashboards</h2>

    @forelse($dashboards as $dashboard)
        @php($visibility = $dashboard->visibility ?? 'private')
        <div style="background:white;border:1px solid #e2e8f0;border-radius:10px;padding:18px;margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap;">
            <div>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <h3 style="font-weight:650;font-size:17px;margin:0;">{{ $dashboard->title }}</h3>
                    <span style="font-size:11px;padding:4px 8px;border-radius:999px; background:{{ $visibility === 'public' ? '#dcfce7' : '#e2e8f0' }}; color:{{ $visibility === 'public' ? '#166534' : '#475569' }};">
                        {{ ucfirst($visibility) }}
                    </span>
                </div>
                <p style="color:#64748b;font-size:13px;margin-top:4px;">
                    {{ $dashboard->description ?: 'No description added.' }}
                </p>
                <p style="color:#94a3b8;font-size:12px;margin-top:5px;">
                    Created {{ \Carbon\Carbon::parse($dashboard->created_at)->format('d M Y') }}
                </p>
            </div>

            <a href="{{ route('dashboard-studio.show', $dashboard->id) }}"
               style="background:#eff6ff;color:#1d4ed8;padding:10px 15px;border-radius:7px;text-decoration:none;">
                Open dashboard →
            </a>
        </div>
    @empty
        <div style="padding:35px;text-align:center;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:10px;color:#64748b;">
            No dashboards created yet. Create your first dashboard above.
        </div>
    @endforelse
</div>
@endsection