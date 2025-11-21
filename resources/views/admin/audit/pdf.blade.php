@php
  $fmt = function ($dt) {
    if (empty($dt)) return '—';
    try {
      return \Carbon\Carbon::parse($dt)
        ->timezone(config('app.timezone'))
        ->format('Y-m-d H:i:s');
    } catch (\Throwable) {
      return (string) $dt;
    }
  };
@endphp
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
    h1 { font-size: 18px; margin-bottom: 4px; }
    .muted { color: #555; font-size: 10px; }
    table { width:100%; border-collapse: collapse; margin-top: 12px; }
    th, td { border:1px solid #ddd; padding:6px 8px; vertical-align: top; }
    th { background:#f2f4f7; text-align:left; }
    .nowrap { white-space: nowrap; }
  </style>
</head>
<body>
  <h1>Audit Log Export</h1>
  <div class="muted">
    Generated: {{ now()->timezone(config('app.timezone'))->format('Y-m-d H:i:s') }}<br>
    Filters:
    @if(empty($filters))
      none
    @else
      @foreach($filters as $k => $v)
        {{ $k }}={{ $v }}@if(!$loop->last); @endif
      @endforeach
    @endif
  </div>

  <table>
    <thead>
      <tr>
        <th class="nowrap">When</th>
        <th>User</th>
        <th>Action</th>
        <th>Target</th>
        <th>User Agent</th>
        <th>IP</th>
        <th>Meta</th>
      </tr>
    </thead>
    <tbody>
      @forelse($logs as $log)
      <tr>
        <td class="nowrap">{{ $fmt($log->created_at ?? null) }}</td>
        <td>
          @php
            $actor = $log->actor ?? null;
            $name = null;
            if ($actor) {
              $full = trim(($actor->first_name ?? '').' '.($actor->last_name ?? ''));
              $name = $full !== '' ? $full : ($actor->name ?? ($actor->email ?? null));
            }
          @endphp
          {{ $name ?? '—' }}
          @if(!empty($log->user_id))
            (#{{ $log->user_id }})
          @endif
        </td>
        <td>{{ $log->action ?? '' }}</td>
        <td>
          {{ $log->target_type ? class_basename($log->target_type) : '—' }}
          @if(!empty($log->target_id))
            #{{ $log->target_id }}
          @endif
        </td>
        <td>{{ $log->ua ?? ($log->user_agent ?? '—') }}</td>
        <td>{{ $log->ip ?? '—' }}</td>
        <td>
          @php
            $meta = $log->meta ?? [];
            if (!is_array($meta)) {
              $decoded = json_decode((string) $meta, true);
              $meta = is_array($decoded) ? $decoded : [];
            }
          @endphp
          @if(!empty($meta))
            <pre style="white-space: pre-wrap; margin:0;">{{ json_encode($meta, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
          @else
            —
          @endif
        </td>
      </tr>
      @empty
      <tr>
        <td colspan="7" style="text-align:center; padding:12px;">No audit entries found.</td>
      </tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
