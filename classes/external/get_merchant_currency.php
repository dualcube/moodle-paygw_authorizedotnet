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
use core_external\external_single_structure;
use core_payment\helper;
use paygw_authorizedotnet\authorizedotnet_helper;

/**
 * External API class for fetching the Authorize.net merchant currency.
 *
 * This class provides a webservice endpoint to securely retrieve the supported
 * currency of the merchant's Authorize.net account. This allows for a currency
 * check to be performed on the client-side before the user is prompted to pay.
 *
 * @package paygw_authorizedotnet
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_merchant_currency extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'component' => new external_value(PARAM_COMPONENT, 'Component'),
            'paymentarea' => new external_value(PARAM_AREA, 'Payment area in the component'),
            'itemid' => new external_value(PARAM_INT, 'An identifier for payment area in the component'),
        ]);
    }

    /**
     * Returns the currency supported by the merchant's Authorize.net account.
     *
     * @param string $component
     * @param string $paymentarea
     * @param int $itemid
     * @return array
     */
    public static function execute(string $component, string $paymentarea, int $itemid): array {
        self::validate_parameters(self::execute_parameters(), [
            'component' => $component,
            'paymentarea' => $paymentarea,
            'itemid' => $itemid,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);

        $config = helper::get_gateway_configuration($component, $paymentarea, $itemid, 'authorizedotnet');

        $helper = new authorizedotnet_helper($config['apiloginid'], $config['transactionkey'], $config['environment'] == 'sandbox');

        return $helper->get_merchant_currency();
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the request was successful.'),
            'currency' => new external_value(PARAM_TEXT, 'The merchant\'s currency code.'),
            'message' => new external_value(PARAM_RAW, 'An error or success message.'),
        ]);
    }
}
