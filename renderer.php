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

class qbehaviour_stateful_renderer extends qbehaviour_renderer {

    public function controls(question_attempt $qa, question_display_options $options) {
        // We add some progresbars to help students understand how far they have gotten
        // in the question.

        // Only provide button if there is a reason for it to exist.
        if (!$qa->get_question()->is_in_end_scene()) {
            $r = '<hr/>' . $this->submit_button($qa, $options);
            $r .= '<p>';
            if ($qa->get_question()->get_expected_sequence_length() !== null &&
                $qa->get_question()->get_expected_sequence_length() > 0) {
                $r .= get_string('progress_defined_length', 'qbehaviour_stateful', [
                    'current' => $qa->get_question()->get_scene_sequence_number(null),
                    'target' => $qa->get_question()->get_expected_sequence_length()]);

            } else {
                $r .= get_string('progress_undefined_length', 'qbehaviour_stateful', [
                    'current' => $qa->get_question()->get_scene_sequence_number(null),
                    'target' => 0]);
            }

            $r .= '</p>';
            return $r;
        } else {
            return '<hr/><p>' . get_string('progress_end_of_chain', 'qbehaviour_stateful', null) . '</p>';
        }
    }
}