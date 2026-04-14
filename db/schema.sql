-- ============================================================
-- LOUVOR.NET - Schema do Banco de Dados Completo
-- MySQL 8.0+
-- ============================================================

CREATE TABLE IF NOT EXISTS musicas (
    id          VARCHAR(36)  NOT NULL COMMENT 'UUID v4',
    nome        VARCHAR(100)          COMMENT 'Nome do usuário (opcional)',
    telefone    VARCHAR(20)           COMMENT 'Telefone para SMS (opcional)',
    inspiracao  TEXT         NOT NULL COMMENT 'Texto de inspiração enviado pelo usuário',
    titulo      VARCHAR(255)          COMMENT 'Título gerado pelo Claude',
    letra       TEXT                  COMMENT 'Letra completa gerada pelo Claude',
    audio_url   VARCHAR(500)          COMMENT 'URL do áudio (remota ou local)',
    task_id     VARCHAR(255)          COMMENT 'ID da task na PiAPI para polling',
    status      ENUM(
                    'aguardando_pagamento',
                    'processando',
                    'concluido',
                    'erro'
                ) NOT NULL DEFAULT 'aguardando_pagamento',
    asaas_id    VARCHAR(255)          COMMENT 'ID do pagamento no Asaas',
    pix_key     TEXT                  COMMENT 'Chave PIX copia e cola',
    qr_code_img TEXT                  COMMENT 'Imagem do QR Code em base64',
    short_code  VARCHAR(20)           COMMENT 'Código do encurtador',
    sms_enviado TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_status     (status),
    INDEX idx_asaas_id   (asaas_id),
    INDEX idx_created_at (created_at),
    INDEX idx_short_code (short_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS short_links (
    code        VARCHAR(20)  NOT NULL,
    url         TEXT         NOT NULL,
    musica_id   VARCHAR(36)           DEFAULT NULL,
    hits        INT          NOT NULL DEFAULT 0,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (code),
    INDEX idx_musica_id (musica_id),
    FOREIGN KEY (musica_id) REFERENCES musicas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(100) NOT NULL,
    email       VARCHAR(100) NOT NULL UNIQUE,
    senha_hash  VARCHAR(255) NOT NULL,
    totp_secret VARCHAR(32)           DEFAULT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sac_mensagens (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(100) NOT NULL,
    email       VARCHAR(100) NOT NULL,
    whatsapp    VARCHAR(20)           DEFAULT NULL,
    assunto     VARCHAR(200) NOT NULL,
    mensagem    TEXT         NOT NULL,
    musica_id   VARCHAR(36)           DEFAULT NULL,
    lido        TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (musica_id) REFERENCES musicas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
