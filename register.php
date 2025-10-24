<?php
// register.php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name  = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);

    // --- 1️⃣ Send confirmation email to user ---
    $subject = "Welcome to AWS Solution Architect Kickstart!";
    $message = "
    Hi $name,

    🎉 Thank you for registering for the AWS Career Kickstart program!
    You’ll receive course access details soon.

    Best regards,
    Snehal Dhamdar
    AWS Certified Solutions Architect
    https://ssdblog.com
    ";

    $headers = "From: Snehal Dhamdar <no-reply@ssdblog.com>\r\n";
    $headers .= "Reply-To: snehal.pdhamdar@gmail.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    mail($email, $subject, $message, $headers);

    // --- 2️⃣ Prepare webhook payload ---
    $data = [
        "event" => "registration.created",
        "id" => uniqid("evt_", true),
        "data" => [
            "name" => $name,
            "email" => $email,
            "source" => "ssdblog.com/form"
        ]
    ];
    $json_data = json_encode($data);

    // --- 3️⃣ Generate HMAC signature ---
    $secret = "MySuperSecret_987654321!"; // same secret as webhook.php
    $signature = 'sha256=' . hash_hmac('sha256', $json_data, $secret);

    // --- 4️⃣ Send POST to webhook.php ---
    $ch = curl_init("https://ssdblog.com/webhook.php");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Webhook-Signature: ' . $signature
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_exec($ch);
    curl_close($ch);

    // --- 5️⃣ Redirect user to Thank You pag
