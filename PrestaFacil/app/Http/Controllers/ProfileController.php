<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Writer;

class ProfileController extends Controller
{
    /**
     * Muestra el formulario del perfil con el QR del 2FA integrado.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $qrImage = null;

        // Generamos el QR solo si el usuario NO tiene activado el 2FA todavía y NO es verificador
        if (!$user->google2fa_enabled && class_exists(Google2FA::class) && !$user->esVerificador()) {
            $google2fa = new Google2FA();

            if (!$user->google2fa_secret) {
                // Generamos la clave. Al guardar, el cast 'encrypted' en User.php la cifra en la BD
                $user->google2fa_secret = $google2fa->generateSecretKey();
                $user->save();
            }

            // El cast en User.php nos entrega la clave desencriptada automáticamente aquí
            $qrCodeUrl = $google2fa->getQRCodeUrl(
                'PrestaFacil',
                $user->email,
                $user->google2fa_secret
            );

            $renderer = new ImageRenderer(
                new RendererStyle(180),
                new SvgImageBackEnd()
            );
            $writer = new Writer($renderer);
            $qrImage = $writer->writeString($qrCodeUrl);
        }

        return view('profile.edit', [
            'user' => $user,
            'qrImage' => $qrImage,
        ]);
    }

    /**
     * Confirma el primer código de 6 dígitos y activa el 2FA.
     */
    public function activar2FA(Request $request): RedirectResponse
    {
        $request->validate([
            'one_time_password' => 'required|digits:6',
        ]);

        $user = $request->user();
        $google2fa = new Google2FA();

        $valid = $google2fa->verifyKey($user->google2fa_secret, $request->one_time_password);

        if ($valid) {
            $user->google2fa_enabled = true;
            $user->save();

            return back()->with('status', '2fa-enabled');
        }

        return back()->withErrors(['one_time_password' => 'El código ingresado es incorrecto.']);
    }

    /**
     * Desactiva el 2FA y elimina el secreto.
     */
    public function desactivar2FA(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->google2fa_enabled = false;
        $user->google2fa_secret = null;
        $user->save();

        return back()->with('status', '2fa-disabled');
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}