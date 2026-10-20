@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6"><p class="text-sm font-semibold uppercase tracking-wide text-brand-700">Timekeeping</p><h1 class="mt-1 text-3xl font-bold text-slate-900">{{ $isManager ? 'Leave management' : 'My leave requests' }}</h1><p class="mt-1 text-sm text-slate-500">Approved leave is excluded from absence calculations. Leave policy and balances are not assumed.</p></div>
    @if ($isManager)
        <div class="mb-6 grid gap-6 lg:grid-cols-3">
            <x-card title="Add a leave type">
                <form method="POST" action="{{ route('timekeeping.leave-types.store') }}" class="space-y-4">@csrf
                    <x-form-field label="Leave type name" for="name"><x-input id="name" name="name" placeholder="Annual leave" required /></x-form-field>
                    <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="requires_attachment" value="1" class="rounded border-slate-300 text-brand-600"> Requires a supporting file</label>
                    <x-button>Add leave type</x-button>
                </form>
            </x-card>
            <x-card class="lg:col-span-2" title="Configured leave types" subtitle="Inactive types remain on historical requests">
                <div class="flex flex-wrap gap-2">@forelse ($allTypes as $type)<div class="flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-2 text-sm"><span>{{ $type->name }}{{ $type->requires_attachment ? ' · attachment required' : '' }}</span><x-badge :tone="$type->is_active ? 'emerald' : 'slate'">{{ $type->is_active ? 'Active' : 'Inactive' }}</x-badge><form method="POST" action="{{ route('timekeeping.leave-types.toggle', $type) }}">@csrf @method('PATCH')<x-button variant="ghost">{{ $type->is_active ? 'Deactivate' : 'Activate' }}</x-button></form></div>@empty<p class="text-sm text-slate-500">No leave types have been configured.</p>@endforelse</div>
            </x-card>
        </div>
    @else
        <x-card class="mb-6" title="Submit a leave request" subtitle="Requests must start today or later and may not overlap pending or approved requests">
            <form method="POST" action="{{ route('timekeeping.leaves.store') }}" enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-2">@csrf
                <x-form-field label="Leave type" for="leave_type_id"><x-select id="leave_type_id" name="leave_type_id" required><option value="">Choose a type</option>@foreach ($types as $type)<option value="{{ $type->id }}">{{ $type->name }}{{ $type->requires_attachment ? ' · attachment required' : '' }}</option>@endforeach</x-select></x-form-field>
                <div class="grid grid-cols-2 gap-3"><x-form-field label="Start date" for="start_date"><x-input id="start_date" name="start_date" type="date" min="{{ now('Asia/Manila')->toDateString() }}" required /></x-form-field><x-form-field label="End date" for="end_date"><x-input id="end_date" name="end_date" type="date" min="{{ now('Asia/Manila')->toDateString() }}" required /></x-form-field></div>
                <div class="sm:col-span-2"><x-form-field label="Reason" for="reason"><x-textarea id="reason" name="reason" rows="3" required minlength="5" maxlength="3000"></x-textarea></x-form-field></div>
                <x-form-field label="Supporting document (PDF or image, max 5 MB)" for="attachment"><x-input id="attachment" name="attachment" type="file" accept=".pdf,.jpg,.jpeg,.png" /></x-form-field>
                <div class="flex items-end"><x-button>Submit request</x-button></div>
            </form>
        </x-card>
    @endif

    <x-card title="Requests" subtitle="Approval decisions and comments are retained with the request">
        <form method="GET" class="mb-5 flex items-end gap-3"><x-form-field label="Status" for="status"><x-select id="status" name="status"><option value="">All statuses</option>@foreach (['pending','approved','rejected','cancelled'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>@endforeach</x-select></x-form-field><x-button variant="secondary">Filter requests</x-button></form>
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead><tr class="text-left text-xs uppercase text-slate-500"><th class="py-3 pr-3">Employee / leave type</th><th class="py-3 pr-3">Dates</th><th class="py-3 pr-3">Reason / comment</th><th class="py-3 pr-3">Status</th><th class="py-3">Action</th></tr></thead><tbody class="divide-y divide-slate-100">
            @forelse ($requests as $leave)<tr class="align-top"><td class="py-3 pr-3"><p class="font-semibold text-slate-900">{{ $leave->employee->user->name }}</p><p class="text-xs text-slate-500">{{ $leave->employee->employee_number }} · {{ $leave->type->name }}</p>@if ($leave->attachment_path)<a class="mt-1 inline-block text-xs font-semibold text-brand-700 hover:underline" href="{{ route('timekeeping.leaves.attachment', $leave) }}">Supporting document</a>@endif</td><td class="py-3 pr-3">{{ $leave->start_date->format('M j, Y') }}<span class="block text-slate-500">to {{ $leave->end_date->format('M j, Y') }}</span></td><td class="max-w-xs py-3 pr-3"><p>{{ $leave->reason }}</p>@if ($leave->decision_comment)<p class="mt-1 text-xs text-slate-500">Decision: {{ $leave->decision_comment }}</p>@endif</td><td class="py-3 pr-3"><x-badge :tone="match($leave->status) {'approved' => 'emerald', 'rejected' => 'rose', 'pending' => 'amber', default => 'slate'}">{{ ucfirst($leave->status) }}</x-badge>@if ($leave->reviewer)<span class="mt-1 block text-xs text-slate-500">by {{ $leave->reviewer->name }}</span>@endif</td><td class="py-3">
                @if ($isManager && $leave->status === 'pending')<div class="flex flex-col gap-2"><form method="POST" action="{{ route('timekeeping.leaves.decide', $leave) }}">@csrf<input type="hidden" name="decision" value="approved"><x-input name="decision_comment" placeholder="Optional approval note" class="mb-2"/><x-button variant="success">Approve</x-button></form><form method="POST" action="{{ route('timekeeping.leaves.decide', $leave) }}">@csrf<input type="hidden" name="decision" value="rejected"><x-input name="decision_comment" placeholder="Reason for rejection" required class="mb-2"/><x-button variant="danger">Reject</x-button></form></div>
                @elseif (!$isManager && $leave->status === 'pending')<form method="POST" action="{{ route('timekeeping.leaves.cancel', $leave) }}">@csrf @method('DELETE')<x-button variant="ghost">Cancel request</x-button></form>
                @else<span class="text-xs text-slate-500">{{ $leave->reviewed_at?->setTimezone('Asia/Manila')->format('M j, g:i A') ?? '—' }}</span>@endif
            </td></tr>@empty<tr><td colspan="5" class="py-10 text-center text-slate-500">No leave requests found.</td></tr>@endforelse
        </tbody></table></div><div class="mt-4">{{ $requests->links() }}</div>
    </x-card>
</section>
@endsection
