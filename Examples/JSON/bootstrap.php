<?php
use \Phramework\Testphase\Testphase;
use \Phramework\Testphase\TestParser;

Testphase::setBase('http://localhost/ostomate/api/');

TestParser::addGlobal(
    'headerRequestAuthorization',
    'Authorization: Basic bm9ocG9uZXhAZ21haWwuY29tOjEyMzQ1Njc4eFg='
);
TestParser::addGlobal(
    'headerRequestContentType',
    'Content-Type: application/vnd.api+json'
);
TestParser::addGlobal(
    'headerRequestAccept',
    'Accept: application/vnd.api+json'
);
TestParser::addGlobal(
    'headerResponseContentType',
    'application/vnd.api+json;charset=utf-8'
);

TestParser::addGlobal(
    'caretaker_request_id',
    8
);



/*$jsonapiBaseResource = new Object(
    [
        'data' => new Object(
            [
                'type' => new String(),
                'id'  => new UnsignedInteger(),
                'attributes'  => new Object()
            ],
            ['type', 'id']
        ),
        'links' => new Object(
            [
                'self' => new URL(),
                'related' => new URL()
            ],
            ['self']
        )
    ],
    ['data']
);*/
