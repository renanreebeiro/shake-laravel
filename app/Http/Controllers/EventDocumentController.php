<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use App\Models\EventMoney;
use App\Models\ShakeEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EventDocumentController
{
    private const TYPES = ['contrato' => 'contrato-evento', 'recibo' => 'recibo-evento'];

    public function create(Request $request, ShakeEvent $event, string $kind)
    {
        $template = $this->template($kind, $event);
        $data = $event->document_data ?? [];
        $first = $event->payments()->orderBy('received_on')->orderBy('id')->first();
        $payment = $kind === 'recibo' ? $event->payments()->findOrFail($request->integer('payment')) : null;
        if ($payment?->document) {
            return redirect()->route('events.documents.show', [$event->id, $payment->document->id]);
        }
        $total = $event->document_total;
        if ($kind === 'contrato' && !$first) {
            return redirect(route('events.show', $event).'#recebimentos')
                ->withErrors(['payment' => 'Registre o primeiro recebimento antes de gerar o contrato vinculado.']);
        }

        return response()->view('issue', [
            'template' => $template, 'fields' => $this->fields($template), 'event' => $event,
            'kind' => $kind, 'payment' => $payment, 'requestKey' => (string) Str::uuid(),
            'defaults' => array_replace($data, [
                'evento' => $kind === 'recibo' ? $event->title : ($data['evento'] ?? $event->title),
                'recebi' => $data['nome'] ?? '', 'cpf_pag' => $data['cpf'] ?? '',
                'quantidade' => $event->quantity, 'data_evento' => $event->event_date->toDateString(),
                'hora' => $data['hora'] ?? ($event->all_day ? '' : substr($event->starts_at, 0, 5)),
                'local' => $event->address, 'emissao' => now('America/Belem')->toDateString(),
                'termino' => ($event->ends_on ?? $event->event_date)->toDateString(),
                'total' => $total === null ? '' : number_format($total / 100, 2, ',', '.'),
                'entrada' => $first ? number_format($first->amount / 100, 2, ',', '.') : '',
                'data_entrada' => $first?->received_on->toDateString(), 'cidade' => 'Belém/PA',
                'forma_entrada' => $first?->method, 'prazo_quitacao' => $event->paymentDue()->toDateString(),
                'valor' => $payment ? number_format($payment->amount / 100, 2, ',', '.') : '',
                'forma_pag' => $payment?->method, 'data_recebimento' => $payment?->received_on->toDateString(),
            ]),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, ShakeEvent $event, string $kind)
    {
        $template = $this->template($kind, $event);
        $fields = $this->fields($template);
        $rules = ['request_key' => 'required|uuid', 'values' => 'present|array'];
        $fixed = ['entrega_objeto' => 'acompanhada de personagem/animador temático',
            'entrega_obrigacao' => 'e acompanhados de personagem/animador temático devidamente uniformizado'];
        foreach ($fields as $field) {
            if (($kind === 'contrato' && (in_array($field, ['forma_entrada', 'prazo_quitacao'], true) || isset($fixed[$field])))
                || ($kind === 'recibo' && in_array($field, ['valor', 'forma_pag', 'data_recebimento'], true))) {
                continue;
            }
            $rules['values.'.$field] = match ($field) {
                'tipo_doc' => ['required', Rule::in(['CPF', 'CNPJ'])],
                'quantidade' => 'required|integer|min:1|max:1000000',
                default => 'required|string|max:500',
            };
        }
        if ($kind === 'recibo') {
            $rules['payment_id'] = 'required|integer';
            $rules['values.num'] = 'required|string|max:80';
        }
        $data = $request->validate($rules);
        $document = DB::transaction(function () use ($event, $kind, $data, $template, $fixed, $fields, $request) {
            $event = ShakeEvent::lockForUpdate()->findOrFail($event->id);
            $existing = $event->documents()->where('request_key', $data['request_key'])->first();
            if ($existing) {
                abort_unless($existing->kind === $kind, 409);
                return $existing;
            }

            $values = array_intersect_key($data['values'], array_flip($fields));
            $total = $event->document_total;
            if (!$total) {
                throw ValidationException::withMessages(['total' => 'Informe o valor total na ficha do evento antes de gerar o documento.']);
            }
            $receipt = [];
            if ($kind === 'recibo') {
                $payment = $event->payments()->findOrFail($data['payment_id']);
                if ($payment->document) {
                    return $payment->document;
                }
                if ($event->documents()->where('receipt_number', $values['num'])->exists()) {
                    throw ValidationException::withMessages(['num' => 'Este número de recibo já foi usado neste evento.']);
                }
                $values['valor'] = number_format($payment->amount / 100, 2, ',', '.');
                $values['forma_pag'] = $payment->method;
                $values['data_recebimento'] = $payment->received_on->toDateString();
                $receipt = ['event_payment_id' => $payment->id, 'receipt_number' => $values['num']];
            } else {
                $first = $event->payments()->orderBy('received_on')->orderBy('id')->first();
                $this->requireContractPayment($first);
                $values['forma_entrada'] = $first->method;
                $values['prazo_quitacao'] = $event->paymentDue()->toDateString();
                foreach (['total' => $total, 'entrada' => $first->amount] as $field => $amount) {
                    if (in_array($field, $fields, true) && (!preg_match('/^'.EventMoney::PATTERN.'$/D', $values[$field] ?? '')
                        || EventMoney::cents($values[$field]) !== $amount)) {
                        throw ValidationException::withMessages([$field => 'Os valores precisam corresponder à ficha e ao primeiro recebimento registrado.']);
                    }
                }
                if (isset($values['data_entrada']) && $values['data_entrada'] !== $first->received_on->toDateString()) {
                    throw ValidationException::withMessages(['data_entrada' => 'Use a data do primeiro recebimento registrado.']);
                }
                $values['total'] = number_format($total / 100, 2, ',', '.');
                $values = $fixed + $values;
            }

            $body = preg_replace_callback('/(R\$[^\S\r\n]*)?\{\{\s*([a-z][a-z0-9_]*)\s*\}\}/', function ($match) use ($values) {
                $field = $match[2];
                $value = trim((string) ($values[$field] ?? ''));
                if (in_array($field, ['valor', 'total', 'entrada'], true)
                    || (str_starts_with($field, 'valor_') && !str_ends_with($field, '_extenso'))) {
                    if (!preg_match('/^'.EventMoney::PATTERN.'$/D', $value)) {
                        throw ValidationException::withMessages([$field => 'Informe um valor em reais válido.']);
                    }
                    return EventMoney::format(EventMoney::cents($value));
                }
                if (in_array($field, ['data_evento', 'data_entrada', 'emissao', 'termino', 'prazo_quitacao', 'data_recebimento'], true)) {
                    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $value, $date)
                        || !checkdate((int) $date[2], (int) $date[3], (int) $date[1])) {
                        throw ValidationException::withMessages([$field => 'Informe uma data válida.']);
                    }
                    $value = $date[3].'/'.$date[2].'/'.$date[1];
                }
                return ($match[1] ?? '').$value;
            }, $template->body);

            $document = $event->documents()->create($receipt + [
                'request_key' => $data['request_key'], 'kind' => $kind, 'title' => $template->title,
                'body' => $body, 'generated_at' => now(),
            ]);
            $event->activities()->create([
                'action' => $kind === 'recibo' ? 'receipt_issued' : 'contract_issued', 'source' => 'central',
                'actor_email' => $request->user()->email, 'actor_name' => $request->user()->name, 'occurred_at' => now(),
            ]);

            return $document;
        });

        return response()->json(['url' => route('events.documents.show', [$event->id, $document->id])], 201);
    }

    public function show(int $eventId, int $documentId)
    {
        $event = ShakeEvent::withTrashed()->findOrFail($eventId);
        $document = $event->documents()->findOrFail($documentId);

        return response()->view('events.document', compact('event', 'document'))->header('Cache-Control', 'private, no-store');
    }

    private function template(string $kind, ShakeEvent $event): DocumentTemplate
    {
        abort_unless(isset(self::TYPES[$kind]), 404);
        $template = DocumentTemplate::where('slug', self::TYPES[$kind])->where('active', true)->firstOrFail();
        if ($kind === 'contrato') {
            $template->body = str_replace(
                ['via transferência PIX', 'em até 10 (dez) dias antes da data do evento'],
                ['via {{forma_entrada}}', 'até {{prazo_quitacao}}'],
                $template->body
            );
            if ($event->document_total > 0 && $event->payments()->sum('amount') >= $event->document_total) {
                $template->body = str_replace('O saldo remanescente deverá ser quitado até {{prazo_quitacao}}.', 'O valor contratado já está quitado.', $template->body);
            }
        } elseif (!str_contains($template->body, '{{data_recebimento}}')) {
            $template->body .= "\nData do recebimento: {{data_recebimento}}.";
        }

        return $template;
    }

    private function requireContractPayment(?\App\Models\EventPayment $first): void
    {
        if (!$first) {
            throw ValidationException::withMessages(['payment' => 'Registre o primeiro recebimento antes de gerar o contrato vinculado.']);
        }
    }

    private function fields(DocumentTemplate $template): array
    {
        preg_match_all('/\{\{\s*([a-z][a-z0-9_]*)\s*\}\}/', $template->body, $matches);
        return array_unique($matches[1]);
    }
}
