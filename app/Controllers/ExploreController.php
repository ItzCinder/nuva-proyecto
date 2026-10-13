<?php
namespace App\Controllers;
use app\Models\Workspace;
use app\Models\Tournament;

class ExploreController
{
    public function handle(string $page, callable $render): void
    {
        $data=['exploreError'=>'','publicEnvironment'=>null,'publicEnvironments'=>[],'tournamentItems'=>[],
            'collectionType'=>'competitions','collectionPage'=>'explore','collectionAll'=>true];
        try {
            $spaces=new Workspace(); $tournaments=new Tournament();
            $data['tournamentItems']=$tournaments->publicList();
            if ($page==='public-environment') {
                $raw=$_GET['id'] ?? ''; $id=is_string($raw)?(int)$raw:0;
                $data['publicEnvironment']=$spaces->publicInfo($id);
                if (!$data['publicEnvironment']) {http_response_code(404);$data['exploreError']='No encontramos ese entorno público.';}
                $data['tournamentItems']=array_values(array_filter($data['tournamentItems'],static fn($t)=>(int)$t['workspace_id']===$id));
            } else {
                $data['publicEnvironments']=$spaces->publicList();
            }
        } catch (\Throwable $e) {
            error_log('Explorar: '.$e->getMessage());http_response_code(503);
            $data['exploreError']='No se pudo cargar la información pública. Inténtalo de nuevo.';
        }
        $render('public/explore',$data);
    }
}
