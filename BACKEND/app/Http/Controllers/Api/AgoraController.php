<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Include Agora SDK classes dengan path absolut agar bisa berjalan di production (cPanel)
require_once app_path('Services/Agora/AccessToken2.php');
require_once app_path('Services/Agora/RtcTokenBuilder2.php');

require_once app_path('Services/Agora/AccessToken.php');
require_once app_path('Services/Agora/RtcTokenBuilder.php');

class AgoraController extends Controller
{
    public function getToken(Request $request)
    {
        // Mengambil kredensial dengan aman melalui config (bukan langsung dari env() untuk menghindari masalah cache cPanel)
        $appId = trim(config('services.agora.app_id'));
        $appCertificate = trim(config('services.agora.app_certificate'));

        $channelName = $request->query('channelName');
        if (!$channelName) {
            return response()->json(['error' => 'channelName is required.'], 400);
        }

        // Use 0 if not provided (Agora will assign an internal string UID if we use string, but let's stick to 0 for random int UID)
        $uid = (int) $request->query('uid', 0); 
        
        $role = $request->query('role', 'publisher');
        $expireTimeInSeconds = 3600 * 24; // 24 hours validity

        $agoraRole = \RtcTokenBuilder::RolePublisher;
        if ($role === 'subscriber') {
            $agoraRole = \RtcTokenBuilder::RoleSubscriber;
        }

        // RtcTokenBuilder (v1) uses Unix Timestamp for expiration!
        $currentTimestamp = time();
        $privilegeExpiredTs = $currentTimestamp + $expireTimeInSeconds;

        $token = \RtcTokenBuilder::buildTokenWithUid(
            $appId, 
            $appCertificate, 
            $channelName, 
            $uid, 
            $agoraRole, 
            $privilegeExpiredTs
        );

        return response()->json([
            'token' => $token,
            'channel' => $channelName,
            'uid' => $uid,
            'app_id' => $appId
        ]);
    }
}
