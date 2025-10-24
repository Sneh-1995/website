<?php
session_start();

// config
$key_id = getenv('RAZORPAY_KEY_ID') ?: 'rzp_test_xxx';
$key_secret = getenv('RAZORPAY_KEY_SECRET') ?: 'your_secret';
$webhook_secret = getenv('WEBHOOK_SECRET') ?: 'MySuperSecret_987654321!';

// read Razorpay POSTed fields
$payment_id = $_POST['razorpay_payment_id'] ?? '';
$order_id = $_POST['razorpay_order_id'] ?? '';
$signature = $_POST['razorpay_signature'] ?? '';

if (!$payment_id || !$order_id || !$signature) {
  die("Missing payment info");
}

// verify signature (Razorpay: expected = HMAC_SHA256(order_id + '|' + payment_id, key_secret))
$payload = $order_id . '|' . $payment_id;
$expected_sig = hash_hmac('sha256', $payload, $key_secret);
if (!hash_equals($expected_sig, $signature)) {
  die("Invalid signature");
}

// lookup pending registration
$client_key = $_SESSION['order_map'][$order_id] ?? null;
$pending = $client_key ? ($_SESSION['pending'][$client_key] ?? null) : null;

// fallback: if you used pending_registrations.csv, lookup order_id there
$name = $pending['name'] ?? 'Participant';
$email = $pending['email'] ?? '';

// finalize: log and send email and webhook
$event_id = $order_id; // or use uniqid
$created_at = gmdate('c');

// append to registrations.csv
$fp = fopen('registrations.csv', 'a');
if ($fp) {
  fputcsv($fp, [$event_id,$created_at,$name,$email]);
  fclose($fp);
}

// send confirmation email (use SMTP in prod)
$subject = "Payment confirmed — AWS Kickstart";
$message = "Hi $name,\n\nYour payment (ID: $payment_id) is confirmed. Welcome!\n\nRegards,\nSnehal";
$headers = "From: Snehal <no-reply@ssdblog.com>\r\n";
@mail($email, $subject, $message, $headers);

// forward signed JSON to your webhook.php (optional but recommended)
$payload = json_encode([
  "event"=>"payment.success",
  "id"=>$event_id,
  "created_at"=>$created_at,
  "data"=>["name"=>$name,"email"=>$email,"payment_id"=>$payment_id,"order_id"=>$order_id]
]);
$sig = 'sha256=' . hash_hmac('sha256', $payload, $webhook_secret);
$ch = curl_init('https://' . $_SERVER['HTTP_HOST'] . '/webhook.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json','X-Webhook-Signature: '.$sig]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_TIMEOUT, 4);
curl_exec($ch);
curl_close($ch);

// redirect user to thank you page
header("Location: thankyou.html");
exit;