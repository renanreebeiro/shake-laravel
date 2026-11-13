@php
    $coverage = $event->coverage;
    $type = old('coverage.type', $coverage?->type ?? 'pending');
    $contacts = $coverage?->contacts ?? [];
    $same = $type === 'both' && !empty($contacts['photo']['email']) && $contacts['photo'] === ($contacts['video'] ?? null);
@endphp
<fieldset class="coverage-section">
    <legend>Cobertura audiovisual</legend>
    <label for="coverage-type">Que tipo de cobertura o evento terá?</label>
    <select id="coverage-type" name="coverage[type]" required>
        @foreach(\App\Models\EventCoverage::TYPES as $value => $label)
            <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
        @endforeach
    </select>

    <div id="coverage-details">
        <div class="events-fields events-three">
            <div>
                <label for="coverage-start">Início da cobertura</label>
                <input type="time" id="coverage-start" name="coverage[starts_at]" value="{{ old('coverage.starts_at', substr($coverage?->starts_at ?? '', 0, 5)) }}">
            </div>
            <div>
                <label for="coverage-end">Término da cobertura</label>
                <input type="time" id="coverage-end" name="coverage[ends_at]" value="{{ old('coverage.ends_at', substr($coverage?->ends_at ?? '', 0, 5)) }}">
            </div>
            <div>
                <label for="coverage-deadline">Prazo para envio dos arquivos</label>
                <input type="date" id="coverage-deadline" name="coverage[deadline]" value="{{ old('coverage.deadline', $coverage?->deadline?->toDateString()) }}">
            </div>
        </div>

        <label id="coverage-same-label"><input type="checkbox" id="coverage-same" name="coverage[same]" value="1" @checked(old('coverage.same', old('coverage.type') === null && $same))> A mesma pessoa faz foto e vídeo</label>
        @foreach(\App\Models\EventCoverage::ROLES as $role => $label)
            <fieldset data-coverage-role="{{ $role }}" class="coverage-section">
                <legend>{{ $label }}</legend>
                <div class="events-fields">
                    <div>
                        <label for="coverage-{{ $role }}-name">Nome</label>
                        <input id="coverage-{{ $role }}-name" name="coverage[{{ $role }}][name]" maxlength="120" value="{{ old('coverage.'.$role.'.name', $contacts[$role]['name'] ?? '') }}">
                    </div>
                    <div>
                        <label for="coverage-{{ $role }}-email">E-mail da conta Google</label>
                        <input type="email" id="coverage-{{ $role }}-email" name="coverage[{{ $role }}][email]" maxlength="254" value="{{ old('coverage.'.$role.'.email', $contacts[$role]['email'] ?? '') }}">
                    </div>
                    <div>
                        <label for="coverage-{{ $role }}-whatsapp">WhatsApp com DDD</label>
                        <input type="tel" id="coverage-{{ $role }}-whatsapp" name="coverage[{{ $role }}][whatsapp]" placeholder="(91) 99999-9999" maxlength="32" value="{{ old('coverage.'.$role.'.whatsapp', $contacts[$role]['whatsapp'] ?? '') }}">
                    </div>
                    <div>
                        <label for="coverage-{{ $role }}-channel">Canal para receber o link</label>
                        <select id="coverage-{{ $role }}-channel" name="coverage[{{ $role }}][channel]">
                            <option value="">Selecione</option>
                            @foreach(\App\Models\EventCoverage::CHANNELS as $channel => $channelLabel)
                                <option value="{{ $channel }}" @selected(old('coverage.'.$role.'.channel', $contacts[$role]['channel'] ?? '') === $channel)>{{ $channelLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </fieldset>
        @endforeach
    </div>
</fieldset>
<script>
    const coverageType = document.getElementById('coverage-type');
    const coverageSame = document.getElementById('coverage-same');
    function updateCoverageFields() {
        const type = coverageType.value;
        const both = type === 'both';
        document.getElementById('coverage-details').hidden = !['photo', 'video', 'both'].includes(type);
        document.getElementById('coverage-same-label').hidden = !both;
        coverageSame.disabled = !both;
        document.querySelectorAll('[data-coverage-role]').forEach(group => {
            const role = group.dataset.coverageRole;
            const visible = (both || type === role) && !(both && coverageSame.checked && role === 'video');
            group.hidden = group.disabled = !visible;
        });
    }
    coverageType.addEventListener('change', updateCoverageFields);
    coverageSame.addEventListener('change', updateCoverageFields);
    updateCoverageFields();
</script>
