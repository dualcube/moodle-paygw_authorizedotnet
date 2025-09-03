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

declare(strict_types=1);

namespace paygw_authorizedotnet\external;

use core_external\external_api;
use core_external\external_value;
use core_external\external_function_parameters;
use core_payment\helper;
use core_payment\helper as payment_helper;
use paygw_authorizedotnet\authorizedotnet_helper;

/**
 * External API for processing Authorize.net payments.
 *
 * This class handles the final processing of a payment after the client-side
 * form has submitted the opaque data to Moodle. It uses the Authorize.net
 * helper class to create the transaction and, if successful, completes the
 * payment process within Moodle.
 *
 * @package paygw_authorizedotnet
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class process_payment extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'component' => new external_value(PARAM_COMPONENT, 'The component name'),
            'paymentarea' => new external_value(PARAM_AREA, 'Payment area in the component'),
            'itemid' => new external_value(PARAM_INT, 'The item id in the context of the component area'),
            'opaquedata' => new external_value(PARAM_RAW, 'The opaque data from Authorize.net'),
        ]);
    }

    /**
     * Perform what needs to be done when a transaction is reported to be complete.
     *
     * @param string $component Name of the component that the itemid belongs to.
     * @param string $paymentarea Payment area within the component.
     * @param int $itemid Internal identifier used by the component.
     * @param string $opaquedata JSON string of opaque data from Authorize.Net (Accept.js).
     * @return array
     */
    public static function execute(string $component, string $paymentarea, int $itemid, string $opaquedata): array {
        global $USER, $DB;

        $params = [
            'component' => $component,
            'paymentarea' => $paymentarea,
            'itemid' => $itemid,
            'opaquedata' => $opaquedata,
        ];
        self::validate_parameters(self::execute_parameters(), $params);

        $opaquedataobject = json_decode($opaquedata);

        $config = (object)helper::get_gateway_configuration($component, $paymentarea, $itemid, 'authorizedotnet');

        $payable = payment_helper::get_payable($component, $paymentarea, $itemid);
        $currency = $payable->get_currency();

        // Add surcharge if there is any.
        $surcharge = helper::get_gateway_surcharge('authorizedotnet');
        $amount = helper::get_rounded_cost($payable->get_amount(), $currency, $surcharge);

        $helper = new authorizedotnet_helper($config->apiloginid, $config->transactionkey, $config->environment == 'sandbox');

        $response = $helper->create_transaction($amount,  $opaquedataobject);

        $success = false;
        $message = '';

        if ($response['success']) {
            $success = true;
            // Everything is correct. Let's give them what they paid for.
            try {
                $paymentid = payment_helper::save_payment($payable->get_account_id(), $component, $paymentarea,
                    $itemid, (int) $USER->id, $amount, $currency, 'authorizedotnet');

                // Store transaction extra information.
                $record = new \stdClass();
                $record->paymentid = $paymentid;
                $record->transactionid = $response['transactionid'];

                $DB->insert_record('paygw_authorizedotnet', $record);

                payment_helper::deliver_order($component, $paymentarea, $itemid, $paymentid, (int) $USER->id);
            } catch (\Exception $e) {
                debugging('Exception while trying to process payment: ' . $e->getMessage(), DEBUG_DEVELOPER);
                $success = false;
                $message = get_string('internalerror', 'paygw_authorizedotnet');
            }
        } else {
            $success = false;
            $message = $response['message'];
        }

        return [
            'success' => $success,
            'message' => $message,
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_function_parameters
     */
    public static function execute_returns() {
        return new external_function_parameters([
            'success' => new external_value(PARAM_BOOL, 'Whether everything was successful or not.'),
            'message' => new external_value(PARAM_RAW, 'Message (usually the error message).'),
        ]);
    }
}
