<?php
/* =========================================================================
   Lista o que está dentro de uma pasta de documentos, a quem tem sessão.

   Serve os ícones de "pasta" do ecrã de guardar os registos: abre a pasta
   onde os registos ficam, e cada ficheiro abre pelo doc.php.
   ========================================================================= */

require_once __DIR__ . '/_sessao.php';

$zt = zt_exigir_sessao();

$ingles = isset($_GET['en']);
$T = $ingles
   ? ['t' => 'Folder', 'voltar' => 'Back to the portal', 'vazia' => 'This folder is empty.',
      'nome' => 'Document', 'tam' => 'Size', 'sair' => 'Sign out', 'nada' => 'Folder not found.']
   : ['t' => 'Pasta', 'voltar' => 'Voltar ao portal', 'vazia' => 'Esta pasta está vazia.',
      'nome' => 'Documento', 'tam' => 'Tamanho', 'sair' => 'Sair', 'nada' => 'Pasta não encontrada.'];
$portal = $ingles ? 'index-en.php' : 'index.php';

/* --- Validação do caminho: a mesma do doc.php ---------------------------- */
$pedido = trim((string) ($_GET['d'] ?? ''), '/');
$passos = explode('/', $pedido);
if ($pedido === ''
    || strpos($pedido, "\0") !== false
    || strpos($pedido, '\\') !== false
    || in_array('..', $passos, true)
    || in_array('.', $passos, true)
    || in_array('', $passos, true)) {
    http_response_code(400);
    exit('Caminho inválido.');
}

$raiz = realpath(ZT_DOCS);
$pasta = $raiz === false ? false : realpath($raiz . DIRECTORY_SEPARATOR . $pedido);
if ($pasta === false
    || strncmp($pasta, $raiz . DIRECTORY_SEPARATOR, strlen($raiz) + 1) !== 0
    || !is_dir($pasta)) {
    http_response_code(404);
    exit($T['nada']);
}

/* --- O que há lá dentro, só os tipos do sistema -------------------------- */
$TIPOS = ['pdf', 'docx', 'xlsx', 'doc', 'xls', 'png', 'jpg', 'jpeg'];
$ficheiros = $pastas = [];
foreach (scandir($pasta) ?: [] as $n) {
    if ($n === '.' || $n === '..' || $n[0] === '.') {
        continue;
    }
    $c = $pasta . DIRECTORY_SEPARATOR . $n;
    if (is_dir($c)) {
        $pastas[] = $n;
    } elseif (is_file($c) && in_array(strtolower(pathinfo($n, PATHINFO_EXTENSION)), $TIPOS, true)) {
        $ficheiros[] = ['n' => $n, 'b' => filesize($c)];
    }
}
natcasesort($pastas);
usort($ficheiros, fn($a, $b) => strnatcasecmp($a['n'], $b['n']));

function zt_tamanho(int $b): string
{
    if ($b >= 1048576) return number_format($b / 1048576, 1) . ' MB';
    if ($b >= 1024)    return number_format($b / 1024, 0) . ' kB';
    return $b . ' B';
}
$lig = fn(string $sub) => 'pasta.php?d=' . rawurlencode($pedido . '/' . $sub) . ($ingles ? '&en=1' : '');
$doc = fn(string $f) => 'doc.php?f=' . rawurlencode($pedido . '/' . $f);

/* --- Migalhas ------------------------------------------------------------ */
$partes = explode('/', $pedido);
$migalhas = [];
$acc = '';
foreach ($partes as $i => $p) {
    $acc .= ($acc === '' ? '' : '/') . $p;
    $migalhas[] = ['nome' => str_replace('_', ' ', $p),
                   'url'  => $i === count($partes) - 1 ? null
                           : 'pasta.php?d=' . rawurlencode($acc) . ($ingles ? '&en=1' : '')];
}
?><!DOCTYPE html>
<html lang="<?= $ingles ? 'en' : 'pt' ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($T['t']) ?> — ZEUS TECH</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:"Segoe UI",system-ui,-apple-system,Arial,sans-serif;background:#f4f7fb;
 color:#16222f;padding:26px 18px;line-height:1.5}
.folha{max-width:880px;margin:0 auto;background:#fff;border-radius:13px;
 box-shadow:0 2px 14px rgba(13,27,42,.09);overflow:hidden}
header{background:linear-gradient(135deg,#0d1b2a,#1b4e85);color:#fff;padding:20px 24px}
header h1{font-size:1.05rem;font-weight:640}
.mig{font-size:.8rem;color:#bcd2ea;margin-top:5px}
.mig a{color:#bcd2ea}
table{width:100%;border-collapse:collapse}
th,td{text-align:left;padding:11px 24px;border-bottom:1px solid #eef3f9;font-size:.9rem}
th{font-size:.73rem;text-transform:uppercase;letter-spacing:.7px;
 color:#5d748d;background:#fafcfe;font-weight:650}
td.tam{text-align:right;color:#7e93a9;font-size:.83rem;white-space:nowrap}
tr:last-child td{border-bottom:none}
a{color:#12539a;text-decoration:none}
a:hover{text-decoration:underline}
.ic{display:inline-block;width:19px;color:#8ca3bb}
.rodape{padding:15px 24px;background:#fafcfe;border-top:1px solid #eef3f9;
 font-size:.82rem;display:flex;gap:18px;justify-content:space-between;flex-wrap:wrap}
.vazia{padding:30px 24px;color:#7e93a9;font-size:.9rem}
</style>
</head>
<body>
<div class="folha">
 <header>
  <h1><?= e($T['t']) ?></h1>
  <p class="mig"><?php foreach ($migalhas as $i => $m) {
      echo $i ? ' / ' : '';
      echo $m['url'] === null ? e($m['nome'])
         : '<a href="' . e($m['url']) . '">' . e($m['nome']) . '</a>';
  } ?></p>
 </header>
<?php if (!$pastas && !$ficheiros): ?>
 <p class="vazia"><?= e($T['vazia']) ?></p>
<?php else: ?>
 <table>
  <tr><th><?= e($T['nome']) ?></th><th class="tam"><?= e($T['tam']) ?></th></tr>
<?php foreach ($pastas as $p): ?>
  <tr><td><span class="ic">&#128193;</span>
   <a href="<?= e($lig($p)) ?>"><?= e(str_replace('_', ' ', $p)) ?></a></td><td class="tam">—</td></tr>
<?php endforeach; ?>
<?php foreach ($ficheiros as $f): ?>
  <tr><td><span class="ic">&#128196;</span>
   <a href="<?= e($doc($f['n'])) ?>"><?= e($f['n']) ?></a></td>
   <td class="tam"><?= e(zt_tamanho($f['b'])) ?></td></tr>
<?php endforeach; ?>
 </table>
<?php endif; ?>
 <div class="rodape">
  <a href="<?= e($portal) ?>">&larr; <?= e($T['voltar']) ?></a>
  <a href="sair.php"><?= e($T['sair']) ?></a>
 </div>
</div>
</body>
</html>
