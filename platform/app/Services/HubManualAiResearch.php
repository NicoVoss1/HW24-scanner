<?php
declare(strict_types=1);
namespace App\Services;

/** Manual exception action only, not a scheduler or automatic field approval. */
final class HubManualAiResearch
{
    public static function budgetBlock(array $response,bool $web): ?string
    {
        $b=$response['data']??[];
        if(($response['ok']??false)!==true||($b['enabled']??false)!==true||($b['archive_ready']??false)!==true||
            !is_numeric($b['usd_month']??null)||!is_numeric($b['usd_budget']??null)||
            !is_numeric($b['daily_calls']??null)||!is_numeric($b['daily_limit']??null)||
            (float)$b['usd_month']<0||(float)$b['usd_budget']<=0||
            (int)$b['daily_calls']<0||(int)$b['daily_calls']>=(int)$b['daily_limit'])
            return 'KI-Budget oder Archivfreigabe nicht bestätigt. Keine neue Abfrage.';
        if($web&&(!in_array(3,$b['review_versions']??[],true)||($b['web_research_enabled']??false)!==true||
            !is_numeric($b['web_reserve_usd']??null)||(float)$b['web_reserve_usd']<0.04))
            return 'OpenAI-Webrecherche noch nicht serverseitig freigegeben. Keine neue Abfrage.';
        $reserve=$web?(float)$b['web_reserve_usd']:0.04;
        return (float)$b['usd_month']+$reserve>=(float)$b['usd_budget']
            ?'Nicht genügend bestätigtes Restbudget. Keine neue Abfrage.':null;
    }

    /** Only URLs actually returned by the search tool and archived by the server. */
    public static function sources(array $review): array
    {
        $out=[];
        foreach(is_array($review['webSources']??null)?array_slice($review['webSources'],0,12):[] as $r){
            if(!is_array($r)||!is_string($r['url']??null)||strlen($r['url'])>2000)continue;
            $p=parse_url($r['url']);
            if(!is_array($p)||($p['scheme']??null)!=='https'||empty($p['host'])||isset($p['user'])||isset($p['pass']))continue;
            $out[$r['url']]=['url'=>$r['url'],'title'=>mb_substr((string)($r['title']??$p['host']),0,200)];
        }
        return array_values($out);
    }
}
