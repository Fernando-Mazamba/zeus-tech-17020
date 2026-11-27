#!/bin/bash
# =============================================================================
# Instala o portal ZEUS TECH neste alojamento.
#
#   /home/contine1/zeus_publico/     a aplicação, servida pelo subdomínio
#   /home/contine1/zeus_documentos/  os documentos, FORA da pasta pública
#
# Corre-se uma vez. Pode repetir-se sem estragar nada.
# =============================================================================
set -u

CASA=/home/contine1
REPO=https://codeload.github.com/Fernando-Mazamba/zeus-tech-17020/tar.gz/refs/heads
OBRA=$CASA/_instalacao

echo "=== $(date -u) ==="
echo "Quem sou: $(whoami)  ·  onde: $CASA"

rm -rf "$OBRA"; mkdir -p "$OBRA" || exit 1
cd "$OBRA" || exit 1

echo
echo "--- a descarregar ---"
curl -sSL --fail -o app.tgz "$REPO/app-php" || { echo "FALHOU a aplicação"; exit 1; }
curl -sSL --fail -o doc.tgz "$REPO/main"    || { echo "FALHOU os documentos"; exit 1; }
ls -la *.tgz

echo
echo "--- a extrair ---"
tar xzf app.tgz || exit 1
tar xzf doc.tgz || exit 1

APP=$(find . -maxdepth 1 -type d -name 'zeus-tech-17020-app-php*' | head -1)
DOC=$(find . -maxdepth 1 -type d -name 'zeus-tech-17020-main*'    | head -1)
echo "aplicação em: $APP"
echo "documentos em: $DOC"
[ -z "$APP" ] || [ -z "$DOC" ] && { echo "FALHOU a extracção"; exit 1; }

echo
echo "--- a instalar a aplicação ---"
mkdir -p "$CASA/zeus_publico"
# o _config.php escreve-se à mão no servidor e nunca se apaga
cp -a "$APP"/. "$CASA/zeus_publico/"
rm -f "$CASA/zeus_publico/LEIA-ME.md" "$CASA/zeus_publico/instalar.sh"

echo
echo "--- a instalar os documentos, fora da pasta pública ---"
mkdir -p "$CASA/zeus_documentos"
for p in 01_Manual 02_Procedimentos_PG 03_Instrucoes_Tecnicas_IT \
         04_Documentos_DC_RG_ORG 05_Formularios_F 06_Registos \
         08_SGI_ZEUS_TECH EN; do
  if [ -d "$DOC/$p" ]; then
    rm -rf "$CASA/zeus_documentos/${p:?}"
    cp -a "$DOC/$p" "$CASA/zeus_documentos/"
    printf '  %-34s %s ficheiros\n' "$p" "$(find "$CASA/zeus_documentos/$p" -type f | wc -l)"
  else
    echo "  EM FALTA: $p"
  fi
done

echo
echo "--- permissões ---"
find "$CASA/zeus_publico"    -type d -exec chmod 755 {} \;
find "$CASA/zeus_publico"    -type f -exec chmod 644 {} \;
find "$CASA/zeus_documentos" -type d -exec chmod 755 {} \;
find "$CASA/zeus_documentos" -type f -exec chmod 644 {} \;

echo
echo "--- resultado ---"
echo "aplicação:  $(find "$CASA/zeus_publico" -type f | wc -l) ficheiros, $(du -sh "$CASA/zeus_publico" | cut -f1)"
echo "documentos: $(find "$CASA/zeus_documentos" -type f | wc -l) ficheiros, $(du -sh "$CASA/zeus_documentos" | cut -f1)"
echo
ls -la "$CASA/zeus_publico"
echo
if [ -f "$CASA/zeus_publico/_config.php" ]; then
  echo "_config.php: presente"
else
  echo "_config.php: EM FALTA — escrever à mão no servidor"
fi

rm -rf "$OBRA"
echo
echo "=== terminado $(date -u) ==="
