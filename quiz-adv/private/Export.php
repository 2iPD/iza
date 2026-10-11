<?php
declare(strict_types=1);
namespace QuizAdv;
final class QuizExport {
 public static function files(array $snapshot):array {
  $root=dirname(__DIR__);$files=[];
  // Explicit allowlist: never copy runtime configuration, sessions or database data.
  foreach(['index.php','api.php','login.php','.htaccess','assets/editor.js','assets/player.js','assets/style.css','private/.htaccess','private/Domain.php','private/bootstrap.php','private/Store.php','private/GitHub.php','private/Service.php','private/Export.php','private/install.php','private/password-hash.php','private/schema.sql','private/restore-export.php','private/PORTABLE.md'] as $path){$content=file_get_contents($root.'/'.$path);if($content===false)throw new ApiError(500,'Não foi possível preparar a exportação.');$files[]=['path'=>'quiz-adv/'.$path,'content'=>$content];}
  $snapshot['webhook']='';
  $files[]=['path'=>'quiz-adv/private/quiz-export.json','content'=>encode($snapshot)];
  $template=file_get_contents($root.'/private/config.example.php');if($template===false)throw new ApiError(500,'Configuração modelo indisponível.');$template=str_replace(["'GITHUB_OWNER'=>'2iPD'","'GITHUB_REPO'=>'iza'","'GITHUB_TOKEN'=>'TOKEN_PRIVADO_COM_CONTENTS_READ_WRITE_APENAS_NESTE_REPOSITORIO'"],["'GITHUB_OWNER'=>''","'GITHUB_REPO'=>''","'GITHUB_TOKEN'=>''"],$template);$template=str_replace(" 'WEBHOOK_HOSTS'=>"," 'LOCAL_PUBLICATION'=>true,\n 'WEBHOOK_HOSTS'=>",$template);
  $files[]=['path'=>'quiz-adv/private/config.example.php','content'=>$template];
  $files[]=['path'=>'quiz-1/index.html','content'=>publicShell($snapshot['name'])];
  $files[]=['path'=>'LEIA-ME.md','content'=>file_get_contents($root.'/private/PORTABLE.md')];return $files;
 }
 public static function restore(Store $db,array $config,string $email,array $hosts):string {
  $snapshot=Domain::config($config,$hosts);
  return $db->transaction(function()use($db,$snapshot,$email){$owner=$db->one('SELECT id FROM qa_users WHERE email = ?',[$email]);if(!$owner)throw new ApiError(409,'Execute a instalação do administrador primeiro.');if($db->one('SELECT id FROM qa_quizzes LIMIT 1'))throw new ApiError(409,'Use um banco novo e vazio para importar este pacote.');$id=Domain::id();$now=Domain::now();$json=encode($snapshot);$db->query('INSERT INTO qa_quizzes (id,owner_id,config_json,version,created_at,updated_at) VALUES (?,?,?,?,?,?)',[$id,$owner['id'],$json,1,$now,$now]);$db->query('INSERT INTO qa_publications (id,quiz_id,owner_id,slug,active,live_json,published_at,created_at) VALUES (?,?,?,?,?,?,?,?)',[Domain::id(),$id,$owner['id'],'quiz-1',1,$json,$now,$now]);$db->query('UPDATE qa_counters SET next_value = ? WHERE counter_name = ?',[2,'public_quiz']);return $id;});
 }
}
