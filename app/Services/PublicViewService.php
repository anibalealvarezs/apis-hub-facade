<?php

namespace App\Services;

use App\Models\Dashboard;
use App\Models\DashboardPublicView;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class PublicViewService
{
    public function generateToken(DashboardPublicView $pv): string
    {
        $payload = [
            'pvid' => $pv->id,
            'iat' => time(),
        ];

        $key = hash('sha256', $pv->token_secret . config('app.key'));

        return JWT::encode($payload, $key, 'HS256');
    }

    public function verifyToken(string $rawToken): ?DashboardPublicView
    {
        try {
            $tks = explode('.', $rawToken);
            if (count($tks) !== 3) {
                return null;
            }

            $payloadRaw = JWT::urlsafeB64Decode($tks[1]);
            $payload = json_decode($payloadRaw, true);

            if (!is_array($payload) || empty($payload['pvid'])) {
                return null;
            }

            $pv = DashboardPublicView::withTrashed()->find($payload['pvid']);
            if (!$pv) {
                return null;
            }

            $key = hash('sha256', $pv->token_secret . config('app.key'));
            $decoded = JWT::decode($rawToken, new Key($key, 'HS256'));

            if ((int) $decoded->pvid !== (int) $pv->id) {
                return null;
            }

            return $pv;
        } catch (Exception $e) {
            return null;
        }
    }

    public function create(Dashboard $dashboard, array $data): DashboardPublicView
    {
        /** @var DashboardPublicView $pv */
        $pv = $dashboard->publicViews()->create([
            'name' => $data['name'],
            'asset_group_ids' => $data['asset_group_ids'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return $pv;
    }

    public function regenerateToken(DashboardPublicView $pv): DashboardPublicView
    {
        $pv->token_secret = bin2hex(random_bytes(32));
        $pv->token = ''; // Temp empty to avoid duplicate issue if needed
        $pv->save();

        $pv->token = $this->generateToken($pv);
        $pv->save();

        return $pv;
    }

    public function getEmbedJs(DashboardPublicView $pv): string
    {
        $publicUrl = $pv->getPublicUrl();
        $embedUrl = $publicUrl . (str_contains($publicUrl, '?') ? '&' : '?') . 'embedded=1';
        $prefix = substr(md5((string)$pv->id), 0, 8);

        return <<<JS
(function() {
    var iframeSrc = "{$embedUrl}";
    var containerId = "apis-hub-pv-{$prefix}";
    var container = document.getElementById(containerId);
    if (!container && document.currentScript) {
        container = document.currentScript.parentElement;
    }
    if (!container) {
        container = document.body;
    }
    var iframe = document.createElement("iframe");
    iframe.src = iframeSrc;
    iframe.style.cssText = "width:100%;border:none;display:block;min-height:400px;";
    iframe.setAttribute("sandbox", "allow-scripts allow-same-origin allow-forms");
    iframe.setAttribute("loading", "lazy");
    container.appendChild(iframe);

    window.addEventListener("message", function(e) {
        if (e.data && e.data.type === "apis-hub-resize" && e.source === iframe.contentWindow) {
            iframe.style.height = e.data.height + "px";
        }
    });
})();
JS;
    }
}
