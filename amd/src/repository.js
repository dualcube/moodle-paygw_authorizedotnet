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

import Ajax from 'core/ajax';

/**
 * Returns the gateway config that is needed for JS.
 *
 * @param {string} component
 * @param {string} paymentArea
 * @param {number} itemId
 * @returns {Promise}
 */
export const getConfigForJs = (component, paymentArea, itemId) => {
    return Ajax.call([{
        methodname: 'paygw_authorizedotnet_get_config_for_js',
        args: {
            component: component,
            paymentarea: paymentArea,
            itemid: itemId,
        },
    }])[0];
};

/**
 * Marks the transaction as complete.
 *
 * @param {string} component
 * @param {string} paymentArea
 * @param {number} itemId
 * @param {object} opaqueData
 * @returns {Promise}
 */
export const processPayment = (component, paymentArea, itemId, opaqueData) => {
    return Ajax.call([{
        methodname: 'paygw_authorizedotnet_process_payment',
        args: {
            component: component,
            paymentarea: paymentArea,
            itemid: itemId,
            opaquedata: JSON.stringify(opaqueData),
        },
    }])[0];
};