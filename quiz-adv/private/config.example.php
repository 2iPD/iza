<?php
// Copie como quiz-adv-config.php no diretório ACIMA de public_html.
// Nunca envie a configuração preenchida ao GitHub.
return [
 'APP_ORIGIN'=>'https://SEU-DOMINIO.com',
 'APP_SECRET'=>'TROQUE_POR_64_CARACTERES_ALEATORIOS',
 'DB_HOST'=>'localhost','DB_PORT'=>3306,'DB_NAME'=>'BANCO_HOSTINGER',
 'DB_USER'=>'USUARIO_HOSTINGER','DB_PASSWORD'=>'SENHA_DO_BANCO',
 'ADMIN_EMAIL'=>'seu-email@exemplo.com','ADMIN_PASSWORD_HASH'=>'HASH_GERADO_PELO_SCRIPT',
 'GITHUB_OWNER'=>'2iPD','GITHUB_REPO'=>'iza','GITHUB_BRANCH'=>'main',
 'GITHUB_TOKEN'=>'TOKEN_PRIVADO_COM_CONTENTS_READ_WRITE_APENAS_NESTE_REPOSITORIO',
 'WEBHOOK_HOSTS'=>['hooks.zapier.com','hook.us1.make.com','hook.us2.make.com','hook.eu1.make.com','hook.eu2.make.com'],
];
