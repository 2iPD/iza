<?php
declare(strict_types=1);
namespace QuizAdv;
require __DIR__.'/private/bootstrap.php';require __DIR__.'/private/Store.php';
try{$p=store()->publication(Domain::slug($_GET['slug']??''));$q=decode($p['live_json']);headers();header('Content-Type: text/html; charset=utf-8');echo publicShell($q['name']);}
catch(\Throwable $e){http_response_code($e instanceof ApiError?$e->status:503);header('Content-Type: text/html; charset=utf-8');echo '<!doctype html><html lang="pt-BR"><meta charset="utf-8"><title>Quiz indisponível</title><p>Este quiz ainda não está disponível.</p></html>';}
