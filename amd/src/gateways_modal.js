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

import * as Repository from './repository';
import Templates from 'core/templates';
import Modal from 'core/modal';
import ModalEvents from 'core/modal_events';
import {getString} from 'core/str';
import Notification from 'core/notification';

/**
 * Process the payment.
 *
 * @param {string} component Name of the component that the itemId belongs to
 * @param {string} paymentArea The area of the component that the itemId belongs to
 * @param {number} itemId An internal identifier that is used by the component
 * @param {string} description Description of the payment
 * @returns {Promise<string>}
 */
export const process = (component, paymentArea, itemId, description) => {
    let modal;
    return Repository.getConfigForJs(component, paymentArea, itemId)
    .then(async config => {
        // First render the payment button template
        const body = await Templates.render('paygw_authorizedotnet/authorizedotnet_button', {
            apiloginid: config.apiloginid,
            clientkey: config.publicclientkey,
        });

        // Create modal with button already in DOM
        return Modal.create({
            title: getString('pluginname', 'paygw_authorizedotnet'),
            body: body,
            show: true,
            removeOnClose: true,
        }).then(modalInstance => {
            modal = modalInstance;
            // Now load the SDK (button exists already)
            return switchSdk(config.environment);
        });
    })
    .then(() => {
        // Set up the response handler after SDK is loaded
        return new Promise(resolve => {
            window.responseHandler = function(response) {
                modal.getRoot().on(ModalEvents.outsideClick, (e) => {
                    e.preventDefault();
                });

                if (response.messages.resultCode === "Error") {
                    let errorMessages = '';
                    for (let i = 0; i < response.messages.message.length; i++) {
                        errorMessages += response.messages.message[i].text + '\n';
                    }
                    Notification.alert(getString('error', 'moodle'), errorMessages);
                    return;
                }

                modal.setBody(getString('authorising', 'paygw_authorizedotnet'));

                Repository.processPayment(component, paymentArea, itemId, response.opaqueData)
                .then(res => {
                    modal.hide();
                    return res;
                })
                .then(resolve);
            };
        });
    })
    .then(res => {
        if (res.success) {
            return Promise.resolve(res.message);
        }
        return Promise.reject(res.message);
    });
};

/**
 * Unloads the previously loaded Authorize.net JavaScript SDK, and loads a new one.
 *
 * @param {string} environment The environment (sandbox or live)
 * @returns {Promise}
 */
const switchSdk = (environment) => {
    const sdkUrl = (environment === 'sandbox')
        ? 'https://jstest.authorize.net/v3/AcceptUI.js'
        : 'https://js.authorize.net/v3/AcceptUI.js';

    if (switchSdk.currentlyloaded === sdkUrl) {
        return Promise.resolve();
    }

    if (switchSdk.currentlyloaded) {
        const suspectedScript = document.querySelector(`script[src="${switchSdk.currentlyloaded}"]`);
        if (suspectedScript) {
            suspectedScript.parentNode.removeChild(suspectedScript);
        }
    }

    const script = document.createElement('script');

    return new Promise(resolve => {
        if (script.readyState) {
            script.onreadystatechange = function() {
                if (this.readyState == 'complete' || this.readyState == 'loaded') {
                    this.onreadystatechange = null;
                    resolve();
                }
            };
        } else {
            script.onload = function() {
                resolve();
            };
        }

        script.setAttribute('src', sdkUrl);
        script.setAttribute('charset', 'utf-8');
        document.head.appendChild(script);

        switchSdk.currentlyloaded = sdkUrl;
    });
};

/**
 * Holds the full url of loaded Authorize.net JavaScript SDK.
 *
 * @static
 * @type {string}
 */
switchSdk.currentlyloaded = '';