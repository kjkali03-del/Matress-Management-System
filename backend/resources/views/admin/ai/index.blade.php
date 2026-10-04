@extends('layouts.admin')

@section('title', 'AI Agent | Wonder Godoro Point')

@push('styles')
<style>
.ai-center{max-width:1180px;margin:0 auto;padding:22px 0 44px;color:var(--wgp-text)}
.ai-head,.ai-section-head{display:flex;justify-content:space-between;align-items:flex-end;gap:16px}
.ai-head{margin-bottom:20px}.ai-head h1{margin:0;font-size:clamp(25px,3vw,32px);letter-spacing:-.04em}.ai-head p,.ai-section-head p{margin:6px 0 0;color:var(--wgp-muted);font-size:13px}
.ai-kicker{margin:0 0 6px;color:var(--wgp-gold-dark);font-size:10px;font-weight:850;letter-spacing:.14em;text-transform:uppercase}
.ai-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:20px}
.ai-card,.ai-panel{background:#fff;border:1px solid var(--wgp-line);border-radius:14px;box-shadow:var(--wgp-shadow)}
.ai-card{padding:15px}.ai-card span{display:block;color:var(--wgp-muted);font-size:11px;font-weight:700}.ai-card strong{display:block;margin-top:8px;color:var(--wgp-text);font-size:23px}.ai-card small{display:block;margin-top:5px;color:#8b95a5;font-size:10px}
.ai-panel{padding:20px;margin-top:16px}.ai-section-head{margin-bottom:15px}.ai-section-head h2{margin:0;font-size:17px}.ai-status{display:inline-flex;align-items:center;gap:7px;padding:7px 10px;border-radius:99px;background:#f1f4f8;color:#516075;font-size:11px;font-weight:800}.ai-status::before{content:"";width:7px;height:7px;border-radius:50%;background:#98a2b3}.ai-status.is-on{background:#fff5d7;color:#80600f}.ai-status.is-on::before{background:var(--wgp-gold)}
.ai-notice{padding:11px 13px;border:1px solid #ead99e;border-radius:10px;background:#fffaf0;color:#68531c;font-size:12px;line-height:1.55;margin-bottom:14px}
.ai-filters{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:13px}.ai-filters select,.ai-form input,.ai-form select,.ai-form textarea,.ai-edit input,.ai-edit select,.ai-edit textarea{min-height:39px;padding:8px 10px;border:1px solid var(--wgp-line);border-radius:9px;background:#fff;color:var(--wgp-text);font:inherit;font-size:12px}.ai-filters select:focus,.ai-form input:focus,.ai-form select:focus,.ai-form textarea:focus,.ai-edit input:focus,.ai-edit select:focus,.ai-edit textarea:focus{outline:0;border-color:var(--wgp-gold);box-shadow:0 0 0 3px rgba(214,166,45,.14)}
.ai-table-wrap{width:100%;overflow:auto}.ai-table{width:100%;border-collapse:collapse;min-width:780px}.ai-table th,.ai-table td{padding:11px 10px;border-bottom:1px solid var(--wgp-line);text-align:left;vertical-align:middle;font-size:12px}.ai-table th{background:#f7f8fa;color:var(--wgp-muted);font-size:9px;letter-spacing:.08em;text-transform:uppercase}.ai-table tr:last-child td{border-bottom:0}.ai-table td small{display:block;margin-top:3px;color:var(--wgp-muted);font-size:10px}.ai-link{color:var(--wgp-gold-dark);font-weight:800;text-decoration:none}.ai-link:hover{text-decoration:underline}
.ai-form{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;align-items:end}.ai-field{display:grid;gap:5px}.ai-field label{color:var(--wgp-muted);font-size:10px;font-weight:800}.ai-field--wide{grid-column:span 2}.ai-field--full{grid-column:1/-1}.ai-field textarea{min-height:72px;resize:vertical}.ai-checks{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:9px;margin:12px 0}.ai-check{display:flex;gap:8px;align-items:center;padding:10px;border:1px solid var(--wgp-line);border-radius:9px;font-size:11px;font-weight:700}.ai-check input{accent-color:var(--wgp-gold)}
.ai-button{display:inline-flex;min-height:38px;align-items:center;justify-content:center;padding:0 13px;border:1px solid var(--wgp-gold);border-radius:9px;background:linear-gradient(135deg,var(--wgp-gold),var(--wgp-gold-2));color:#101114;font-family:inherit;font-size:11px;font-weight:800;text-decoration:none;cursor:pointer}.ai-button--plain{background:#fff;border-color:var(--wgp-line);color:var(--wgp-text)}.ai-inline{display:inline}
.ai-knowledge-list{display:grid;gap:10px;margin-top:16px}.ai-knowledge-item{padding:13px;border:1px solid var(--wgp-line);border-radius:11px;background:#fff}.ai-knowledge-title{display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:10px}.ai-knowledge-title strong{font-size:12px}.ai-knowledge-title small{color:var(--wgp-muted);font-size:10px}
.ai-edit{display:grid;grid-template-columns:1fr 1fr 100px;gap:8px}.ai-edit textarea{grid-column:1/-1;min-height:55px}.ai-edit-actions{display:flex;gap:7px;align-items:center;grid-column:1/-1}.ai-empty{padding:20px;text-align:center;color:var(--wgp-muted);font-size:12px}
.ai-flash{margin-bottom:13px;padding:10px 12px;border-radius:9px;background:#edf9f2;color:#176b43;font-size:12px}.ai-errors{margin-bottom:13px;padding:10px 12px;border-radius:9px;background:#fff0f0;color:#a32626;font-size:12px}
.ai-pagination{margin-top:12px}
@media(max-width:900px){.ai-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.ai-form{grid-template-columns:repeat(2,minmax(0,1fr))}.ai-checks{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:600px){.ai-center{padding-top:12px}.ai-head,.ai-section-head{align-items:flex-start;flex-direction:column}.ai-grid{gap:8px}.ai-card{padding:12px}.ai-card strong{font-size:20px}.ai-form{grid-template-columns:1fr}.ai-field--wide,.ai-field--full{grid-column:auto}.ai-checks{grid-template-columns:1fr}.ai-edit{grid-template-columns:1fr}.ai-edit textarea,.ai-edit-actions{grid-column:auto}}
</style>
@endpush

@section('content')
@php
    $agentActive = $deploymentEnabled
        && $providerConfigured
        && $settings->get('ai_enabled') === 'true'
        && $settings->get('auto_reply_enabled') === 'true';
@endphp
<div class="ai-center">
    <header class="ai-head">
        <div>
            <p class="ai-kicker">Customer sales &amp; service</p>
            <h1>AI Agent Control Center</h1>
            <p>Controlled WhatsApp sales assistance grounded in WGP catalogue and approved business knowledge.</p>
        </div>
        <span class="ai-status {{ $agentActive ? 'is-on' : '' }}">{{ $agentActive ? 'AI AUTOREPLY ACTIVE' : 'AI AUTOREPLY OFF' }}</span>
    </header>

    @if(session('status'))<div class="ai-flash" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="ai-errors" role="alert">{{ $errors->first() }}</div>@endif

    @if(!$deploymentEnabled || !$providerConfigured)
        <div class="ai-notice">
            AI replies are safely disabled until server configuration is ready.
            Set <code>AI_ENABLED=true</code>, <code>AI_API_KEY</code>, and <code>AI_MODEL</code> in the server environment, then enable AI and auto-reply below. Provider secrets are never stored in the database or shown here.
        </div>
    @endif

    <section class="ai-grid" aria-label="AI agent metrics">
        <article class="ai-card"><span>Active conversations</span><strong>{{ number_format($stats['active_conversations']) }}</strong><small>Currently in AI mode</small></article>
        <article class="ai-card"><span>AI conversations today</span><strong>{{ number_format($stats['ai_conversations_today']) }}</strong><small>Based on recorded AI turns</small></article>
        <article class="ai-card"><span>Orders created by AI</span><strong>{{ number_format($stats['orders_created_today']) }}</strong><small>Only after explicit confirmation</small></article>
        <article class="ai-card"><span>Customers assisted</span><strong>{{ number_format($stats['customers_assisted_today']) }}</strong><small>Today</small></article>
        <article class="ai-card"><span>Escalated conversations</span><strong>{{ number_format($stats['escalated_conversations']) }}</strong><small>Require administrator review</small></article>
        <article class="ai-card"><span>Follow-ups sent</span><strong>{{ number_format($stats['follow_ups_sent_today']) }}</strong><small>Today, within quiet hours</small></article>
        <article class="ai-card"><span>AI sales value</span><strong>TSh {{ number_format((float)$stats['ai_sales_value_today'], 0) }}</strong><small>Confirmed AI-created orders today</small></article>
        <article class="ai-card"><span>Provider</span><strong style="font-size:17px">{{ config('ai.provider') }}</strong><small>{{ $providerConfigured ? 'Configured on server' : 'Not configured' }}</small></article>
    </section>

    <section class="ai-panel" id="settings">
        <div class="ai-section-head"><div><h2>AI Settings</h2><p>Server credentials remain environment-managed. Order confirmation is always required.</p></div></div>
        <form class="ai-form" method="POST" action="{{ route('admin.ai.settings.update') }}">
            @csrf @method('PUT')
            <div class="ai-checks ai-field--full">
                @foreach([
                    'ai_enabled' => 'AI enabled',
                    'auto_reply_enabled' => 'WhatsApp auto-reply',
                    'auto_order_creation' => 'AI order creation',
                    'auto_follow_up' => 'Automatic follow-ups',
                ] as $key => $label)
                    <label class="ai-check"><input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, $settings->get($key) === 'true'))> {{ $label }}</label>
                @endforeach
            </div>
            <div class="ai-field"><label for="default_language">Response language</label><select id="default_language" name="default_language"><option value="auto" @selected(old('default_language', $settings->get('default_language', 'auto'))==='auto')>Detect automatically</option><option value="sw" @selected(old('default_language', $settings->get('default_language'))==='sw')>Swahili</option><option value="en" @selected(old('default_language', $settings->get('default_language'))==='en')>English</option></select></div>
            <div class="ai-field"><label for="business_tone">Business tone</label><select id="business_tone" name="business_tone"><option value="warm_professional" @selected(old('business_tone', $settings->get('business_tone', 'warm_professional'))==='warm_professional')>Warm professional</option><option value="professional" @selected(old('business_tone', $settings->get('business_tone'))==='professional')>Professional</option><option value="friendly" @selected(old('business_tone', $settings->get('business_tone'))==='friendly')>Friendly</option></select></div>
            <div class="ai-field"><label for="maximum_follow_ups">Maximum follow-ups</label><input id="maximum_follow_ups" type="number" name="maximum_follow_ups" min="0" max="5" value="{{ old('maximum_follow_ups', $settings->get('maximum_follow_ups', '2')) }}"></div>
            <div class="ai-field"><label for="escalation_threshold">Escalation confidence threshold</label><input id="escalation_threshold" type="number" name="escalation_threshold" min="0.1" max="0.95" step="0.05" value="{{ old('escalation_threshold', $settings->get('escalation_threshold', '0.55')) }}"></div>
            <div class="ai-field"><label for="follow_up_delay_minutes">Follow-up delay (minutes, within 24-hour WhatsApp window)</label><input id="follow_up_delay_minutes" type="number" name="follow_up_delay_minutes" min="5" max="1380" value="{{ old('follow_up_delay_minutes', $settings->get('follow_up_delay_minutes', '60')) }}"></div>
            <div class="ai-field"><label for="quiet_hours_start">Quiet hours start</label><input id="quiet_hours_start" type="time" name="quiet_hours_start" value="{{ old('quiet_hours_start', $settings->get('quiet_hours_start', '21:00')) }}"></div>
            <div class="ai-field"><label for="quiet_hours_end">Quiet hours end</label><input id="quiet_hours_end" type="time" name="quiet_hours_end" value="{{ old('quiet_hours_end', $settings->get('quiet_hours_end', '08:00')) }}"></div>
            <div class="ai-field"><label>Order confirmation</label><input value="Required before creating any order" disabled aria-label="Order confirmation is always required"></div>
            <div class="ai-field ai-field--full"><button class="ai-button" type="submit">Save AI Settings</button></div>
        </form>
    </section>

    <section class="ai-panel" id="conversations">
        <div class="ai-section-head"><div><h2>AI Conversation Monitor</h2><p>Review customer context, handoff state, and recent messages.</p></div></div>
        <form method="GET" action="{{ route('admin.ai.index') }}" class="ai-filters">
            <select name="status" aria-label="Conversation status">
                @foreach(['all'=>'All statuses','active'=>'Active','waiting'=>'Waiting','escalated'=>'Escalated','human'=>'Human','paused'=>'Paused','closed'=>'Completed'] as $value=>$label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? 'all')===$value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="period" aria-label="Conversation period">
                @foreach(['all'=>'All dates','today'=>'Today','week'=>'This week'] as $value=>$label)
                    <option value="{{ $value }}" @selected(($filters['period'] ?? 'all')===$value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="ai-button ai-button--plain" type="submit">Apply filters</button>
        </form>
        <div class="ai-table-wrap">
            <table class="ai-table">
                <thead><tr><th>Customer</th><th>Intent / Stage</th><th>AI status</th><th>Last message</th><th>Last activity</th><th>Action</th></tr></thead>
                <tbody>
                @forelse($states as $state)
                    @php $conversation=$state->conversation; $last=$conversation?->latestMessage; @endphp
                    <tr>
                        <td><strong>{{ $conversation?->customer?->name ?: 'Customer' }}</strong><small>{{ $conversation?->customer?->phone }}</small></td>
                        <td>{{ $state->intent ?: '—' }}<small>{{ str_replace('_',' ', $state->stage) }}</small></td>
                        <td><span class="ai-status {{ $state->status==='active'?'is-on':'' }}">{{ strtoupper(str_replace('_',' ', $state->status)) }}</span></td>
                        <td>{{ \Illuminate\Support\Str::limit($last?->body ?: $last?->media_caption ?: ucfirst($last?->message_type ?? 'No message'), 90) }}<small>{{ $last?->created_at?->diffForHumans() }}</small></td>
                        <td>{{ $state->last_customer_message_at?->format('M j, H:i') ?: '—' }}</td>
                        <td><a class="ai-link" href="{{ route('admin.ai.conversations.show', $conversation) }}">Open conversation →</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="ai-empty">No AI conversations match these filters.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="ai-pagination">{{ $states->links() }}</div>
    </section>

    <section class="ai-panel" id="knowledge">
        <div class="ai-section-head"><div><h2>Business Knowledge Center</h2><p>Only approved entries are used as factual business policy or FAQ information.</p></div></div>
        <form class="ai-form" method="POST" action="{{ route('admin.ai.knowledge.store') }}">
            @csrf
            <div class="ai-field"><label for="knowledge_category">Category</label><select id="knowledge_category" name="category" required>@foreach($knowledgeCategories as $category)<option value="{{ $category }}">{{ ucfirst($category) }}</option>@endforeach</select></div>
            <div class="ai-field ai-field--wide"><label for="knowledge_title">Title</label><input id="knowledge_title" name="title" maxlength="180" required></div>
            <div class="ai-field"><label for="knowledge_language">Language</label><select id="knowledge_language" name="language"><option value="sw">Swahili</option><option value="en">English</option></select></div>
            <div class="ai-field ai-field--wide"><label for="knowledge_question">FAQ question (optional)</label><input id="knowledge_question" name="question" maxlength="500"></div>
            <div class="ai-field ai-field--wide"><label for="knowledge_order">Display order</label><input id="knowledge_order" type="number" name="sort_order" min="0" value="0"></div>
            <div class="ai-field ai-field--full"><label for="knowledge_answer">Approved answer / policy</label><textarea id="knowledge_answer" name="answer" maxlength="5000" required></textarea></div>
            <div class="ai-field ai-field--full"><button class="ai-button" type="submit">Add Approved Knowledge</button></div>
        </form>
        <div class="ai-knowledge-list">
            @forelse($knowledge as $item)
                <article class="ai-knowledge-item">
                    <div class="ai-knowledge-title"><strong>{{ $item->title }}</strong><small>{{ ucfirst($item->category) }} · {{ strtoupper($item->language) }} · {{ $item->is_active ? 'Active' : 'Inactive' }}</small></div>
                    <form class="ai-edit" method="POST" action="{{ route('admin.ai.knowledge.update', $item) }}">
                        @csrf @method('PUT')
                        <select name="category" aria-label="Knowledge category">@foreach($knowledgeCategories as $category)<option value="{{ $category }}" @selected($item->category===$category)>{{ ucfirst($category) }}</option>@endforeach</select>
                        <input name="title" value="{{ $item->title }}" maxlength="180" required aria-label="Knowledge title">
                        <select name="language" aria-label="Knowledge language"><option value="sw" @selected($item->language==='sw')>Swahili</option><option value="en" @selected($item->language==='en')>English</option></select>
                        <input name="question" value="{{ $item->question }}" maxlength="500" placeholder="FAQ question" aria-label="FAQ question">
                        <input type="number" name="sort_order" value="{{ $item->sort_order }}" min="0" aria-label="Sort order">
                        <textarea name="answer" maxlength="5000" required aria-label="Approved answer">{{ $item->answer }}</textarea>
                        <div class="ai-edit-actions">
                            <input type="hidden" name="is_active" value="0">
                            <label class="ai-check"><input type="checkbox" name="is_active" value="1" @checked($item->is_active)> Active</label>
                            <button class="ai-button" type="submit">Save</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('admin.ai.knowledge.destroy', $item) }}" onsubmit="return confirm('Remove this knowledge entry?')">@csrf @method('DELETE')<button class="ai-button ai-button--plain" type="submit">Delete</button></form>
                </article>
            @empty
                <div class="ai-empty">No admin-approved business knowledge has been added yet. The agent will not invent missing policies.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection
