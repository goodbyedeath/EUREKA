<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Publish the API contract over HTTP, so the Android build agent can read it directly.
 *
 * Git already carries these files, but the client project lives on a different machine and may
 * not have credentials for a private repo — which left a person downloading a file and pasting
 * it to the client team. That is the failure this whole contract-driven setup exists to remove:
 * a rule that travels by hand is a rule that arrives late, or not at all.
 *
 * Nothing secret is exposed. The document describes request shapes, rate limits and which
 * endpoints need a token; every one of them is already reachable from any installed copy of the
 * app, and authorisation is enforced by middleware regardless of what is documented. If it ever
 * needs closing, put a shared-secret header check in the `contract` middleware group rather than
 * removing the endpoints — the client agent needs SOME automatic way in.
 */
class ContractController extends Controller
{
    /** Guides that may be fetched, mapped to their file. Whitelisted, not path-joined. */
    private const GUIDES = [
        'build'     => 'APK-BUILD-GUIDE.md',
        'contract'  => 'API-V1-CONTRACT.md',
        'sync'      => 'APK-SYNC-FEEDBACK.md',
        'agent'     => 'APK-AGENT-INSTRUCTIONS.md',
        'work'      => 'APK-WORK-ORDER.md',
    ];

    /** The full OpenAPI document. */
    public function show()
    {
        $path = base_path('openapi.json');

        if (! is_file($path)) {
            return response()->json([
                'success' => false,
                'error' => 'contract_missing',
                'message' => 'openapi.json has not been generated on this server.',
            ], 503);
        }

        return response(file_get_contents($path), 200, [
            'Content-Type' => 'application/json',
            // The client agent polls this to decide whether to regenerate; let it revalidate
            // cheaply rather than re-downloading 67 KB every time.
            'ETag' => '"' . substr(hash_file('sha256', $path), 0, 16) . '"',
            'Cache-Control' => 'public, max-age=60',
        ]);
    }

    /**
     * Just the fingerprints — cheap enough to poll.
     *
     * A client that only wants to know "has anything moved?" should ask here, not download the
     * whole document. `guides` is the same map the contract carries in info.x-behaviour-guides:
     * when one of those hashes changes, the PROSE rules changed even though no endpoint did.
     */
    public function version()
    {
        $path = base_path('openapi.json');

        if (! is_file($path)) {
            return response()->json(['success' => false, 'error' => 'contract_missing'], 503);
        }

        $doc = json_decode(file_get_contents($path), true) ?: [];

        return response()->json([
            'success' => true,
            'contract_sha' => substr(hash_file('sha256', $path), 0, 16),
            'operations' => array_sum(array_map('count', $doc['paths'] ?? [])),
            'guides' => $doc['info']['x-behaviour-guides'] ?? [],
            'generated_at' => date('c', filemtime($path)),
            'guide_urls' => collect(self::GUIDES)->map(
                fn ($f, $k) => url('/api/v1/contract/guide/' . $k)
            ),
        ]);
    }

    /** One of the human-written companions, as markdown. */
    public function guide(Request $request, string $name)
    {
        if (! isset(self::GUIDES[$name])) {
            return response()->json([
                'success' => false,
                'error' => 'unknown_guide',
                'message' => 'Known guides: ' . implode(', ', array_keys(self::GUIDES)),
            ], 404);
        }

        $path = base_path(self::GUIDES[$name]);

        if (! is_file($path)) {
            return response()->json(['success' => false, 'error' => 'guide_missing'], 404);
        }

        return response(file_get_contents($path), 200, [
            'Content-Type' => 'text/markdown; charset=utf-8',
            'ETag' => '"' . substr(hash_file('sha256', $path), 0, 16) . '"',
            'Cache-Control' => 'public, max-age=60',
        ]);
    }
}
