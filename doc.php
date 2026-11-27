<?php
/* =========================================================================
   Entrega um documento a quem tem sessão iniciada.

   É esta a diferença para o sistema antigo: os ficheiros vivem FORA da pasta
   pública, por isso não há endereço directo que os abra. A única porta é este
   script, e ele começa por exigir sessão.
   ========================================================================= */

require_once __DIR__ . '/_sessao.php';

$utilizador = zt_exigir_sessao();

$pedido = (string) ($_GET['f'] ?? '');
if ($pedido === '') {
    http_response_code(400);
    exit('Falta o nome do documento.');
}

/* --- Validação do caminho ------------------------------------------------
   Nunca confiar no que vem no endereço. Recusa byte nulo, barra inicial,
   barra invertida e qualquer PASSO do caminho que seja "." ou ".." — repare-se
   que é o passo inteiro, e não o texto: há documentos cujo nome acaba em três
   pontos, e esses são legítimos. Depois confirma-se com realpath() que o
   ficheiro resolvido fica mesmo dentro da pasta dos documentos. As duas
   verificações juntas fecham a porta à travessia de directórios.            */
$passos = explode('/', $pedido);
if (strpos($pedido, "\0") !== false
    || strpos($pedido, '\\') !== false
    || $pedido[0] === '/'
    || in_array('..', $passos, true)
    || in_array('.', $passos, true)
    || in_array('', $passos, true)) {
    http_response_code(400);
    exit('Caminho inválido.');
}

$raiz = realpath(ZT_DOCS);
if ($raiz === false) {
    error_log('ZT: a pasta dos documentos nao existe: ' . ZT_DOCS);
    http_response_code(500);
    exit('Documentos indisponíveis.');
}

$caminho = realpath($raiz . DIRECTORY_SEPARATOR . $pedido);
if ($caminho === false
    || strncmp($caminho, $raiz . DIRECTORY_SEPARATOR, strlen($raiz) + 1) !== 0
    || !is_file($caminho)) {
    http_response_code(404);
    exit('Documento não encontrado.');
}

/* --- Só os tipos que o sistema tem -------------------------------------- */
$TIPOS = [
    'pdf'  => 'application/pdf',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'doc'  => 'application/msword',
    'xls'  => 'application/vnd.ms-excel',
    'png'  => 'image/png',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
];
$ext = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));
if (!isset($TIPOS[$ext])) {
    http_response_code(403);
    exit('Tipo de ficheiro não permitido.');
}

/* --- O PDF abre no navegador; o resto descarrega para ser editado -------- */
$disposicao = ($ext === 'pdf' || $ext === 'png' || $ext === 'jpg' || $ext === 'jpeg')
            ? 'inline' : 'attachment';
$nome = basename($caminho);

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: ' . $TIPOS[$ext]);
header('Content-Length: ' . filesize($caminho));
header("Content-Disposition: $disposicao; filename=\"" . str_replace('"', '', $nome) . '"; '
     . "filename*=UTF-8''" . rawurlencode($nome));
header('X-Content-Type-Options: nosniff');
// Documento controlado: não deixar ficar em caches partilhadas.
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: no-cache');

readfile($caminho);
