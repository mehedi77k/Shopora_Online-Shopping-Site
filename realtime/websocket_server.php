<?php
/**
 * Shopora lightweight WebSocket broadcast server.
 *
 * Browser clients connect to TCP port 8081. Application PHP requests publish
 * small, non-sensitive event envelopes over localhost UDP port 8082. The
 * server then broadcasts those events to connected browsers. Message content,
 * account data and order data are never sent through this broadcast channel;
 * browsers fetch authorized data from the normal PHP API after an event.
 */

require_once __DIR__ . '/../config/realtime.php';

set_time_limit(0);
error_reporting(E_ALL);

$tcpAddress = 'tcp://' . REALTIME_WS_HOST . ':' . REALTIME_WS_PORT;
$udpAddress = 'udp://' . REALTIME_NOTIFY_HOST . ':' . REALTIME_NOTIFY_PORT;

$errno = 0;
$errstr = '';
$server = @stream_socket_server($tcpAddress, $errno, $errstr);
if (!$server) {
    fwrite(STDERR, "Unable to start WebSocket server on {$tcpAddress}: {$errstr} ({$errno})\n");
    exit(1);
}

$udp = @stream_socket_server($udpAddress, $udpErrno, $udpErrstr, STREAM_SERVER_BIND);
if (!$udp) {
    fwrite(STDERR, "Unable to start notification listener on {$udpAddress}: {$udpErrstr} ({$udpErrno})\n");
    fclose($server);
    exit(1);
}

stream_set_blocking($server, false);
stream_set_blocking($udp, false);

$clients = [];
$buffers = [];
$handshaken = [];
$lastHeartbeat = time();

function ws_frame(string $payload, int $opcode = 0x1): string
{
    $length = strlen($payload);
    $header = chr(0x80 | ($opcode & 0x0f));
    if ($length <= 125) {
        return $header . chr($length) . $payload;
    }
    if ($length <= 65535) {
        return $header . chr(126) . pack('n', $length) . $payload;
    }

    // Payloads in this application are tiny, but support a 64-bit length.
    $high = intdiv($length, 4294967296);
    $low = $length % 4294967296;
    return $header . chr(127) . pack('NN', $high, $low) . $payload;
}

function ws_broadcast(array &$clients, array &$handshaken, string $payload): void
{
    $frame = ws_frame($payload);
    foreach ($clients as $id => $client) {
        if (empty($handshaken[$id])) continue;
        $written = @fwrite($client, $frame);
        if ($written === false) {
            @fclose($client);
            unset($clients[$id], $handshaken[$id]);
        }
    }
}

function ws_handshake(string $request): ?string
{
    if (!preg_match('/Sec-WebSocket-Key:\s*(.+)\r?\n/i', $request, $match)) {
        return null;
    }
    $key = trim($match[1]);
    $accept = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
    return "HTTP/1.1 101 Switching Protocols\r\n"
        . "Upgrade: websocket\r\n"
        . "Connection: Upgrade\r\n"
        . "Sec-WebSocket-Accept: {$accept}\r\n\r\n";
}

function ws_decode_client_frame(string $data): array
{
    if (strlen($data) < 2) return ['opcode' => null, 'payload' => ''];
    $b1 = ord($data[0]);
    $b2 = ord($data[1]);
    $opcode = $b1 & 0x0f;
    $masked = ($b2 & 0x80) !== 0;
    $length = $b2 & 0x7f;
    $offset = 2;

    if ($length === 126) {
        if (strlen($data) < 4) return ['opcode' => null, 'payload' => ''];
        $length = unpack('n', substr($data, 2, 2))[1];
        $offset = 4;
    } elseif ($length === 127) {
        if (strlen($data) < 10) return ['opcode' => null, 'payload' => ''];
        $parts = unpack('Nhigh/Nlow', substr($data, 2, 8));
        $length = $parts['high'] * 4294967296 + $parts['low'];
        $offset = 10;
    }

    $mask = '';
    if ($masked) {
        if (strlen($data) < $offset + 4) return ['opcode' => null, 'payload' => ''];
        $mask = substr($data, $offset, 4);
        $offset += 4;
    }

    $payload = substr($data, $offset, (int)$length);
    if ($masked && $mask !== '') {
        $decoded = '';
        $payloadLength = strlen($payload);
        for ($i = 0; $i < $payloadLength; $i++) {
            $decoded .= $payload[$i] ^ $mask[$i % 4];
        }
        $payload = $decoded;
    }

    return ['opcode' => $opcode, 'payload' => $payload];
}

function ws_event(string $event, array $data = []): string
{
    return json_encode([
        'event' => $event,
        'data' => $data,
        'time' => gmdate('c'),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

fwrite(STDOUT, "Shopora WebSocket server running\n");
fwrite(STDOUT, "Browser socket : ws://localhost:" . REALTIME_WS_PORT . "\n");
fwrite(STDOUT, "App notifier   : " . REALTIME_NOTIFY_HOST . ':' . REALTIME_NOTIFY_PORT . " (UDP)\n");
fwrite(STDOUT, "Press Ctrl+C to stop.\n\n");

while (true) {
    $read = [$server, $udp];
    foreach ($clients as $client) $read[] = $client;
    $write = null;
    $except = null;

    $changed = @stream_select($read, $write, $except, 1);
    if ($changed === false) {
        usleep(100000);
        continue;
    }

    foreach ($read as $stream) {
        if ($stream === $server) {
            $client = @stream_socket_accept($server, 0);
            if ($client) {
                stream_set_blocking($client, false);
                $id = (int)$client;
                $clients[$id] = $client;
                $buffers[$id] = '';
                $handshaken[$id] = false;
            }
            continue;
        }

        if ($stream === $udp) {
            $peer = null;
            $packet = @stream_socket_recvfrom($udp, 65507, 0, $peer);
            if ($packet !== false && trim($packet) !== '') {
                $decoded = json_decode($packet, true);
                if (is_array($decoded) && isset($decoded['event']) && is_string($decoded['event'])) {
                    // Publish only a sanitized envelope. The notifier already avoids
                    // private message bodies, and this protects against malformed input.
                    $event = substr(preg_replace('/[^a-zA-Z0-9._-]/', '', $decoded['event']), 0, 80);
                    $data = is_array($decoded['data'] ?? null) ? $decoded['data'] : [];
                    ws_broadcast($clients, $handshaken, ws_event($event, $data));
                }
            }
            continue;
        }

        $id = (int)$stream;
        $chunk = @fread($stream, 8192);
        if ($chunk === '' || $chunk === false) {
            if (feof($stream)) {
                @fclose($stream);
                unset($clients[$id], $buffers[$id], $handshaken[$id]);
            }
            continue;
        }

        if (empty($handshaken[$id])) {
            $buffers[$id] .= $chunk;
            if (str_contains($buffers[$id], "\r\n\r\n")) {
                $response = ws_handshake($buffers[$id]);
                if ($response === null) {
                    @fclose($stream);
                    unset($clients[$id], $buffers[$id], $handshaken[$id]);
                    continue;
                }
                @fwrite($stream, $response);
                $handshaken[$id] = true;
                $buffers[$id] = '';
                @fwrite($stream, ws_frame(ws_event('realtime.connected')));
            }
            continue;
        }

        $frame = ws_decode_client_frame($chunk);
        if ($frame['opcode'] === 0x8) { // close
            @fwrite($stream, ws_frame('', 0x8));
            @fclose($stream);
            unset($clients[$id], $buffers[$id], $handshaken[$id]);
        } elseif ($frame['opcode'] === 0x9) { // ping
            @fwrite($stream, ws_frame($frame['payload'], 0xA));
        }
    }

    if (time() - $lastHeartbeat >= 25) {
        ws_broadcast($clients, $handshaken, ws_event('realtime.heartbeat'));
        $lastHeartbeat = time();
    }
}
