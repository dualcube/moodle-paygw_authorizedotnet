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

/**
 * The gateway class for Authorize.net payment gateway.
 *
 * @copyright  2023 Me <me@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gateway extends \core_payment\gateway {
    /**
     * Returns a list of supported currencies.
     *
     * @return array
     */
    public static function get_supported_currencies(): array {
        return [
            'USD',
            'CAD',
            'CHF',
            'DKK',
            'EUR',
            'GBP',
            'NOK',
            'PLN',
            'SEK',
            'AUD',
            'NZD',
        ];
    }

    /**
     * Configuration form for the gateway instance
     *
     * Use $form->get_mform() to access the \MoodleQuickForm instance
     *
     * @param \core_payment\form\account_gateway $form
     */
    public static function add_configuration_to_gateway_form(\core_payment\form\account_gateway $form): void {
        $mform = $form->get_mform();

        $mform->addElement('text', 'apiloginid', get_string('apiloginid', 'paygw_authorizedotnet'));
        $mform->setType('apiloginid', PARAM_TEXT);
        $mform->addHelpButton('apiloginid', 'apiloginid', 'paygw_authorizedotnet');

        $mform->addElement('text', 'publicclientkey', get_string('publicclientkey', 'paygw_authorizedotnet'));
        $mform->setType('publicclientkey', PARAM_TEXT);
        $mform->addHelpButton('publicclientkey', 'publicclientkey', 'paygw_authorizedotnet');

        $mform->addElement('text', 'transactionkey', get_string('transactionkey', 'paygw_authorizedotnet'));
        $mform->setType('transactionkey', PARAM_TEXT);
        $mform->addHelpButton('transactionkey', 'transactionkey', 'paygw_authorizedotnet');

        $options = [
            'live' => get_string('live', 'paygw_authorizedotnet'),
            'sandbox'  => get_string('sandbox', 'paygw_authorizedotnet'),
        ];

        $mform->addElement('select', 'environment', get_string('environment', 'paygw_authorizedotnet'), $options);
        $mform->addHelpButton('environment', 'environment', 'paygw_authorizedotnet');
    }

    /**
     * Validates the gateway configuration form.
     *
     * @param \core_payment\form\account_gateway $form
     * @param \stdClass $data
     * @param array $files
     * @param array $errors form errors (passed by reference)
     */
    public static function validate_gateway_form(\core_payment\form\account_gateway $form,
                                                 \stdClass $data, array $files, array &$errors): void {
        if ($data->enabled &&
                (empty($data->apiloginid) || empty($data->publicclientkey) || empty($data->transactionkey))) {
            $errors['enabled'] = get_string('gatewaycannotbeenabled', 'payment');
        }
    }
}
