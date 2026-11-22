<?php
declare(strict_types=1);
require __DIR__.'/../api/hw24-actor-identity.php';

function expect(bool $ok, string $why): void {
 if(!$ok){fwrite(STDERR, 'FAIL '.$why.PHP_EOL);exit(1);}
}

$cfg=['nvoss'=>['hw24-test'], 'another'=>['old-other']];
expect(hw24ReadOwnersFromAliases('nvoss',$cfg)===['nvoss','hw24-test'],
 'new login must read BOTH owner histories');
expect(hw24ReadOwnersFromAliases('hw24-test',$cfg)===['hw24-test'],
 'old login must not access nvoss data');
expect(hw24ReadOwnersFromAliases('unrelated',$cfg)===['unrelated'],
 'unmapped user must not access foreign history');
expect(hw24ReadOwnersFromAliases('another',$cfg)===['another','old-other'],
 'delegations are per authenticated identity');
expect(hw24ReadOwnersFromAliases('nvoss',['nvoss'=>['hw24-test','hw24-test']])
 ===['nvoss','hw24-test'], 'duplicate keys removed');
expect(hw24ReadOwnersFromAliases('nvoss',['nvoss'=>'hw24-test'])===['nvoss'],
 'malformed config fails closed');
expect(hw24ReadOwnersFromAliases('nvoss',['nvoss'=>['../secret']])===['nvoss'],
 'unsafe value fails closed');
expect(hw24ReadOwnersFromAliases('nvoss',['nvoss'=>['hw24-test','../secret']])
 ===['nvoss'], 'partially invalid list fails closed');
expect(hw24ReadOwnersFromAliases('nvoss',['nvoss'=>array_fill(0,6,'other')])
 ===['nvoss'], 'oversized alias list fails closed');
expect(hw24ReadOwnersFromAliases('',['nvoss'=>['hw24-test']])===[],
 'missing server auth never permits access');

$scan=file_get_contents(__DIR__.'/../api/scanliste.php');
$reviews=file_get_contents(__DIR__.'/../api/ai-reviews.php');
$paid=file_get_contents(__DIR__.'/../api/ai-review.php');
$helper=file_get_contents(__DIR__.'/../api/hw24-actor-identity.php');

foreach(['scanliste'=>$scan,'AI-Archiv'=>$reviews,'AI-Kosten'=>$paid] as $label=>$file){
 expect(is_string($file)&&str_contains($file,'hw24ReadableOwners($actor)'),
  $label.' must use server-only owner scope');
 expect(str_contains($file,"require_once __DIR__.'/hw24-actor-identity.php'"),
  $label.' must use shared private mapping');
}
expect(str_contains($scan,'created_by IN ($marks)'),
 'scan list and batch access include both actor IDs');
expect(str_contains($scan,'$s->execute($readOwners)'),
 'batch list uses bound actor values');
expect(str_contains($scan,'$s->execute([$label,$actor])'),
 'NEW batches are always owned by current Basic login');
expect(str_contains($scan,'getBatch($pdo,$batch,$readOwners,true)'),
 'edits/CSV imports must be checked against authorized owners');
expect(str_contains($scan,'VALUES (?,?,?,?)')&&str_contains($scan,'$event->execute([$id,$ean,$eventKey,$actor])'),
 'new scan events keep the current authenticated actor');
expect(str_contains($scan,'$audit->execute([$item,$expected+1,$actor'),
 'new scan audit entries record current Basic login');
expect(str_contains($reviews,'actor IN ($actorMarks)'),
 'archive summary spans authorized historic/current owners');
expect(str_contains($reviews,"FROM hw24_ai_reviews WHERE actor IN ('.implode"),
 'archive list spans authorized historic/current owners');
expect(str_contains($paid,"FROM hw24_ai_reviews WHERE actor IN ('.implode"),
 'paid AI reuse searches across both scopes');
expect(str_contains($paid,'$write->execute([$actor,'),
 'new paid AI records still assigned to CURRENT Basic login');
expect(!str_contains($helper,'$_GET')&&!str_contains($helper,'$_POST'),
 'untrusted query/body inputs cannot alter owner scope');
expect(!str_contains($helper,'UPDATE hw24_'),
 'never rewrite historical actor values');

echo "PASS: Old+new scan batches and KI reports remain visible to delegated Basic user; writes stay new owner".PHP_EOL;
