<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BioPage;
use App\Models\Domain;
use App\Models\QrCode;
use Illuminate\Http\Request;

class ModerationController extends Controller
{
    public const TABS = ['bio' => 'Bio pages', 'qr' => 'QR codes', 'domains' => 'Custom domains'];

    public function index(Request $request)
    {
        $tab = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'bio';
        $q = trim((string) $request->query('q', ''));

        $items = match ($tab) {
            'qr' => QrCode::with('user')
                ->when($q !== '', fn ($x) => $x->where('name', 'like', "%{$q}%"))
                ->latest()->paginate(20)->withQueryString(),
            'domains' => Domain::with('user')->where('is_default', false)
                ->when($q !== '', fn ($x) => $x->where('host', 'like', "%{$q}%"))
                ->latest()->paginate(20)->withQueryString(),
            default => BioPage::with('user')
                ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w->where('slug', 'like', "%{$q}%")->orWhere('title', 'like', "%{$q}%")))
                ->latest()->paginate(20)->withQueryString(),
        };

        return view('admin.moderation.index', [
            'tab' => $tab,
            'tabs' => self::TABS,
            'q' => $q,
            'items' => $items,
        ]);
    }

    public function updateBioPage(Request $request, BioPage $bioPage)
    {
        $action = $request->input('action');
        match ($action) {
            'publish' => $bioPage->update(['is_published' => true]),
            'unpublish' => $bioPage->update(['is_published' => false]),
            'delete' => $bioPage->delete(),
            default => null,
        };

        AuditLog::record("bio.{$action}", "Bio /{$bioPage->slug}", $bioPage);

        return back()->with('status', 'Bio page updated.');
    }

    public function destroyQrCode(QrCode $qr)
    {
        AuditLog::record('qr.delete', $qr->name ?: ('QR #'.$qr->id), $qr);
        $qr->delete();

        return back()->with('status', 'QR code deleted.');
    }

    public function updateDomain(Request $request, Domain $domain)
    {
        abort_if($domain->is_default, 403);

        $action = $request->input('action');
        match ($action) {
            'verify' => $domain->update(['status' => 'active', 'last_checked_at' => now()]),
            'delete' => $domain->delete(),
            default => null,
        };

        AuditLog::record("domain.{$action}", $domain->host, $domain);

        return back()->with('status', 'Domain updated.');
    }
}
