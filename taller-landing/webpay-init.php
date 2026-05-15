<?php
/**
 * webpay-init.php
 * Centro Metabólico · Taller Nutricional
 *
 * Inicia transacción Webpay Plus y redirige a Transbank.
 * Las credenciales se cargan desde el archivo .env (NUNCA subir a Git).
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

use Transbank\Webpay\WebpayPlus\Transaction;
use Transbank\Webpay\Options;

// ============================================================
// 1. VALIDACIÓN BÁSICA
// ============================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Método no permitido');
}

$nombre   = trim($_POST['nombre']   ?? '');
$email    = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$telefono = trim($_POST['telefono'] ?? '');
$rut      = trim($_POST['rut']      ?? '');
$edad     = (int)($_POST['edad']    ?? 0);
$objetivo = trim($_POST['objetivo'] ?? '');
$comentario = trim($_POST['comentario'] ?? '');
$monto    = (int)($_POST['monto']   ?? 0);

if (!$nombre || !$email || !$telefono || !$rut || !$objetivo || $monto <= 0) {
    http_response_code(400);
    die('Faltan datos obligatorios.');
}

// ============================================================
// 2. IDENTIFICADORES
// ============================================================
$buyOrder  = 'CM-' . date('YmdHis') . '-' . rand(1000, 9999);
$sessionId = uniqid('sess_', true);
$returnUrl = env('APP_URL') . '/webpay-confirm.php';

// ============================================================
// 3. GUARDAR RESERVA PENDIENTE
// ============================================================
$reserva = [
    'buy_order'  => $buyOrder,
    'session_id' => $sessionId,
    'nombre'     => $nombre,
    'email'      => $email,
    'telefono'   => $telefono,
    'rut'        => $rut,
    'edad'       => $edad,
    'objetivo'   => $objetivo,
    'comentario' => $comentario,
    'monto'      => $monto,
    'estado'     => 'pendiente',
    'creado'     => date('Y-m-d H:i:s'),
];

$reservasDir = __DIR__ . '/reservas';
if (!is_dir($reservasDir)) mkdir($reservasDir, 0750, true);
file_put_contents(
    "$reservasDir/$buyOrder.json",
    json_encode($reserva, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

// ============================================================
// 4. CONFIGURAR WEBPAY (credenciales desde .env)
// ============================================================
$options = env('TRANSBANK_ENV') === 'production'
    ? Options::forProduction(env('TRANSBANK_COMMERCE_CODE'), env('TRANSBANK_API_KEY'))
    : Options::forIntegration(env('TRANSBANK_COMMERCE_CODE'), env('TRANSBANK_API_KEY'));

$tx = new Transaction($options);

// ============================================================
// 5. CREAR TRANSACCIÓN Y REDIRIGIR
// ============================================================
try {
    $response = $tx->create($buyOrder, $sessionId, $monto, $returnUrl);

    $reserva['token_ws'] = $response->getToken();
    file_put_contents(
        "$reservasDir/$buyOrder.json",
        json_encode($reserva, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    $url   = $response->getUrl();
    $token = $response->getToken();

    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Redirigiendo…</title></head>
    <body onload='document.forms[0].submit()'>
        <p style='font-family:sans-serif;text-align:center;padding:40px'>Redirigiendo al portal de pagos seguros de Transbank…</p>
        <form action='$url' method='POST'><input type='hidden' name='token_ws' value='$token'></form>
    </body></html>";
    exit;

} catch (Exception $e) {
    error_log('[Webpay] Error al crear: ' . $e->getMessage());
    http_response_code(500);
    die('Lo sentimos, hubo un error al iniciar el pago. Intenta nuevamente o contáctanos por WhatsApp.');
}
