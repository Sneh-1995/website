<?php
// create_order.php
session_start();

// load config (or getenv)
$key_id = getenv('RAZORPAY_KEY_ID') ?: 'rzp_test_xxx';
$key_secret = getenv('RAZORPAY_KEY_SECRET') ?: 'your_secret';

// read form
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  die("Invalid input");
}

// create a temporary entry keyed by a client-side temporary id OR use session:
$order_client_id = uniqid('pre_', true);
$_SESSION['pending'][$order_client_id] = ['name'=>$name,'email'=>$email];

// create Razorpay order via API
$amount_in_rupees = 99; // change as needed
$amount_paise = $amount_in_rupees * 100;

$postData = [
  "amount" => $amount_paise,
  "currency" => "INR",
  "receipt" => $order_client_id,
  "payment_capture" => 1
];

$ch = curl_init('https://api.razorpay.com/v1/orders');
curl_setopt($ch, CURLOPT_USERPWD, $key_id . ":" . $key_secret);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$result = curl_exec($ch);
curl_close($ch);

$order = json_decode($result, true);
if (!$order || empty($order['id'])) {
  die("Unable to create order");
}

$order_id = $order['id'];

// store mapping order_id -> session key (for lookup after redirect)
$_SESSION['order_map'][$order_id] = $order_client_id;

// Render a small page to launch Razorpay Checkout with the order_id
?>
<!doctype html><html><head><meta charset="utf-8"></head><body>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
var options = {
  "key": "<?php echo $key_id;?>",
  "amount": "<?php echo $amount_paise;?>",
  "currency": "INR",
  "name": "AWS Kickstart",
  "description": "Course fee",
  "order_id": "<?php echo $order_id;?>",
  "handler": function (response){
    // On success Razorpay gives payment_id, order_id, signature.
    // We'll redirect to register.php (server side) to verify & complete
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = 'register.php';
    var fields = {
      razorpay_payment_id: response.razorpay_payment_id,
      razorpay_order_id: response.razorpay_order_id,
      razorpay_signature: response.razorpay_signature
    };
    for (var k in fields) {
      var inp = document.createElement('input');
      inp.type = 'hidden'; inp.name = k; inp.value = fields[k];
      form.appendChild(inp);
    }
    document.body.appendChild(form);
    form.submit();
  },
  "prefill": { "name": "<?php echo addslashes($name);?>", "email": "<?php echo addslashes($email);?>" },
  "theme": { "color": "#1a73e8" }
};
var rzp = new Razorpay(options);
rzp.open();
</script>
</body></html>