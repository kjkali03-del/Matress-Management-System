@extends('layouts.admin')

@section('title', 'AI Conversation | Wonder Godoro Point')

@push('styles')
<style>
.ai-live{max-width:1180px;margin:0 auto;padding:22px 0 44px;color:var(--wgp-text)}
.ai-live-head,.ai-live-grid,.ai-controls,.ai-data-list,.ai-timeline{display:grid;gap:12px}
.ai-live-head{grid-template-columns:1fr auto;align-items:end;margin-bottom:16px}.ai-live-head h1{margin:0;font-size:clamp(24px,3vw,32px);letter-spacing:-.04em}.ai-live-head p{margin:5px 0 0;color:var(--wgp-muted);font-size:12px}
.ai-live-grid{grid-template-columns:minmax(0,1.4fr) minmax(270px,.8fr)}.ai-live-panel{background:#fff;border:1px solid var(--wgp-line);border-radius:14px;box-shadow:var(--wgp-shadow);padding:17px}.ai-live-panel h2{margin:0 0 12px;font-size:15px}.ai-controls{grid-template-columns:repeat(3,minmax(0,1fr));margin:0 0 14px}
.ai-control-button{min-height:37px;padding:0 11px;border:1px solid var(--wgp-gold);border-radius:9px;background:linear-gradient(135deg,var(--wgp-gold),var(--wgp-gold-2));color:#101114;font-family:inherit;font-size:10px;font-weight:800;cursor:pointer}.ai-control-button.secondary{background:#fff;border-color:var(--wgp-line);color:var(--wgp-text)}.ai-control-button.danger{background:#fff;color:#a32626;border-color:#f0c8c8}
.ai-banner{margin:0 0 13px;padding:10px 12px;border:1px solid #ead99e;border-radius:9px;background:#fffaf0;color:#68531c;font-size:11px}.ai-chat{display:flex;flex-direction:column;gap:10px;max-height:650px;overflow:auto;padding:5px}.ai-message{max-width:84%;padding:10px 12px;border:1px solid var(--wgp-line);border-radius:12px;background:#f7f8fa;white-space:pre-wrap;overflow-wrap:anywhere;font-size:12px;line-height:1.5}.ai-message.outbound{align-self:flex-end;border-color:rgba(214,166,45,.35);background:#fff8e6}.ai-message small{display:block;margin-top:5px;color:var(--wgp-muted);font-size:9px}
.ai-data-list{grid-template-columns:1fr 1fr}.ai-data{padding:10px;border:1px solid var(--wgp-line);border-radius:9px}.ai-data span{display:block;color:var(--wgp-muted);font-size:9px;font-weight:800;letter-spacing:.05em;text-transform:uppercase}.ai-data strong{display:block;margin-top:5px;font-size:12px;overflow-wrap:anywhere}.ai-context{margin:10px 0 0;padding:10px;border-radius:9px;background:#f7f8fa;color:#586477;font-size:11px;line-height:1.5;white-space:pre-wrap}
.ai-timeline{gap:0}.ai-event{position:relative;padding:0 0 14px 16px;border-left:1px solid var(--wgp-line);font-size:11px}.ai-event:last-child{border-left-color:transparent}.ai-event:before{position:absolute;left:-4px;top:3px;width:7px;height:7px;border-radius:50%;background:var(--wgp-gold);content:""}.ai-event small{display:block;margin-top:3px;color:var(--wgp-muted);font-size:9px}
.ai-escalation{margin-top:10px;padding:10px;border:1px solid #ead99e;border-radius:9px;background:#fffaf0;font-size:11px;line-height:1.5}.ai-escalation strong{display:block;margin-bottom:4px}
.ai-return{margin-bottom:12px}.ai-flash{margin-bottom:12px;padding:10px;border-radius:8px;background:#edf9f2;color:#176b43;font-size:12px}
@media(max-width:850px){.ai-live-grid{grid-template-columns:1fr}.ai-live-head{grid-template-columns:1fr}.ai-controls{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:520px){.ai-controls{grid-template-columns:1fr 1fr}.ai-message{max-width:94%}.ai-data-list{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="ai-live">
    @if(session('status'))<div class="ai-flash" role="status">{{ session('status') }}</div>@endif
    <header class="ai-live-head">
        <div><p class="ai-kicker">AI Agent / Conversation Monitor</p><h1>{{ $conversation->customer->name ?: 'WhatsApp customer' }}</h1><p>{{ $conversation->customer->phone }} · Conversation #{{ $conversation->id }}</p></div>
        <a class="ai-link" href="{{ route('admin.ai.index') }}">← Back to AI Agent</a>
    </header>

    <section class="ai-live-panel ai-return">
        <h2>Conversation controls · {{ strtoupper(str_replace('_', ' ', $state->status)) }}</h2>
        <div class="ai-controls">
            @if(!in_array($state->status, ['human', 'closed'], true))
                <form method="POST" action="{{ route('admin.ai.conversations.take-over', $conversation) }}">@csrf<button class="ai-control-button" type="submit">TAKE OVER</button></form>
            @endif
            @if(in_array($state->status, ['human', 'paused', 'escalated'], true))
                <form method="POST" action="{{ route('admin.ai.conversations.return-to-ai', $conversation) }}">@csrf<button class="ai-control-button" type="submit">RETURN TO AI</button></form>
            @endif
            @if($state->status === 'active')
                <form method="POST" action="{{ route('admin.ai.conversations.pause', $conversation) }}">@csrf<button class="ai-control-button secondary" type="submit">PAUSE AI</button></form>
            @elseif($state->status === 'paused')
                <form method="POST" action="{{ route('admin.ai.conversations.resume', $conversation) }}">@csrf<button class="ai-control-button" type="submit">RESUME AI</button></form>
            @endif
            @if(!in_array($state->status, ['escalated', 'closed'], true))
                <form method="POST" action="{{ route('admin.ai.conversations.escalate', $conversation) }}">@csrf<button class="ai-control-button secondary" type="submit">ESCALATE</button></form>
            @endif
            @if($state->status !== 'closed')
                <form method="POST" action="{{ route('admin.ai.conversations.close', $conversation) }}">@csrf<button class="ai-control-button danger" type="submit">CLOSE</button></form>
            @endif
        </div>
        @if($state->status==='active' && !$deploymentEnabled)
            <div class="ai-banner">The deployment-level AI switch is off. Returning this conversation to AI will not send automated replies until the server is configured.</div>
        @endif
    </section>

    <div class="ai-live-grid">
        <section class="ai-live-panel">
            <h2>Conversation</h2>
            <div class="ai-chat" aria-label="Conversation messages">
                @forelse($messages as $message)
                    <article class="ai-message {{ $message->direction==='outbound'?'outbound':'' }}">
                        {{ $message->body ?: $message->media_caption ?: '[' . ucfirst($message->message_type) . ' message]' }}
                        <small>{{ $message->direction==='inbound'?'Customer':'WGP' }} · {{ $message->created_at?->format('M j, Y H:i') }} · {{ $message->status }}</small>
                    </article>
                @empty
                    <p>No messages in this conversation.</p>
                @endforelse
            </div>
        </section>

        <div class="ai-data-list" style="align-content:start">
            <section class="ai-live-panel" style="grid-column:1/-1">
                <h2>Customer &amp; AI state</h2>
                <div class="ai-data-list">
                    <div class="ai-data"><span>Customer</span><strong>{{ $conversation->customer->name ?: 'Not provided' }}</strong></div>
                    <div class="ai-data"><span>Phone</span><strong>{{ $conversation->customer->phone }}</strong></div>
                    <div class="ai-data"><span>Intent</span><strong>{{ $state->intent ?: 'Not detected' }}</strong></div>
                    <div class="ai-data"><span>Stage</span><strong>{{ str_replace('_',' ', $state->stage) }}</strong></div>
                    <div class="ai-data"><span>AI status</span><strong>{{ strtoupper(str_replace('_',' ', $state->status)) }}</strong></div>
                    <div class="ai-data"><span>Latest order</span><strong>{{ $latestOrder?->order_number ?: 'None recorded' }}</strong></div>
                </div>
                @if($state->summary)<div class="ai-context"><strong>AI summary</strong><br>{{ $state->summary }}</div>@endif
                @if($state->next_action)<div class="ai-context"><strong>Next AI action</strong><br>{{ $state->next_action }}</div>@endif
                @if(data_get($state->context, 'pending_order'))
                    <div class="ai-context"><strong>Pending, unconfirmed order</strong><br>{{ json_encode(data_get($state->context, 'pending_order'), JSON_UNESCAPED_UNICODE) }}</div>
                @endif
                <h2 style="margin-top:14px">Products discussed</h2>
                @forelse($discussedProducts as $product)
                    <div class="ai-data"><span>{{ $product->size ?: 'Size not set' }}</span><strong>{{ $product->name }} · TSh {{ number_format((float)$product->price, 0) }}</strong></div>
                @empty
                    <p>No product has been confirmed as discussed.</p>
                @endforelse
            </section>

            @if($escalations->isNotEmpty())
                <section class="ai-live-panel" style="grid-column:1/-1">
                    <h2>Escalation</h2>
                    @foreach($escalations as $escalation)
                        <div class="ai-escalation"><strong>{{ strtoupper($escalation->status) }} · {{ $escalation->reason }}</strong>{{ $escalation->summary }}@if($escalation->recommended_action)<br><small>Recommended: {{ $escalation->recommended_action }}</small>@endif</div>
                    @endforeach
                </section>
            @endif

            <section class="ai-live-panel" style="grid-column:1/-1">
                <h2>AI activity timeline</h2>
                <div class="ai-timeline">
                    @forelse($actions as $action)
                        <div class="ai-event"><strong>{{ str_replace('_',' ', $action->tool) }}</strong> · {{ $action->summary }}<small>{{ $action->actor }} · {{ strtoupper($action->status) }} · {{ $action->created_at?->format('M j, H:i:s') }}</small></div>
                    @empty
                        <p>No AI or administrator actions have been recorded.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
