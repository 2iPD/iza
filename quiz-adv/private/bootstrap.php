<?php
declare(strict_types=1);
namespace QuizAdv;
require_once __DIR__.'/Domain.php';
function config():array {
 static $c=null;if($c!==null)return $c;
 $filename=dirname(__DIR__,3).'/quiz-adv-config.php';if(!is_file($filename))$filename=__DIR__.'/config.local.php';
 if(is_file($filename)){$c=require $filename;if(!is_array($c))throw new ApiError(503,'Configuração do servidor inválida.');}
 else {$c=[];foreach(['APP_ORIGIN','DB_HOST','DB_PORT','DB_NAME','DB_USER','DB_PASSWORD','APP_SECRET','GITHUB_TOKEN','ADMIN_EMAIL','ADMIN_PASSWORD_HASH'] as $key)$c[$key]=getenv($key)?:'';}
 $c+=['GITHUB_OWNER'=>'2iPD','GITHUB_REPO'=>'iza','GITHUB_BRANCH'=>'main','WEBHOOK_HOSTS'=>['hooks.zapier.com','hook.us1.make.com','hook.us2.make.com','hook.eu1.make.com','hook.eu2.make.com']];
 $origin=$c['APP_ORIGIN']??'';$parsed=parse_url($origin);
 $local=PHP_SAPI==='cli-server'&&in_array($parsed['host']??'',['127.0.0.1','localhost'],true)&&($parsed['scheme']??'')==='http';
 if(!$parsed||(!$local&&($parsed['scheme']??'')!=='https')||isset($parsed['user'])||isset($parsed['pass'])||!empty($parsed['path'])||isset($parsed['query'])||isset($parsed['fragment'])||strlen($c['APP_SECRET']??'')<32)throw new ApiError(503,'Configure o domínio, o banco de dados e a chave do aplicativo na Hostinger.');
 $c['LOCAL_PREVIEW']=$local;return $c;
}
function headers():void {header('Cache-Control: private, no-store');header('X-Content-Type-Options: nosniff');header('Referrer-Policy: no-referrer');header('Permissions-Policy: camera=(), microphone=(), geolocation=()');header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; object-src 'none'; base-uri 'none'; form-action 'self'; frame-ancestors 'self'");header('Strict-Transport-Security: max-age=31536000');}
function encode(mixed $v):string {return json_encode($v,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}
function decode(string $v):array {$out=json_decode($v,true,64,JSON_THROW_ON_ERROR);if(!is_array($out))throw new ApiError(500,'Dados persistidos inválidos.');return $out;}
function response(mixed $data,int $status=200):never {headers();http_response_code($status);header('Content-Type: application/json; charset=utf-8');echo encode($data);exit;}
function input():array {if(strtolower(trim(explode(';',$_SERVER['CONTENT_TYPE']??'')[0]))!=='application/json')throw new ApiError(415,'Envie JSON.');$raw=file_get_contents('php://input',false,null,0,131073);if(strlen($raw)>131072)throw new ApiError(413,'Conteúdo muito grande.');try{return decode($raw);}catch(\JsonException){throw new ApiError(400,'JSON inválido.');}}
function csrf():void {if(($_SERVER['HTTP_ORIGIN']??'')!==config()['APP_ORIGIN']||($_SERVER['HTTP_X_QUIZ_ACTION']??'')!=='1'||in_array($_SERVER['HTTP_SEC_FETCH_SITE']??'',['cross-site','same-site'],true))throw new ApiError(403,'Origem não autorizada.');}
function adminSession():void {if(session_status()===PHP_SESSION_ACTIVE)return;ini_set('session.use_strict_mode','1');ini_set('session.use_only_cookies','1');ini_set('session.gc_maxlifetime','28800');session_name('__Host-QuizAdvAdmin');session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!config()['LOCAL_PREVIEW'],'httponly'=>true,'samesite'=>'Strict']);session_start();}
function owner():string {if(empty($_COOKIE['__Host-QuizAdvAdmin']))throw new ApiError(401,'Entre no editor para continuar.');adminSession();$id=$_SESSION['owner_id']??'';$now=time();if(!$id||$now-($_SESSION['authenticated_at']??0)>28800||$now-($_SESSION['last_seen']??0)>7200){$_SESSION=[];session_destroy();throw new ApiError(401,'Sua sessão expirou. Entre novamente.');}$_SESSION['last_seen']=$now;$id=Domain::uuid($id);session_write_close();return $id;}
function visitor(bool $create=false):string {$secret=$_COOKIE['__Host-QuizAdvVisitor']??'';if(!is_string($secret)||!preg_match('/^[a-f0-9]{64}$/D',$secret)){if(!$create)throw new ApiError(404,'Sessão não encontrada.');$secret=bin2hex(random_bytes(32));setcookie('__Host-QuizAdvVisitor',$secret,['expires'=>time()+86400,'path'=>'/','secure'=>!config()['LOCAL_PREVIEW'],'httponly'=>true,'samesite'=>'Lax']);$_COOKIE['__Host-QuizAdvVisitor']=$secret;}return hash('sha256',$secret);}
function rateKey(string $scope):string {return hash_hmac('sha256',$scope.'|'.($_SERVER['REMOTE_ADDR']??''),config()['APP_SECRET']);}
function publicShell(string $title):string {$title=htmlspecialchars($title,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');return '<!doctype html><!-- QUIZ ADV managed public page --><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.$title.'</title><meta name="robots" content="noindex,nofollow"><link rel="stylesheet" href="/quiz-adv/assets/style.css"></head><body><main id="player" class="secure-player"><p>Carregando quiz…</p></main><script src="/quiz-adv/assets/player.js"></script></body></html>';}

