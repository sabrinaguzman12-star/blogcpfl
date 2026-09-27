<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

// =================================================================
// ⚙️ CONFIGURAÇÃO (COLOQUE SEU LINK ABAIXO)
// =================================================================
$black_url = 'index1.html'; // Abre o index do próprio domínio
$safe_page = 'index10.html';                      // Sua Página Safe Local

// Garante que o arquivo safe existe para não dar erro
if (!file_exists($safe_page)) { 
    file_put_contents($safe_page, '<h1>PAGINA SAFE</h1>'); 
}

// =================================================================
// 🛡️ CAMADA 1: PHP (IP BRASIL + USER AGENT)
// =================================================================

function is_blocked_traffic() {
    $ua = strtolower($_SERVER['HTTP_USER_AGENT']);
    $ip = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
    if (strpos($ip, ',') !== false) { $ip = explode(',', $ip)[0]; }

    // 1. BLOQUEIO DE ROBÔS POR NOME (User-Agent)
    if (preg_match('/(googlebot|adsbot|mediapartners|facebook|externalhit|headless|phantom|selenium|crawler)/i', $ua)) {
        return true; 
    }

    // 2. BLOQUEIO GEO (SÓ ACEITA BRASIL)
    $ctx = stream_context_create(['http' => ['timeout' => 2]]);
    $json = @file_get_contents("http://ip-api.com/json/{$ip}?fields=countryCode", false, $ctx);
    
    if ($json) {
        $data = json_decode($json);
        if (isset($data->countryCode) && $data->countryCode !== 'BR') {
            return true; 
        }
    }
    
    return false; 
}

// Executa verificação PHP
$blocked_by_php = is_blocked_traffic();

// Prepara conteúdo da SAFE (Base64 para ocultar do crawler)
$conteudo_safe = base64_encode(file_get_contents($safe_page));

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Carregando...</title>
<style>body{margin:0;padding:0;background:#fff;}</style>
</head>
<body>

<script>
(function() {
    // Função para renderizar a página safe (Camuflagem)
    function render(b64) {
        if(!b64) return;
        document.open();
        document.write(decodeURIComponent(escape(window.atob(b64))));
        document.close();
    }

    var safeContent = "<?php echo $conteudo_safe; ?>";
    var phpApproved = <?php echo $blocked_by_php ? 'false' : 'true'; ?>;
    var blackUrl = "<?php echo $black_url; ?>";

    // --- DETECTOR DE HARDWARE (FILTRO DE GPU) ---
    function checkHardware() {
        try {
            if (navigator.webdriver) return false;

            var canvas = document.createElement('canvas');
            var gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
            if (!gl) return true; 

            var debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
            if (!debugInfo) return true; 

            var renderer = gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL);
            
            // Lista negra de GPUs de servidores/bots do Google
            var botGPUs = ["SwiftShader", "Google", "Headless"];

            for (var i = 0; i < botGPUs.length; i++) {
                if (renderer.indexOf(botGPUs[i]) !== -1) return false;
            }

            return true; 
        } catch (e) {
            return true; 
        }
    }

    // --- DECISÃO FINAL ---
    if (!phpApproved) {
        // Bloqueado pelo PHP (Gringo ou Bot conhecido) -> Mostra Safe
        render(safeContent);
    } else {
        // Aprovado pelo PHP -> Checa se o Hardware é de uma pessoa real
        if (checkHardware()) {
            // SUCESSO: É humano real no Brasil
            // Pega as UTMs da URL atual para não perder o rastreio
            var query = window.location.search;
            
            // Se a sua URL de vendas já tiver um '?', usa '&', se não, usa '?'
            var separator = blackUrl.indexOf('?') !== -1 ? '&' : '?';
            
            // Redireciona
            window.location.href = blackUrl + (query ? separator + query.substring(1) : "");
        } else {
            // Bloqueado pelo Hardware (Provavelmente bot avançado) -> Mostra Safe
            render(safeContent);
        }
    }
})();
</script>

<noscript><meta http-equiv="refresh" content="0;url=<?php echo $safe_page; ?>"></noscript>
</body>
</html>