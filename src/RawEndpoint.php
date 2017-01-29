<?php

namespace Phramework\Testphase;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Psr7\Request;
use Phramework\JSONAPI\Client\Endpoint;
use Phramework\JSONAPI\Client\Exceptions\ResponseException;

class RawEndpoint extends Endpoint
{
    public function raw(string $method, string $body = null) {
        $url = $this->url;

        $client = new \GuzzleHttp\Client([]);

        $request = (new Request(
            $method,
            $url,
            [],
            $body
        ));

        //Add headers
        foreach ($this->headers as $header => $values) {
            $request = $request->withAddedHeader(
                $header,
                $values
            );
        }

        try {
            $response = $client->send($request);
        } catch (BadResponseException $exception) {
            throw new ResponseException(
                new \Phramework\Testphase\Response($exception->getResponse())
            );
        }

        return (new \Phramework\Testphase\Response(($response)));
    }
}
