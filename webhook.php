<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name  = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);

    // Email subject and message
    $subject = "Welcome to AWS Solution Architect Kickstart!";
    $message = "
    Hi $name,

    🎉 Thank you for registering for the AWS Solution Architect Career Kickstart program!

    You’ll soon receive details about sessions, materials, and how to get started.
    
    Best Regards,
    Snehal Dhamdar
    AWS Certified Solutions Architect
    https://ssdblog.com
    ";

    $headers = "From: Snehal Dhamdar <no-reply@ssdblog.com>\r\n";
    $headers .= "Reply-To: snehal.pdhamdar@gmail.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Send email
    mail($email, $subject, $message, $headers);

    // Redirect to Thank You page
    header("Location: thankyou.html");
    exit();
}
?>
