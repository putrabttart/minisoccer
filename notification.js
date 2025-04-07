export default async function handler(req, res) {
  if (req.method === 'POST') {
    const notification = req.body;

    // Contoh log biar bisa lihat data notifikasi
    console.log("Midtrans Notification Received:", notification);

    const order_id = notification.order_id;
    const transaction = notification.transaction_status;
    const fraud = notification.fraud_status || '';

    let statusBaru = 'pending';

    if (transaction === 'settlement' || (transaction === 'capture' && fraud !== 'challenge')) {
      statusBaru = 'booked';
    } else if (['expire', 'cancel', 'deny'].includes(transaction)) {
      statusBaru = 'cancelled';
    }

    // Di sini kamu bisa hubungkan ke database menggunakan API eksternal kamu
    // Misal: fetch('https://your-php-api/update-booking.php', {...})

    res.status(200).json({ message: 'Notification received and processed', status: statusBaru });
  } else {
    res.status(405).json({ message: 'Method not allowed' });
  }
}
