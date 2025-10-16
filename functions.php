<?php

/** Log Function Notion & Quantium */
function NotionLog($message, $status, $data = null)
{
    $date = (new DateTime())->format('Y-m-d');
    // $date = "15-07-2025";
    $logFolder = __DIR__ . "/logs/$date/Notion";

    if (!file_exists($logFolder)) {
        mkdir($logFolder, 0755, true);
    }

    $logFileName = strtolower($status) === 'success' ? 'success.json' : 'error.json';
    $logFile = "$logFolder/$logFileName";

    $logEntry = [
        'timestamp' => (new DateTime())->format('Y-m-d H:i:s'),
        'status' => $status,
        'message' => $message,
    ];

    if ($data !== null) {
        $logEntry['data'] = $data;
    }

    $logs = [];
    if (file_exists($logFile)) {
        $logs = json_decode(file_get_contents($logFile), true);
        if (!is_array($logs)) {
            $logs = [];
        }
    }

    $logs[] = $logEntry;

    file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT));
}

function QuantiumLog($message, $status, $data = null)
{
    $date = (new DateTime())->format('Y-m-d');
    // $date = "2025-09-09";
    $logFolder = __DIR__ . "/logs/$date/Quantium";

    if (!file_exists($logFolder)) {
        mkdir($logFolder, 0755, true);
    }

    $logFileName = strtolower($status) === 'success' ? 'success.json' : 'error.json';
    $logFile = "$logFolder/$logFileName";

    $logEntry = [
        'timestamp' => (new DateTime())->format('Y-m-d H:i:s'),
        'status' => $status,
        'message' => $message,
    ];

    if ($data !== null) {
        $logEntry['data'] = $data;
    }

    $logs = [];
    if (file_exists($logFile)) {
        $logs = json_decode(file_get_contents($logFile), true);
        if (!is_array($logs)) {
            $logs = [];
        }
    }

    $logs[] = $logEntry;

    file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT));
}

/** Function to remove space */
function normalizeName($name)
{
    $name = strtolower(trim($name));
    $name = preg_replace('/[\s\-\(\)\.]/', '', $name);
    $name = preg_replace('/[\x{00AD}\x{200B}-\x{200D}\x{FEFF}]+/u', '', $name);
    return $name;
}

function cleanString($str)
{
    $str = preg_replace('/[\x{00AD}\x{200B}-\x{200D}\x{FEFF}]/u', '', $str);
    return trim($str);
}

/** Notion Functions */
/** Latest Access token of Notion */
function getNotionAccessToken($token)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.notion.com/v1/oauth/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => "{
                                'grant_type': 'refresh_token',
                                'refresh_token': $token
                            }",
        CURLOPT_HTTPHEADER => array(
            'Authorization: Basic MjJhZDg3MmItNTk0Yy04MDQ1LWIxYzUtMDAzN2ZhNTA4Y2U2OnNlY3JldF9ucFZYdW9CSDBtdFpwQjVZYlVhV0wwRzVEZjVmTFlnU0t0T0lsMlBSc1VZ',
            'Notion-Version: 2025-07-08',
            'Content-Type: application/json',
            'Accept: application/json',
            'Cookie: __cf_bm=NO8tPOeJ78G5ugCxoI6tz15aQ1PtotpDwovrdYTiwD4-1758601680-1.0.1.1-0Sng9.fYkK0tVX3IFXtoG6uHkmlWOByyhTd0jpW.rUaAG38C7Y8PxPVLWirVx.y364JynXFol0vmRwzLNb1qmwez4A5HcKVLW6BcsmiV1nI; _cfuvid=rt2HI14uIjgRARya0BpYcgULgQptrJOTYvLBriuwriI-1758601680606-0.0.1.1-604800000'
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    $decoderesponse = json_decode($response, true);
    $accessToken = $decoderesponse['access_token'];
    $refreshToken = $decoderesponse['refresh_token'];

    if ($accessToken) {
        file_put_contents('AccessToken.txt', $accessToken);
    }

    if ($refreshToken) {
        file_put_contents('RefreshToken.txt', $refreshToken);
    }

    return $accessToken;
}

/** To get Notion data by Page ID */
function getNotionData($token, $pageID)
{
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.notion.com/v1/pages/$pageID",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer $token",
            'Notion-Version: 2022-06-28',
            'Cookie: __cf_bm=VQ7gEVoTVwdzdLeL0a_qutsWHEVxxdDDYDCPMSRoeZo-1752118117-1.0.1.1-ak57KP_bL0AuRZPRBtQhrbLF15mOmOTkEV3QEkD4Ov4PawlG81n8ZDxcglzrwFvYydsoFFZ5wMk13n9NclXTv05GB5ftGpTJLmAZfCFb3.M; _cfuvid=STBFThfwtMm8O964WG7923zjd1yqHoaHxTqlpQM0OEo-1752118117830-0.0.1.1-604800000'
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    $decoderesponse = json_decode($response, true);
    return $decoderesponse;
}

/** Get User data by ID from Notion */
function getNotionUser($token, $id)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.notion.com/v1/users/$id",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer $token",
            'Notion-Version: 2022-06-28',
            'Cookie: __cf_bm=9sHEQO52x4sxsvbVK3cPjDYBugedc6S9XReo.g_1S4Y-1752553280-1.0.1.1-G5m8mzA0SOI39SF2fl7DAPYVe9PvLdwhiDvYG5hPIxRvI38vgUIhPog.MzxB5RFWBZSTlACRrt5OF0yL0qDsrg0XjZzSy3xdAEelficGQQI; _cfuvid=mSJnyKrtLZzvVL62SWE7_bnI3A1G9PIU1ZDmH_57hKE-1752553280056-0.0.1.1-604800000'
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    $decoderesponse = json_decode($response, true);
    return $decoderesponse;
}

/** Get all pages data from Notion */
function getPagesID($token)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.notion.com/v1/databases/4fe17a523b0649e1b707a7f74b994647/query',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer $token",
            'Notion-Version: 2022-06-28',
            'Content-Type: application/json',
            'Cookie: __cf_bm=a1ALzOT5T7WJSSlYKTwGNimNEjF0N8ZB3gJy23jOpuM-1752635809-1.0.1.1-LA6tdI6rRgt7_e8pNis8Nun0VEf_ppvfCjBGPSEdNy7WVFbyNUahEY7_5kIbVDMoSKkk1lfZ8Tzc.bSFHdxPOZUwdOqHxOU91EKR6wUUiy0; _cfuvid=l1Y8ggwh306orWs9KerCQEeDV20b0y.G.H0285FHKjI-1752634693853-0.0.1.1-604800000'
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    $decoderesponse = json_decode($response, true);
    return $decoderesponse;
}

/** Update data in Notion */
function updatePage($token, $pageId, $properties)
{
    $url = "https://api.notion.com/v1/pages/" . $pageId;

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'PATCH',
        CURLOPT_POSTFIELDS => json_encode(["properties" => $properties]),
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $token",
            'Notion-Version: 2022-06-28',
            'Content-Type: application/json',
        ],
    ]);

    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, true);
}


/** Quantium Functions */
/** To get Quantium Token */
function getQuantiumtoken()
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://accounts-quantium.azurewebsites.net/api/auth/password/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => '{
                                "email": "api.support@quantium.pe",
                                "password": "aajSULcRJ$p-3Sr"
                                }',
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'Content-Type: application/json',
            'Cookie: .AspNetCore.Identity.Application=CfDJ8MElgMU0-YRDjDVrHsvx7fbsIPtRit8pXckP8wArremzVW0aT_DCEmGOLfmZnm2jwQ79mhyLVw29XIIldYrKEAnacisaK7IAvlKY8wqN1j4yTpSFHUZKpMBz03XRjSADwR_qqTsjI-RrFbhVp8GXgHXfW8w4HjnL937hBr5JTq7OU7USXgwiWGiQHW6je6LV6Sxt5a0VszlpG5ReiQqgEo0qGbHUdIJf8pL93EG5DBcXWEG229y8bSGOkl7owhgeEsIqfW4gCdhQf8Rg2DwAEXvJlGI0nMIdChthHb65UaGnpTE3h-j5_gieqb2_0oVT1dlO0MgTMFO4KZQwjqbfAL4MHrXWxaDppgKov0k2VzrJAY22l2q94w_Qjd97lYsooSObVrTvb2EIUFDbuU6KPARoTXvwU6UtwQQjgkYd0GHtMUDAbFpzjYjebNJEwthkYKXBB7SaEk2kXsVMR7w6XOjqe2FzkNK1t6agNeOySVQMrZH-0DTbWI8kM_OBlAN5xUJpPQPhB-FG8AVnXAxDvntMSbk2HvftMzoAT1L5vr7P5uCkRLMydw8xqAJF_f1uqMTo41AyqKtmlMy-W3AHhpazYZUEoa3xcI6AM06OjpWhgtFKdeKIf0n5mkeJ42OQPITnJV3sKbAIUf6PQ6bCYZhpScq5fXpc0bA9ASygIRJRjE_zMHPCA5ZRusDbTozgJHj4eBAxpiTtqgEPmv6W0hynfVgcDnwhd5pWuBNTrBxufuoeZ1xQox8bTkErVFTLympm06hYc1wx1e2KEjlKDpaBANmPf98K7cefs9jf37RzI7AnTLkh3LGbVtuhAo3kdchUEmB3jb7Te9_kwSd83tEV386VYr2bUULlbT5EqzqmekdPgEMv1GLZPFDnBc6kwZbmC2Y2jg7daSJUvH-uWydNl4RNJTRjmS9ATa2nmHjPcvSGCQ3jdsb22uvwNxmhWeSbrIRl6gUU0gJQrEFq9NGYVK6AvN8GnBFKn9PATtkl4eg9275T4UOYTrGPaBPP2opdT8whiXCDEqU08Pyquy5MivVj4BgFDS_cwajeOLofB5l-iT6fePYs5lVzCbtB6_Y0D95XfSo0P6u91Q2m_VZYyIxD2HnTIud_s-Sv8BpDqB7xOW06Oos0Te72dXjdBfUtz--c03GjLFBEaz5EhajdItN-KhNFJWHf1lVNNBmSbXnAWhEIJU4e6LgURQaw-tYT8YP9bgLcY3f8C9NyjDK-eCIdNmPDms6rqUvEQGjmPoznBZMeMbNHjoaTXaOYGhT5edUad32Brs_nTjBwvP4pPcNA-pjxIJ6xhvHyRBBoDWC3Qa8U8yDo4xreP1bqtbwVTAMgn5h1YN-r8lB18qARC1Lg_aCrteTd1YkvatOEA4oYJIeVhFS7z9yE3jf_TYxqzQzjXmh1V96HSWi808gNQ4e3aABIzKFXst6Q8UkpplggA_CNtASCK00P4L15DQYS0RqXQd19gUIMRuLsw2FwpItfxJuxtTXPdY2bWevpvwL8OQJRpenuq7V5N922-A; idsrv.session=E55B3A3A5BDFCB7DCC06BAD124946667'
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    $decoderesponse = json_decode($response, true);
    $accessToken = $decoderesponse['token'];

    if ($accessToken) {
        file_put_contents('quantiumToken.txt', $accessToken);
    }

    return $accessToken;
}

/** To get Portfolio/Assets from Quantium */
function getListAssets($token)
{
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://quantiumfundwebapi.azurewebsites.net/api/entities/asset/list/all/2025-07-10',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            "Authorization: $token",
            'Content-Type: application/json',
            'Accept: application/json',
            'Cookie: ARRAffinity=ebe8e766413c77e0e2665665be2695e1bb4877302c90f4e6968a208f631c2c93; ARRAffinitySameSite=ebe8e766413c77e0e2665665be2695e1bb4877302c90f4e6968a208f631c2c93'
        ),
    ));

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    $decodedResponse = json_decode($response, true);
    if ($httpCode === 200 && !empty($decodedResponse)) {
        return $decodedResponse;
    }
    return ['code' => $httpCode];
}

/** Get Partner & Board from Quantium */
function getTableData($token)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://quantiumfundwebapi.azurewebsites.net/api/field/alltypes',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            "Authorization: $token",
            'Cookie: ARRAffinity=ebe8e766413c77e0e2665665be2695e1bb4877302c90f4e6968a208f631c2c93; ARRAffinitySameSite=ebe8e766413c77e0e2665665be2695e1bb4877302c90f4e6968a208f631c2c93'
        ),
    ));

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    $decodedResponse = json_decode($response, true);
    if ($httpCode === 200 && !empty($decodedResponse)) {
        return $decodedResponse;
    }
    return ['code' => $httpCode];
}

/** Get All Assets Status from Quantium */
function getStatus($token)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://quantiumfundwebapi.azurewebsites.net/api/entities/asset/status',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            "Authorization: $token",
            'Accept: application/json',
            'Cookie: ARRAffinity=ebe8e766413c77e0e2665665be2695e1bb4877302c90f4e6968a208f631c2c93; ARRAffinitySameSite=ebe8e766413c77e0e2665665be2695e1bb4877302c90f4e6968a208f631c2c93'
        ),
    ));

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    $decodedResponse = json_decode($response, true);
    if ($httpCode === 200 && !empty($decodedResponse)) {
        return $decodedResponse;
    }
    return ['code' => $httpCode];
}

/** To put Assets in Quantium */
function putAssets($token, $scopeID, $assetsID, $data)
{
    $url = "https://quantiumfundwebapi.azurewebsites.net/api/entities/asset/$scopeID/$assetsID";
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
            "Authorization: $token",
            'Content-Type: application/json',
            'Accept: application/json',
            'Cookie: ARRAffinity=ebe8e766413c77e0e2665665be2695e1bb4877302c90f4e6968a208f631c2c93; ARRAffinitySameSite=ebe8e766413c77e0e2665665be2695e1bb4877302c90f4e6968a208f631c2c93'
        ),
    ));

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    $decodedResponse = json_decode($response, true);
    if ($httpCode === 200 && !empty($decodedResponse)) {
        return $decodedResponse;
    }
    return ['code' => $httpCode];
}

/** Function to get the Valuation from Quantium */
function getCoreValue($token, $assetsID)
{
    $url = "https://quantiumfundwebapi.azurewebsites.net/api/summary/asset/1/$assetsID/2025-09-08/USD";
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            "Authorization: $token",
            'Content-Type: application/json',
            'Accept: application/json',
            'Cookie: ARRAffinity=e7ed6179fcc415c959a66e79451ad8abe928124f351cb1184802b2d1a939665c; ARRAffinitySameSite=e7ed6179fcc415c959a66e79451ad8abe928124f351cb1184802b2d1a939665c'
        ),
    ));

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    $decodedResponse = json_decode($response, true);
    if ($httpCode === 200 && !empty($decodedResponse)) {
        return $decodedResponse;
    }
    return ['code' => $httpCode];
}

function formatMetricLatestMonth($dateString)
{
    try {
        $timestamp = strtotime($dateString);
        if ($timestamp === false) {
            return $dateString;
        }
        return date("F Y", $timestamp);
    } catch (Exception $e) {
        return $dateString;
    }
}
