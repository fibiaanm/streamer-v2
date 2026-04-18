<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invitación a {{ $enterpriseName }}</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { background-color: #f4f6f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1a1a2e; }
    .wrapper { max-width: 560px; margin: 40px auto; padding: 0 16px 40px; }
    .card { background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
    .header { background: #0d1426; padding: 32px; text-align: center; }
    .header-logo { font-size: 18px; font-weight: 700; color: #ffffff; letter-spacing: -0.3px; }
    .header-logo span { color: #6366f1; }
    .body { padding: 40px 32px; }
    .avatar { width: 56px; height: 56px; border-radius: 50%; background: #eef2ff; color: #6366f1; font-size: 22px; font-weight: 700; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; border: 3px solid #e0e7ff; text-transform: uppercase; line-height: 56px; text-align: center; }
    h1 { font-size: 22px; font-weight: 700; color: #0d1426; text-align: center; margin-bottom: 12px; line-height: 1.3; }
    .subtitle { font-size: 15px; color: #64748b; text-align: center; margin-bottom: 32px; line-height: 1.6; }
    .subtitle strong { color: #334155; }
    .role-badge { display: inline-block; background: #eef2ff; color: #6366f1; font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 100px; letter-spacing: 0.3px; text-transform: capitalize; margin: 0 auto 32px; }
    .badge-wrapper { text-align: center; margin-bottom: 32px; }
    .cta { display: block; background: #6366f1; color: #ffffff !important; text-decoration: none; font-size: 15px; font-weight: 600; padding: 14px 32px; border-radius: 10px; text-align: center; margin-bottom: 24px; letter-spacing: 0.1px; }
    .cta:hover { background: #4f46e5; }
    .divider { border: none; border-top: 1px solid #f1f5f9; margin: 24px 0; }
    .link-fallback { font-size: 13px; color: #94a3b8; text-align: center; line-height: 1.6; }
    .link-fallback a { color: #6366f1; word-break: break-all; }
    .expiry { font-size: 13px; color: #94a3b8; text-align: center; margin-top: 16px; }
    .footer { padding: 20px 32px; background: #f8fafc; border-top: 1px solid #f1f5f9; text-align: center; }
    .footer p { font-size: 12px; color: #94a3b8; line-height: 1.6; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="card">

      <div class="header">
        <div class="header-logo">streamer<span>.</span></div>
      </div>

      <div class="body">
        <div class="avatar">{{ strtoupper(substr($invitedByName, 0, 1)) }}</div>

        <h1>{{ $invitedByName }} te invita a unirte a<br>{{ $enterpriseName }}</h1>

        <p class="subtitle">
          Has sido invitado a colaborar en <strong>{{ $enterpriseName }}</strong>.<br>
          Acepta la invitación para empezar.
        </p>

        <div class="badge-wrapper">
          <span class="role-badge">{{ $roleName }}</span>
        </div>

        <a href="{{ $acceptUrl }}" class="cta">Aceptar invitación</a>

        <hr class="divider">

        <p class="link-fallback">
          Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
          <a href="{{ $acceptUrl }}">{{ $acceptUrl }}</a>
        </p>

        <p class="expiry">Esta invitación expira el <strong>{{ $expiresAt }}</strong>.</p>
      </div>

      <div class="footer">
        <p>Si no esperabas esta invitación, puedes ignorar este correo con seguridad.<br>
        &copy; {{ date('Y') }} {{ config('app.name') }}</p>
      </div>

    </div>
  </div>
</body>
</html>
