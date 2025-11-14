<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitud de Autorización</title>
    <style>
        @page {
            size: 8.5in 11in;
            margin: 0.45in;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 10.5px;
            color: #000;
            margin: 0;
            background: #fff;
        }
        .sheet {
            width: 100%;
            margin: 0 auto;
            padding: 0;
        }
        .letterhead {
            text-align: center;
            text-transform: uppercase;
            font-size: 10.5px;
            line-height: 1.3;
        }
        .banner {
            margin-top: 8px;
            background: #12a138;
            color: #fff;
            text-align: center;
            font-weight: bold;
            padding: 7px 9px;
            border: 1px solid #0e7a2b;
            font-size: 10.5px;
            line-height: 1.3;
        }
        .date-box {
            margin-top: 10px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 6px;
            font-size: 10.5px;
            font-weight: 600;
        }
        .date-value {
            font-weight: 700;
        }
        .form-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            border: 1px solid #0e7a2b;
            table-layout: fixed;
        }
        .form-table th,
        .form-table td {
            border: 1px solid #b7c2b7;
            padding: 6px 8px;
            vertical-align: top;
        }
        .form-table__title-row th {
            background: #12a138;
            color: #fff;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.4px;
            border: none;
        }
        .form-table__label {
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            color: #0f4f23;
            background: #f5fbf6;
        }
        .form-table__value {
            font-size: 10.5px;
            background: #fff;
        }
        .footer {
            margin-top: 12px;
            font-size: 9px;
            line-height: 1.4;
        }
    </style>
</head>
<body>
@php
    use Illuminate\Support\Carbon;

    $form = $form ?? [];
    $value = static fn (string $key, ?string $fallback = null) => trim((string) ($form[$key] ?? '')) ?: ($fallback ?? '');
    $schedule = static function (?string $start, ?string $end): string {
        if (!$start && !$end) {
            return '';
        }
        $startLabel = $start ? Carbon::parse($start)->format('g:i A') : '—';
        $endLabel = $end ? Carbon::parse($end)->format('g:i A') : '—';
        return "{$startLabel} - {$endLabel}";
    };
    $dateSource = $form['start_time'] ?? $form['submitted_at'] ?? now();
    $date = Carbon::parse($dateSource);
    $currentDateDisplay = $date->copy()->locale('es')->isoFormat('D [de] MMMM [de] YYYY');
    $applicantName = $value('requester_name') ?: trim($value('creator_first_name', '') . ' ' . $value('creator_last_name', ''));
    $activityDateSource = $form['start_time'] ?? $dateSource;
    $activityDate = Carbon::parse($activityDateSource)->locale('es')->isoFormat('D [de] MMMM [de] YYYY');
    $scheduleLabel = $schedule($form['start_time'] ?? null, $form['end_time'] ?? null) ?: '—';
    $organizationName = $value('organization_name', '—');
    $activityTitle = $value('title', '—');
    $activityDescription = trim((string)($form['description'] ?? ''));
    $activityDescriptionDisplay = $activityDescription !== '' ? $activityDescription : '—';
    $venueName = $value('venue_name', 'Por confirmar');
    $guestSize = $value('guest_size', '—');
    $venueCode = $value('venue_code', '—');
    $requesterId = $value('creator_institutional_number', '—');
    $requesterPhone = $value('creator_phone_number', '—');
    $advisorName = $value('organization_advisor_name', '—');
    $advisorPhone = $value('organization_advisor_phone', '—');
    $signatureApplicant = trim((string)($form['applicant_signature'] ?? '')) ?: '—';
    $signatureAdvisor = trim((string)($form['advisor_signature'] ?? '')) ?: '—';
    $statusLabel = strtoupper((string)($form['status_label'] ?? $form['status'] ?? ''));
    $statusLabel = $statusLabel !== '' ? $statusLabel : '—';
    $observations = trim((string)($form['observations'] ?? ''));
    $observations = $observations !== '' ? $observations : '—';
@endphp

<div class="sheet">
    <div class="letterhead">
        UNIVERSIDAD DE PUERTO RICO<br>
        RECINTO UNIVERSITARIO DE MAYAGÜEZ<br>
        DECANATO DE ESTUDIANTES<br>
        DEPARTAMENTO DE ACTIVIDADES SOCIALES Y CULTURALES<br>
        MAYAGÜEZ, PUERTO RICO
    </div>

    <div class="banner">
        SOLICITUD DE AUTORIZACIÓN PARA LA CELEBRACIÓN DE ACTIVIDADES ESTUDIANTILES
        Y RESERVACIÓN DE INSTALACIONES FÍSICAS
    </div>

    <div class="date-box">
        Fecha:
        <span class="date-value">{{ $currentDateDisplay }}</span>
    </div>

    <table class="form-table">
        <thead>
            <tr class="form-table__title-row">
                <th colspan="3">I. INFORMACIÓN GENERAL DE LA ACTIVIDAD</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="3" class="form-table__label">
                    Nombre de la Organización:
                </td>
            </tr>
            <tr>
                <td colspan="3" class="form-table__value">
                    {{ $organizationName }}
                </td>
            </tr>
            <tr>
                <td class="form-table__label">
                    Nombre de la Actividad:
                </td>
                <td colspan="2" class="form-table__label">
                    Descripción de la Actividad:
                </td>
            </tr>
            <tr>
                <td class="form-table__value">
                    {{ $activityTitle }}
                </td>
                <td colspan="2" class="form-table__value">
                    {!! nl2br(e($activityDescriptionDisplay)) !!}
                </td>
            </tr>
            <tr>
                <td colspan="2" class="form-table__label">
                    Fecha de la Actividad:
                </td>
                <td class="form-table__label">
                    Hora de la Actividad:
                </td>
            </tr>
            <tr>
                <td colspan="2" class="form-table__value">
                    {{ $activityDate }}
                </td>
                <td class="form-table__value">
                    {{ $scheduleLabel }}
                </td>
            </tr>
            <tr>
                <td colspan="2" class="form-table__label">
                    Lugar de la Actividad:
                </td>
                <td class="form-table__label">
                    Número Estimado de Participantes:
                </td>
            </tr>
            <tr>
                <td colspan="2" class="form-table__value">
                    {{ $venueName }}
                </td>
                <td class="form-table__value">
                    {{ $guestSize }}
                </td>
            </tr>
            <tr>
                <td colspan="3" class="form-table__label">
                    Código de la Instalación:
                </td>
            </tr>
            <tr>
                <td colspan="3" class="form-table__value">
                    {{ $venueCode }}
                </td>
            </tr>
            <tr>
                <td class="form-table__label">
                    Nombre del Solicitante:
                </td>
                <td class="form-table__label">
                    Número de Identificación del Solicitante:
                </td>
                <td class="form-table__label">
                    Teléfono del Solicitante:
                </td>
            </tr>
            <tr>
                <td class="form-table__value">
                    {{ $applicantName ?: '—' }}
                </td>
                <td class="form-table__value">
                    {{ $requesterId }}
                </td>
                <td class="form-table__value">
                    {{ $requesterPhone }}
                </td>
            </tr>
            <tr>
                <td colspan="2" class="form-table__label">
                    Nombre del Consejero:
                </td>
                <td class="form-table__label">
                    Teléfono del Consejero:
                </td>
            </tr>
            <tr>
                <td colspan="2" class="form-table__value">
                    {{ $advisorName }}
                </td>
                <td class="form-table__value">
                    {{ $advisorPhone }}
                </td>
            </tr>
            <tr>
                <td colspan="2" class="form-table__label">
                    Firma electrónica del Solicitante:
                </td>
                <td class="form-table__label">
                    Firma electrónica del Consejero:
                </td>
            </tr>
            <tr>
                <td colspan="2" class="form-table__value">
                    {{ $signatureApplicant }}
                </td>
                <td class="form-table__value">
                    {{ $signatureAdvisor }}
                </td>
            </tr>
        </tbody>
    </table>

    <table class="form-table">
        <thead>
            <tr class="form-table__title-row">
                <th colspan="2">PARTE II: PARA USO OFICIAL DEL ENCARGADO DE LAS INSTALACIONES FÍSICAS</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="form-table__label">
                    Nombre del Funcionario:
                </td>
                <td class="form-table__label">
                    Departamento o Unidad:
                </td>
            </tr>
            <tr>
                <td class="form-table__value">&nbsp;</td>
                <td class="form-table__value">&nbsp;</td>
            </tr>
            <tr>
                <td class="form-table__label">
                    Fecha de firma:
                </td>
                <td class="form-table__label">
                    Firma del Funcionario:
                </td>
            </tr>
            <tr>
                <td class="form-table__value">&nbsp;</td>
                <td class="form-table__value">&nbsp;</td>
            </tr>
        </tbody>
    </table>

    <table class="form-table">
        <thead>
            <tr class="form-table__title-row">
                <th colspan="2">PARTE III - PARA USO OFICIAL DEL DEPARTAMENTO DE ACTIVIDADES SOCIALES Y CULTURALES</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="form-table__label">
                    Estado de Solicitud:
                </td>
                <td class="form-table__value">
                    {{ $statusLabel }}
                </td>
            </tr>
            <tr>
                <td colspan="2" class="form-table__label">
                    Observaciones:
                </td>
            </tr>
            <tr>
                <td colspan="2" class="form-table__value">
                    {!! nl2br(e($observations)) !!}
                </td>
            </tr>
            <tr>
                <td class="form-table__label">
                    Fecha de firma:
                </td>
                <td class="form-table__label">
                    Firma del Funcionario:
                </td>
            </tr>
            <tr>
                <td class="form-table__value">&nbsp;</td>
                <td class="form-table__value">&nbsp;</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Original – Departamento de Actividades Sociales y Culturales<br>
        Copia – Encargado de las Instalaciones Físicas<br><br>
        Título IX Prohíbe Discriminaciones por razón de Sexo en Programas Educativos y de Empleo en el Recinto Universitario de Mayagüez – Patrono con Igualdad de Oportunidades de Empleo M/F/V/I<br>
        DE 12/90&nbsp;&nbsp;&nbsp;&nbsp;8/2013
    </div>
</div>
</body>

</html>
