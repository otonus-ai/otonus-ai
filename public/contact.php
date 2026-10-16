<?php
// Contact form handler for cPanel hosting: emails each enquiry to info@otonus.ai.
$to = 'info@otonus.ai';
$back = '/?sent=%d#contact';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /'); exit; }
// Bots fill the hidden field; pretend success and drop it.
if (!empty($_POST['company-website'])) { header('Location: ' . sprintf($back, 1)); exit; }

function field($k, $max = 200) {
  $v = isset($_POST[$k]) ? trim((string)$_POST[$k]) : '';
  return mb_substr(str_replace(["\r", "\n"], ' ', $v), 0, $max);
}
$name = field('name');
$phone = field('phone', 40);
$email = field('email');
$service = field('service', 60);
$message = isset($_POST['message']) ? mb_substr(trim((string)$_POST['message']), 0, 5000) : '';

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { header('Location: ' . sprintf($back, 0)); exit; }

$body = "New enquiry from otonus.ai\n\n"
  . "Name: $name\nPhone: $phone\nEmail: $email\nInterested in: $service\n\nMessage:\n$message\n";
$headers = "From: Otonus.ai Website <noreply@otonus.ai>\r\n"
  . "Reply-To: $email\r\n"
  . "Content-Type: text/plain; charset=UTF-8\r\n";
$ok = mail($to, "New enquiry: $service from $name", $body, $headers, '-fnoreply@otonus.ai');

header('Location: ' . sprintf($back, $ok ? 1 : 0));
