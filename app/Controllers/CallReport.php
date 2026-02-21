<?php namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\RingbaCallModel;
use GuzzleHttp\Client;
use Google\Client as GoogleClient;
use Google\Service\Sheets as GoogleSheets;
use Google\Service\Sheets\ValueRange;
use GuzzleHttp\Exception\RequestException;


class CallReport extends Controller {

    public function __construct()
    {
       
    }

    public function index() {
        $model = new RingbaCallModel();
        $data['calls'] = $model->orderBy('timestamp','DESC')->findAll();
        return view('calls_view', $data);
    }

   



public function fetchRingbaCalls()
{
    $model      = new \App\Models\RingbaCallModel();
    $token      = getenv('ringba.token');
    $openaiKey  = getenv('openai.key');
    $accountId  = "RAaf74a45d14124eecb6fbbe591191c6e6";

        $client = new \GuzzleHttp\Client();
        $settingsModel = new \App\Models\IntegrationSettingsModel();

        $lastFetch = $settingsModel->getLastFetchTime();

        $reportStart = $lastFetch 
            ? gmdate("Y-m-d\TH:i:s\Z", strtotime($lastFetch))
            : gmdate("Y-m-d\TH:i:s\Z", strtotime("-1 day"));

        $reportEnd = gmdate("Y-m-d\TH:i:s\Z");

    try {
        // echo "1";exit;

        // 🔹 Fetch last 24 hours calls
     $response = $client->post("https://api.ringba.com/v2/$accountId/calllogs", [
            'headers' => [
                'Authorization' => "Token $token",
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json'
            ],
            'json' => [
                "reportStart" => $reportStart,
                "reportEnd"   => $reportEnd,
                "size"        => 100
            ]
        ]);

        $body  = $response->getBody()->getContents();
        $calls = json_decode($body);

        // echo "<pre>";print_r($calls->report->records);exit;
        $records = $calls->report->records;
        

        $deepgramKey = getenv('dipagram.key');

        



        if (empty($records)) {
            return "No calls found.";
        }

        foreach ($records as $call) {
            // echo "<pre>data";print_r($call);

            // 🔹 Skip duplicate calls
            if ($model->where('call_id', $call->inboundCallId)->first()) {
                continue;
            }

            $transcript = '';
            $summary    = '';
            $sentiment  = 'Neutral';
            $minDuration = 60;
            $fixedPayout = 18;
            $payout = "";
            $tier = 3; 

            // 🔹 Derive hasRecording flag
            // $hasRecording= "";
            $hasRecording = (!empty($call->recordingUrl) &&
                             filter_var($call->recordingUrl, FILTER_VALIDATE_URL));

            if (isset($call->hasConnected) && 
                $call->hasConnected == 1 &&
                isset($call->connectedCallLengthInSeconds) &&
                $call->connectedCallLengthInSeconds >= 60) {

                $payout = 18; // your fixed payout
            }else{
                $payout = 0;
            }

            if (isset($call->connectedCallLengthInSeconds)) {
                $duration = $call->connectedCallLengthInSeconds;
            } else {
                $duration = 0;
            }

            // Tier 1 → Long + Converted
            if ($duration >= 60 && !empty($call->hasPayout)) {
                $tier = 1;
            }
            // Tier 2 → Medium duration
            elseif ($duration >= 30) {
                $tier = 2;
            }
            // Tier 3 → Short / no conversion
            else {
                $tier = 3;
            }
            if (!empty($hasRecording)) {

                try {

                    $transResponse = $client->post(
                        'https://api.deepgram.com/v1/listen?model=nova-2&punctuate=true&sentiment=true&summarize=true&topics=true',
                        [
                            'headers' => [
                                'Authorization' => 'Token ' . $deepgramKey,
                                'Content-Type'  => 'application/json'
                            ],
                            'json' => [
                                'url' => $call->recordingUrl
                            ],
                            'timeout' => 60
                        ]
                    );

                    $result = json_decode($transResponse->getBody()->getContents(), true);

                    //  Transcript
                    $transcript = $result['results']['channels'][0]['alternatives'][0]['transcript'] ?? '';

                    //  Summary
                    $summary = $result['results']['summary']['short'] ?? '';

                    //  Sentiment score convert to label
                    $sentimentScore = $result['results']['sentiments']['average']['score'] ?? 0;

                    if ($sentimentScore > 0.2) {
                        $sentiment = "Positive";
                    } elseif ($sentimentScore < -0.2) {
                        $sentiment = "Negative";
                    } else {
                        $sentiment = "Neutral";
                    }

                    // ✅ Topics as bullet points
                    $topics = $result['results']['topics']['segments'] ?? [];

                    // echo "<h3>Transcript:</h3>$transcript<br><br>";
                    // echo "<h3>Summary:</h3>$summary<br><br>";
                    // echo "<h3>Key Topics:</h3>$sentiment";
                    // echo "<pre>topic";print_r($topics);
                    // foreach ($topics as $topic) {
                    //     echo "- " . $topic['topics'] . "<br>";
                    // }
                    if (!empty($topics)) {
                        foreach ($topics as $segment) {
                            if (!empty($segment['topics'])) {
                                foreach ($segment['topics'] as $t) {
                                    // echo "- " . ($t['topic'] ?? 'N/A') . "<br>";
                                }
                            }
                        }
                    } else {
                        echo "No topics detected.<br>";
                    }

                    // echo "<br><h3>Sentiment:</h3>$sentiment";

                } catch (\GuzzleHttp\Exception\RequestException $e) {

                    echo "<h3>Deepgram API Error</h3>";

                    if ($e->hasResponse()) {
                        echo "Status Code: " . $e->getResponse()->getStatusCode();
                        echo "<pre>";
                        echo $e->getResponse()->getBody()->getContents();
                        echo "</pre>";
                    } else {
                        echo $e->getMessage();
                    }
                }

            } else {

                $transcript = "No Recording Available";
                $summary    = "";
                $sentiment  = "Neutral";
            }

            

            // 🔹 Prepare DB Data
            $insertData = [
                'call_id'        => $call->inboundCallId,
                'timestamp'      =>  date('Y-m-d H:i:s'),
                'campaign_id'    => $call->campaignId ?? '',
                'campaign_name'  => $call->campaignName ?? '',
                'payout'         => $payout,
                'tier'           => $tier,
                'duration'       => $duration,
                'has_recording'  => $hasRecording ? 1 : 0,
                'sentiment'      => $sentiment,
                'summary'        => $summary,
                'transcript_url' => $call->recordingUrl ?? '',
                'raw_json'       => json_encode($call)
            ];

            // echo "<pre>";print_r($insertData);

            // // 🔹 Insert into DB
            $model->insert($insertData);
            $settingsModel->updateLastFetchTime(date('Y-m-d H:i:s'));

            // // 🔹 Append to Google Sheet
            $this->appendGoogleSheet($insertData);
        }

        return "Fetch complete.";

    } catch (\Exception $e) {
        log_message('error', 'Fetch Error: ' . $e->getMessage());
        return "Error occurred while fetching calls.";
    }
}

private function appendGoogleSheet($data) {

        try {
            // Initialize Google Client
            $googleClient = new GoogleClient();
            $googleClient->setAuthConfig(APPPATH . 'credentials.json');
            $googleClient->addScope(GoogleSheets::SPREADSHEETS);

            $service = new GoogleSheets($googleClient);
            $spreadsheetId = getenv('google.sheet_id'); // or hard-code
            $sheetName = "Sheet1";

            // -----------------------------
            // Add Header Row (bold, black, big font)
            // -----------------------------
            $header = ["Timestamp", "Campaign Name", "Payout", "Tier", "Duration", "Has Recording", "Sentiment", "Summary", "Transcript URL", "Raw JSON"];

            // First row
            $body = new ValueRange(['values' => [$header]]);
            $params = ['valueInputOption' => 'RAW'];
            $service->spreadsheets_values->update($spreadsheetId, $sheetName . '!A1:J1', $body, $params);

            // -----------------------------
            // Format Header row
            // -----------------------------
            $requests = [
                // Bold + font size 12-14
                new \Google\Service\Sheets\Request([
                    'repeatCell' => [
                        'range' => [
                            'sheetId' => 0, // default first sheet
                            'startRowIndex' => 0,
                            'endRowIndex' => 1
                        ],
                        'cell' => [
                            'userEnteredFormat' => [
                                'backgroundColor' => ['red'=>0,'green'=>0,'blue'=>0],
                                'horizontalAlignment' => 'CENTER',
                                'textFormat' => [
                                    'foregroundColor' => ['red'=>1,'green'=>1,'blue'=>1],
                                    'fontSize' => 14,
                                    'bold' => true
                                ]
                            ]
                        ],
                        'fields' => 'userEnteredFormat(backgroundColor,textFormat,horizontalAlignment)'
                    ]
                ]),
                // Auto resize columns
                new \Google\Service\Sheets\Request([
                    'autoResizeDimensions' => [
                        'dimensions' => [
                            'sheetId' => 0,
                            'dimension' => 'COLUMNS',
                            'startIndex' => 0,
                            'endIndex' => count($header)
                        ]
                    ]
                ])
            ];

            $batchUpdateRequest = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
                'requests' => $requests
            ]);
            $service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);

            // -----------------------------
            //  Append Data Row
            // -----------------------------
            $values = [[
                $data['timestamp'],
                $data['campaign_name'],
                $data['payout'],
                $data['tier'],
                $data['duration'],
                $data['has_recording'] ? 'Yes' : 'No',
                $data['sentiment'],
                $data['summary'],
                $data['transcript_url'],
                $data['raw_json']
            ]];

            $body = new ValueRange(['values' => $values]);
            $service->spreadsheets_values->append($spreadsheetId, $sheetName . '!A2:J2', $body, ['valueInputOption' => 'RAW']);

            // echo "Data added successfully with header formatting!";

        } catch (\Exception $e) {
            echo "Google Sheets Error: " . $e->getMessage();
            exit;
        }


}


}
