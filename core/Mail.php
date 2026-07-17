<?php

namespace Core;

/**
 * مرسل بريد بسيط بلا أي مكتبة خارجية: يستخدم إعدادات SMTP عند توفرها،
 * وإلا يعتمد على دالة mail() المدمجة في PHP (تعمل على معظم الاستضافات
 * المشتركة عبر sendmail المحلي).
 */
final class Mail
{
    public static function send(string $toEmail, string $subject, string $bodyHtml): bool
    {
        $fromName = Settings::get('mail_from_name', '') ?: Settings::get('identity_short_name', 'الموقع');
        $fromEmail = Settings::get('mail_from_email', '') ?: ('no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));

        $smtpHost = Settings::get('mail_smtp_host', '');

        try {
            if ($smtpHost !== '') {
                return self::sendViaSmtp($toEmail, $subject, $bodyHtml, $fromName, $fromEmail);
            }
            return self::sendViaNativeMail($toEmail, $subject, $bodyHtml, $fromName, $fromEmail);
        } catch (\Throwable $e) {
            Support\Logger::exception($e);
            return false;
        }
    }

    private static function sendViaNativeMail(string $to, string $subject, string $bodyHtml, string $fromName, string $fromEmail): bool
    {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= 'From: ' . self::encodeHeader($fromName) . " <{$fromEmail}>\r\n";

        return @mail($to, self::encodeHeader($subject), $bodyHtml, $headers);
    }

    private static function sendViaSmtp(string $to, string $subject, string $bodyHtml, string $fromName, string $fromEmail): bool
    {
        $host = Settings::get('mail_smtp_host', '');
        $port = (int) Settings::get('mail_smtp_port', 587);
        $user = Settings::get('mail_smtp_user', '');
        $pass = Settings::get('mail_smtp_pass', '');
        $encryption = Settings::get('mail_smtp_encryption', 'tls');

        $transport = $encryption === 'ssl' ? 'ssl://' . $host : $host;
        $socket = @stream_socket_client($transport . ':' . $port, $errno, $errstr, 10);
        if (!$socket) {
            throw new \RuntimeException('تعذر الاتصال بخادم SMTP: ' . $errstr);
        }

        self::expect($socket, '220');
        self::command($socket, 'EHLO ' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '250');

        if ($encryption === 'tls') {
            self::command($socket, 'STARTTLS', '220');
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            self::command($socket, 'EHLO ' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '250');
        }

        if ($user !== '') {
            self::command($socket, 'AUTH LOGIN', '334');
            self::command($socket, base64_encode($user), '334');
            self::command($socket, base64_encode($pass), '235');
        }

        self::command($socket, "MAIL FROM:<{$fromEmail}>", '250');
        self::command($socket, "RCPT TO:<{$to}>", '250');
        self::command($socket, 'DATA', '354');

        $message = "Subject: " . self::encodeHeader($subject) . "\r\n";
        $message .= 'From: ' . self::encodeHeader($fromName) . " <{$fromEmail}>\r\n";
        $message .= "To: <{$to}>\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $message .= $bodyHtml . "\r\n.\r\n";

        fwrite($socket, $message);
        self::expect($socket, '250');
        self::command($socket, 'QUIT', '221');
        fclose($socket);

        return true;
    }

    private static function command($socket, string $line, string $expectedCode): void
    {
        fwrite($socket, $line . "\r\n");
        self::expect($socket, $expectedCode);
    }

    private static function expect($socket, string $expectedCode): void
    {
        $response = '';
        while ($line = fgets($socket, 512)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        if (!str_starts_with($response, $expectedCode)) {
            throw new \RuntimeException('استجابة SMTP غير متوقعة: ' . trim($response));
        }
    }

    private static function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }
}
