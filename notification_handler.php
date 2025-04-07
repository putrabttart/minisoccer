<?php
require_once 'vendor/autoload.php';
include 'includes/koneksi.php';

\Midtrans\Config::$serverKey = 'Mid-server-h7fNyGU7PqDyvcWqEmukDURe'; // Ganti dengan server key kamu
\Midtrans\Config::$isProduction = false;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

// Ambil raw notifikasi dari Midtrans
$json = file_get_contents('php://input');
$notification = json_decode($json, true);

// Logging untuk debug
file_put_contents('log.txt', date('Y-m-d H:i:s') . ' - ' . $json . "\n", FILE_APPEND);

if ($notification && isset($notification['order_id'])) {
    $transaction = $notification['transaction_status'];
    $order_id = $notification['order_id'];
    $fraud = $notification['fraud_status'] ?? '';

    // Cek dulu apakah order ID valid di DB
    $check = $conn->prepare("SELECT * FROM bookings WHERE order_id = ? LIMIT 1");
    $check->bind_param("s", $order_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        file_put_contents('log.txt', date('Y-m-d H:i:s') . " - Order ID $order_id tidak ditemukan\n", FILE_APPEND);
        exit("Order ID tidak ditemukan.");
    }

    // Mapping status
    $statusBaru = 'pending';
    if ($transaction === 'settlement' || ($transaction === 'capture' && $fraud !== 'challenge')) {
        $statusBaru = 'booked';
    } elseif ($transaction === 'pending') {
        $statusBaru = 'pending';
    } elseif (in_array($transaction, ['expire', 'cancel', 'deny'])) {
        $statusBaru = 'cancelled';
    }

    // Update status ke database
    $update = $conn->prepare("UPDATE bookings SET status = ? WHERE order_id = ?");
    $update->bind_param("ss", $statusBaru, $order_id);
    $update->execute();

    http_response_code(200);
    echo "Notifikasi diproses.";
} else {
    http_response_code(400);
    echo "Notifikasi tidak valid.";
}
?>
