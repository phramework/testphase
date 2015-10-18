<?php
use \Phramework\Testphase\Testphase;
use \Phramework\Testphase\TestParser;
use \Phramework\Validate\Object;
use \Phramework\Validate\String;
use \Phramework\Validate\UnsignedInteger;
use \Phramework\Validate\ArrayValidator;
use \Phramework\Validate\URL;

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

TestParser::addGlobal(
    'responseBodyJsonapiResource',
    TestParser::getResponseBodyJsonapiResource()
);

TestParser::addGlobal(
    'responseBodyJsonapiCollection',
    TestParser::getResponseBodyJsonapiCollection()
);
