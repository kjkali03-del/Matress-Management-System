@extends('layouts.admin')
@section('title', 'Edit Automation | Wonder Godoro Point')
@push('styles')
<style>
.auto-page{max-width:980px;margin:0 auto;padding:32px}.auto-head{display:flex;justify-content:space-between;gap:20px;align-items:center;margin-bottom:24px}.auto-head h1{margin:0;font-size:32px}.auto-head p{margin:6px 0 0;color:#737373}.auto-card{background:#fff;border:1px solid #e7e2d9;border-radius:16px;padding:26px;box-shadow:0 8px 25px rgba(0,0,0,.04);margin-bottom:18px}.auto-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:18px}.auto-field{display:flex;flex-direction:column;gap:7px}.auto-full{grid-column:1/-1}.auto-field label{font-size:13px;font-weight:700}.auto-field input,.auto-field select,.auto-field textarea{box-sizing:border-box;width:100%;border:1px solid #ddd7ce;border-radius:9px;padding:11px 12px;font:inherit}.auto-actions{display:flex;justify-content:flex-end;gap:10px}.auto-btn{display:inline-flex;padding:11px 17px;border-radius:9px;text-decoration:none;font-weight:700;border:1px solid #ddd;background:#fff;color:#333}.auto-primary{background:#171717;color:#fff;border-color:#171717}.auto-note{padding:13px 15px;border-radius:10px;background:#f7f5ef;color:#6b6255;font-size:13px;margin-bottom:18px}@media(max-width:700px){.auto-page{padding:20px 14px}.auto-head{align-items:flex-start;flex-direction:column}.auto-grid{grid-template-columns:1fr}.auto-full{grid-column:auto}}
</style>
@endpush
@section('content')
<div class="auto-page">
    <div class="auto-head"><div><h1>Edit Automation</h1><p>{{ $automation->name }}</p></div><a class="auto-btn" href="{{ route('admin.automations.index') }}">← Automations</a></div>
    @if($errors->any())<div class="dashboard-alert dashboard-alert--error"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="auto-note">The builder stores rules as structured JSON. This editor keeps the existing automation data intact while making the main trigger, keyword, delay and response editable.</div>
    <form method="POST" action="{{ route('admin.automations.update', $automation) }}">
        @csrf @method('PUT')
        <div class="auto-card"><div class="auto-grid">
            <div class="auto-field"><label>Name</label><input name="name" value="{{ old('name', $automation->name) }}" required></div>
            <div class="auto-field"><label>Trigger</label><select name="trigger" id="trigger"><option value="new_customer" @selected(old('trigger',$automation->trigger)==='new_customer')>New customer</option><option value="message_received" @selected(old('trigger',$automation->trigger)==='message_received')>Message received</option><option value="keyword" @selected(old('trigger',$automation->trigger)==='keyword')>Keyword</option><option value="no_reply" @selected(old('trigger',$automation->trigger)==='no_reply')>No reply / follow-up</option></select></div>
            <div class="auto-field auto-full"><label>Description</label><textarea name="description" rows="3">{{ old('description', $automation->description) }}</textarea></div>
        </div></div>
        <div class="auto-card">
            @php $condition = $automation->conditions[0] ?? []; $action = $automation->actions[0] ?? []; @endphp
            <div class="auto-grid">
                <div class="auto-field"><label>Keyword</label><input name="conditions[0][type]" type="hidden" value="keyword"><input name="conditions[0][value]" value="{{ old('conditions.0.value', $condition['value'] ?? '') }}" placeholder="bei, godoro, delivery"></div>
                <div class="auto-field"><label>Follow-up delay value</label><input type="number" min="1" max="720" name="conditions[0][delay_value]" value="{{ old('conditions.0.delay_value', $condition['delay_value'] ?? 24) }}"></div>
                <div class="auto-field"><label>Delay unit</label><select name="conditions[0][delay_unit]"><option value="minutes" @selected(($condition['delay_unit'] ?? 'hours')==='minutes')>Minutes</option><option value="hours" @selected(($condition['delay_unit'] ?? 'hours')==='hours')>Hours</option><option value="days" @selected(($condition['delay_unit'] ?? 'hours')==='days')>Days</option></select></div>
                <div class="auto-field auto-full"><label>Keyword response</label><textarea name="conditions[0][response]" rows="3" placeholder="Reply when the keyword matches...">{{ old('conditions.0.response', $condition['response'] ?? '') }}</textarea></div>
                <div class="auto-field auto-full"><label>Automatic response / follow-up message</label><textarea name="actions[0][message]" rows="5" placeholder="Write the message to send automatically...">{{ old('actions.0.message', $action['message'] ?? ($condition['response'] ?? '')) }}</textarea><input type="hidden" name="actions[0][type]" value="send_text"></div>
                <div class="auto-field auto-full"><label class="product-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $automation->is_active))> Automation is active</label></div>
            </div>
        </div>
        <div class="auto-actions"><a class="auto-btn" href="{{ route('admin.automations.index') }}">Cancel</a><button class="auto-btn auto-primary" type="submit">Save Automation</button></div>
    </form>
</div>
@endsection
