<?php
// Function to add email to queue
function enqueueEmail($to, $name, $subject, $body) {
    require_once __DIR__ . '/paths.php';
    
    $queue_file = ROOT_PATH . 'cache/email_queue.json';
    if (!file_exists(dirname($queue_file))) {
        mkdir(dirname($queue_file), 0777, true);
    }
    
    $queue = file_exists($queue_file) ? json_decode(file_get_contents($queue_file), true) : [];
    $queue[] = [
        'to' => $to,
        'name' => $name,
        'subject' => $subject,
        'body' => $body,
        'status' => 'pending',
        'created_at' => time(),
        'error' => null
    ];
    
    file_put_contents($queue_file, json_encode($queue));
    
    // Trigger async worker
    triggerWorker();
}

function triggerWorker() {
    $url = $_ENV['APP_URL'] ?? "http://" . $_SERVER['HTTP_HOST'] . "/Reservation-transport";
    $url .= "/api/queue_worker.php";
    
    $parts = parse_url($url);
    if (!$parts || !isset($parts['host'])) return;
    
    $port = isset($parts['port']) ? $parts['port'] : ($parts['scheme'] === 'https' ? 443 : 80);
    $fp = @fsockopen($parts['host'], $port, $errno, $errstr, 1);
        
    if ($fp) {
        $path = isset($parts['path']) ? $parts['path'] : '/';
        $out = "GET " . $path . " HTTP/1.1\r\n";
        $out .= "Host: " . $parts['host'] . "\r\n";
        $out .= "Connection: Close\r\n\r\n";
        fwrite($fp, $out);
        fclose($fp);
    }
}
