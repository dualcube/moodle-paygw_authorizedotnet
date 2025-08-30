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
 * @author     DualCube
 * @copyright  2025 DualCube
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_authorizedotnet;

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../vendor/authorizenet/authorizenet/autoload.php';

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

/**
 * Helper class for interacting with the Authorize.net API.
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
     * Creates a transaction using the Authorize.net API.
     *
     * @param float $amount Transaction amount.
     * @param string $currency Currency (not used by API, but kept for consistency).
     * @param object $opaquedata Opaque data object.
     * @return array Transaction result.
     */
    public function create_transaction(float $amount, string $currency, object $opaquedata): array {
        // Authentication.
        $merchantauthentication = new AnetAPI\MerchantAuthenticationType();
        $merchantauthentication->setName($this->apiloginid);
        $merchantauthentication->setTransactionKey($this->transactionkey);

        // Payment details.
        $opaquedatatype = new AnetAPI\OpaqueDataType();
        $opaquedatatype->setDataDescriptor($opaquedata->dataDescriptor);
        $opaquedatatype->setDataValue($opaquedata->dataValue);

        $payment = new AnetAPI\PaymentType();
        $payment->setOpaqueData($opaquedatatype);

        // Transaction request.
        $transactionrequest = new AnetAPI\TransactionRequestType();
        $transactionrequest->setTransactionType("authCaptureTransaction");
        $transactionrequest->setAmount($amount);
        $transactionrequest->setPayment($payment);

        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication($merchantauthentication);
        $request->setRefId('ref' . time());
        $request->setTransactionRequest($transactionrequest);

        // Execute.
        $controller = new AnetController\CreateTransactionController($request);
        $environment = $this->sandbox
            ? \net\authorize\api\constants\ANetEnvironment::SANDBOX
            : \net\authorize\api\constants\ANetEnvironment::PRODUCTION;

        $response = $controller->executeWithApiResponse($environment);

        if ($response === null) {
            return ['success' => false, 'message' => 'No response from Authorize.net'];
        }

        if ($response->getMessages()->getResultCode() !== "Ok") {
            $tresponse = $response->getTransactionResponse();
            $message = $tresponse && $tresponse->getErrors()
                ? $tresponse->getErrors()[0]->getErrorText()
                : $response->getMessages()->getMessage()[0]->getText();
            return ['success' => false, 'message' => $message];
        }

        $tresponse = $response->getTransactionResponse();
        if ($tresponse && $tresponse->getResponseCode() === "1") {
            return [
                'success'       => true,
                'transactionid' => $tresponse->getTransId(),
                'status'        => $tresponse->getMessages()[0]->getDescription(),
            ];
        }

        $message = $tresponse && $tresponse->getErrors()
            ? $tresponse->getErrors()[0]->getErrorText()
            : 'Transaction Failed';

        return ['success' => false, 'message' => $message];
    }
}