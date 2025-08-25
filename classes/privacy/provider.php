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
 * Privacy provider for the Authorize.net payment gateway.
 *
 * @package    paygw_authorizedotnet
 * @copyright  2025 Me
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_authorizedotnet\privacy;

/**
 * Privacy provider for the Authorize.net payment gateway.
 *
 * @package    paygw_authorizedotnet
 * @copyright  2025 Me
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\provider {

    /**
     * Get the language string identifier for the component area.
     *
     * @return string The language string identifier.
     */
    public static function get_area_name(): string {
        return get_string('pluginname', 'paygw_authorizedotnet');
    }
}
