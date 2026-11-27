<?php
/* =========================================================================
   Entrada no portal. Pede-se uma vez; a sessão dura 8 horas de inactividade.
   ========================================================================= */

require_once __DIR__ . '/_sessao.php';

$erro = '';
$de   = (string) ($_GET['de'] ?? '');
if ($de !== '' && (strpos($de, '://') !== false || strpos($de, '//') === 0)) {
    $de = '';                       // só caminhos deste site
}

if (zt_utilizador() !== null) {
    header('Location: ' . ($de !== '' ? $de : 'index.php'), true, 302);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!zt_csrf_valido($_POST['csrf'] ?? null)) {
        $erro = 'A sessão expirou. Tente outra vez. · The session expired. Try again.';
    } else {
        $u = trim((string) ($_POST['utilizador'] ?? ''));
        $s = (string) ($_POST['senha'] ?? '');
        usleep(random_int(200000, 500000));       // trava a tentativa à força bruta

        $linha = null;
        if ($u !== '' && $s !== '') {
            $q = zt_bd()->prepare('SELECT * FROM zt_utilizadores WHERE utilizador = ? LIMIT 1');
            $q->execute([$u]);
            $linha = $q->fetch() ?: null;
        }

        if ($linha !== null && password_verify($s, $linha['senha'])) {
            if (password_needs_rehash($linha['senha'], PASSWORD_DEFAULT)) {
                zt_bd()->prepare('UPDATE zt_utilizadores SET senha = ? WHERE id = ?')
                       ->execute([password_hash($s, PASSWORD_DEFAULT), $linha['id']]);
            }
            zt_bd()->prepare('UPDATE zt_utilizadores SET ultimo_acesso = NOW() WHERE id = ?')
                   ->execute([$linha['id']]);
            zt_registar($u, 'entrou');
            zt_entrar($linha);
            header('Location: ' . ($de !== '' ? $de : 'index.php'), true, 302);
            exit;
        }

        zt_registar($u !== '' ? $u : '(vazio)', 'recusado');
        $erro = 'Utilizador ou senha incorrectos. · Wrong user name or password.';
    }
}

$logo = @file_get_contents(__DIR__ . '/logo.b64') ?: '';
?><!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Entrada — Sistema de Gestão ZEUS TECH</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:"Segoe UI",system-ui,-apple-system,Arial,sans-serif;min-height:100vh;
 display:flex;align-items:center;justify-content:center;padding:22px;
 background:linear-gradient(135deg,#0d1b2a 0%,#16314f 55%,#1b4e85 100%);color:#0d1b2a}
.caixa{background:#fff;border-radius:15px;padding:34px 32px;width:100%;max-width:392px;
 box-shadow:0 18px 54px rgba(0,0,0,.34)}
.marca{text-align:center;margin-bottom:22px}
.marca img{max-width:188px;height:auto}
h1{font-size:1.07rem;font-weight:640;text-align:center;margin-bottom:5px}
.sub{font-size:.86rem;color:#6b7f96;text-align:center;margin-bottom:24px;line-height:1.45}
label{display:block;font-size:.76rem;text-transform:uppercase;letter-spacing:.8px;
 color:#38506b;font-weight:650;margin-bottom:6px}
input{width:100%;padding:11px 13px;border:1.5px solid #dde5ee;border-radius:9px;
 font-size:.97rem;font-family:inherit;margin-bottom:15px;color:#0d1b2a}
input:focus{outline:none;border-color:#1b6fc4}
button{width:100%;padding:12px;border:none;border-radius:9px;background:#12539a;color:#fff;
 font-size:.97rem;font-weight:620;font-family:inherit;cursor:pointer}
button:hover{background:#0d4280}
.erro{background:#fdeeee;border:1px solid #f3d2d2;color:#9b2222;border-radius:8px;
 padding:9px 12px;font-size:.86rem;margin-bottom:14px}
.rodape{margin-top:21px;padding-top:15px;border-top:1px solid #eef3f9;
 font-size:.76rem;color:#8b9cb0;text-align:center;line-height:1.5}
</style>
</head>
<body>
<div class="caixa">
 <?php if ($logo !== ''): ?>
 <div class="marca"><img src="data:image/png;base64,<?= $logo ?>" alt="ZEUS TECH OIL &amp; GAS"></div>
 <?php endif; ?>
 <h1>Sistema de Gestão do Organismo de Inspecção</h1>
 <p class="sub">ISO/IEC 17020 · Acesso reservado<br>
  <span style="font-size:.93em">Inspection Body Management System · Restricted access</span></p>
 <?php if ($erro !== ''): ?><div class="erro"><?= e($erro) ?></div><?php endif; ?>
 <form method="post" autocomplete="off">
  <input type="hidden" name="csrf" value="<?= e(zt_csrf()) ?>">
  <label for="utilizador">Utilizador · User</label>
  <input type="text" id="utilizador" name="utilizador" autocapitalize="none"
         spellcheck="false" required autofocus>
  <label for="senha">Senha · Password</label>
  <input type="password" id="senha" name="senha" required>
  <button type="submit">Entrar · Sign in</button>
 </form>
 <p class="rodape">ZEUS TECH OIL &amp; GAS<br>Documento controlado</p>
</div>
</body>
</html>
