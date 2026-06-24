<?php
declare(strict_types=1);

function hr_mail_local_config(): array {
  $path = __DIR__ . '/mail-config.php';
  if (is_file($path)) {
    $data = require $path;
    if (is_array($data)) return $data;
  }
  return [];
}

function hr_mail_db_config(): array {
  if (!function_exists('central_db')) return [];
  try {
    $d = central_db();
    $st = $d->prepare("SELECT value FROM app_kv WHERE key=? LIMIT 1");
    $st->execute(['smtp_settings']);
    $row = $st->fetch();
    if (!$row) return [];
    $val = json_decode((string)($row['value'] ?? ''), true);
    return is_array($val) ? $val : [];
  } catch (Throwable $e) {
    return [];
  }
}

function hr_mail_config(): array {
  static $cfg = null;
  if (is_array($cfg)) return $cfg;
  $local = hr_mail_local_config();
  $db = hr_mail_db_config();
  $pick = function(string $key, $default = '') use ($local) {
    $env = getenv($key);
    if (is_string($env) && trim($env) !== '') return trim($env);
    return $default;
  };
  $pickMerged = function(string $key, $default = '') use ($pick, $db, $local) {
    $env = $pick($key, '__HR_EMPTY__');
    if ($env !== '__HR_EMPTY__') return $env;
    if (array_key_exists($key, $db) && $db[$key] !== null && trim((string)$db[$key]) !== '') return trim((string)$db[$key]);
    if (array_key_exists($key, $local) && $local[$key] !== null && trim((string)$local[$key]) !== '') return trim((string)$local[$key]);
    return $default;
  };
  $cfg = [
    'host' => $pickMerged('HR_SMTP_HOST', ''),
    'port' => (int)$pickMerged('HR_SMTP_PORT', '465'),
    'encryption' => strtolower((string)$pickMerged('HR_SMTP_ENCRYPTION', 'ssl')),
    'username' => $pickMerged('HR_SMTP_USERNAME', ''),
    'password' => $pickMerged('HR_SMTP_PASSWORD', ''),
    'from_email' => $pickMerged('HR_SMTP_FROM_EMAIL', ''),
    'from_name' => $pickMerged('HR_SMTP_FROM_NAME', 'HR Seva'),
    'reply_to' => $pickMerged('HR_SMTP_REPLY_TO', ''),
    'admin_emails' => $pickMerged('HR_SMTP_ADMIN_EMAILS', ''),
    'enabled' => in_array(strtolower((string)$pickMerged('HR_SMTP_ENABLED', 'false')), ['1','true','yes','on'], true),
  ];
  return $cfg;
}

function hr_mail_enabled(): bool {
  $cfg = hr_mail_config();
  return !empty($cfg['enabled']) && $cfg['host'] !== '' && $cfg['username'] !== '' && $cfg['password'] !== '' && $cfg['from_email'] !== '';
}

function hr_mail_admin_list(): array {
  $cfg = hr_mail_config();
  $raw = (string)($cfg['admin_emails'] ?? '');
  if ($raw === '') return [];
  $parts = preg_split('/[;,]+/', $raw) ?: [];
  $out = [];
  foreach ($parts as $part) {
    $email = trim((string)$part);
    if ($email !== '') $out[] = $email;
  }
  return array_values(array_unique($out));
}

function hr_mail_is_valid_email(string $email): bool {
  return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function hr_mail_subject_encode(string $subject): string {
  return '=?UTF-8?B?' . base64_encode($subject) . '?=';
}

function hr_mail_header_encode(string $value): string {
  return str_replace(["\r", "\n"], ' ', trim($value));
}

function hr_mail_html_to_text(string $html): string {
  $text = preg_replace('/<\s*br\s*\/?>/i', "\n", $html);
  $text = preg_replace('/<\s*\/p\s*>/i', "\n\n", (string)$text);
  $text = strip_tags((string)$text);
  $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  $text = preg_replace("/\n{3,}/", "\n\n", (string)$text);
  return trim((string)$text);
}

function hr_mail_read_response($fp): array {
  $lines = [];
  while (!feof($fp)) {
    $line = fgets($fp, 515);
    if ($line === false) break;
    $lines[] = rtrim($line, "\r\n");
    if (preg_match('/^\d{3}\s/', $line)) break;
  }
  $last = end($lines);
  $code = is_string($last) ? (int)substr($last, 0, 3) : 0;
  return ['code' => $code, 'message' => implode("\n", $lines)];
}

function hr_mail_expect($fp, ?string $command, array $expected): array {
  if ($command !== null) fwrite($fp, $command . "\r\n");
  $res = hr_mail_read_response($fp);
  if (!in_array((int)$res['code'], $expected, true)) {
    throw new RuntimeException('SMTP error: ' . ($res['message'] ?: 'Unknown response'));
  }
  return $res;
}

function hr_mail_open_socket(array $cfg) {
  $host = (string)$cfg['host'];
  $port = (int)$cfg['port'];
  $enc = strtolower((string)($cfg['encryption'] ?? 'ssl'));
  $target = $enc === 'ssl' ? 'ssl://' . $host : $host;
  $fp = @stream_socket_client($target . ':' . $port, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
  if (!$fp) {
    throw new RuntimeException('SMTP connect failed: ' . $errstr . ' (' . $errno . ')');
  }
  stream_set_timeout($fp, 20);
  hr_mail_expect($fp, null, [220]);
  hr_mail_expect($fp, 'EHLO hrseva.in', [250]);
  if ($enc === 'tls') {
    hr_mail_expect($fp, 'STARTTLS', [220]);
    $crypto = @stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    if ($crypto !== true) {
      throw new RuntimeException('SMTP STARTTLS failed');
    }
    hr_mail_expect($fp, 'EHLO hrseva.in', [250]);
  }
  hr_mail_expect($fp, 'AUTH LOGIN', [334]);
  hr_mail_expect($fp, base64_encode((string)$cfg['username']), [334]);
  hr_mail_expect($fp, base64_encode((string)$cfg['password']), [235]);
  return $fp;
}

function hr_mail_send(array $to, string $subject, string $html, array $opts = []): array {
  if (!hr_mail_enabled()) {
    return ['ok' => false, 'error' => 'SMTP is not configured'];
  }
  $recipients = array_values(array_filter(array_map('trim', $to), fn($x) => $x !== '' && hr_mail_is_valid_email($x)));
  if (!$recipients) {
    return ['ok' => false, 'error' => 'No valid recipient email'];
  }

  $cfg = hr_mail_config();
  $plain = hr_mail_html_to_text($html);
  $boundary = 'hrseva_' . bin2hex(random_bytes(8));
  $fromEmail = hr_mail_header_encode((string)$cfg['from_email']);
  $fromName = hr_mail_header_encode((string)$cfg['from_name']);
  $replyTo = hr_mail_header_encode((string)($opts['reply_to'] ?? $cfg['reply_to'] ?? ''));
  $toHeader = implode(', ', array_map('hr_mail_header_encode', $recipients));

  $headers = [
    'Date: ' . gmdate('D, d M Y H:i:s') . ' +0000',
    'From: ' . hr_mail_subject_encode($fromName) . ' <' . $fromEmail . '>',
    'To: ' . $toHeader,
    'Subject: ' . hr_mail_subject_encode($subject),
    'MIME-Version: 1.0',
    'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
  ];
  if ($replyTo !== '') $headers[] = 'Reply-To: ' . $replyTo;

  $body = "--{$boundary}\r\n"
    . "Content-Type: text/plain; charset=UTF-8\r\n"
    . "Content-Transfer-Encoding: 8bit\r\n\r\n"
    . $plain . "\r\n"
    . "--{$boundary}\r\n"
    . "Content-Type: text/html; charset=UTF-8\r\n"
    . "Content-Transfer-Encoding: 8bit\r\n\r\n"
    . $html . "\r\n"
    . "--{$boundary}--\r\n";

  $message = implode("\r\n", $headers) . "\r\n\r\n" . $body;
  $message = preg_replace("/(?m)^\./", '..', $message);

  $fp = null;
  try {
    $fp = hr_mail_open_socket($cfg);
    hr_mail_expect($fp, 'MAIL FROM:<' . $fromEmail . '>', [250]);
    foreach ($recipients as $rcpt) {
      hr_mail_expect($fp, 'RCPT TO:<' . $rcpt . '>', [250, 251]);
    }
    hr_mail_expect($fp, 'DATA', [354]);
    fwrite($fp, $message . "\r\n.\r\n");
    hr_mail_expect($fp, null, [250]);
    hr_mail_expect($fp, 'QUIT', [221]);
    fclose($fp);
    return ['ok' => true, 'error' => ''];
  } catch (Throwable $e) {
    if (is_resource($fp)) {
      @fwrite($fp, "QUIT\r\n");
      @fclose($fp);
    }
    return ['ok' => false, 'error' => $e->getMessage()];
  }
}
