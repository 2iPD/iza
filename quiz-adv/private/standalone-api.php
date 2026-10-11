<?php
declare(strict_types=1);
namespace QuizAdv;
require __DIR__.'/private/Domain.php';
header('Cache-Control: private, no-store');header('X-Content-Type-Options: nosniff');header('Referrer-Policy: no-referrer');
function reply(array $data,int $status=200):never {http_response_code($status);header('Content-Type: application/json; charset=utf-8');echo json_encode($data,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
try {
 $raw=file_get_contents(__DIR__.'/private/quiz.json');$quiz=Domain::config(json_decode($raw,true,64,JSON_THROW_ON_ERROR));
 $host=$_SERVER['HTTP_HOST']??'';if(!preg_match('/^[a-z0-9.-]+(?::[0-9]{1,5})?$/iD',$host))throw new ApiError(400,'Domínio inválido.');
 $local=PHP_SAPI==='cli-server'&&in_array(explode(':',$host)[0],['localhost','127.0.0.1'],true);$secure=($_SERVER['HTTPS']??'')==='on'||($_SERVER['SERVER_PORT']??'')==='443';if(!$secure&&!$local)throw new ApiError(503,'Use HTTPS para abrir o quiz.');
 $method=$_SERVER['REQUEST_METHOD'];if(!in_array($method,['GET','POST'],true))throw new ApiError(405,'Método inválido.');$input=[];
 if($method==='POST'){if(($_SERVER['HTTP_ORIGIN']??'')!==($secure?'https://':'http://').$host||($_SERVER['HTTP_X_QUIZ_ACTION']??'')!=='1'||in_array($_SERVER['HTTP_SEC_FETCH_SITE']??'',['cross-site','same-site'],true))throw new ApiError(403,'Origem não autorizada.');if(strtolower(trim(explode(';',$_SERVER['CONTENT_TYPE']??'')[0]))!=='application/json')throw new ApiError(415,'Envie JSON.');$body=file_get_contents('php://input',false,null,0,8193);if(strlen($body)>8192)throw new ApiError(413,'Resposta muito grande.');$input=json_decode($body,true,32,JSON_THROW_ON_ERROR);if(!is_array($input))throw new ApiError(400,'Dados inválidos.');}
 ini_set('session.use_strict_mode','1');ini_set('session.use_only_cookies','1');session_name(($secure?'__Host-':'').'QuizExport'.substr(hash('sha256',$raw.__DIR__),0,16));session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$secure,'httponly'=>true,'samesite'=>'Lax']);session_start();
 $now=time();$rate=$_SESSION['rate']??['start'=>$now,'hits'=>0];if($now-$rate['start']>=60)$rate=['start'=>$now,'hits'=>0];$rate['hits']++;$_SESSION['rate']=$rate;if($rate['hits']>120)throw new ApiError(429,'Aguarde um momento para continuar.');
 $path=$_GET['path']??'';
 if($path==='/public/quiz-1/sessions'&&$method==='POST'){Domain::object($input,[]);$id=Domain::id();$_SESSION['quiz']=$quiz;$_SESSION['run']=['id'=>$id,'version'=>0,'state'=>Domain::initial('live'),'expires'=>time()+86400];reply(Domain::view($id,0,$quiz,$_SESSION['run']['state']));}
 if(!is_string($path)||!preg_match('~^/sessions/([a-f0-9-]+)(?:/(actions|lead))?$~D',$path,$matches))throw new ApiError(404,'Recurso não encontrado.');$id=Domain::uuid($matches[1]);$run=$_SESSION['run']??null;if(!$run||$run['id']!==$id||$run['expires']<time())throw new ApiError(404,'Sessão não encontrada.');$quiz=$_SESSION['quiz'];$tail=$matches[2]??'';
 if($method==='GET'&&$tail==='')reply(Domain::view($id,$run['version'],$quiz,$run['state']));
 if($method!=='POST'||!in_array($tail,['actions','lead'],true))throw new ApiError(405,'Ação inválida.');Domain::integer($input['version']??null,0,100000);if($input['version']!==$run['version'])throw new ApiError(409,'Esta resposta já foi processada.');
 if($tail==='actions')$state=Domain::action($quiz,$run['state'],$input);
 else {$contact=Domain::contact($input);$state=Domain::complete($quiz,$run['state']);$score=array_sum(array_column(Domain::answers($quiz,$state),'points'));$record=['id'=>Domain::id(),'sessionId'=>$id,'name'=>$contact['name'],'email'=>$contact['email'],'phone'=>$contact['phone'],'score'=>$score,'answers'=>Domain::answers($quiz,$state),'consent'=>$quiz['consent'],'createdAt'=>Domain::now()];$line=json_encode($record,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)."\n";if(file_put_contents(__DIR__.'/private/contatos.jsonl',$line,FILE_APPEND|LOCK_EX)!==strlen($line))throw new ApiError(500,'Não foi possível salvar o contato.');}
 $_SESSION['run']=['id'=>$id,'version'=>$run['version']+1,'state'=>$state,'expires'=>$run['expires']];reply(Domain::view($id,$run['version']+1,$quiz,$state));
}catch(ApiError $e){reply(['error'=>$e->getMessage()],$e->status);}catch(\JsonException){reply(['error'=>'Dados JSON inválidos.'],400);}catch(\Throwable){reply(['error'=>'Não foi possível carregar o quiz. Confira PHP e as permissões da pasta privada.'],503);}
