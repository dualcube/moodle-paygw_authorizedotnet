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
 * @copyright  2015 DualCube Team(https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_authorizedotnet;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../vendor/autoload.php');

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

/**
 * Helper class for interacting with the Authorize.net API.
 */
class authorizedotnet_helper {

    /**
     * The API Login ID for authentication.
     *
     * @var string
     */
    private $apiloginid;

    /**
     * The Transaction Key for authentication.
     *
     * @var string
     */
    private $transactionkey;

    /**
     * Whether the class is in sandbox mode or production.
     *
     * @var bool
     */
    private $sandbox;

    /**
     * Constructor for the Authorize.net helper.
     *
     * @param string $apiloginid The API Login ID.
     * @param string $transactionkey The Transaction Key.
     * @param bool $sandbox Whether to use sandbox mode.
     */
    public function __construct(string $apiloginid, string $transactionkey, bool $sandbox) {
        $this->apiloginid = $apiloginid;
        $this->transactionkey = $transactionkey;
        $this->sandbox = $sandbox;
    }

    /**
     * Creates a transaction using the Authorize.net API.
     *
     * @param float $amount The transaction amount.
     * @param string $currency The transaction currency.
     * @param object $opaquedata The opaque data object.
     * @return array An array containing the transaction result.
     */
    public function create_transaction(float $amount, string $currency, object $opaquedata): array {
        // Create a merchantAuthenticationType object with authentication details.
        $merchantauthentication = new AnetAPI\MerchantAuthenticationType();
        $merchantauthentication->setName($this->apiloginid);
        $merchantauthentication->setTransactionKey($this->transactionkey);

        // Set the transaction's refId.
        $refid = 'ref' . time();

        // Create the payment data for a credit card.
        $opaquedatatype = new AnetAPI\OpaqueDataType();
        $opaquedatatype->setDataDescriptor($opaquedata->dataDescriptor);
        $opaquedatatype->setDataValue($opaquedata->dataValue);

        // Add the payment data to a paymentType object.
        $paymentone = new AnetAPI\PaymentType();
        $paymentone->setOpaqueData($opaquedatatype);

        // Create a TransactionRequestType object and add the previous objects to it.
        $transactionrequesttype = new AnetAPI\TransactionRequestType();
        $transactionrequesttype->setTransactionType("authCaptureTransaction");
        $transactionrequesttype->setAmount($amount);
        $transactionrequesttype->setPayment($paymentone);

        // Assemble the complete transaction request.
        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication($merchantauthentication);
        $request->setRefId($refid);
        $request->setTransactionRequest($transactionrequesttype);

        // Create the controller and get the response.
        $controller = new AnetController\CreateTransactionController($request);

        $environment = $this->sandbox
            ? \net\authorize\api\constants\ANetEnvironment::SANDBOX
            : \net\authorize\api\constants\ANetEnvironment::PRODUCTION;

        $response = $controller->executeWithApiResponse($environment);
        if ($response != null) {
            if ($response->getMessages()->getResultCode() == "Ok") {
                $tresponse = $response->getTransactionResponse();
                if ($tresponse != null) {
                    if ($tresponse->getResponseCode() == "1") {
                        return [
                            'success'       => true,
                            'transactionid' => $tresponse->getTransId(),
                            'status'        => $tresponse->getMessages()[0]->getDescription(),
                        ];
                    } else {
                        // Not approved, capture error details.
                        $message = 'Transaction Failed';
                        if ($tresponse->getErrors() != null) {
                            $message = $tresponse->getErrors()[0]->getErrorText();
                        }
                        return [
                            'success' => false,
                            'message' => $message,
                        ];
                    }
                } else {
                    $message = 'Transaction Failed';
                    if ($tresponse->getErrors() != null) {
                        $message = $tresponse->getErrors()[0]->getErrorText();
                    }
                    return [
                        'success' => false,
                        'message' => $message,
                    ];
                }
            } else {
                $tresponse = $response->getTransactionResponse();
                $message = 'Transaction Failed';
                if ($tresponse != null && $tresponse->getErrors() != null) {
                    $message = $tresponse->getErrors()[0]->getErrorText();
                } else {
                    $message = $response->getMessages()->getMessage()[0]->getText();
                }
                return [
                    'success' => false,
                    'message' => $message,
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'No response from Authorize.net',
            ];
        }
    }
}
