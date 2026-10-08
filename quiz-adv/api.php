<?php
declare(strict_types=1);
namespace QuizAdv;
require __DIR__.'/private/bootstrap.php';require __DIR__.'/private/Store.php';require __DIR__.'/private/GitHub.php';require __DIR__.'/private/Service.php';
try {
 $method=$_SERVER['REQUEST_METHOD'];$path=$_SERVER['PATH_INFO']??'/';
 if(!in_array($method,['GET','POST','PUT'],true))throw new ApiError(405,'Método não permitido.');
 $input=[];if($method!=='GET'){csrf();$input=input();}
 $db=store();$db->rate(rateKey('requests'),240,60);
 if($path==='/auth/login'&&$method==='POST'){
  Domain::object($input,['email','password'],['email','password']);$email=strtolower(Domain::text($input['email'],191));$password=$input['password'];if(!is_string($password)||strlen($password)>1024)throw new ApiError(400,'Credenciais inválidas.');
  $db->rate(rateKey('login'),8,900);$db->rate(hash_hmac('sha256','login-email|'.$email,config()['APP_SECRET']),15,900);
  $user=$db->one('SELECT id,password_hash FROM qa_users WHERE email = ?',[$email]);$fallback='$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi';
  if(!password_verify($password,$user['password_hash']??$fallback)||!$user)throw new ApiError(401,'E-mail ou senha incorretos.');
  adminSession();session_regenerate_id(true);$_SESSION=['owner_id'=>$user['id'],'authenticated_at'=>time(),'last_seen'=>time()];session_write_close();response(['ok'=>true]);
 }
 if($path==='/auth/logout'&&$method==='POST'){Domain::object($input,[]);owner();adminSession();$_SESSION=[];session_destroy();setcookie('__Host-QuizAdvAdmin','',['expires'=>1,'path'=>'/','secure'=>!config()['LOCAL_PREVIEW'],'httponly'=>true,'samesite'=>'Strict']);response(['ok'=>true]);}
 $owner=null;if(!empty($_COOKIE['__Host-QuizAdvAdmin'])){try{$owner=owner();}catch(ApiError $e){if($e->status!==401)throw $e;}}
 $visitor=null;$publicStart=$method==='POST'&&preg_match('~^/public/quiz-[1-9][0-9]{0,8}/sessions$~D',$path);
 if($publicStart){$db->rate(rateKey('public-start'),30,3600);$visitor=visitor(true);}elseif(!empty($_COOKIE['__Host-QuizAdvVisitor']))$visitor=visitor();
 $result=(new Service($db,new GitHubPublisher(config()),config()))->handle($path,$method,$input,$owner,$visitor);
 // A completed live response has already been committed before webhook delivery.
 if($method==='POST'&&str_ends_with($path,'/lead')&&($result['mode']??'')==='live'){
  $session=$db->session($result['id'],$owner,$visitor);$snapshot=decode($session['snapshot_json']);$lead=$db->one('SELECT * FROM qa_leads WHERE session_id = ? AND owner_id = ?',[$session['id'],$session['owner_id']]);
  if($lead&&$snapshot['webhook']!==''){
   // Only server-authorized HTTPS hosts are accepted, including old snapshots.
   try{$url=Domain::url($snapshot['webhook'],config()['WEBHOOK_HOSTS']);$ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_HTTPHEADER=>['Content-Type: application/json','Idempotency-Key: '.$lead['id']],CURLOPT_POSTFIELDS=>encode(['id'=>$lead['id'],'quizId'=>$lead['quiz_id'],'name'=>$lead['name'],'email'=>$lead['email'],'phone'=>$lead['phone'],'consent'=>$lead['consent_text'],'score'=>(int)$lead['score'],'answers'=>decode($lead['answers_json'])]),CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_TIMEOUT=>5,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);curl_exec($ch);}catch(\Throwable){error_log('QUIZ ADV: falha no webhook; contato preservado no banco.');}
  }
 }
 response($result);
}catch(ApiError $e){response(['error'=>$e->getMessage()],$e->status);}catch(\Throwable $e){error_log('QUIZ ADV: falha interna de operação.');response(['error'=>'Não foi possível concluir. Confira a configuração do servidor.'],500);}
