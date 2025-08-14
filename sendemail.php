<?php
$to = "wijesekararsc@gmail.com";
$subject = "Sample Message from Evento";
$message = "Hello,\n\nThis is a test message sent from your PHP application.\n\nRegards,\nEvento Team";
$headers = "From: noreply@evento.com\r\n";
$headers .= "Reply-To: noreply@evento.com\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

if (mail($to, $subject, $message, $headers)) {
    echo "Email successfully sent to $to";
} else {
    echo "Failed to send email.";
}
?>
