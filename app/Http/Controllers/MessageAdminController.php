<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Support\Access;
use App\Support\Audit;
use Illuminate\Http\Request;

class MessageAdminController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Access::allows($request->user(), 'messages'), 403);
        $request->validate(['q' => 'nullable|string|max:200', 'status' => 'nullable|in:new,read,resolved']);

        return ContactMessage::when($request->q, fn ($q) => $q->where('name', 'like', '%'.$request->q.'%'))->when($request->status, fn ($q) => $q->where('status', $request->status))->latest()->paginate(15);
    }

    public function show(Request $request, ContactMessage $message)
    {
        abort_unless(Access::allows($request->user(), 'messages'), 403);

        return $message;
    }

    public function update(Request $request, ContactMessage $message)
    {
        abort_unless(Access::allows($request->user(), 'messages'), 403);
        $message->update($request->validate(['status' => 'required|in:new,read,resolved', 'internal_notes' => 'nullable|string|max:5000']));
        Audit::record('updated', 'messages', $message->id);

        return ['message' => 'Message updated.'];
    }
}
