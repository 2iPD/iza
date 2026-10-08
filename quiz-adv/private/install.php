<?php
declare(strict_types=1);
namespace QuizAdv;
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/bootstrap.php';require __DIR__.'/Store.php';
try {
 $c=config();$email=strtolower(trim($c['ADMIN_EMAIL']??''));$hash=$c['ADMIN_PASSWORD_HASH']??'';
 if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>191||empty(password_get_info($hash)['algo']))throw new ApiError(503,'Configure ADMIN_EMAIL e ADMIN_PASSWORD_HASH antes de instalar.');
 $db=store();foreach(explode(';',file_get_contents(__DIR__.'/schema.sql')) as $sql)if(trim($sql)!=='')$db->pdo->exec($sql);
 $db->transaction(function()use($db,$email,$hash){if(!$db->one('SELECT id FROM qa_counters WHERE counter_name = ?',['public_quiz']))$db->query('INSERT INTO qa_counters (id,counter_name,next_value) VALUES (?,?,?)',[Domain::id(),'public_quiz',1]);if(!$db->one('SELECT id FROM qa_users WHERE email = ?',[$email]))$db->query('INSERT INTO qa_users (id,email,password_hash,created_at) VALUES (?,?,?,?)',[Domain::id(),$email,$hash,Domain::now()]);});
 echo "Instalação concluída. Entre em /quiz-adv/. A instalação não altera senhas existentes.\n";
}catch(\Throwable $e){fwrite(STDERR,$e instanceof ApiError?$e->getMessage()."\n":"Falha na instalação. Confira o banco e as extensões PHP.\n");exit(1);}
