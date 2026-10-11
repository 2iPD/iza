<?php
declare(strict_types=1);
namespace QuizAdv;
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/bootstrap.php';require __DIR__.'/Store.php';require __DIR__.'/Export.php';
try{$c=config();$raw=file_get_contents(__DIR__.'/quiz-export.json');if($raw===false)throw new ApiError(400,'Arquivo do quiz não encontrado.');QuizExport::restore(store(),decode($raw),strtolower(trim($c['ADMIN_EMAIL']??'')),$c['WEBHOOK_HOSTS']);echo "Quiz importado. Abra ".$c['APP_ORIGIN']."/quiz-1/ . Edite em /quiz-adv/.\n";}catch(\Throwable $e){fwrite(STDERR,$e instanceof ApiError?$e->getMessage()."\n":"Falha na importação. Confira a instalação e o banco.\n");exit(1);}
