<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
fwrite(STDERR,"Digite uma senha exclusiva de pelo menos 12 caracteres (entrada padrão):\n");
$password=rtrim(fgets(STDIN),"\r\n");if(strlen($password)<12){fwrite(STDERR,"Senha muito curta.\n");exit(1);}echo password_hash($password,PASSWORD_DEFAULT)."\n";
