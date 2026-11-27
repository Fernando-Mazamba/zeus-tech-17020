<?php
/* Termina a sessão e volta à entrada. */
require_once __DIR__ . '/_sessao.php';
zt_terminar();
header('Location: login.php', true, 302);
exit;
