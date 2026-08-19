<?php

namespace App\Http\Controllers;

use App\Model\Branch;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Square\Models\ObtainTokenRequest;
use Square\SquareClient;

class SquareOAuthController extends Controller
{
    public function connect(Request $request, $branchId)
    {
        $branch = Branch::find($branchId);
        if (!$branch) {
            Toastr::error('Branch not found.');
            return back();
        }

        $clientId = $this->clientId($branch);
        $clientSecret = $this->clientSecret();
        if (!$clientId || !$clientSecret) {
            Toastr::error('Square OAuth Application ID and Application Secret are required.');
            return back();
        }

        $environment = $this->environment($branch);
        $state = $branch->id . '|' . Str::random(40);

        $request->session()->put('square_oauth_state', $state);
        $request->session()->put('square_oauth_branch_id', $branch->id);
        $request->session()->put('square_oauth_environment', $environment);

        $query = http_build_query([
            'client_id' => $clientId,
            'response_type' => 'code',
            'scope' => implode(' ', $this->scopes()),
            'state' => $state,
            'redirect_uri' => $this->redirectUri(),
        ]);

        return redirect()->away($this->authorizeUrl($environment) . '?' . $query);
    }

    public function callback(Request $request)
    {
        $branchId = $request->session()->get('square_oauth_branch_id');
        $branch = $branchId ? Branch::find($branchId) : null;

        if ($request->has('error')) {
            Toastr::error('Square OAuth was cancelled or denied: ' . $request->get('error'));
            return $this->redirectToBranch($branch);
        }

        if (!$branch) {
            Toastr::error('Square OAuth branch session expired. Please connect Square again.');
            return redirect()->route('admin.branch.list');
        }

        if (!$request->get('code')) {
            Toastr::error('Square OAuth authorization code is missing.');
            return $this->redirectToBranch($branch);
        }

        if (!$request->get('state') || $request->get('state') !== $request->session()->get('square_oauth_state')) {
            Toastr::error('Square OAuth state did not match. Please connect Square again.');
            return $this->redirectToBranch($branch);
        }

        $clientId = $this->clientId($branch);
        $clientSecret = $this->clientSecret();
        $environment = $request->session()->get('square_oauth_environment') ?: $this->environment($branch);

        if (!$clientId || !$clientSecret) {
            Toastr::error('Square OAuth Application ID and Application Secret are required.');
            return $this->redirectToBranch($branch);
        }

        try {
            $body = new ObtainTokenRequest($clientId, 'authorization_code');
            $body->setClientSecret($clientSecret);
            $body->setCode($request->get('code'));
            $body->setRedirectUri($this->redirectUri());
            $body->setScopes($this->scopes());

            $response = $this->client($environment)->getOAuthApi()->obtainToken($body);
            if (!$response->isSuccess()) {
                Toastr::error($this->formatSquareErrors($response->getErrors()));
                return $this->redirectToBranch($branch);
            }

            $token = $response->getResult();
            if (!$token || !$token->getAccessToken()) {
                Toastr::error('Square did not return an access token.');
                return $this->redirectToBranch($branch);
            }

            $locationId = $branch->square_location_id ?: $this->firstLocationId($token->getAccessToken(), $environment);

            $this->saveToken($branch, [
                'square_status' => 1,
                'square_application_id' => $clientId,
                'square_merchant_id' => $token->getMerchantId(),
                'square_access_token' => $token->getAccessToken(),
                'square_oauth_refresh_token' => $token->getRefreshToken(),
                'square_oauth_token_expires_at' => $this->toTimestamp($token->getExpiresAt()),
                'square_oauth_refresh_token_expires_at' => $this->toTimestamp($token->getRefreshTokenExpiresAt()),
                'square_oauth_connected_at' => now(),
                'square_environment' => $environment,
                'square_location_id' => $locationId,
            ]);

            $request->session()->forget([
                'square_oauth_state',
                'square_oauth_branch_id',
                'square_oauth_environment',
            ]);

            Toastr::success('Square OAuth connected successfully.');
            return $this->redirectToBranch($branch);
        } catch (\Exception $exception) {
            Log::error('Square OAuth callback failed: ' . $exception->getMessage(), [
                'branch_id' => $branch->id,
            ]);

            Toastr::error('Square OAuth connection failed: ' . $exception->getMessage());
            return $this->redirectToBranch($branch);
        }
    }

    protected function saveToken(Branch $branch, array $fields)
    {
        foreach ($fields as $column => $value) {
            if (Schema::hasColumn('branches', $column) && $value !== null && $value !== '') {
                $branch->{$column} = $value;
            }
        }

        $branch->save();
    }

    protected function firstLocationId($accessToken, $environment)
    {
        try {
            $response = $this->client($environment, $accessToken)->getLocationsApi()->listLocations();
            if (!$response->isSuccess()) {
                return null;
            }

            $locations = $response->getResult() ? ($response->getResult()->getLocations() ?: []) : [];
            foreach ($locations as $location) {
                if ($location->getStatus() === 'ACTIVE') {
                    return $location->getId();
                }
            }

            return count($locations) ? $locations[0]->getId() : null;
        } catch (\Exception $exception) {
            Log::warning('Unable to read Square OAuth locations: ' . $exception->getMessage());
            return null;
        }
    }

    protected function client($environment, $accessToken = null)
    {
        return new SquareClient([
            'accessToken' => $accessToken ?: '',
            'environment' => $environment,
        ]);
    }

    protected function clientId(Branch $branch)
    {
        return config('services.square.application_id') ?: $branch->square_application_id;
    }

    protected function clientSecret()
    {
        return config('services.square.application_secret');
    }

    protected function redirectUri()
    {
        return config('services.square.oauth_redirect_url') ?: route('square.oauth.callback');
    }

    protected function environment(Branch $branch)
    {
        return strtolower($branch->square_environment ?: config('services.square.environment', 'sandbox')) === 'production'
            ? 'production'
            : 'sandbox';
    }

    protected function authorizeUrl($environment)
    {
        return $environment === 'production'
            ? 'https://connect.squareup.com/oauth2/authorize'
            : 'https://connect.squareupsandbox.com/oauth2/authorize';
    }

    protected function scopes()
    {
        return [
            'MERCHANT_PROFILE_READ',
            'PAYMENTS_READ',
            'PAYMENTS_WRITE',
            'PAYMENTS_WRITE_ADDITIONAL_RECIPIENTS',
            'ORDERS_READ',
            'ORDERS_WRITE',
        ];
    }

    protected function formatSquareErrors($errors)
    {
        if (!$errors) {
            return 'Square OAuth request failed.';
        }

        $messages = [];
        foreach ($errors as $error) {
            $messages[] = method_exists($error, 'getDetail') ? $error->getDetail() : (string) $error;
        }

        return implode(' ', $messages);
    }

    protected function toTimestamp($value)
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Exception $exception) {
            return null;
        }
    }

    protected function redirectToBranch($branch)
    {
        if ($branch) {
            return redirect()->route('admin.branch.edit', [$branch->id]);
        }

        return redirect()->route('admin.branch.list');
    }
}
