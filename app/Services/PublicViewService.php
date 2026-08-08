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
        $token = $pv->token;
        $prefix = substr(md5((string)$pv->id), 0, 8);

        return <<<JS
(function() {
    var iframeSrc = "{$embedUrl}";
    var containerId = "apis-hub-pv-{$prefix}";
    var container = document.getElementById(containerId);

    // Optional max-height config: <script src="...embed.js" data-max-height="800" defer></script>
    // The iframe never grows past this limit; content beyond it scrolls inside the iframe.
    var maxHeight = 0;
    var scriptTags = document.getElementsByTagName("script");
    for (var i = 0; i < scriptTags.length; i++) {
        var src = scriptTags[i].getAttribute("src") || "";
        if (src.indexOf("{$token}") !== -1 && src.indexOf("embed.js") !== -1) {
            if (!container && scriptTags[i].parentElement) {
                container = scriptTags[i].parentElement;
            }
            var parsed = parseInt(scriptTags[i].getAttribute("data-max-height") || "0", 10);
            if (!isNaN(parsed) && parsed > 0) {
                maxHeight = parsed;
            }
            break;
        }
    }
    if (!container) {
        container = document.body;
    }

    function clampHeight(h) {
        return (maxHeight && h > maxHeight) ? maxHeight : h;
    }

    var iframe = document.createElement("iframe");
    iframe.src = iframeSrc;
    iframe.style.cssText = "width:100%;border:none;display:block;min-height:400px;" + (maxHeight ? "max-height:" + maxHeight + "px;" : "");
    iframe.setAttribute("sandbox", "allow-scripts allow-same-origin allow-forms");
    iframe.setAttribute("loading", "lazy");
    container.appendChild(iframe);

    var popOutLocked = false;
    var popOutRestore = null;

    window.addEventListener("message", function(e) {
        if (e.source !== iframe.contentWindow || !e.data) return;

        if (e.data.type === "apis-hub-popout") {
            popOutLocked = !!e.data.active;
            if (popOutLocked) {
                // Pin the iframe over the real visible viewport while a widget is expanded,
                // so the fullscreen modal doesn't grow to the whole dashboard height.
                // We pin it (instead of scrolling the page to the iframe top) so the
                // embedder's scroll position is preserved while the modal is open.
                var rect = iframe.getBoundingClientRect();
                popOutRestore = {
                    position: iframe.style.position,
                    top: iframe.style.top,
                    left: iframe.style.left,
                    width: iframe.style.width,
                    height: iframe.style.height,
                    zIndex: iframe.style.zIndex,
                    margin: iframe.style.margin
                };
                iframe.style.position = "fixed";
                iframe.style.top = "0";
                iframe.style.left = rect.left + "px";
                iframe.style.width = rect.width + "px";
                iframe.style.margin = "0";
                iframe.style.zIndex = "999999";
                iframe.style.height = clampHeight(window.innerHeight) + "px";
            } else {
                if (popOutRestore) {
                    iframe.style.position = popOutRestore.position;
                    iframe.style.top = popOutRestore.top;
                    iframe.style.left = popOutRestore.left;
                    iframe.style.width = popOutRestore.width;
                    iframe.style.height = popOutRestore.height;
                    iframe.style.zIndex = popOutRestore.zIndex;
                    iframe.style.margin = popOutRestore.margin;
                    popOutRestore = null;
                }
                iframe.contentWindow.postMessage({ type: "apis-hub-measure" }, "*");
            }
        } else if (e.data.type === "apis-hub-resize" && !popOutLocked) {
            iframe.style.height = clampHeight(e.data.height) + "px";
        }
    });

    window.addEventListener("resize", function() {
        if (popOutLocked) {
            iframe.style.height = clampHeight(window.innerHeight) + "px";
        }
    });
})();
JS;
    }
}
