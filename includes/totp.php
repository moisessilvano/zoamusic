<?php
/**
 * LOUVOR.NET - TOTP Minimalista para 2FA
 * Suporta Google Authenticator, Authy, etc.
 */

class TOTP {
    private static $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Gera um segredo base32 aleatório
     */
    public static function generateSecret($length = 16): string {
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::$base32chars[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Gera código atual de 6 dígitos
     */
    public static function getCode($secret, $timeSlice = null): string {
        if ($timeSlice === null) {
            $timeSlice = floor(time() / 30);
        }

        $secretkey = self::base32Decode($secret);

        // Converte o time slice em string binária de 8 bytes
        $timeStr = chr(0) . chr(0) . chr(0) . chr(0) . pack('N*', $timeSlice);

        // Cria o hash HMAC-SHA1
        $hmac = hash_hmac('SHA1', $timeStr, $secretkey, true);

        // Extrai o offset
        $offset = ord(substr($hmac, -1)) & 0x0F;

        // Extrai os 4 bytes para formar o número interiro
        $hashpart = substr($hmac, $offset, 4);

        $value = unpack('N', $hashpart);
        $value = $value[1];

        // Ignora o bit de sinal
        $value = $value & 0x7FFFFFFF;

        // 6 dígitos max
        $modulo = pow(10, 6);

        return str_pad((string)($value % $modulo), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verifica um código tolerando pequenas variações de tempo (janela de segurança)
     */
    public static function verifyCode($secret, $code, $discrepancy = 1): bool {
        $currentTimeSlice = floor(time() / 30);

        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedCode = self::getCode($secret, $currentTimeSlice + $i);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Retorna a URL para gerar o QR code no Google Charts ou equivalente local
     */
    public static function getQRCodeUrl($name, $secret, $title = 'LOUVOR.NET') {
        $urlencoded = urlencode('otpauth://totp/' . $name . '?secret=' . $secret . '&issuer=' . urlencode($title));
        return 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=' . $urlencoded;
    }

    private static function base32Decode($secret) {
        $secret = strtoupper($secret);
        $decoded = '';
        $n = 0;
        $j = 0;

        for ($i = 0; $i < strlen($secret); $i++) {
            $char = $secret[$i];
            // Se for padding (=), ignora e encerra
            if ($char === '=') break;

            $val = strpos(self::$base32chars, $char);
            if ($val === false) continue; // Ignora char inválido

            $n = $n << 5;
            $n = $n + $val;
            $j += 5;

            if ($j >= 8) {
                $j -= 8;
                $decoded .= chr(($n & (0xFF << $j)) >> $j);
            }
        }
        return $decoded;
    }
}
