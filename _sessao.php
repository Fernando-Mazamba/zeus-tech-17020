<?php
/* =========================================================================
   Sessão, ligação à base de dados e verificação de acesso.
   Todos os ficheiros do portal começam por incluir este.
   ========================================================================= */

require_once __DIR__ . '/_config.php';

// ---------------------------------------------------------------- sessão
function zt_sessao_iniciar(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $seguro = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
           || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_name(ZT_SESSAO_NOME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $seguro,
        'httponly' => true,      // o JavaScript não lê o cookie
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ------------------------------------------------------------ base de dados
function zt_bd(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $dsn = 'mysql:host=' . ZT_BD_SERVIDOR . ';dbname=' . ZT_BD_NOME . ';charset=utf8mb4';
    try {
        $pdo = new PDO($dsn, ZT_BD_UTIL, ZT_BD_SENHA, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        // Nunca mostrar a mensagem do MySQL a quem visita: diz demasiado.
        error_log('ZT: ligacao a base de dados falhou: ' . $e->getMessage());
        http_response_code(503);
        exit('O sistema está temporariamente indisponível. Tente dentro de momentos.');
    }
    return $pdo;
}

// ------------------------------------------------------------- autenticação
function zt_utilizador(): ?array
{
    zt_sessao_iniciar();
    if (empty($_SESSION['zt_id'])) {
        return null;
    }
    // Sessão expirada por inactividade?
    $limite = ZT_SESSAO_MINUTOS * 60;
    if (isset($_SESSION['zt_visto']) && (time() - $_SESSION['zt_visto']) > $limite) {
        zt_terminar();
        return null;
    }
    $_SESSION['zt_visto'] = time();
    return [
        'id'         => (int) $_SESSION['zt_id'],
        'utilizador' => (string) $_SESSION['zt_utilizador'],
        'nome'       => (string) ($_SESSION['zt_nome'] ?? ''),
        'dono'       => !empty($_SESSION['zt_dono']),
    ];
}

function zt_exigir_sessao(string $raiz = ''): array
{
    $u = zt_utilizador();
    if ($u === null) {
        $destino = $raiz . 'login.php';
        $pedida = $_SERVER['REQUEST_URI'] ?? '';
        if ($pedida !== '' && strpos($pedida, 'login.php') === false) {
            $destino .= '?de=' . rawurlencode($pedida);
        }
        header('Location: ' . $destino, true, 302);
        exit;
    }
    return $u;
}

function zt_exigir_dono(): array
{
    $u = zt_exigir_sessao();
    if (!$u['dono']) {
        http_response_code(403);
        exit('Sem permissão.');
    }
    return $u;
}

function zt_entrar(array $linha): void
{
    zt_sessao_iniciar();
    session_regenerate_id(true);          // impede fixação de sessão
    $_SESSION['zt_id']         = (int) $linha['id'];
    $_SESSION['zt_utilizador'] = $linha['utilizador'];
    $_SESSION['zt_nome']       = $linha['nome'];
    $_SESSION['zt_dono']       = (int) $linha['dono'] === 1;
    $_SESSION['zt_visto']      = time();
}

function zt_terminar(): void
{
    zt_sessao_iniciar();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
                  $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// ------------------------------------------------------------------ registo
function zt_registar(string $utilizador, string $resultado): void
{
    try {
        zt_bd()->prepare(
            'INSERT INTO zt_acessos (utilizador, quando, ip, resultado)
             VALUES (?, NOW(), ?, ?)'
        )->execute([
            mb_substr($utilizador, 0, 60),
            mb_substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45),
            $resultado,
        ]);
    } catch (Throwable $e) {
        error_log('ZT: nao consegui registar o acesso: ' . $e->getMessage());
    }
}

// --------------------------------------------------------------------- CSRF
function zt_csrf(): string
{
    zt_sessao_iniciar();
    if (empty($_SESSION['zt_csrf'])) {
        $_SESSION['zt_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['zt_csrf'];
}

function zt_csrf_valido(?string $enviado): bool
{
    zt_sessao_iniciar();
    return is_string($enviado)
        && !empty($_SESSION['zt_csrf'])
        && hash_equals($_SESSION['zt_csrf'], $enviado);
}

// ------------------------------------------------------------------ atalhos
function e(?string $t): string
{
    return htmlspecialchars((string) $t, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
