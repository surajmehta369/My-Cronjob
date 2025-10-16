<?php
echo "<style> 
        body {
            font-size: 16px;
        }
        </style>";

include "functions.php";

$refreshToken = file_get_contents('RefreshToken.txt');
$notiontoken = file_get_contents('AccessToken.txt');
$quantiumtoken = file_get_contents('quantiumToken.txt');

// $notion = file_get_contents('notionContent.txt');
// $notionDecode = json_decode($notion, true);

// $id = $notionDecode['data']['id'];

// $cleanId = str_replace("-", "", $id);

// $notionData = getNotionData($notiontoken, $cleanId);
// if ((isset($notionData['status']) && $notionData['status'] == 401) && (isset($notionData['code']) && $notionData['code'] == 'unauthorized')) {
//     $token = getNotionAccessToken($refreshToken);
//     $notionData = getNotionData($token, $cleanId);
// }

$pageID = "22d04a94cc7c80fca70cddec01ab2324"; //DemoSuraj
$notionData = getNotionData($notiontoken, $pageID);
if ((isset($notionData['status']) && $notionData['status'] == 401) && (isset($notionData['code']) && $notionData['code'] == 'unauthorized')) {
    $token = getNotionAccessToken($refreshToken);
    $notionData = getNotionData($token, $pageID);
}

$notionFund = $notionData['properties']['Fund']['rich_text'][0]['text']['content'];
$notionName = $notionData['properties']['Company']['title'][0]['text']['content'];
$notionPriority = $notionData['properties']['Priority']['select']['name'];
$notionSupport = $notionData['properties']['Level of Support/ Touch']['select']['name'];
$notionStatus = $notionData['properties']['Status']['select']['name'];
$notionGGVAss = $notionData['properties']['GGV Assessment']['select']['name'];
$notionRaiseShd = $notionData['properties']['Next Raise Schedule']['select']['name'];
$notionCoverage = $notionData['properties']['D&O Coverage']['date']['start'];
$notionPartner = $notionData['properties']['Partner']['people'][0]['name'];
$notionBoard = $notionData['properties']['Board (Seat or Observer)']['people'][0]['name'];
$notionContactID = $notionData['properties']['POC']['people'][0]['id'];

$listAssets = getListAssets($quantiumtoken);
if (isset($listAssets['code']) && $listAssets['code'] == 401) {
    $token = getQuantiumtoken();
    $listAssets = getListAssets($token);
}

$matchedData = array_filter($listAssets, function ($item) use ($notionName) {
    return isset($item['name']) && strtolower(trim($item['name'])) == strtolower(trim($notionName));
});

$matchedData = array_values($matchedData);

if (empty($matchedData)) {
    NotionLog("No matching asset found for Notion company name: $notionName", "error", $listAssets);
    return;
}

$assetsID = $matchedData[0]['internalId'];

$tableData = getTableData($quantiumtoken);
if (isset($tableData['code']) && $tableData['code'] == 401) {
    $token = getQuantiumtoken();
    $tableData = getTableData($token);
}

$filteredData = [];

foreach ($tableData as $item) {
    if (isset($item['label']) && in_array($item['label'], ['Partner', 'Board'])) {
        $label = $item['label'];
        $options = [];

        if (!empty($item['optionItems'])) {
            foreach ($item['optionItems'] as $option) {
                if (isset($option['value'])) {
                    $options[] = $option['value'];
                }
            }
        }

        $filteredData[$label] = $options;
    }
}

$allPartners = $filteredData['Partner'];
$allBoard = $filteredData['Board'];

if (in_array($notionPartner, $allPartners)) {
    $matchedPartner = $notionPartner;
} else {
    NotionLog("No matching Partner found for: $notionPartner", "error");
    return;
}

if (in_array($notionBoard, $allBoard)) {
    $matchedBoard = $notionBoard;
} else {
    NotionLog("No matching Board member found for: $notionBoard", "error");
    return;
}

$notionUser = getNotionUser($notiontoken, $notionContactID);
if ((isset($notionUser['status']) && $notionUser['status'] == 401) && (isset($notionUser['code']) && $notionUser['code'] == 'unauthorized')) {
    $token = getNotionAccessToken($refreshToken);
    $notionUser = getNotionUser($token, $notionContactID);
}
$POC = $notionUser['name'];

if (empty($POC)) {
    NotionLog("No matching user found for Notion company name: $notionName", "error", $notionUser);
    return;
}

$allStatus = getStatus($quantiumtoken);
if (isset($allStatus['code']) && $allStatus['code'] == 401) {
    $token = getQuantiumtoken();
    $allStatus = getStatus($token);
}

$statusId = null;

foreach ($allStatus as $status) {
    if (isset($status['label']) && trim(strtolower($status['label'])) == trim(strtolower($notionStatus))) {
        $statusId = $status['internalId'];
        break;
    }
}

if (empty($statusId)) {
    NotionLog("No matching status found for Notion company name: $notionName", "error", $allStatus);
    return;
}

$quantiumData = [
    "name" => $matchedData[0]['name'],
    "sectorId" => $matchedData[0]['sectorId'],
    "industryId" => $matchedData[0]['industryId'],

    "custom_1562684242606" => $notionPriority,
    "custom_1627608235905" => $notionSupport,
    "statusInternalId" => $statusId,
    "custom_1699781611656" => $notionGGVAss,
    "custom_1562684973920" => $notionRaiseShd,
    "custom_1632298923768" => $notionCoverage,
    "custom_1562684267539" => $matchedPartner,
    "custom_1562684658198" => $matchedBoard,
    "custom_1591114711948" => $POC
];

$fundData = [
    2 => "5dc63bf6fe453e5704554c06",
    3 => "5dc63bf6fe453e5704554c05",
    4 => "61dc17457377e2eef1e5982c",
];

if (!array_key_exists($notionFund, $fundData)) {
    NotionLog("No matching internalId for notion fund ID: $notionFund", "error");
    return;
}

$scopeID = $fundData[$notionFund];

$putData = putAssets($quantiumtoken, $scopeID, $assetsID, $quantiumData);
if (isset($putData['code']) && $putData['code'] == 401) {
    $token = getQuantiumtoken();
    $putData = putAssets($token, $scopeID, $assetsID, $quantiumData);
}

if (isset($putData['code']) && $putData['code'] != 200) {
    NotionLog("Failed to update asset for $notionName", "error", $putData);
} else {
    NotionLog("Successfully updated asset for $notionName", "success", $quantiumData);
}
