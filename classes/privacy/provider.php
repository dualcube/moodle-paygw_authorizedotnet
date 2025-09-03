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
 * Privacy Subsystem implementation for paygw_paypal.
 *
 * @package    paygw_authorizedotnet
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2025 DualCube Team(https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_authorizedotnet\privacy;

use core_payment\privacy\paygw_provider;
use core_privacy\local\request\writer;

/**
 * Privacy Subsystem implementation for paygw_authorizedotnet.
 *
 * @package    paygw_authorizedotnet
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2025 DualCube Team(https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\null_provider, paygw_provider {

    /**
     * Explain why this plugin stores no personal data.
     *
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }

    /**
     * Export all user data for the specified payment record.
     *
     * @param \context $context Context
     * @param array $subcontext The location within the current context
     * @param \stdClass $payment The payment record
     */
    public static function export_payment_data(\context $context, array $subcontext, \stdClass $payment) {
        global $DB;

        $subcontext[] = get_string('gatewayname', 'paygw_authorizedotnet');
        if ($record = $DB->get_record('paygw_authorizedotnet', ['paymentid' => $payment->id])) {
            $data = (object) [
                'transactionid' => $record->transactionid,
            ];
            writer::with_context($context)->export_data($subcontext, $data);
        }
    }

    /**
     * Delete all user data related to the given payments.
     *
     * @param string $paymentsql SQL query that selects payment.id field
     * @param array $paymentparams Array of parameters for $paymentsql
     */
    public static function delete_data_for_payment_sql(string $paymentsql, array $paymentparams) {
        global $DB;
        $DB->delete_records_select('paygw_authorizedotnet', "paymentid IN ({$paymentsql})", $paymentparams);
    }
}
