<?php
// ============================================================
// LOUVOR.NET - Encurtador de Links Interno
// Usa a tabela `short_links` no banco de dados.
// ============================================================

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

/**
 * Gera ou recupera um link curto para uma URL.
 *
 * @param string      $url       URL completa para encurtar
 * @param string|null $musica_id UUID da música (opcional)
 * @return string  Código curto gerado (ex: "aB3xYz")
 */
function shortlink_criar(string $url, ?string $musica_id = null): string {
    // Se já existe para esta música, retorna o mesmo código
    if ($musica_id) {
        $row = db()->prepare('SELECT code FROM short_links WHERE musica_id = ?');
        $row->execute([$musica_id]);
        $existing = $row->fetchColumn();
        if ($existing) return $existing;
    }

    // Gera código único de 7 chars
    do {
        $code = shortlink_gerar_codigo(7);
        $exists = db()->prepare('SELECT COUNT(*) FROM short_links WHERE code = ?');
        $exists->execute([$code]);
    } while ($exists->fetchColumn() > 0);

    db()->prepare('INSERT INTO short_links (code, url, musica_id) VALUES (?, ?, ?)')
        ->execute([$code, $url, $musica_id]);

    return $code;
}

/**
 * Resolve um código curto para a URL original.
 * Incrementa o contador de cliques.
 *
 * @param string $code Código curto
 * @return string|null URL completa ou null se não encontrado
 */
function shortlink_resolver(string $code): ?string {
    $stmt = db()->prepare('SELECT url FROM short_links WHERE code = ?');
    $stmt->execute([$code]);
    $url = $stmt->fetchColumn();

    if ($url) {
        db()->prepare('UPDATE short_links SET hits = hits + 1 WHERE code = ?')->execute([$code]);
        return $url;
    }
    return null;
}

/**
 * Retorna a URL curta completa para exibir ao usuário.
 */
function shortlink_url(string $code): string {
    return rtrim(BASE_URL, '/') . '/s/' . $code;
}

/**
 * Gera um código alfanumérico aleatório.
 */
function shortlink_gerar_codigo(int $length = 7): string {
    $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $code  = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}
