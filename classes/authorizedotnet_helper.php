<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * authorize.net payment gateway plugin.
 *
 * @package    paygw_authorizedotnet
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2025 DualCube Team(https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_authorizedotnet;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

/**
 * Helper class for interacting with the Authorize.Net API (REST).
 */
class authorizedotnet_helper {

    private string $apiloginid;
    private string $transactionkey;
    private bool $sandbox;

    public function __construct(string $apiloginid, string $transactionkey, bool $sandbox) {
        $this->apiloginid = $apiloginid;
        $this->transactionkey = $transactionkey;
        $this->sandbox = $sandbox;
    }

    /**
     * Creates a transaction using the Authorize.Net REST API.
     *
     * @param float $amount Transaction amount.
     * @param string $currency Currency code (e.g., USD).
     * @param object $opaquedata Opaque data object from Accept.js (descriptor + value).
     * @return array Transaction result.
     */
    public function create_transaction(float $amount, string $currency, object $opaquedata): array {
        $url = $this->sandbox
            ? 'https://apitest.authorize.net/xml/v1/request.api'
            : 'https://api.authorize.net/xml/v1/request.api';

        // Build request payload.
        $payload = [
            'createTransactionRequest' => [
                'merchantAuthentication' => [
                    'name'           => $this->apiloginid,
                    'transactionKey' => $this->transactionkey,
                ],
                'refId' => 'ref' . time(),
                'transactionRequest' => [
                    'transactionType' => 'authCaptureTransaction',
                    'amount' => $amount,
                    'payment' => [
                        'opaqueData' => [
                            'dataDescriptor' => $opaquedata->dataDescriptor,
                            'dataValue'      => $opaquedata->dataValue,
                        ]
                    ]
                ]
            ]
        ];

        // Moodle curl wrapper.
        $curl = new \curl();
        $options = [
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_HTTPHEADER'     => ['Content-Type: application/json'],
            'CURLOPT_TIMEOUT'        => 30,
            'CURLOPT_SSL_VERIFYPEER' => true,
            'CURLOPT_SSL_VERIFYHOST' => 2,
        ];

        $response = $curl->post($url, json_encode($payload), $options);

        if ($response === false) {
            return ['success' => false, 'message' => 'No response from Authorize.Net'];
        }

        $response = preg_replace('/^\xEF\xBB\xBF/', '', $response);

        $result = json_decode(trim($response), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'message' => 'Invalid JSON response from Authorize.Net'];
        }

        $messages = $result['messages'] ?? null;
        if (!$messages || $messages['resultCode'] !== 'Ok') {
            $message = $messages['message'][0]['text'] ?? 'Unknown error';
            return ['success' => false, 'message' => $message];
        }

        $tresponse = $result['transactionResponse'] ?? null;
        if ($tresponse && $tresponse['responseCode'] === "1") {
            return [
                'success'       => true,
                'transactionid' => $tresponse['transId'] ?? '',
                'status'        => $tresponse['messages'][0]['description'] ?? 'Approved',
            ];
        }

        $message = $tresponse['errors'][0]['errorText'] ?? 'Transaction Failed';
        return ['success' => false, 'message' => $message];
    }
}
