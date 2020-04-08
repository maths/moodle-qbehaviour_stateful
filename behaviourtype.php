<?php
// This file is part of Stateful
//
// Stateful is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Stateful is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Stateful.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

class qbehaviour_stateful_type {
    public function is_archetypal() {
        // This behaviour is automatically used by the questions using it,
        // defining it as an option for other questions makes no sense.
        return false;
    }

    public function can_questions_finish_during_the_attempt() {
        // Essenttialy, if a stateful scene reaches a scene with no inputs
        // is is finished.
        return true;
    }

    public function allows_multiple_submitted_responses() {
        return true;
    }
}