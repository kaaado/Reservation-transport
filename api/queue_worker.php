<?php
ignore_user_abort(true);
set_time_limit(0);

require_once __DIR__ . '/../core/paths.php';
require_once ROOT_PATH . 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    $dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
    $dotenv->load();
} catch (\Exception $e) {}

$queue_file = ROOT_PATH . 'cache/email_queue.json';
if (!file_exists($queue_file)) die('No queue');

$queue = json_decode(file_get_contents($queue_file), true);
if (empty($queue)) die('Empty queue');

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = $_ENV['EMAIL_HOST'] ?? $_SERVER['EMAIL_HOST'] ?? 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['EMAIL_USER'] ?? $_SERVER['EMAIL_USER'] ?? '';
    $mail->Password   = $_ENV['EMAIL_PASS'] ?? $_SERVER['EMAIL_PASS'] ?? '';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $_ENV['EMAIL_PORT'] ?? $_SERVER['EMAIL_PORT'] ?? 587;
    $mail->CharSet    = 'UTF-8';
    
    // Disable SSL verification to fix "Called QUIT without being connected" SMTP error locally
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
} catch (Exception $e) {
    die('SMTP config error');
}

$processed = false;
foreach ($queue as &$item) {
    if ($item['status'] === 'pending') {
        try {
            $mail->clearAddresses();
            $mail->setFrom($mail->Username, 'CargoConnect Security');
            $mail->addAddress($item['to'], $item['name']);
            $mail->isHTML(true);
            $mail->Subject = $item['subject'];
            $mail->Body    = $item['body'];
            
            $mail->send();
            
            $item['status'] = 'sent';
            $processed = true;
        } catch (Exception $e) {
            $item['status'] = 'failed';
            $item['error'] = $mail->ErrorInfo;
            $processed = true;
        }
    }
}

if ($processed) {
    file_put_contents($queue_file, json_encode($queue));
}
echo "OK";
