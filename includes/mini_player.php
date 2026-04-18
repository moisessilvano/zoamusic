<?php
/**
 * ZOA MUSIC — Mini-player flutuante persistente
 * Include este arquivo antes do </body> em todas as páginas
 * exceto ouvir.php (onde o usuário ouve a própria música).
 *
 * A faixa que estava tocando na home é salva no sessionStorage
 * e o mini-player retoma automaticamente ao navegar.
 */
?>
<!-- ═══════════════════════════════
     MINI-PLAYER PERSISTENTE
═══════════════════════════════ -->
<div id="mini-player"
     style="display:none; position:fixed; bottom:20px; right:20px; z-index:9999;
            background:rgba(255,255,255,0.96); backdrop-filter:blur(20px);
            border:1px solid rgba(201,168,76,0.3); border-radius:20px;
            box-shadow:0 8px 40px rgba(0,0,0,0.12), 0 0 0 1px rgba(201,168,76,0.1);
            padding:12px 16px; min-width:260px; max-width:300px;
            transition: transform 0.3s, opacity 0.3s;">
    <div style="display:flex; align-items:center; gap:10px;">
        <!-- Ícone / Visualizer -->
        <div id="mp-icon" style="width:36px; height:36px; border-radius:50%;
             background:linear-gradient(135deg,#C9A84C,#E8CC80);
             display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <span id="mp-bars" style="display:flex; align-items:flex-end; gap:2px; height:16px;">
                <span style="width:3px; background:#fff; border-radius:2px; height:6px;
                      animation:mpbar 0.8s ease-in-out infinite; transform-origin:bottom;"></span>
                <span style="width:3px; background:#fff; border-radius:2px; height:10px;
                      animation:mpbar 0.8s ease-in-out 0.15s infinite; transform-origin:bottom;"></span>
                <span style="width:3px; background:#fff; border-radius:2px; height:7px;
                      animation:mpbar 0.8s ease-in-out 0.3s infinite; transform-origin:bottom;"></span>
            </span>
        </div>
        <!-- Info -->
        <div style="flex:1; min-width:0;">
            <p style="font-size:11px; font-weight:600; color:#1C1917; margin:0;
                      white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" id="mp-title">
                ZOA MUSIC
            </p>
            <p style="font-size:10px; color:#A08060; margin:0;">Música de fundo</p>
        </div>
        <!-- Controles -->
        <div style="display:flex; gap:6px; align-items:center; flex-shrink:0;">
            <button id="mp-play-btn" onclick="mpToggle()"
                style="width:30px; height:30px; border-radius:50%; border:none; cursor:pointer;
                       background:linear-gradient(135deg,#C9A84C,#D4AF37);
                       color:#fff; font-size:12px; display:flex; align-items:center; justify-content:center;">
                ▶
            </button>
            <button onclick="mpClose()"
                style="width:24px; height:24px; border-radius:50%; border:1px solid #E8D9A8;
                       background:transparent; cursor:pointer; color:#B8A07A; font-size:12px;
                       display:flex; align-items:center; justify-content:center;">
                ✕
            </button>
        </div>
    </div>
    <!-- Barra de progresso -->
    <div style="margin-top:8px; height:2px; background:#F0E8CC; border-radius:2px; overflow:hidden;">
        <div id="mp-progress" style="height:100%; background:linear-gradient(to right,#C9A84C,#E8CC80);
             width:0%; transition:width 0.5s;"></div>
    </div>
</div>

<style>
@keyframes mpbar {
    0%, 100% { transform: scaleY(0.4); }
    50%       { transform: scaleY(1); }
}
#mini-player.mp-hidden { transform: translateY(100px); opacity: 0; }
</style>

<audio id="mp-audio" loop preload="auto" style="display:none;"></audio>

<script>
(function() {
    const TRACK_URL  = 'assets/musica.mp3';
    const TRACK_KEY  = 'louvor_mini_player';
    const CLOSE_KEY  = 'louvor_player_closed';

    const player = document.getElementById('mini-player');
    const audio  = document.getElementById('mp-audio');
    const btn    = document.getElementById('mp-play-btn');
    const bars   = document.getElementById('mp-bars');
    const prog   = document.getElementById('mp-progress');

    // Recupera posição do sessionStorage (persiste na sessão)
    let saved = {};
    try { saved = JSON.parse(sessionStorage.getItem(TRACK_KEY) || '{}'); } catch(e) {}

    const closedByUser = sessionStorage.getItem(CLOSE_KEY) === '1';
    if (closedByUser) return; // usuário fechou explicitamente, não mostra

    // Configura fonte
    audio.src  = saved.src || TRACK_URL;
    audio.volume = 0.30;

    // Restaura posição
    let startPos = parseFloat(saved.pos || 0);

    function showPlayer() {
        player.style.display = 'block';
        setTimeout(() => player.classList.remove('mp-hidden'), 20);
    }

    function updateBtn() {
        btn.textContent = audio.paused ? '▶' : '⏸';
        bars.style.animationPlayState = audio.paused ? 'paused' : 'running';
    }

    // Atualiza progresso
    audio.addEventListener('timeupdate', () => {
        if (audio.duration) {
            prog.style.width = (audio.currentTime / audio.duration * 100) + '%';
        }
        // Salva posição a cada 2s
        try {
            sessionStorage.setItem(TRACK_KEY, JSON.stringify({
                src: audio.src, pos: audio.currentTime
            }));
        } catch(e) {}
    });

    audio.addEventListener('play',  updateBtn);
    audio.addEventListener('pause', updateBtn);

    async function mpStart() {
        try {
            audio.muted = true;
            await audio.play();
            if (startPos > 0) audio.currentTime = startPos;
            // Tenta desmutar na primeira interação
            const unmute = async () => {
                audio.muted = false;
                document.removeEventListener('click', unmute);
                document.removeEventListener('touchstart', unmute);
            };
            document.addEventListener('click', unmute, { once: true });
            document.addEventListener('touchstart', unmute, { once: true });
            showPlayer();
            updateBtn();
        } catch(e) {
            // Autoplay bloqueado — mostra player e aguarda clique
            showPlayer();
        }
    }

    window.mpToggle = function() {
        if (audio.paused) {
            audio.muted = false;
            audio.play().catch(()=>{});
        } else {
            audio.pause();
        }
        updateBtn();
    };

    window.mpClose = function() {
        audio.pause();
        player.style.display = 'none';
        sessionStorage.setItem(CLOSE_KEY, '1');
    };

    mpStart();
})();
</script>
