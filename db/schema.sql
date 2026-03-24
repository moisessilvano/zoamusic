-- ============================================================
-- LOUVOR.NET - Schema do Banco de Dados
-- MySQL 8.0+
-- Execute: mysql -u root -p louvor_net < db/schema.sql
-- ============================================================

-- O banco de dados já deve existir. Rode com: mysql -h 108.181.92.73 -u louvor-user -p'aG2vH9c@2CtkDZ68' louvor-db < db/schema.sql

CREATE TABLE IF NOT EXISTS musicas (
    id          VARCHAR(36)  NOT NULL COMMENT 'UUID v4',
    inspiracao  TEXT         NOT NULL COMMENT 'Texto de inspiração enviado pelo usuário',
    titulo      VARCHAR(255)          COMMENT 'Título gerado pelo Claude',
    letra       TEXT                  COMMENT 'Letra completa gerada pelo Claude',
    audio_url   VARCHAR(500)          COMMENT 'URL do áudio gerado pela PiAPI/Suno',
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
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_status     (status),
    INDEX idx_asaas_id   (asaas_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tabela principal de músicas geradas pelo LOUVOR.NET';
