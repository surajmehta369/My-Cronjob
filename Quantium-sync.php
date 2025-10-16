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

$pageData = getPagesID($notiontoken);
if ((isset($pageData['status']) && $pageData['status'] == 401) && (isset($pageData['code']) && $pageData['code'] == 'unauthorized')) {
    $token = getNotionAccessToken($refreshToken);
    $pageData = getPagesID($token);
}

if (!isset($pageData['results'])) {
    QuantiumLog("Failed to fetch page data or 'results' missing", "error", $pageData);
    exit;
}

$pageIds = [];

foreach ($pageData['results'] as $page) {
    if (isset($page['id'])) {
        $pageIds[] = $page['id'];
    }
}

$results = [];

foreach ($pageIds as $pageID) {
    $notionData = getNotionData($notiontoken, $pageID);
    if ((isset($notionData['status']) && $notionData['status'] == 401) && (isset($notionData['code']) && $notionData['code'] == 'unauthorized')) {
        $token = getNotionAccessToken($refreshToken);
        $notionData = getNotionData($token, $pageID);
    }

    $notionName = $notionData['properties']['Company']['title'][0]['text']['content'];
    $notionFund = $notionData['properties']['Fund']['rich_text'][0]['text']['content'];

    if (empty($notionName) || empty($notionFund)) {
        continue;
    }

    $results[] = [
        'page_id' => $pageID,
        'name' => $notionName,
        'fund' => $notionFund,
    ];
}

// $filtered = [];

$filtered = [
    // ["Shohoz", "01a41b1a-9f3a-48f1-8477-11458b0ac0dc"],
    // ["Moneysmart", "0cef5c08-f463-40d4-9355-0d53ec826b69"],
    ["ABC (Suraj) - For Demo", "22d04a94cc7c80fca70cddec01ab2324"],
];

// foreach ($results as $item) {
//     if (in_array($item['fund'], ['2', '3', '4'])) {
//         $filtered[] = [
//             $item['name'],
//             $item['page_id']
//         ];
//     }
// }

$listAssets = getListAssets($quantiumtoken);
if (isset($listAssets['code']) && $listAssets['code'] == 401) {
    $token = getQuantiumtoken();
    $listAssets = getListAssets($token);
}

$notionNames = array_map(function ($item) {
    $name = isset($item[0]) ? $item[0] : null;
    return $name ? normalizeName($name) : null;
}, $filtered);

$notionNames = array_filter($notionNames);
$notionNames = array_unique($notionNames);

$matchedAssets = array_filter($listAssets, function ($asset) use ($notionNames) {
    if (!isset($asset['name'])) return false;
    $normalizedAssetName = normalizeName($asset['name']);
    return in_array($normalizedAssetName, $notionNames);
});

$allAssetNames = array_map(function ($asset) {
    return $asset['name'] ?? '';
}, $listAssets);

$unmatchedNames = array_diff($notionNames, array_map('normalizeName', $allAssetNames));

if (!empty($unmatchedNames)) {
    QuantiumLog("Unmatched Notion names", "error", $unmatchedNames);
}

if (empty($matchedAssets)) {
    QuantiumLog("No matched assets found", "error", $listAssets);
}

$matchedAssets = array_values(array_map(function ($asset) {
    return array_map(function ($value) {
        return is_string($value) ? cleanString($value) : $value;
    }, $asset);
}, $matchedAssets));

$allCoreData = [];

foreach ($matchedAssets as $item) {
    $name = $item['name'];
    $number = $item['internalId'];

    $coredata = getCoreValue($quantiumtoken, $number);
    if (isset($coredata['code']) && $coredata['code'] == 401) {
        $token = getQuantiumtoken();
        $coredata = getCoreValue($token, $number);
    }

    $allCoreData[] = [
        "name" => $coredata['assetName'],
        "totalRemainingFmv" => $coredata['totalRemainingFmv']
    ];
}

$totalRemainingFmvMap = [];
foreach ($allCoreData as $coreItem) {
    $normalizedName = normalizeName($coreItem['name']);
    $totalRemainingFmvMap[$normalizedName] = $coreItem['totalRemainingFmv'];
}

$notionDataList = [];

foreach ($matchedAssets as $asset) {
    $databaseId = null;

    foreach ($filtered as $filterItem) {
        if (isset($filterItem[0]) && normalizeName($filterItem[0]) == normalizeName($asset['name'])) {
            $databaseId = $filterItem[1];
            break;
        }
    }

    if (!$databaseId) {
        QuantiumLog("Database ID not found for asset: " . $asset['name'], "error", $asset);
        continue;
    }

    $formattedMetricLatestMonth = formatMetricLatestMonth($asset['metricLatestMonth']);

    $normalizedAssetName = normalizeName($asset['name']);
    $valuationNumber = isset($totalRemainingFmvMap[$normalizedAssetName]) ? $totalRemainingFmvMap[$normalizedAssetName] : 0;

    $notionData = [
        "parent" => [
            "database_id" => $databaseId
        ],
        "properties" => [
            "Valuation" => [
                "number" => isset($valuationNumber) ? $valuationNumber : 0
            ],
            "Invested Capital" => [
                "number" => isset($asset['investedToDate']) ? $asset['investedToDate'] : null
            ],
            "Metric - Latest Available Period" => [
                "rich_text" => [
                    [
                        "text" => [
                            "content" => isset($formattedMetricLatestMonth) ? $formattedMetricLatestMonth : ""
                        ]
                    ]
                ]
            ],
            "Last Audited FS Received" => [
                "rich_text" => [
                    [
                        "text" => [
                            "content" => isset($asset['custom_1679894671168']) ? $asset['custom_1679894671168'] : ""
                        ]
                    ]
                ]
            ],
            "Shareholding" => [
                "rich_text" => [
                    [
                        "text" => [
                            "content" => isset($asset['percentOwnership']) ? $asset['percentOwnership'] : ""
                        ]
                    ]
                ]
            ],
            "Last Raise" => [
                "rich_text" => [
                    [
                        "text" => [
                            "content" => isset($asset['lastRaise']) ? $asset['lastRaise'] : ""
                        ]
                    ]
                ]
            ],
            "Last Post Money" => [
                "rich_text" => [
                    [
                        "text" => [
                            "content" => isset($asset['latestPostMoney']) ? $asset['latestPostMoney'] : ""
                        ]
                    ]
                ]
            ]
        ]
    ];

    $notionDataList[] = $notionData;
}

QuantiumLog("Processed and mapped data successfully", "success", $notionDataList);

$allResponses = [];

foreach ($notionDataList as $item) {
    $pageId     = $item['parent']['database_id'];
    $properties = $item['properties'];

    $response = updatePage($notiontoken, $pageId, $properties);
    if ((isset($response['status']) && $response['status'] == 401) && (isset($response['code']) && $response['code'] == 'unauthorized')) {
        $token = getNotionAccessToken($refreshToken);
        $response = updatePage($token, $pageId, $properties);
    }

    QuantiumLog("Notion API response for Page ID: $pageId", "info", $response);

    $allResponses[$pageId] = $response;
}

echo "<pre>";
print_r($allResponses);
echo "</pre>";
