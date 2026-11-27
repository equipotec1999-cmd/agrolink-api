{{--
    Plantilla única de los correos de AgroLink. Todo va con estilos en línea y tablas
    porque Gmail, Outlook y los clientes del celular ignoran casi todo el CSS moderno.
    Variables: $title, $intro, $code (opcional), $lines (párrafos), $note (opcional),
    $logoUrl (opcional), $preheader.
--}}
@php
    $ink = '#1F2A23';
    $forest = '#3F6E52';
    $leaf = '#5E9272';
    $honey = '#CDAE72';
    $clay = '#C98468';
    $bone = '#F4F0E6';
    $paper = '#FFFDF8';
    $text = '#2B3A31';
    $muted = '#6F776F';
    $font = "'Segoe UI', Roboto, Helvetica, Arial, sans-serif";
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background:{{ $bone }};-webkit-text-size-adjust:100%;">
{{-- Texto que se ve en la bandeja de entrada junto al asunto --}}
<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">{{ $preheader }}</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:{{ $bone }};">
<tr><td align="center" style="padding:28px 12px;">

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:520px;">

    {{-- Encabezado: emblema y marca sobre verde monte --}}
    <tr><td style="background:{{ $ink }};border-radius:18px 18px 0 0;padding:28px 24px 22px;text-align:center;">
      @if (! empty($logoUrl))
        <img src="{{ $logoUrl }}" width="64" height="64" alt="AgroLink"
             style="display:block;margin:0 auto 12px;border:0;border-radius:16px;width:64px;height:64px;">
      @endif
      <div style="font-family:{{ $font }};font-size:26px;line-height:30px;font-weight:800;color:#ECEAE4;letter-spacing:0.5px;">
        Agro<span style="color:{{ $honey }};">Link</span>
      </div>
      <div style="font-family:{{ $font }};font-size:12px;line-height:18px;color:#A9B8AC;letter-spacing:2px;text-transform:uppercase;margin-top:4px;">
        El mercado del campo
      </div>
    </td></tr>

    {{-- Surcos: franja de colores de la marca --}}
    <tr><td style="padding:0;line-height:0;font-size:0;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
        <td width="40%" height="6" style="background:{{ $leaf }};height:6px;"></td>
        <td width="35%" height="6" style="background:{{ $honey }};height:6px;"></td>
        <td width="25%" height="6" style="background:{{ $clay }};height:6px;"></td>
      </tr></table>
    </td></tr>

    {{-- Cuerpo --}}
    <tr><td style="background:{{ $paper }};padding:32px 28px 8px;">
      <h1 style="margin:0 0 12px;font-family:{{ $font }};font-size:22px;line-height:28px;font-weight:700;color:{{ $text }};">
        {{ $title }}
      </h1>
      @if (! empty($intro))
        <p style="margin:0 0 20px;font-family:{{ $font }};font-size:15px;line-height:23px;color:{{ $text }};">{{ $intro }}</p>
      @endif

      @if (! empty($code))
        {{-- El código, grande y separado por dígito para leerlo sin errores --}}
        <table role="presentation" align="center" cellpadding="0" cellspacing="0" border="0" style="margin:8px auto 10px;">
          <tr>
            @foreach (str_split($code) as $digit)
              <td style="padding:0 3px;">
                <div style="width:42px;height:54px;line-height:54px;text-align:center;background:#EEF3EC;border:2px solid {{ $leaf }};border-radius:10px;font-family:'Courier New', Courier, monospace;font-size:28px;font-weight:700;color:{{ $forest }};">{{ $digit }}</div>
              </td>
            @endforeach
          </tr>
        </table>
        <p style="margin:0 0 24px;text-align:center;font-family:{{ $font }};font-size:13px;line-height:19px;color:{{ $muted }};">
          Vence en <strong style="color:{{ $text }};">15 minutos</strong>
        </p>
      @endif

      @foreach ($lines ?? [] as $line)
        <p style="margin:0 0 14px;font-family:{{ $font }};font-size:15px;line-height:23px;color:{{ $text }};">{!! nl2br(e($line)) !!}</p>
      @endforeach

      @if (! empty($note))
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:10px 0 24px;">
          <tr><td style="background:#FBF4E4;border-left:4px solid {{ $honey }};border-radius:8px;padding:12px 14px;font-family:{{ $font }};font-size:13px;line-height:20px;color:#6B5A33;">
            {{ $note }}
          </td></tr>
        </table>
      @endif
    </td></tr>

    {{-- Pie --}}
    <tr><td style="background:{{ $paper }};border-radius:0 0 18px 18px;padding:0 28px 26px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr><td style="border-top:1px solid #E4DFD2;padding-top:16px;font-family:{{ $font }};font-size:12px;line-height:18px;color:{{ $muted }};text-align:center;">
          🌱 Compra y vende ganado, cosechas y productos del campo cerca de ti.<br>
          AgroLink · Tizimín, Yucatán, México<br>
          <span style="color:#9AA197;">Este es un correo automático, no lo respondas.</span>
        </td></tr>
      </table>
    </td></tr>

  </table>

</td></tr>
</table>
</body>
</html>
