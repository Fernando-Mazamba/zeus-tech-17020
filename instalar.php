<?php
/* =========================================================================
   Arranque: cria o PRIMEIRO utilizador, o dono do sistema de gestão.

   Só funciona enquanto não existir nenhum utilizador. A partir do momento em
   que o dono está criado, este ficheiro recusa-se a fazer nada — e deve ser
   apagado do servidor. Os utilizadores seguintes criam-se dentro do portal,
   em "Utilizadores".
   ========================================================================= */

require_once __DIR__ . '/_sessao.php';

$quantos = (int) zt_bd()->query('SELECT COUNT(*) FROM zt_utilizadores')->fetchColumn();
$feito   = $quantos > 0;
$erro    = '';
$ok      = false;

const ZT_NOME_OK = '/^[A-Za-z0-9._@-]{3,40}$/';

if (!$feito && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!zt_csrf_valido($_POST['csrf'] ?? null)) {
        $erro = 'A página expirou. Recarregue e tente outra vez.';
    } else {
        $u  = trim((string) ($_POST['utilizador'] ?? ''));
        $n  = trim((string) ($_POST['nome'] ?? ''));
        $s1 = (string) ($_POST['senha'] ?? '');
        $s2 = (string) ($_POST['senha2'] ?? '');

        if ($u === '' || $s1 === '') {
            $erro = 'Preencha o utilizador e a senha.';
        } elseif (!preg_match(ZT_NOME_OK, $u)) {
            $erro = 'No utilizador use apenas letras, algarismos e . _ - @ (3 a 40 caracteres).';
        } elseif (mb_strlen($s1) < 8) {
            $erro = 'A senha deve ter pelo menos 8 caracteres.';
        } elseif ($s1 !== $s2) {
            $erro = 'As duas senhas não coincidem.';
        } else {
            zt_bd()->prepare(
                'INSERT INTO zt_utilizadores (utilizador, senha, nome, dono, criado)
                 VALUES (?, ?, ?, 1, NOW())'
            )->execute([$u, password_hash($s1, PASSWORD_DEFAULT), mb_substr($n, 0, 120)]);
            $ok = $feito = true;
        }
    }
}
?><!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Arranque — Sistema de Gestão ZEUS TECH</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:"Segoe UI",system-ui,-apple-system,Arial,sans-serif;min-height:100vh;
 display:flex;align-items:center;justify-content:center;padding:22px;
 background:linear-gradient(135deg,#0d1b2a,#16314f 55%,#1b4e85);color:#0d1b2a}
.caixa{background:#fff;border-radius:15px;padding:32px;width:100%;max-width:430px;
 box-shadow:0 18px 54px rgba(0,0,0,.34)}
h1{font-size:1.07rem;font-weight:640;margin-bottom:6px}
.sub{font-size:.86rem;color:#6b7f96;margin-bottom:22px;line-height:1.5}
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
.bom{background:#eefaf1;border:1px solid #cfeeda;color:#1d6b3a;border-radius:8px;
 padding:12px 14px;font-size:.88rem;line-height:1.55}
.nota{font-size:.8rem;color:#8b9cb0;margin-top:18px;padding-top:14px;
 border-top:1px solid #eef3f9;line-height:1.55}
a{color:#12539a}
code{background:#f2f6fb;padding:1px 5px;border-radius:4px;font-size:.92em}
</style>
</head>
<body>
<div class="caixa">
<?php if ($ok): ?>
 <h1>Dono criado</h1>
 <div class="bom">O sistema já tem dono. <b>Apague agora o ficheiro
  <code>instalar.php</code> do servidor</b> — não volta a ser preciso.<br><br>
  <a href="login.php">Entrar no portal &rarr;</a></div>
<?php elseif ($feito): ?>
 <h1>Já instalado</h1>
 <div class="bom">Este sistema já tem utilizadores, por isso o arranque está
  fechado. <b>Apague o ficheiro <code>instalar.php</code> do servidor.</b><br><br>
  Novos utilizadores criam-se dentro do portal, em "Utilizadores".<br><br>
  <a href="login.php">Entrar no portal &rarr;</a></div>
<?php else: ?>
 <h1>Criar o dono do sistema de gestão</h1>
 <p class="sub">Este é o primeiro e único utilizador criado aqui. Fica com
  permissão para criar, apagar e repor os outros dentro do portal.</p>
 <?php if ($erro !== ''): ?><div class="erro"><?= e($erro) ?></div><?php endif; ?>
 <form method="post" autocomplete="off">
  <input type="hidden" name="csrf" value="<?= e(zt_csrf()) ?>">
  <label for="utilizador">Utilizador</label>
  <input type="text" id="utilizador" name="utilizador" autocapitalize="none"
         spellcheck="false" required autofocus
         value="<?= e((string) ($_POST['utilizador'] ?? '')) ?>">
  <label for="nome">Nome (opcional)</label>
  <input type="text" id="nome" name="nome"
         value="<?= e((string) ($_POST['nome'] ?? '')) ?>">
  <label for="senha">Senha (mínimo 8 caracteres)</label>
  <input type="password" id="senha" name="senha" required>
  <label for="senha2">Repetir a senha</label>
  <input type="password" id="senha2" name="senha2" required>
  <button type="submit">Criar o dono</button>
 </form>
 <p class="nota">Depois de criado, apague este ficheiro do servidor. O dono
  pode mudar a própria senha e gerir os restantes utilizadores dentro do portal.</p>
<?php endif; ?>
</div>
</body>
</html>
