/* =========================================================================
   ZEUS TECH OIL & GAS — Sistema de Gestão do Organismo de Inspecção
   Controlo de acesso ao portal documental

   Os utilizadores criam-se e apagam-se no próprio portal, na vista
   "Utilizadores" da barra lateral. Esse ecrã escreve este ficheiro já
   pronto; basta substituí-lo no repositório para a alteração valer para
   toda a gente.

   Os códigos NÃO estão aqui em texto. O que está guardado é o resumo
   (SHA-256) de sal|utilizador|código, que não se pode reverter. Quem perder
   o código não o recupera: cria-se o utilizador outra vez com um código novo.
   ========================================================================= */

var SGI_SAL = 'ZEUS-TECH-17020';

/* --- utilizadores --- */
var SGI_UTILIZADORES = [
  { u: 'zeustech', h: 'f0eca4cf0a16e5880037d776122b01939e807a1b97670428782c40f3992ad032' }
];
/* --- fim dos utilizadores --- */

/* -------------------------------------------------------------------------
   AVISO IMPORTANTE — LEIA ANTES DE CONFIAR NISTO

   O código é pedido UMA VEZ por computador. Fica guardado no navegador e não
   volta a pedir, mesmo depois de o fechar e abrir outra vez. Só torna a pedir
   se limparem os dados do navegador, usarem uma janela anónima ou abrirem o
   portal noutro computador.

   Este controlo corre no navegador de quem visita. Guardar resumos em vez de
   códigos impede que alguém leia as palavras-passe no código-fonte, mas isto
   continua a NÃO ser uma proteção verdadeira:

     - os documentos continuam acessíveis pelo endereço directo. Quem tiver a
       ligação de um PDF abre-o sem passar por aqui;
     - quem perceba de navegadores contorna a verificação, que corre do lado
       de quem visita.

   Para proteção a sério num site estático há três caminhos:
     1. Cloudflare Access à frente do domínio (gratuito até 50 utilizadores):
        a autenticação acontece antes de o ficheiro ser servido;
     2. alojamento com palavra-passe ao nível do servidor (Netlify, ou Apache
        com .htaccess num alojamento tipo cPanel);
     3. não publicar os documentos sensíveis: deixá-los só no pacote que se
        entrega à empresa.
   ------------------------------------------------------------------------- */

var SGI_CHAVE = 'zt_sgi_sessao';

/* Resumo de um par utilizador/código. Devolve uma promessa com o hexadecimal.
   Usa a Web Crypto, que existe em https e em localhost. */
function sgiResumo(utilizador, codigo) {
  var texto = SGI_SAL + '|' + String(utilizador).trim().toLowerCase() + '|' + String(codigo);
  if (!(window.crypto && window.crypto.subtle)) {
    return Promise.reject(new Error('sem-crypto'));
  }
  return window.crypto.subtle
    .digest('SHA-256', new TextEncoder().encode(texto))
    .then(function (buf) {
      return Array.prototype.map
        .call(new Uint8Array(buf), function (b) { return ('0' + b.toString(16)).slice(-2); })
        .join('');
    });
}

/* Confere um par contra a lista. Promessa com o utilizador encontrado, ou null. */
function sgiConfere(utilizador, codigo) {
  var nome = String(utilizador).trim().toLowerCase();
  return sgiResumo(nome, codigo).then(function (h) {
    for (var i = 0; i < SGI_UTILIZADORES.length; i++) {
      if (SGI_UTILIZADORES[i].u.toLowerCase() === nome && SGI_UTILIZADORES[i].h === h) {
        return SGI_UTILIZADORES[i].u;
      }
    }
    return null;
  });
}

(function () {
  'use strict';

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
    // localStorage sobrevive a fechar o navegador; o sessionStorage fica como
    // alternativa para quando o localStorage está bloqueado (janela anónima).
    try {
      if (window.localStorage.getItem(SGI_CHAVE)) return true;
    } catch (e) { /* bloqueado: tenta o outro */ }
    try {
      return !!window.sessionStorage.getItem(SGI_CHAVE);
    } catch (e) {
      return false;
    }
  }

  // A página de entrada não se protege a si própria.
  if (/(^|\/)login\.html($|[?#])/.test(location.pathname + location.search)) return;

  if (autenticado()) return;

  try {
    document.documentElement.style.visibility = 'hidden';
  } catch (e) { /* ignorar */ }

  location.replace(raiz() + 'login.html');
})();
