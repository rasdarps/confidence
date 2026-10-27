<?php
require __DIR__ . '/bootstrap/autoload.php';
require __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

try {
    Mail::raw('Test email from Laravel', function(Message $message) {
        $message->to('rasdarps@gmail.com')->subject('Test Email');
    });
    echo "Email sent successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
