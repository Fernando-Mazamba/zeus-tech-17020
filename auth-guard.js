/* =========================================================================
   ZEUS TECH OIL & GAS — Sistema de Gestão do Organismo de Inspecção
   Controlo de acesso ao portal documental

   >>> PARA MUDAR O UTILIZADOR E O CÓDIGO, EDITE AS DUAS LINHAS ABAIXO. <<<
   Depois de editar, guarde o ficheiro e faça o envio para o repositório.
   ========================================================================= */

var SGI_UTILIZADOR = 'zeustech';
var SGI_CODIGO     = 'Zt17020';

/* -------------------------------------------------------------------------
   AVISO IMPORTANTE — LEIA ANTES DE CONFIAR NISTO

   O código é pedido UMA VEZ, à entrada do portal. Enquanto o navegador
   estiver aberto não volta a pedir.

   Este controlo corre no navegador de quem visita. Serve para que o portal
   não fique à vista de quem apanhe o endereço por acaso, mas NÃO é uma
   proteção verdadeira:

     - o utilizador e o código estão neste ficheiro, que é servido ao
       navegador e pode ser lido por qualquer visitante;
     - os documentos continuam acessíveis pelo endereço direto. Quem tiver
       a ligação de um PDF abre-o sem passar por aqui.

   Para proteção a sério num site estático há três caminhos:
     1. Cloudflare Access à frente do domínio (gratuito até 50 utilizadores):
        a autenticação acontece antes de o ficheiro ser servido;
     2. alojamento com palavra-passe ao nível do servidor (Netlify, ou
        Apache com .htaccess num alojamento tipo cPanel);
     3. não publicar os documentos sensíveis: deixá-los só no pacote que
        se entrega à empresa.
   ------------------------------------------------------------------------- */

(function () {
  'use strict';

  var CHAVE = 'zt_sgi_sessao';

  function raiz() {
    var s = document.currentScript;
    if (s && s.getAttribute('data-raiz') !== null) return s.getAttribute('data-raiz');
    if (s && s.src) {
      var m = s.src.match(/^(.*\/)auth-guard\.js(?:\?.*)?$/);
      if (m) return m[1];
    }
    return '';
  }

  function autenticado() {
    try {
      return sessionStorage.getItem(CHAVE) === 'ok';
    } catch (e) {
      return false;   // sessionStorage bloqueado: trata como não autenticado
    }
  }

  // A página de entrada não se protege a si própria.
  if (/(^|\/)login\.html($|[?#])/.test(location.pathname + location.search)) return;

  if (autenticado()) return;

  // Esconder a página antes de qualquer coisa aparecer, e reencaminhar.
  try {
    document.documentElement.style.visibility = 'hidden';
  } catch (e) { /* ignorar */ }

  location.replace(raiz() + 'login.html');
})();
