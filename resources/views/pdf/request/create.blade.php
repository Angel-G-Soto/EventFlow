<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Request Summary</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 12px;
            color: #0f172a;
            margin: 0;
            padding: 32px;
            background: #fff;
        }
        h1 {
            font-size: 22px;
            margin: 0 0 4px;
            letter-spacing: 0.5px;
            color: #111827;
        }
        h2 {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 24px 0 8px;
            color: #1d4ed8;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        th, td {
            border: 1px solid #cbd5f5;
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #eff6ff;
            font-weight: 600;
        }
        .table-small td,
        .table-small th {
            font-size: 11px;
        }
        .meta {
            font-size: 11px;
            color: #6b7280;
        }
        .pill {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 999px;
            background: #1d4ed8;
            color: #fff;
            font-size: 10px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .muted {
            color: #6b7280;
        }
        .spaced td {
            width: 50%;
        }
    </style>
</head>
<body>
@php
    $form = $form ?? [];
    $formatDateTime = static function ($value) {
        if (empty($value)) {
            return '—';
        }

        return \Illuminate\Support\Carbon::parse($value)->format('M j, Y g:i A');
    };
    $yesNo = static fn ($value) => !empty($value) ? 'Yes' : 'No';
    $valueOrDash = static fn ($value) => $value === null || $value === '' ? '—' : $value;
    $categories = $form['selected_categories'] ?? ($form['category_labels'] ?? []);
    if (is_array($categories) && array_values($categories) !== $categories) {
        $categories = array_values($categories);
    }
    $requirements = $form['required_documents'] ?? [];
@endphp

<header style="margin-bottom: 24px;">
    <h1>Event Request Summary</h1>
    <div class="meta">
        Generated on {{ now()->format('M j, Y g:i A') }}
    </div>
</header>

<section>
    <h2>Request Overview</h2>
    <table class="table-small">
        <tbody>
        <tr>
            <th style="width: 25%;">Status</th>
            <td>
                <span class="pill">{{ strtoupper($form['status_label'] ?? $form['status'] ?? 'Pending') }}</span>
            </td>
        </tr>
        <tr>
            <th>Submitted On</th>
            <td>{{ $formatDateTime($form['submitted_at'] ?? null) }}</td>
        </tr>
        <tr>
            <th>Venue</th>
            <td>{{ $valueOrDash($form['venue_name'] ?? 'Pending assignment') }}</td>
        </tr>
        </tbody>
    </table>
</section>

<section>
    <h2>Requester & Organization</h2>
    <table class="spaced">
        <tbody>
        <tr>
            <th>Requester Name</th>
            <td>{{ $valueOrDash($form['requester_name'] ?? null) }}</td>
        </tr>
        <tr>
            <th>Requester Email</th>
            <td>{{ $valueOrDash($form['requester_email'] ?? null) }}</td>
        </tr>
        <tr>
            <th>Student Phone</th>
            <td>{{ $valueOrDash($form['creator_phone_number'] ?? null) }}</td>
        </tr>
        <tr>
            <th>Student ID</th>
            <td>{{ $valueOrDash($form['creator_institutional_number'] ?? null) }}</td>
        </tr>
        <tr>
            <th>Organization</th>
            <td>{{ $valueOrDash($form['organization_name'] ?? null) }}</td>
        </tr>
        <tr>
            <th>Advisor</th>
            <td>
                {{ $valueOrDash($form['organization_advisor_name'] ?? null) }}<br>
                <span class="muted">{{ $valueOrDash($form['organization_advisor_email'] ?? null) }}</span>
            </td>
        </tr>
        </tbody>
    </table>
</section>

<section>
    <h2>Event Details</h2>
    <table class="spaced">
        <tbody>
        <tr>
            <th>Title</th>
            <td>{{ $valueOrDash($form['title'] ?? null) }}</td>
        </tr>
        <tr>
            <th>Description</th>
            <td>{!! nl2br(e($form['description'] ?? '—')) !!}</td>
        </tr>
        <tr>
            <th>Expected Guests</th>
            <td>{{ $valueOrDash($form['guest_size'] ?? null) }}</td>
        </tr>
        <tr>
            <th>Start Time</th>
            <td>{{ $formatDateTime($form['start_time'] ?? null) }}</td>
        </tr>
        <tr>
            <th>End Time</th>
            <td>{{ $formatDateTime($form['end_time'] ?? null) }}</td>
        </tr>
        <tr>
            <th>Venue Code</th>
            <td>{{ $valueOrDash($form['venue_code'] ?? null) }}</td>
        </tr>
        </tbody>
    </table>
</section>

<section>
    <h2>Compliance Checklist</h2>
    <table class="table-small">
        <thead>
        <tr>
            <th>Requirement</th>
            <th>Selection</th>
            <th>Notes</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Food Handling</td>
            <td>{{ $yesNo($form['handles_food'] ?? false) }}</td>
            <td>Required for fundraising or concessions.</td>
        </tr>
        <tr>
            <td>Institutional Funds</td>
            <td>{{ $yesNo($form['use_institutional_funds'] ?? false) }}</td>
            <td>Must follow DSCA purchasing guidelines.</td>
        </tr>
        <tr>
            <td>External Guests</td>
            <td>{{ $yesNo($form['external_guest'] ?? false) }}</td>
            <td>Security review when outside attendees participate.</td>
        </tr>
        </tbody>
    </table>
</section>

<section>
    <h2>Categories</h2>
    @if (!empty($categories))
        <table class="table-small">
            <thead>
            <tr>
                <th style="width: 10%;">#</th>
                <th>Category Name</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($categories as $index => $category)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ is_array($category) ? ($category['name'] ?? '—') : $category }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @else
        <p class="muted">No categories were selected for this request.</p>
    @endif
</section>

<section>
    <h2>Required Documents</h2>
    @if (!empty($requirements))
        <table class="table-small">
            <thead>
            <tr>
                <th style="width: 32%;">Document</th>
                <th>Description</th>
                <th style="width: 18%;">Mandatory</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($requirements as $doc)
                <tr>
                    <td>{{ $valueOrDash($doc['name'] ?? null) }}</td>
                    <td>{{ $valueOrDash($doc['description'] ?? null) }}</td>
                    <td>{{ !empty($doc['required']) ? 'Yes' : 'Optional' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @else
        <p class="muted">The selected venue did not list additional required documents.</p>
    @endif
</section>
</body>
</html>
