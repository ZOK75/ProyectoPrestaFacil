<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de Verificación - PrestaFácil</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #0f172a;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #f8fafc;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 580px;
            margin: 40px auto;
            background-color: #1e293b;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #334155;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.4);
        }
        .header {
            background: linear-gradient(135deg, #312e81 0%, #1e1b4b 100%);
            padding: 32px 40px;
            text-align: center;
            border-b: 1px solid #3730a3;
        }
        .brand-icon {
            display: inline-block;
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            border-radius: 14px;
            line-height: 48px;
            color: #ffffff;
            font-size: 24px;
            font-weight: bold;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
        }
        .brand-name {
            font-size: 22px;
            font-weight: 800;
            color: #ffffff;
            margin-top: 12px;
            letter-spacing: -0.5px;
        }
        .brand-sub {
            font-size: 11px;
            color: #a5b4fc;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
            margin-top: 2px;
        }
        .content {
            padding: 40px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 12px;
        }
        .text {
            font-size: 14px;
            line-height: 1.6;
            color: #94a3b8;
            margin-bottom: 24px;
        }
        .code-box {
            background-color: #0f172a;
            border: 1px solid #4338ca;
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            margin: 28px 0;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        .code-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #818cf8;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .code-digits {
            font-family: 'Courier New', Courier, monospace;
            font-size: 36px;
            font-weight: 900;
            letter-spacing: 10px;
            color: #ffffff;
            text-shadow: 0 2px 8px rgba(99, 102, 241, 0.4);
        }
        .alert-box {
            background-color: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.25);
            border-radius: 12px;
            padding: 14px 18px;
            font-size: 12px;
            color: #fcd34d;
            margin-top: 24px;
            display: flex;
            align-items: center;
        }
        .footer {
            background-color: #0f172a;
            padding: 24px 40px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #1e293b;
        }
        .footer p {
            margin: 4px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Corporativo -->
        <div class="header">
            <div class="brand-icon">P</div>
            <div class="brand-name">PrestaFácil</div>
            <div class="brand-sub">Sistema de Gestión de Vales</div>
        </div>

        <!-- Contenido Principal -->
        <div class="content">
            <div class="greeting">¡Hola, {{ $user->name ?? 'Usuario' }}!</div>
            <div class="text">
                Has solicitado iniciar sesión en la plataforma <strong>PrestaFácil</strong> como usuario administrativo. Utiliza el siguiente código de seguridad de 6 dígitos para completar tu acceso:
            </div>

            <!-- Código de 6 Dígitos -->
            <div class="code-box">
                <div class="code-title">Código de Verificación 2FA</div>
                <div class="code-digits">{{ $code }}</div>
            </div>

            <div class="text" style="font-size: 13px;">
                Este código de seguridad es de un solo uso y vencerá automáticamente en <strong>10 minutos</strong>.
            </div>

            <div class="alert-box">
                Si no intentaste iniciar sesión en PrestaFácil, ignora este correo. Tu cuenta se mantiene segura.
            </div>
        </div>

        <!-- Footer Corporativo (Sin menciones a Laravel) -->
        <div class="footer">
            <p><strong>PrestaFácil © {{ date('Y') }}</strong> - Todos los derechos reservados.</p>
            <p>Este es un mensaje automático de seguridad generado por el sistema.</p>
        </div>
    </div>
</body>
</html>
