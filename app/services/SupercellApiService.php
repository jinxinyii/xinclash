<?php

declare(strict_types=1);

namespace App\Services;

use JsonException;

final class SupercellApiService
{
    private const BASE_URL = 'https://api.clashofclans.com/v1';

    public function getPlayer(string $playerTag): array
    {
        return $this->request(
            'GET',
            '/players/' . rawurlencode($playerTag)
        );
    }

    public function verifyPlayerToken(string $playerTag, string $playerToken): bool
    {
        $response = $this->request(
            'POST',
            '/players/' . rawurlencode($playerTag) . '/verifytoken',
            ['token' => $playerToken]
        );

        return isset($response['status'])
            && strtolower((string) $response['status']) === 'ok';
    }

    private function request(string $method, string $path, ?array $payload = null): array
    {
        $apiKey = trim((string) ($_ENV['SUPERCELL_API_KEY'] ?? ''));

        if ($apiKey === '') {
            throw new SupercellApiException('The Supercell API key is not configured.');
        }

        if (!function_exists('curl_init')) {
            throw new SupercellApiException('The PHP cURL extension is not enabled.');
        }

        $handle = curl_init(self::BASE_URL . $path);

        if ($handle === false) {
            throw new SupercellApiException('Could not initialize the Supercell API request.');
        }

        $headers = [
            'Accept: application/json',
            'Authorization: Bearer ' . $apiKey,
        ];

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_USERAGENT => 'xinClash/0.1',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CUSTOMREQUEST => $method,
        ];

        if ($payload !== null) {
            try {
                $body = json_encode($payload, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new SupercellApiException('Could not prepare the API request.');
            }

            $options[CURLOPT_POSTFIELDS] = $body;
            $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
        }

        curl_setopt_array($handle, $options);

        $responseBody = curl_exec($handle);
        $curlError = curl_error($handle);
        $httpStatus = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);

        if ($responseBody === false) {
            error_log('Supercell API transport error: ' . $curlError);
            throw new SupercellApiException('Could not reach the Supercell API.');
        }

        try {
            $response = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new SupercellApiException('The Supercell API returned an unreadable response.', $httpStatus);
        }

        if ($httpStatus < 200 || $httpStatus >= 300) {
            error_log(
                'Supercell API Diagnostic: ' .
                json_encode([
                    'method' => $method,
                    'endpoint' => $path,
                    'http_status' => $httpStatus,
                    'response' => substr($responseBody, 0, 500),
                ])
            );
            $message = match ($httpStatus) {
                400 => 'The player tag or request data was rejected by the API.',
                403 => 'Supercell denied this request. Check the developer API key and its allowed server IP. Player-token verification may also require endpoint access enabled for your developer account.',
                404 => 'The player tag was not found.',
                429 => 'The API rate limit was reached. Please try again later.',
                500, 503 => 'The Supercell API is temporarily unavailable.',
                default => 'The Supercell API request failed.',
            };

            throw new SupercellApiException($message, $httpStatus);
        }

        if (!is_array($response)) {
            throw new SupercellApiException('The Supercell API returned an unexpected response.', $httpStatus);
        }

        return $response;
    }
}
