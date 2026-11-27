# Aplicação PHP do portal ZEUS TECH (ISO/IEC 17020)

Ramo separado do sítio. **Não** é servido pelo GitHub Pages.

Está instalado em `https://zeus.continentalconsulting.org`, no alojamento da
Hostever:

- `/home/contine1/zeus_publico/` — estes ficheiros
- `/home/contine1/zeus_documentos/` — os 318 documentos, **fora** da pasta
  pública. Não há endereço na Internet que lhes chegue: a única porta é o
  `doc.php`, e ele começa por exigir sessão iniciada.

O `_config.php` **não está aqui de propósito**: tem a senha da base de dados e
vive só no servidor.

Nota: este alojamento ignora o `.htaccess` (`AllowOverride` desligado). Por
isso o desvio para https está em PHP, no `_sessao.php`, e não no `.htaccess`.
