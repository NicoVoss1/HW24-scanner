{{-- Shared decision table: every choice is an existing source-bound option. --}}
<div class="hw24-decision-wrap" style="overflow-x:auto;width:100%;margin:12px 0">
    <table class="hw24-decision-table" style="border-collapse:collapse;width:100%;min-width:680px;text-align:left">
        <thead><tr>
            @foreach(['Feld','Aktuell','Recherchewert und Quelle','Übernehmen?'] as $heading)
                <th scope="col" style="background:#dceeff;color:#143d62;border:1px solid #8199b0;padding:10px">{{ $heading }}</th>
            @endforeach
        </tr></thead>
        <tbody>
        @foreach($decisionOptions as $field=>$options)
            @php
                $manualMode=isset($decisionManualModel);
                $manualText=$manualMode&&in_array($field,\App\Services\HubArticleFieldEditor::MANUAL,true);
                $manualFlag=$manualMode&&in_array($field,\App\Services\HubArticleFieldEditor::FLAGS,true);
            @endphp
            <tr wire:key="{{ $decisionKey }}-{{ sha1($field) }}">
                <td style="border:1px solid #8199b0;padding:10px;font-weight:600;vertical-align:top">{{ $decisionLabels[$field]??$field }} @if(in_array($field,$decisionRequired??[],true))<small> · Pflicht</small>@endif</td>
                <td style="border:1px solid #8199b0;padding:10px;vertical-align:top;white-space:pre-wrap;overflow-wrap:anywhere;max-width:240px">{{ $decisionCurrent[$field]??'Nicht befüllt' }}</td>
                <td style="border:1px solid #8199b0;padding:10px;vertical-align:top;overflow-wrap:anywhere;max-width:600px">
                    @if(count($options)>3)<details><summary>{{ count($options) }} vorhandene Auswahlwerte anzeigen</summary>@endif
                    @foreach($options as $token=>$option)
                        <div style="margin-bottom:8px">
                            @if(mb_strlen($option['value'])>180)
                                <details><summary>{{ mb_substr($option['value'],0,180) }} …</summary><div style="white-space:pre-wrap">{{ $option['value'] }}</div></details>
                            @else
                                <strong>{{ $option['value'] }}</strong>
                            @endif
                            <small style="display:block">{{ $option['source'] }}</small>
                        </div>
                    @endforeach
                    @if(count($options)>3)</details>@endif
                    @if(!$options)<small>{{ $decisionStates[$field]??'Keine weiteren belegten Vorschläge' }}</small>@endif
                </td>
                <td style="border:1px solid #8199b0;padding:10px;vertical-align:top;min-width:180px">
                    @if($options)
                    <select aria-label="{{ $decisionLabels[$field]??$field }} übernehmen?" wire:model.live="{{ $decisionModel }}.{{ $field }}" style="width:100%;min-height:40px;border:1px solid #8199b0;border-radius:7px">
                        <option value="">Nein · unverändert</option>
                        @foreach($options as $token=>$option)
                            <option value="{{ $token }}">Ja @if(count($options)>1) · {{ mb_strimwidth($option['value'],0,95,' …') }} @endif</option>
                        @endforeach
                    </select>
                    @endif
                    @if($manualText)
                        <label style="display:block;margin-top:8px;font-size:12px">Manuelle Änderung (statt Auswahl)
                            @if(in_array($field,['Beschreibung','Kurzbeschreibung','Meta-Description (SEO)','Anmerkung'],true))
                                <textarea rows="3" maxlength="30000" wire:model.live.debounce.500ms="{{ $decisionManualModel }}.{{ $field }}" style="width:100%;border:1px solid #8199b0;border-radius:7px"></textarea>
                            @else
                                <input type="text" maxlength="500" wire:model.live.debounce.500ms="{{ $decisionManualModel }}.{{ $field }}" placeholder="Unverändert lassen" style="width:100%;border:1px solid #8199b0;border-radius:7px">
                            @endif
                        </label>
                    @elseif($manualFlag)
                        <label style="display:block;margin-top:8px;font-size:12px">Manuelle Änderung
                            <select wire:model.live="{{ $decisionManualModel }}.{{ $field }}" style="width:100%;border:1px solid #8199b0;border-radius:7px"><option value="">Unverändert</option><option value="Y">Ja</option><option value="N">Nein</option></select>
                        </label>
                    @elseif(!$options)
                        <small>Keine bestätigte Auswahl · Quelle/Mapping prüfen</small>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
