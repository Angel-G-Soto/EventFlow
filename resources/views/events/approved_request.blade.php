{{-- UPRM request (read-only) fed by Event + requester + venue --}}
@php
    $safe    = fn($v) => filled($v) ? $v : '—';
    $fmtDate = fn($d) => optional($d)->timezone(config('app.timezone'))->format('d/m/Y');
    $fmtTime = fn($d) => optional($d)->timezone(config('app.timezone'))->format('h:i A');

    $starts = optional($event->start_time);
    $ends   = optional($event->end_time);

    // Compute SIGLAS from organization_name (kept but commented out below)
    $orgSiglas = null;
    if (filled($event->organization_name)) {
        preg_match_all('/\b[A-ZÁÉÍÓÚÑ]/u', mb_strtoupper($event->organization_name), $m);
        $orgSiglas = implode('', $m[0]) ?: null;
    }

    // Applicant (creator/requester)
    $applicantName  = $event->requester?->full_name ?? $event->requester?->name;
    $applicantTitle = $event->requester?->position_title ?? null;
@endphp

    <!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Solicitud de Autorización – {{ $safe($event->title) }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        :root { --green:#2aa756; --border:#111; --muted:#555; }
        * { box-sizing:border-box; }
        body { font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial; margin:24px; color:#111; }
        .header { background:var(--green); color:#fff; text-align:center; padding:10px 14px; border-radius:8px; font-weight:700; }
        .row { display:grid; grid-template-columns: 240px 1fr; gap:10px; align-items:center; margin-top:8px; }
        .label { font-weight:600; color:#0f172a; }
        .underline { border-bottom:1px solid #000; min-height:26px; padding:3px 0; }
        .box { border:1px solid var(--border); border-radius:6px; padding:12px; margin-top:14px; }
        .title { color:#16a34a; font-weight:800; margin:18px 0 8px; }
        .grid2 { display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
        .grid3 { display:grid; grid-template-columns: 1fr 1fr 1fr; gap:12px; }
        .signature { border-top:1px solid #000; height:2px; margin-top:26px; }
        .muted { color:var(--muted); font-size:12px; }
        @media print { body { margin:0; } .no-print { display:none; } }
    </style>
</head>
<body>

<div class="header">
    SOLICITUD DE AUTORIZACIÓN PARA LA CELEBRACIÓN DE ACTIVIDADES ESTUDIANTILES<br>
    Y RESERVACIÓN DE INSTALACIONES FÍSICAS
</div>

<div class="muted" style="text-align:right; margin-top:6px">
    Fecha de emisión: {{ now()->format('d/m/Y') }}
</div>

{{-- Parte I --}}
<h3 class="title">Parte I – Información General de la Actividad del Solicitante y del Consejero</h3>
<div class="box">
    <div class="row">
        <div class="label">Nombre de la Organización</div>
        <div class="underline">{{ $safe($event->organization_name) }}</div>
    </div>

    {{-- Siglas (NO requerido) --}}
    {{--
    <div class="row">
      <div class="label">Siglas</div>
      <div class="underline">{{ $safe($orgSiglas) }}</div>
    </div>
    --}}

    <div class="row">
        <div class="label">Nombre y Descripción de la Actividad</div>
        <div class="underline">{{ $safe($event->title) }} — {{ $safe($event->description) }}</div>
    </div>

    <div class="row">
        <div class="label">Conferenciante / Artista / Participantes</div>
        <div class="underline">{{ $safe($event->guest_list_text ?? null) }}</div>
    </div>

    <div class="grid2">
        <div class="row">
            <div class="label">Fecha</div>
            <div class="underline">{{ $fmtDate($starts) }}</div>
        </div>
        <div class="row">
            <div class="label">Horario (Desde – Hasta)</div>
            <div class="underline">{{ $fmtTime($starts) }} – {{ $fmtTime($ends) }}</div>
        </div>
    </div>

    <div class="grid2">
        <div class="row">
            <div class="label">Instalación Física Solicitada</div>
            <div class="underline">{{ $safe($event->venue?->name) }}</div>
        </div>

        {{-- Núm. Asistentes (NO requerido) --}}
        {{--
        <div class="row">
          <div class="label">Núm. Asistentes</div>
          <div class="underline">{{ $safe($event->guest_size) }}</div>
        </div>
        --}}
    </div>

    <div class="grid3">
        <div class="row">
            <div class="label">Nombre del Solicitante</div>
            <div class="underline">{{ $safe($applicantName) }}</div>
        </div>
        <div class="row">
            <div class="label">Número de Identificación</div>
            <div class="underline">{{ $safe($event->creator_institutional_number) }}</div>
        </div>
        <div class="row">
            <div class="label">Puesto que Ocupa</div>
            <div class="underline">{{ $safe($applicantTitle) }}</div>
        </div>
    </div>

    <div class="grid2">
        <div class="row">
            <div class="label">Teléfono del Solicitante</div>
            <div class="underline">{{ $safe($event->creator_phone_number) }}</div>
        </div>
        <div class="row">
            <div class="label">Maneja alimentos</div>
            <div class="underline">{{ $event->handles_food ? 'Sí' : 'No' }}</div>
        </div>
    </div>

    <div class="grid2">
        <div class="row">
            <div class="label">Fondos institucionales</div>
            <div class="underline">{{ $event->use_institutional_funds ? 'Sí' : 'No' }}</div>
        </div>
        <div class="row">
            <div class="label">Invitado externo</div>
            <div class="underline">{{ $event->external_guest ? 'Sí' : 'No' }}</div>
        </div>
    </div>

    <div class="grid2">
        <div class="row">
            <div class="label">Nombre del Consejero</div>
            <div class="underline">{{ $safe($event->organization_advisor_name) }}</div>
        </div>
        <div class="row">
            <div class="label">Correo del Consejero</div>
            <div class="underline">{{ $safe($event->organization_advisor_email) }}</div>
        </div>
    </div>

    {{-- Dirección Postal y Residencial Local del solicitante si es diferente a la postal (NO requerido) --}}
    {{--
    <div class="row">
      <div class="label">Dirección Postal / Residencial (si difiere)</div>
      <div class="underline">—</div>
    </div>
    --}}

    {{-- Dirección del Consejero (NO requerido) --}}
    {{--
    <div class="row">
      <div class="label">Dirección del Consejero (Facultad / Departamento / Oficina)</div>
      <div class="underline">—</div>
    </div>
    --}}

    <div class="grid2" style="margin-top:18px">
        <div>
            <div class="signature"></div>
            <div class="muted">Firma del Consejero</div>
        </div>
        <div>
            <div class="signature"></div>
            <div class="muted">Firma del Solicitante</div>
        </div>
    </div>
</div>

{{-- Parte II – Encargado de Instalaciones (se mantiene visible; campos vacíos) --}}
<h3 class="title">Parte II – Para Uso Oficial del Encargado de las Instalaciones Físicas</h3>
<div class="box">
    <div class="row"><div class="label">Disponibilidad</div><div class="underline">—</div></div>
    <div class="grid3" style="margin-top:10px">
        <div class="row"><div class="label">Nombre del Funcionario</div><div class="underline">—</div></div>
        <div class="row"><div class="label">Facultad/Departamento/Unidad</div><div class="underline">—</div></div>
        <div class="row"><div class="label">Teléfono</div><div class="underline">—</div></div>
    </div>
    <div class="grid2" style="margin-top:18px">
        <div><div class="signature"></div><div class="muted">Fecha</div></div>
        <div><div class="signature"></div><div class="muted">Firma del Funcionario</div></div>
    </div>
</div>

{{-- Parte III – Decanato de Administración (NO requerido: comentar TODA la sección) --}}
{{--
<h3 class="title">Parte III – Para Uso Oficial de la Oficina del Decano de Administración</h3>
<div class="box">
  <div class="row"><div class="label">Uso de aire acondicionado</div><div class="underline">—</div></div>
  <div class="grid2" style="margin-top:18px">
    <div><div class="signature"></div><div class="muted">Fecha</div></div>
    <div><div class="signature"></div><div class="muted">Firma del Decano o Representante</div></div>
  </div>
</div>
--}}

{{-- Parte IV – Departamento de Actividades Sociales y Culturales (se mantiene) --}}
<h3 class="title">Parte IV – Para Uso Oficial del Departamento de Actividades Sociales y Culturales</h3>
<div class="box">
    <div class="row"><div class="label">Estatus</div><div class="underline">—</div></div>
    <div class="row"><div class="label">Observaciones</div><div class="underline">—</div></div>
    <div class="grid2" style="margin-top:18px">
        <div><div class="signature"></div><div class="muted">Fecha</div></div>
        <div><div class="signature"></div><div class="muted">Firma del Director del Departamento</div></div>
    </div>
</div>

<p class="muted" style="margin-top:12px">
    Copia: Encargado de las Instalaciones Físicas • Original: Departamento de Actividades Sociales y Culturales
</p>

<div class="no-print" style="margin-top:10px"><button onclick="window.print()">Imprimir</button></div>
</body>
</html>
