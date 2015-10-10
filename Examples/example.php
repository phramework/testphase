<?php

require __DIR__ . '/../vendor/autoload.php';

use \Phramework\Testphase\Testphase;
use \Phramework\Phramework;
use \Phramework\Models\Request;
use \Phramework\Validate\Object;
use \Phramework\Validate\String;
use \Phramework\Validate\Integer;

$jsonapiBaseResource = new Object(
    [
        'data' => new Object(
            [
                'type' => new String(),
                'id'  => new Integer(),
                'attributes'  => new Object()
            ],
            ['type', 'id']
        ),
        'links' => new Object(
            [
                'self' => new String(),
                'related' => new String()
            ],
            ['self']
        )
    ],
    ['data']
);

$requestHeaders = [
    'Authorization: Basic bm9ocG9uZXhAZ21haWwuY29tOjEyMzQ1Njc4eFg=',
    'Content-Type: application/vnd.api+json',
    'Accept: application/vnd.api+json'
];

$t = (new Testphase('account', Phramework::METHOD_GET, $requestHeaders))
    ->expectStatusCode(200)
    ->expectResponseHeader([
        Request::HEADER_CONTENT_TYPE => 'application/vnd.api+json;charset=utf-8'
    ])
    ->expectJSON()
    ->expectObject($jsonapiBaseResource);

$t->run(function (
    $responseStatusCode,
    $responseHeaders,
    $responseBody,
    $responseBodyObject
) {
    echo 'Response Status Code:' . PHP_EOL;
    echo $responseStatusCode . PHP_EOL;
    echo 'Response Headers:' . PHP_EOL;
    print_r($responseHeaders);
    echo PHP_EOL;
    echo 'Response Body:' . PHP_EOL;
    echo json_encode($responseBodyObject, JSON_PRETTY_PRINT) . PHP_EOL;


    echo 'Success!' . PHP_EOL;
});
