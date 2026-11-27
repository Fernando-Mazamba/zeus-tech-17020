<?php
/* =========================================================================
   Utilizadores do portal.

   Qualquer pessoa com sessão pode mudar a SUA senha. Quem está marcado como
   dono do sistema pode também criar e apagar utilizadores. Tudo o que aqui
   se faz vale imediatamente para toda a gente: está na base de dados, não
   num ficheiro.
   ========================================================================= */

require_once __DIR__ . '/_sessao.php';

$eu  = zt_exigir_sessao();
$bd  = zt_bd();
$msg = '';
$mau = false;

const ZT_NOME_OK = '/^[A-Za-z0-9._@-]{3,40}$/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!zt_csrf_valido($_POST['csrf'] ?? null)) {
        $msg = 'A sessão expirou. Recarregue a página.';
        $mau = true;
    } else {
        $accao = (string) ($_POST['accao'] ?? '');

        // ---------------------------------------- mudar a minha senha
        if ($accao === 'minha_senha') {
            $actual = (string) ($_POST['actual'] ?? '');
            $nova   = (string) ($_POST['nova'] ?? '');
            $conf   = (string) ($_POST['conf'] ?? '');
            $q = $bd->prepare('SELECT senha FROM zt_utilizadores WHERE id = ?');
            $q->execute([$eu['id']]);
            $hash = (string) ($q->fetchColumn() ?: '');

            if (!password_verify($actual, $hash)) {
                $msg = 'A senha actual não está correcta.'; $mau = true;
            } elseif (mb_strlen($nova) < 8) {
                $msg = 'A senha nova deve ter pelo menos 8 caracteres.'; $mau = true;
            } elseif ($nova !== $conf) {
                $msg = 'As duas senhas novas não coincidem.'; $mau = true;
            } else {
                $bd->prepare('UPDATE zt_utilizadores SET senha = ? WHERE id = ?')
                   ->execute([password_hash($nova, PASSWORD_DEFAULT), $eu['id']]);
                $msg = 'Senha alterada. Já vale a partir de agora, em qualquer computador.';
            }

        // ---------------------------------------- criar utilizador (só o dono)
        } elseif ($accao === 'criar' && $eu['dono']) {
            $u    = trim((string) ($_POST['utilizador'] ?? ''));
            $nome = trim((string) ($_POST['nome'] ?? ''));
            $s    = (string) ($_POST['senha'] ?? '');
            $dono = !empty($_POST['dono']) ? 1 : 0;

            if (!preg_match(ZT_NOME_OK, $u)) {
                $msg = 'O nome de utilizador deve ter 3 a 40 caracteres: letras, '
                     . 'algarismos e . _ - @'; $mau = true;
            } elseif (mb_strlen($s) < 8) {
                $msg = 'A senha deve ter pelo menos 8 caracteres.'; $mau = true;
            } else {
                $j = $bd->prepare('SELECT COUNT(*) FROM zt_utilizadores WHERE utilizador = ?');
                $j->execute([$u]);
                if ((int) $j->fetchColumn() > 0) {
                    $msg = 'Já existe um utilizador com esse nome.'; $mau = true;
                } else {
                    $bd->prepare(
                        'INSERT INTO zt_utilizadores (utilizador, senha, nome, dono, criado)
                         VALUES (?, ?, ?, ?, NOW())'
                    )->execute([$u, password_hash($s, PASSWORD_DEFAULT),
                                mb_substr($nome, 0, 120), $dono]);
                    $msg = 'Utilizador "' . $u . '" criado. Já pode entrar.';
                }
            }

        // ---------------------------------------- apagar utilizador (só o dono)
        } elseif ($accao === 'apagar' && $eu['dono']) {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id === $eu['id']) {
                $msg = 'Não se pode apagar a si próprio.'; $mau = true;
            } elseif ((int) $bd->query('SELECT COUNT(*) FROM zt_utilizadores')->fetchColumn() <= 1) {
                $msg = 'Tem de ficar pelo menos um utilizador.'; $mau = true;
            } else {
                $q = $bd->prepare('SELECT utilizador FROM zt_utilizadores WHERE id = ?');
                $q->execute([$id]);
                $alvo = (string) ($q->fetchColumn() ?: '');
                $bd->prepare('DELETE FROM zt_utilizadores WHERE id = ?')->execute([$id]);
                $msg = $alvo !== '' ? 'Utilizador "' . $alvo . '" apagado.' : 'Utilizador apagado.';
            }

        // ---------------------------------------- repor a senha de outro (dono)
        } elseif ($accao === 'repor' && $eu['dono']) {
            $id = (int) ($_POST['id'] ?? 0);
            $s  = (string) ($_POST['senha'] ?? '');
            if (mb_strlen($s) < 8) {
                $msg = 'A senha deve ter pelo menos 8 caracteres.'; $mau = true;
            } else {
                $bd->prepare('UPDATE zt_utilizadores SET senha = ? WHERE id = ?')
                   ->execute([password_hash($s, PASSWORD_DEFAULT), $id]);
                $msg = 'Senha reposta. Entregue-a à pessoa.';
            }
        }
    }
}

$lista = $bd->query(
    'SELECT id, utilizador, nome, dono, criado, ultimo_acesso
       FROM zt_utilizadores ORDER BY dono DESC, utilizador'
)->fetchAll();

$acessos = $bd->query(
    'SELECT utilizador, quando, ip, resultado FROM zt_acessos
      ORDER BY quando DESC LIMIT 15'
)->fetchAll();

$csrf = zt_csrf();
function d(?string $v): string { return $v ? date('d/m/Y H:i', strtotime($v)) : '—'; }
?><!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Utilizadores — Sistema de Gestão ZEUS TECH</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{--tinta:#0d1b2a;--tinta2:#38506b;--fraco:#6b7f96;--azul:#1b6fc4;--azul-f:#12539a;
 --risca:#dde5ee;--fundo:#f4f7fb;--cartao:#fff;--verde:#0d7a5f}
body{font-family:"Segoe UI",system-ui,-apple-system,Arial,sans-serif;background:var(--fundo);
 color:var(--tinta);line-height:1.55}
.faixa{background:linear-gradient(135deg,#0d1b2a,#16314f 55%,#1b4e85);color:#fff;padding:22px 20px}
.centro{max-width:960px;margin:0 auto;padding:0 20px}
.faixa .centro{display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap}
.faixa h1{font-size:1.2rem;font-weight:650}
.faixa p{color:#b8cde6;font-size:.88rem;margin-top:3px}
.voltar{color:#cfe0f3;text-decoration:none;font-size:.88rem;font-weight:600;
 border:1px solid rgba(255,255,255,.3);border-radius:8px;padding:7px 14px}
.voltar:hover{background:rgba(255,255,255,.12);color:#fff}
main{max-width:960px;margin:0 auto;padding:24px 20px 60px}
.cartao{background:var(--cartao);border:1px solid var(--risca);border-radius:11px;
 padding:19px 21px;margin-bottom:18px}
.cartao h2{font-size:1.04rem;font-weight:640;margin-bottom:4px}
.cartao .nota{font-size:.87rem;color:var(--fraco);margin-bottom:14px}
.campos{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:13px;
 margin-bottom:14px}
.campos label{display:block;font-size:.75rem;text-transform:uppercase;letter-spacing:.7px;
 color:var(--tinta2);font-weight:650}
.campos input{display:block;width:100%;margin-top:6px;padding:9px 11px;
 border:1.5px solid var(--risca);border-radius:8px;font-size:.93rem;font-family:inherit}
.campos input:focus{outline:none;border-color:var(--azul)}
.linha{display:flex;gap:9px;align-items:center;flex-wrap:wrap}
.bt{display:inline-flex;align-items:center;padding:8px 15px;border-radius:8px;font-size:.87rem;
 font-weight:620;font-family:inherit;text-decoration:none;border:1.5px solid transparent;
 cursor:pointer}
.bt-azul{background:var(--azul-f);color:#fff}
.bt-azul:hover{background:#0d4280}
.bt-cinza{background:#eef3f9;color:var(--tinta2);border-color:var(--risca)}
.bt-cinza:hover{background:#e2eaf4}
.bt-red{background:#fdeeee;color:#9b2222;border-color:#f3d2d2}
.bt-red:hover{background:#9b2222;color:#fff}
.bt-s{padding:5px 11px;font-size:.8rem}
table{width:100%;border-collapse:collapse;font-size:.9rem}
th{text-align:left;font-size:.72rem;text-transform:uppercase;letter-spacing:.7px;
 color:var(--tinta2);padding:8px 10px;background:#eef3f9}
td{padding:9px 10px;border-top:1px solid var(--risca);vertical-align:middle}
.sel{background:#e8f0fa;color:var(--azul-f);font-size:.72rem;font-weight:650;
 padding:2px 8px;border-radius:20px}
.msg{border-radius:9px;padding:11px 15px;font-size:.9rem;margin-bottom:18px}
.msg.bom{background:#e7f4ee;border:1px solid #c9e6d9;color:var(--verde)}
.msg.mau{background:#fdeeee;border:1px solid #f3d2d2;color:#9b2222}
.aviso{background:#fff8e8;border:1px solid #f0dcae;border-left:4px solid #c9941f;
 border-radius:9px;padding:13px 16px;font-size:.88rem;color:#5c4512}
code{background:#eef3f9;padding:1px 5px;border-radius:4px;font-size:.88em}
@media(max-width:640px){table,thead,tbody,th,td,tr{display:block}thead{display:none}
 td{border-top:none;padding:3px 10px}tbody tr{border-top:1px solid var(--risca);padding:10px 0}}
</style>
</head>
<body>
<div class="faixa"><div class="centro">
 <div><h1>Utilizadores do portal</h1>
  <p>Sessão de <?= e($eu['utilizador']) ?><?= $eu['dono'] ? ' · dono do sistema' : '' ?></p></div>
 <div class="linha">
  <a class="voltar" href="index.php">&larr; Voltar ao portal</a>
  <a class="voltar" href="sair.php">Sair</a>
 </div>
</div></div>

<main>
<?php if ($msg !== ''): ?>
 <div class="msg <?= $mau ? 'mau' : 'bom' ?>"><?= e($msg) ?></div>
<?php endif; ?>

<div class="cartao">
 <h2>Mudar a minha senha</h2>
 <p class="nota">A alteração vale imediatamente, em todos os computadores.</p>
 <form method="post" autocomplete="off">
  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
  <input type="hidden" name="accao" value="minha_senha">
  <div class="campos">
   <label>Senha actual<input type="password" name="actual" required></label>
   <label>Senha nova<input type="password" name="nova" required minlength="8"></label>
   <label>Repetir a nova<input type="password" name="conf" required minlength="8"></label>
  </div>
  <button class="bt bt-azul" type="submit">Alterar a minha senha</button>
 </form>
</div>

<?php if ($eu['dono']): ?>
<div class="cartao">
 <h2>Criar utilizador</h2>
 <p class="nota">Entregue o nome e a senha à pessoa. Ela pode mudá-la depois, sozinha.</p>
 <form method="post" autocomplete="off">
  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
  <input type="hidden" name="accao" value="criar">
  <div class="campos">
   <label>Utilizador<input type="text" name="utilizador" required
     pattern="[A-Za-z0-9._@\-]{3,40}" spellcheck="false"></label>
   <label>Nome da pessoa (opcional)<input type="text" name="nome"></label>
   <label>Senha<input type="text" name="senha" required minlength="8" spellcheck="false"></label>
  </div>
  <label class="linha" style="font-size:.88rem;margin-bottom:13px">
   <input type="checkbox" name="dono" value="1" style="width:auto">
   &nbsp;Também pode gerir utilizadores
  </label>
  <button class="bt bt-azul" type="submit">Criar</button>
 </form>
</div>
<?php endif; ?>

<div class="cartao">
 <h2>Quem tem acesso</h2>
 <p class="nota"><?= count($lista) ?> utilizador<?= count($lista) === 1 ? '' : 'es' ?>.
  As senhas não se podem ver: fica guardado um resumo que não se reverte.</p>
 <table>
  <thead><tr><th>Utilizador</th><th>Nome</th><th>Criado</th><th>Último acesso</th>
   <th style="text-align:right">Acções</th></tr></thead>
  <tbody>
  <?php foreach ($lista as $l): ?>
   <tr>
    <td><strong><?= e($l['utilizador']) ?></strong>
     <?= (int) $l['dono'] === 1 ? ' <span class="sel">dono</span>' : '' ?>
     <?= (int) $l['id'] === $eu['id'] ? ' <span class="sel">eu</span>' : '' ?></td>
    <td><?= e($l['nome']) ?></td>
    <td><?= d($l['criado']) ?></td>
    <td><?= d($l['ultimo_acesso']) ?></td>
    <td style="text-align:right">
     <?php if ($eu['dono'] && (int) $l['id'] !== $eu['id']): ?>
      <form method="post" style="display:inline" class="linha"
            onsubmit="return confirm('Apagar o utilizador <?= e($l['utilizador']) ?>?')">
       <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
       <input type="hidden" name="accao" value="apagar">
       <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
       <button class="bt bt-red bt-s" type="submit">Apagar</button>
      </form>
     <?php endif; ?>
     <?php if ($eu['dono']): ?>
      <form method="post" style="display:inline-flex;gap:6px;align-items:center">
       <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
       <input type="hidden" name="accao" value="repor">
       <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
       <input type="text" name="senha" placeholder="nova senha" minlength="8"
              style="padding:5px 9px;border:1.5px solid var(--risca);border-radius:7px;
                     font-size:.82rem;width:130px">
       <button class="bt bt-cinza bt-s" type="submit">Repor</button>
      </form>
     <?php endif; ?>
    </td>
   </tr>
  <?php endforeach; ?>
  </tbody>
 </table>
</div>

<?php if ($eu['dono']): ?>
<div class="cartao">
 <h2>Últimas entradas</h2>
 <p class="nota">Serve de prova de controlo de acesso numa auditoria.</p>
 <table>
  <thead><tr><th>Quando</th><th>Utilizador</th><th>Endereço</th><th>Resultado</th></tr></thead>
  <tbody>
  <?php foreach ($acessos as $a): ?>
   <tr><td><?= d($a['quando']) ?></td><td><?= e($a['utilizador']) ?></td>
    <td><?= e($a['ip']) ?></td>
    <td><?= $a['resultado'] === 'entrou' ? 'entrou' : '<strong>recusado</strong>' ?></td></tr>
  <?php endforeach; ?>
  <?php if (!$acessos): ?><tr><td colspan="4">Ainda sem registos.</td></tr><?php endif; ?>
  </tbody>
 </table>
</div>

<div class="aviso"><b>Cópia de segurança.</b> O alojamento não faz cópias por si.
 A lista de utilizadores vive na base de dados <code>zt_utilizadores</code>: exporte-a de
 vez em quando pelo phpMyAdmin. Os documentos estão na pasta do servidor: guarde uma cópia
 fora daqui, por exemplo no OneDrive ou no Google Drive da empresa.</div>
<?php endif; ?>
</main>
</body>
</html>
