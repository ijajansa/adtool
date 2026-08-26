<?php

namespace App\Http\Controllers;

use App\Exceptions\MetaApiException;
use App\Http\Requests\DisconnectMetaConnectionRequest;
use App\Models\MetaConnection;
use App\Services\Meta\MetaAssetSyncService;
use App\Services\Meta\MetaConnectionLogger;
use App\Services\Meta\MetaDisconnectService;
use App\Services\Meta\MetaOAuthService;
use App\Services\Meta\MetaOAuthStateService;
use App\Services\Meta\MetaTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MetaConnectionController extends Controller
{
    public function index(Request $request): View
    {
        $business = $request->user()->currentBusiness;
        Gate::authorize('view', [MetaConnection::class, $business]);
        $business->load([
            'metaConnection',
            'metaBusinessAccounts',
            'metaAdAccounts',
            'metaPages',
            'metaInstagramAccounts',
        ]);

        return view('meta-connection.index', [
            'business' => $business,
            'connection' => $business->metaConnection,
            'requestedScopes' => config('meta.oauth_scopes'),
        ]);
    }

    public function redirect(
        Request $request,
        MetaOAuthService $oauth,
        MetaOAuthStateService $states,
        MetaConnectionLogger $logger,
    ): RedirectResponse {
        $business = $request->user()->currentBusiness;
        Gate::authorize('connect', [MetaConnection::class, $business]);

        try {
            $state = $states->issue($request, $business);
            $url = $oauth->authorizationUrl($state);
            $logger->log($business, 'connection_initiated', 'success', 'Meta connection initiated.', user: $request->user());

            return redirect()->away($url);
        } catch (MetaApiException $exception) {
            $logger->log($business, 'connection_failed', 'error', $exception->getMessage(), $exception->context(), user: $request->user());

            return redirect()->route('meta-connection.index')->withErrors(['meta' => $exception->getMessage()]);
        }
    }

    public function callback(
        Request $request,
        MetaOAuthService $oauth,
        MetaOAuthStateService $states,
        MetaTokenService $tokens,
        MetaAssetSyncService $sync,
        MetaConnectionLogger $logger,
    ): RedirectResponse {
        $business = $request->user()->currentBusiness;
        Gate::authorize('connect', [MetaConnection::class, $business]);
        $connection = null;

        try {
            $states->validate($request, $business);

            if ($request->filled('error')) {
                throw new MetaApiException('Meta authorization was cancelled. No connection was made.', ['reason' => 'authorization_denied']);
            }

            $code = (string) $request->query('code', '');
            if ($code === '') {
                throw new MetaApiException('Meta did not return an authorization code. Please try again.', ['reason' => 'missing_code']);
            }

            $token = $oauth->exchangeCode($code);
            $connection = MetaConnection::withoutBusinessScope()
                ->withTrashed()
                ->firstOrNew(['business_id' => $business->id]);
            if ($connection->trashed()) {
                $connection->restore();
            }
            $connection->fill([
                'connected_by' => $request->user()->id,
                'access_token' => $token['access_token'],
                'token_type' => $token['token_type'],
                'token_expires_at' => $tokens->expiresAt($token['expires_in']),
                'status' => MetaConnection::STATUS_PENDING,
                'last_error' => null,
            ])->save();

            $counts = $sync->synchronize($connection);
            $logger->log(
                $business,
                'connection_successful',
                'success',
                'Meta connection completed successfully.',
                ['asset_counts' => $counts],
                $connection,
                $request->user(),
            );

            return redirect()->route('meta-connection.index')->with('status', 'Meta account connected and assets synchronized.');
        } catch (MetaApiException $exception) {
            if ($connection?->exists) {
                $connection->update(['status' => MetaConnection::STATUS_ERROR, 'last_error' => $exception->getMessage()]);
            }
            $logger->log(
                $business,
                'connection_failed',
                'error',
                $exception->getMessage(),
                $exception->context(),
                $connection,
                $request->user(),
            );

            return redirect()->route('meta-connection.index')->withErrors(['meta' => $exception->getMessage()]);
        }
    }

    public function sync(
        Request $request,
        MetaAssetSyncService $sync,
        MetaConnectionLogger $logger,
    ): RedirectResponse {
        $business = $request->user()->currentBusiness;
        $connection = $business->metaConnection;
        abort_unless($connection, 404);
        Gate::authorize('sync', $connection);

        try {
            $counts = $sync->synchronize($connection);
            $logger->log($business, 'asset_sync_completed', 'success', 'Meta asset synchronization completed.', ['asset_counts' => $counts], $connection, $request->user());

            return redirect()->route('meta-connection.index')->with('status', 'Meta assets synchronized successfully.');
        } catch (MetaApiException $exception) {
            if ($connection->status !== MetaConnection::STATUS_EXPIRED) {
                $connection->update(['status' => MetaConnection::STATUS_ERROR, 'last_error' => $exception->getMessage()]);
            }
            $logger->log($business, 'asset_sync_failed', 'error', $exception->getMessage(), $exception->context(), $connection, $request->user());

            return redirect()->route('meta-connection.index')->withErrors(['meta' => $exception->getMessage()]);
        }
    }

    public function disconnect(
        DisconnectMetaConnectionRequest $request,
        MetaDisconnectService $disconnect,
        MetaConnectionLogger $logger,
    ): RedirectResponse {
        $business = $request->user()->currentBusiness;
        $connection = $business->metaConnection;

        $disconnect->disconnect($connection, $request->user()->id);
        $logger->log($business, 'connection_disconnected', 'success', 'Meta connection disconnected.', connection: $connection, user: $request->user());

        return redirect()->route('meta-connection.index')->with('status', 'Meta account disconnected. Imported metadata was preserved.');
    }
}
