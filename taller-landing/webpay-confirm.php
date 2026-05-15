<?php
/**
 * webpay-confirm.php
 * Centro Metabólico · Taller Nutricional
 *
 * Recibe callback de Transbank, confirma transacción y muestra resultado.
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

use Transbank\Webpay\WebpayPlus\Transaction;
use Transbank\Webpay\Options;

$token            = $_POST['token_ws']         ?? $_GET['token_ws']         ?? null;
$tbkToken         = $_POST['TBK_TOKEN']        ?? null;
$tbkOrdenCompra   = $_POST['TBK_ORDEN_COMPRA'] ?? null;

// Credenciales desde .env
$options = env('TRANSBANK_ENV') === 'production'
    ? Options::forProduction(env('TRANSBANK_COMMERCE_CODE'), env('TRANSBANK_API_KEY'))
    : Options::forIntegration(env('TRANSBANK_COMMERCE_CODE'), env('TRANSBANK_API_KEY'));

$tx = new Transaction($options);

// Usuario canceló desde Webpay
if ($tbkToken && $tbkOrdenCompra) {
    actualizarReserva($tbkOrdenCompra, 'cancelada_por_usuario');
    mostrarResultado('cancelada', $tbkOrdenCompra);
    exit;
}

if (!$token) {
    mostrarResultado('error', null, 'No se recibió token de Transbank.');
    exit;
}

try {
    $response = $tx->commit($token);

    $buyOrder     = $response->getBuyOrder();
    $authorized   = $response->isApproved();
    $authCode     = $response->getAuthorizationCode();
    $amount       = $response->getAmount();
    $paymentType  = $response->getPaymentTypeCode();
    $installments = $response->getInstallmentsNumber();
    $cardNumber   = $response->getCardNumber();

    if ($authorized) {
        actualizarReserva($buyOrder, 'pagada', [
            'auth_code'      => $authCode,
            'monto_pagado'   => $amount,
            'tipo_pago'      => $paymentType,
            'cuotas'         => $installments,
            'tarjeta_final'  => $cardNumber,
            'pagado_en'      => date('Y-m-d H:i:s'),
        ]);
        enviarEmailConfirmacion($buyOrder);
        mostrarResultado('exitosa', $buyOrder, null, [
            'auth_code' => $authCode,
            'monto'     => $amount,
            'tarjeta'   => $cardNumber,
            'cuotas'    => $installments,
        ]);
    } else {
        actualizarReserva($buyOrder, 'rechazada');
        mostrarResultado('rechazada', $buyOrder);
    }
} catch (Exception $e) {
    error_log('[Webpay] Error en commit: ' . $e->getMessage());
    mostrarResultado('error', null, $e->getMessage());
}

// ============================================================
// HELPERS
// ============================================================
function actualizarReserva($buyOrder, $estado, $datos = []) {
    $path = __DIR__ . "/reservas/$buyOrder.json";
    if (!file_exists($path)) return;
    $reserva = json_decode(file_get_contents($path), true);
    $reserva['estado'] = $estado;
    $reserva = array_merge($reserva, $datos);
    file_put_contents($path, json_encode($reserva, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function enviarEmailConfirmacion($buyOrder) {
    $path = __DIR__ . "/reservas/$buyOrder.json";
    if (!file_exists($path)) return;
    $r = json_decode(file_get_contents($path), true);

    $to      = $r['email'];
    $subject = '✅ Tu cupo está confirmado · Taller Nutricional Centro Metabólico';
    $body    = "Hola {$r['nombre']},\n\n"
             . "Tu cupo en el Taller Nutricional de Centro Metabólico está confirmado.\n\n"
             . "📅 Fecha: Viernes 20 de Junio · 15:00 a 18:00 hrs\n"
             . "📍 Lugar: Centro Metabólico, Ñuñoa\n"
             . "💳 Orden: $buyOrder\n\n"
             . "Te enviaremos la dirección exacta y materiales de bienvenida en los próximos días.\n\n"
             . "¡Nos vemos pronto!\nEquipo Centro Metabólico";

    $fromEmail = env('MAIL_FROM', 'contacto@centrometabolico.cl');
    $headers   = "From: $fromEmail\r\nReply-To: $fromEmail\r\n";

    @mail($to, $subject, $body, $headers);
    @mail($fromEmail, 'Nueva inscripción taller: ' . $r['nombre'], $body, $headers);
}

function mostrarResultado($estado, $buyOrder, $errorMsg = null, $detalles = []) {
    $colores = [
        'exitosa'   => ['#1DAEEC', '✅', 'Pago confirmado'],
        'rechazada' => ['#DC2626', '❌', 'Pago rechazado'],
        'cancelada' => ['#F59E0B', '⚠️', 'Pago cancelado'],
        'error'     => ['#DC2626', '⚠️', 'Hubo un error'],
    ];
    [$color, $icono, $titulo] = $colores[$estado] ?? $colores['error'];

    $mensajes = [
        'exitosa'   => 'Tu cupo en el taller está confirmado. Te enviamos un email con todos los detalles. ¡Nos vemos pronto!',
        'rechazada' => 'Tu banco rechazó la transacción. Intenta nuevamente con otro medio de pago o contáctanos por WhatsApp.',
        'cancelada' => 'Cancelaste el pago antes de finalizar. Puedes intentar nuevamente cuando quieras.',
        'error'     => 'Ocurrió un error procesando tu pago. Si el cargo aparece en tu tarjeta, contáctanos.',
    ];
    $mensaje = $mensajes[$estado];

    echo "<!DOCTYPE html><html lang='es'><head>
    <meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'>
    <title>$titulo · Centro Metabólico</title>
    <link href='https://fonts.googleapis.com/css2?family=Instrument+Serif&family=Manrope:wght@400;600;700&display=swap' rel='stylesheet'>
    <style>
      body{font-family:'Manrope',sans-serif;background:#FAFBFC;color:#0A1929;display:grid;place-items:center;min-height:100vh;padding:24px;margin:0}
      .card{background:#fff;border:1px solid #E2E8F0;border-radius:22px;padding:48px;max-width:520px;text-align:center;box-shadow:0 20px 50px -25px rgba(10,25,41,.15)}
      .icon{font-size:56px;margin-bottom:20px}
      h1{font-family:'Instrument Serif',serif;font-weight:400;font-size:38px;color:$color;margin:0 0 14px;line-height:1.1}
      p{color:#64748B;font-size:16px;line-height:1.6;margin:0 0 24px}
      .order{background:#F0F9FF;color:#0277BD;padding:12px 20px;border-radius:10px;font-size:13px;font-weight:600;display:inline-block;margin-bottom:24px}
      .details{text-align:left;background:#FAFBFC;border:1px solid #E2E8F0;border-radius:12px;padding:18px;margin-bottom:24px;font-size:14px}
      .details div{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px dashed #E2E8F0}
      .details div:last-child{border:none}
      .details strong{color:#0A1929}
      .btn{display:inline-block;background:#0A1929;color:#fff;padding:14px 28px;border-radius:999px;text-decoration:none;font-weight:600;font-size:15px}
    </style></head><body><div class='card'>
        <div class='icon'>$icono</div><h1>$titulo</h1><p>$mensaje</p>";

    if ($buyOrder) echo "<div class='order'>Orden: $buyOrder</div>";

    if ($estado === 'exitosa' && !empty($detalles)) {
        echo "<div class='details'>
            <div><span>Código de autorización</span><strong>{$detalles['auth_code']}</strong></div>
            <div><span>Monto pagado</span><strong>\$" . number_format($detalles['monto'], 0, ',', '.') . " CLP</strong></div>
            <div><span>Tarjeta</span><strong>**** {$detalles['tarjeta']}</strong></div>
            <div><span>Cuotas</span><strong>{$detalles['cuotas']}</strong></div>
        </div>";
    }
    if ($errorMsg && $estado === 'error') {
        echo "<p style='font-size:12px;color:#94A3B8'>Detalle técnico: " . htmlspecialchars($errorMsg) . "</p>";
    }
    echo "<a href='/' class='btn'>Volver al inicio</a></div></body></html>";
}
