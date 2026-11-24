<?php
declare(strict_types=1);
namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;

/** Admin-only internal metadata ledger. Never triggers an external API. */
final class QueryAuditWorkbench extends Page
{
    protected static ?string $title = 'Admin · Abfrageprotokoll';
    protected static ?string $navigationLabel = 'Abfrageprotokoll';
    protected static ?string $slug = 'abfrageprotokoll';
    protected static ?int $navigationSort = 90;
    protected string $view = 'filament.pages.query-audit-workbench';

    public string $sourceFilter = '';
    public string $actorFilter = '';
    public string $identifierFilter = '';

    public static function canAccess(): bool
    {
        $allowed=trim((string)config('hw24_legacy.admin_email',''));
        $user=Auth::user();
        return $user instanceof User
            && filter_var($allowed,FILTER_VALIDATE_EMAIL)!==false
            && hash_equals(strtolower($allowed),strtolower((string)$user->email));
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(),403);
    }

    protected function getViewData(): array
    {
        // Recheck server-side on every render, including Livewire requests.
        abort_unless(static::canAccess(),403);
        $enabled=(bool)config('hw24_query_audit.enabled',false);
        try {
            $q=DB::table('query_audit_events');
            if(in_array($this->sourceFilter,['jtl','amazon','ai','scanner','legacy','ebay','shopify'],true))
                $q->where('source_system',$this->sourceFilter);
            if(preg_match('/^[1-9][0-9]{0,17}$/D',$this->actorFilter))
                $q->where('actor_ref',$this->actorFilter);
            if(preg_match('/^[A-Za-z0-9]{8,14}$/D',$this->identifierFilter))
                $q->where('identifier',strtoupper($this->identifierFilter));
            $rows=$q->orderByDesc('id')->limit(100)->get();
            $ids=$rows->pluck('actor_ref')->filter(fn($id)=>is_string($id)&&preg_match('/^[1-9][0-9]{0,17}$/D',$id))->unique()->values()->all();
            $names=User::query()->whereIn('id',$ids)->pluck('name','id');
            return ['rows'=>$rows,'names'=>$names,'auditEnabled'=>$enabled,'storageAvailable'=>true];
        } catch (\Throwable) {
            return ['rows'=>collect(),'names'=>collect(),'auditEnabled'=>$enabled,'storageAvailable'=>false];
        }
    }
}
