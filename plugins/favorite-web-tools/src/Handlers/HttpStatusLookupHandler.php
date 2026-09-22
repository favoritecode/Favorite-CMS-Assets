<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Handlers;

use FavoriteCMS\Tools\Support\ResultType;

class HttpStatusLookupHandler extends AbstractToolHandler
{
    protected array $statuses = [
        100 => ['message' => 'Continue', 'class' => '1xx Informational', 'desc' => 'The server has received the request headers and the client should proceed to send the request body.'],
        101 => ['message' => 'Switching Protocols', 'class' => '1xx Informational', 'desc' => 'The requester has asked the server to switch protocols and the server has agreed to do so.'],
        102 => ['message' => 'Processing', 'class' => '1xx Informational', 'desc' => 'The server has received and is processing the request, but no response is available yet.'],
        103 => ['message' => 'Early Hints', 'class' => '1xx Informational', 'desc' => 'Used to return some response headers before final HTTP message.'],
        200 => ['message' => 'OK', 'class' => '2xx Success', 'desc' => 'Standard response for successful HTTP requests.'],
        201 => ['message' => 'Created', 'class' => '2xx Success', 'desc' => 'The request has been fulfilled, resulting in the creation of a new resource.'],
        202 => ['message' => 'Accepted', 'class' => '2xx Success', 'desc' => 'The request has been accepted for processing, but the processing has not been completed.'],
        204 => ['message' => 'No Content', 'class' => '2xx Success', 'desc' => 'The server successfully processed the request, and is not returning any content.'],
        206 => ['message' => 'Partial Content', 'class' => '2xx Success', 'desc' => 'The server is delivering only part of the resource due to a Range header sent by the client.'],
        301 => ['message' => 'Moved Permanently', 'class' => '3xx Redirection', 'desc' => 'This and all future requests should be directed to the given URI.'],
        302 => ['message' => 'Found', 'class' => '3xx Redirection', 'desc' => 'Tells the client to look at (browse to) another URL temporarily.'],
        303 => ['message' => 'See Other', 'class' => '3xx Redirection', 'desc' => 'The response to the request can be found under another URI using the GET method.'],
        304 => ['message' => 'Not Modified', 'class' => '3xx Redirection', 'desc' => 'Indicates that the resource has not been modified since the version specified by the request headers.'],
        307 => ['message' => 'Temporary Redirect', 'class' => '3xx Redirection', 'desc' => 'In this case, the request should be repeated with another URI; however, future requests should still use the original URI.'],
        308 => ['message' => 'Permanent Redirect', 'class' => '3xx Redirection', 'desc' => 'The request and all future requests should be repeated using another URI.'],
        400 => ['message' => 'Bad Request', 'class' => '4xx Client Error', 'desc' => 'The server cannot or will not process the request due to an apparent client error.'],
        401 => ['message' => 'Unauthorized', 'class' => '4xx Client Error', 'desc' => 'Similar to 403 Forbidden, but specifically for use when authentication is required and has failed or has not yet been provided.'],
        403 => ['message' => 'Forbidden', 'class' => '4xx Client Error', 'desc' => 'The request contained valid data and was understood by the server, but the server is refusing action.'],
        404 => ['message' => 'Not Found', 'class' => '4xx Client Error', 'desc' => 'The requested resource could not be found but may be available in the future.'],
        405 => ['message' => 'Method Not Allowed', 'class' => '4xx Client Error', 'desc' => 'A request method is not supported for the requested resource.'],
        408 => ['message' => 'Request Timeout', 'class' => '4xx Client Error', 'desc' => 'The server timed out waiting for the request.'],
        409 => ['message' => 'Conflict', 'class' => '4xx Client Error', 'desc' => 'Indicates that the request could not be processed because of conflict in the current state of the resource.'],
        410 => ['message' => 'Gone', 'class' => '4xx Client Error', 'desc' => 'Indicates that the resource requested is no longer available and will not be available again.'],
        413 => ['message' => 'Payload Too Large', 'class' => '4xx Client Error', 'desc' => 'The request is larger than the server is willing or able to process.'],
        415 => ['message' => 'Unsupported Media Type', 'class' => '4xx Client Error', 'desc' => 'The request entity has a media type which the server or resource does not support.'],
        418 => ['message' => "I'm a teapot", 'class' => '4xx Client Error', 'desc' => 'The server refuses the attempt to brew coffee with a teapot (RFC 2324).'],
        422 => ['message' => 'Unprocessable Entity', 'class' => '4xx Client Error', 'desc' => 'The request was well-formed but was unable to be followed due to semantic errors.'],
        429 => ['message' => 'Too Many Requests', 'class' => '4xx Client Error', 'desc' => 'The user has sent too many requests in a given amount of time (rate limiting).'],
        500 => ['message' => 'Internal Server Error', 'class' => '5xx Server Error', 'desc' => 'A generic error message, given when an unexpected condition was encountered and no more specific message is suitable.'],
        501 => ['message' => 'Not Implemented', 'class' => '5xx Server Error', 'desc' => 'The server either does not recognize the request method, or it lacks the ability to fulfil the request.'],
        502 => ['message' => 'Bad Gateway', 'class' => '5xx Server Error', 'desc' => 'The server was acting as a gateway or proxy and received an invalid response from the upstream server.'],
        503 => ['message' => 'Service Unavailable', 'class' => '5xx Server Error', 'desc' => 'The server cannot handle the request (because it is overloaded or down for maintenance).'],
        504 => ['message' => 'Gateway Timeout', 'class' => '5xx Server Error', 'desc' => 'The server was acting as a gateway or proxy and did not receive a timely response from the upstream server.'],
    ];

    public function getId(): string
    {
        return 'http_status_lookup';
    }

    public function getName(): string
    {
        return 'HTTP Status Lookup';
    }

    public function execute(array $inputs, array $config = []): array
    {
        $raw = trim((string)($inputs['input'] ?? $inputs['code'] ?? $inputs['query'] ?? ''));

        if ($raw === '') {
            $formatted = [];
            foreach ($this->statuses as $code => $info) {
                $formatted[] = [
                    'code'        => $code,
                    'message'     => $info['message'],
                    'category'    => $info['class'],
                    'description' => $info['desc'],
                ];
            }
            return [
                'success' => true,
                'type'    => ResultType::JSON,
                'data'    => $formatted,
                'value'   => json_encode($formatted, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'meta'    => ['total_statuses' => count($formatted)],
            ];
        }

        if (is_numeric($raw)) {
            $code = (int)$raw;
            if (isset($this->statuses[$code])) {
                $item = [
                    'code'        => $code,
                    'message'     => $this->statuses[$code]['message'],
                    'category'    => $this->statuses[$code]['class'],
                    'description' => $this->statuses[$code]['desc'],
                ];
                return [
                    'success' => true,
                    'type'    => ResultType::JSON,
                    'data'    => $item,
                    'value'   => json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                    'meta'    => ['matched' => 1],
                ];
            }

            return [
                'success' => false,
                'error'   => "HTTP status code {$code} not recognized in standard RFC catalog.",
                'type'    => ResultType::JSON,
                'data'    => null,
                'value'   => null,
            ];
        }

        // Search by query string
        $q = strtolower($raw);
        $matches = [];
        foreach ($this->statuses as $code => $info) {
            if (
                strpos(strtolower($info['message']), $q) !== false ||
                strpos(strtolower($info['class']), $q) !== false ||
                strpos(strtolower($info['desc']), $q) !== false
            ) {
                $matches[] = [
                    'code'        => $code,
                    'message'     => $info['message'],
                    'category'    => $info['class'],
                    'description' => $info['desc'],
                ];
            }
        }

        return [
            'success' => true,
            'type'    => ResultType::JSON,
            'data'    => $matches,
            'value'   => json_encode($matches, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'meta'    => ['query' => $raw, 'matches_found' => count($matches)],
        ];
    }
}

