<?php
namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformAnnouncement;
use App\Services\SuperAdmin\AuditWriter;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function __construct(private AuditWriter $audit) {}

    public function index()
    {
        $announcements = PlatformAnnouncement::orderByDesc('id')->paginate(20);
        return view('superadmin.announcements.index', compact('announcements'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'message'               => 'required|string|max:1000',
            'severity'              => 'required|in:info,warning,danger',
            'show_to_tenant_admins' => 'sometimes|boolean',
            'show_to_super_admins'  => 'sometimes|boolean',
            'starts_at'             => 'nullable|date',
            'ends_at'               => 'nullable|date|after_or_equal:starts_at',
        ]);
        $data['show_to_tenant_admins'] = $request->boolean('show_to_tenant_admins', true);
        $data['show_to_super_admins']  = $request->boolean('show_to_super_admins', true);
        $data['created_by'] = auth('superadmin')->id();
        $a = PlatformAnnouncement::create($data);
        $this->audit->log(auth('superadmin')->user(), 'announcement.created', null, null, $a->only(['id', 'message', 'severity']));
        return back()->with('success', 'Announcement created.');
    }

    public function destroy(int $id)
    {
        $a = PlatformAnnouncement::findOrFail($id);
        $this->audit->log(auth('superadmin')->user(), 'announcement.deleted', null, $a->only(['id', 'message']), null);
        $a->delete();
        return back()->with('success', 'Announcement deleted.');
    }
}
