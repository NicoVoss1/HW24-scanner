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
            <tr wire:key="{{ $decisionKey }}-{{ sha1($field) }}">
                <td style="border:1px solid #8199b0;padding:10px;font-weight:600;vertical-align:top">{{ $field }}</td>
                <td style="border:1px solid #8199b0;padding:10px;vertical-align:top;white-space:pre-wrap;overflow-wrap:anywhere;max-width:240px">{{ $decisionCurrent[$field]??'Nicht befüllt' }}</td>
                <td style="border:1px solid #8199b0;padding:10px;vertical-align:top;overflow-wrap:anywhere;max-width:600px">
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
                </td>
                <td style="border:1px solid #8199b0;padding:10px;vertical-align:top;min-width:180px">
                    <select aria-label="{{ $field }} übernehmen?" wire:model.live="{{ $decisionModel }}.{{ $field }}" style="width:100%;min-height:40px;border:1px solid #8199b0;border-radius:7px">
                        <option value="">Nein · unverändert</option>
                        @foreach($options as $token=>$option)
                            <option value="{{ $token }}">Ja @if(count($options)>1) · {{ mb_strimwidth($option['value'],0,95,' …') }} @endif</option>
                        @endforeach
                    </select>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
