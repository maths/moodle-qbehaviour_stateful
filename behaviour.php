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

require_once __DIR__ . '/../../type/questionbase.php';
require_once __DIR__ . '/../behaviourbase.php';


/**
 * Handler for accessing state-variables of specific sequence-number.
 * @copyright  2019 Matti Harjula
 * @copyright  2019 Aalto University
 */
class qbehaviour_stateful_state_storage {
    private $qa;

    private $cache;

    private $identifiers;

    private $dirty;

    public function __construct($qa, $identifiers) {
        $this->qa = $qa;
        $this->dirty = false;
        $this->identifiers = $identifiers;
        $this->cache = array();
        if ($qa !== null) {
            foreach ($this->identifiers as $id => $name) {
                $this->cache[$id] = $this->qa->get_last_behaviour_var("_sv_$id");
            }
        }
    }

    public function rewind($step) {
        // Set the state as it was at a given step.
        // Load all data before that step.
        foreach ($this->qa->get_step_iterator() as $stp) {
            // NOTE: not reverse as we need to stop when target is met.
            foreach ($this->identifiers as $id => $name) {
                if (!($stp instanceof question_null_step) &&
                    $stp->has_behaviour_var("_sv_$id")) {
                    $this->cache[$id] = $stp->get_behaviour_var("_sv_$id");
                }
            }
            if ($stp->get_id() === $step->get_id()) {
                break;
            }
        }
        // Ensure that the given step will be loaded.
        foreach ($this->identifiers as $id => $name) {
            if (!($step instanceof question_null_step) &&
                $step->has_behaviour_var("_sv_$id")) {
                $this->cache[$id] = $step->get_behaviour_var("_sv_$id");
            }
        }

        $this->dirty = false;
    }

    public function rewind_to_seqn($seqn) {
        // Set the state as it was at a given seqn.
        // Load all data in and stop when the data reaches that seqn.
        foreach ($this->qa->get_step_iterator() as $stp) {
            if (!($stp instanceof question_null_step)) {
                // NOTE: not reverse as we need to load previous values also.
                foreach ($this->identifiers as $id => $name) {
                    if ($stp->has_behaviour_var("_sv_$id")) {
                        $this->cache[$id] = $stp->get_behaviour_var("_sv_$id");
                    }
                }
                if ($this->qa->get_question()->get_scene_sequence_number($this)
                                                                     == $seqn) {
                    break;
                }
            }
        }

        $this->dirty = false;
    }

    public function get(int $id, string $default = null) {
        if (array_key_exists($id, $this->cache)) {
            return $this->cache[$id];
        }
        return $default;
    }

    // NOTE: this function exists to help when building debug code, you should use
    // the numeric identifiers if at all possible.
    public function get_by_name(string $name, string $default = null) {
        foreach ($this->identifiers as $id => $n) {
            if ($n === $name) {
                return $this->get($id);
            }
        }
        return $default;
    }

    public function set(int $id, string $value) {
        if (isset($this->cache[$id]) && $this->cache[$id] == $value) {
            return;
        }
        $this->dirty = true;
        $this->cache[$id] = $value;
    }

    // NOTE: this function exists to help when building debug code, you should use
    // the numeric identifiers if at all possible.
    public function set_by_name(string $name, string $value) {
        foreach ($this->identifiers as $id => $n) {
            if ($n === $name) {
                $this->set($id, $value);
                return;
            }
        }
    }

    public function is_dirty() {
        return $this->dirty;
    }

    public function save($targetstep) {
        if (!$this->is_dirty()) {
            return;
        }
        foreach ($this->identifiers as $id => $name) {
            $value = $this->get($id);
            if (!$targetstep->has_behaviour_var("_sv_$id") ||
                $targetstep->get_behaviour_var("_sv_$id") !== $value) {
                $targetstep->set_behaviour_var("_sv_$id", $value);
            }
        }
        $this->dirty = false;
    }

    public function string_dump(): string {
        $out = '<table><tr><th>key</th><th>value</th></tr>';
        foreach ($this->cache as $id => $value) {
            $out .= '<tr><td>' . $this->identifiers[$id] . '</td>';
            $out .= '<td>' . $value . '</td></tr>';
        }
        $out .= '</table>';
        return $out;
    }

}

// TODO! What interface do we extend? The sensible names bring pointless functions.
interface question_stateful extends question_automatically_gradable {

    /**
     * Gives the question access to the state storage. You can assume that this
     * has been called early in the process, before anything that requires
     * evaluation of responses.
     * @param qbehaviour_stateful_state_storage access to the storage of this step.
     */
    public function set_state(qbehaviour_stateful_state_storage $state);

    /**
     * Asks the question for an array defining the numeric identifiers of all
     * the state variables it needs stored or retrieved from storage. Always,
     * called before set_state. Also provides matching names for debug displays.
     * @return array of integer identifiers mapped to variable names.
     */
    public function get_state_variable_identifiers();

    /**
     * Note assume that the state is not the state used when setting state,
     * just read from it do not store it.
     * @param a state for the question. null means use already given state.
     * @return integers.
     */
    public function get_scene_sequence_number(qbehaviour_stateful_state_storage $state=null): int;

    /**
     * Asks the question to define how long the sequence is expected to get,
     * for a sensible answer. If undefinable return null. Used to give indication
     * on how far one has gotten in the question the score is used as a separate source
     * of similar information.
     * @return integers. or null
     */
    public function get_expected_sequence_length(): ?int;
}

class qbehaviour_stateful extends question_behaviour_with_save {

    /* @var qbehaviour_stateful_state_storage */
    private $statestore = null;

    /* @var bool do we evalaute penalties? */
    private $penalties = true;

    public function __construct($questionattempt, $preferredbehaviour) {
        parent::__construct($questionattempt, $preferredbehaviour);
        $this->statestore = new qbehaviour_stateful_state_storage($this->qa,
            $this->question->get_state_variable_identifiers());
        $this->statestore->rewind($this->qa->get_last_step());

        $this->question->set_state($this->statestore);
    }

    // TODO: What is the point of these two? Wouldn't either one be enough?
    public function required_question_definition_class() {
        return 'question_stateful';
    }

    public function is_compatible_question(question_definition $question) {
        return $question instanceof question_stateful;
    }

    /**
     * Initialise the first step in a question attempt when a new
     * {@link question_attempt} is being started.
     *
     * This method must call $this->question->start_attempt($step, $variant), and may
     * perform additional processing if the behaviour requries it.
     *
     * @param question_attempt_step $step the first step of the
     *      question_attempt being started.
     * @param int $variant which variant of the question to use.
     */
    public function init_first_step(question_attempt_step $step, $variant) {
        $step->set_behaviour_var('_applypenalties', (int) $this->penalties);

        // This rewind is pretty pointless as there is practically no sensible
        // situation where one would init a first step again.
        $this->statestore->rewind($step);

        parent::init_first_step($step, $variant);

        $this->statestore->save($step);
    }

    public function apply_attempt_state(question_attempt_step $step) {
        if ($step->has_behaviour_var('_applypenalties')) {
            $this->penalties = (bool) $step->get_behaviour_var('_applypenalties');
        }

        // Now it would make sense to apply the state of the step we are in...
        // But as that is not possible when we always get the first step lets
        // assume we are at the last step.
        $this->statestore->rewind($this->qa->get_last_step());

        parent::apply_attempt_state($step);
    }

    /**
     * The main entry point for processing an action.
     *
     * All the various operations that can be performed on a
     * {@link question_attempt} get channeled through this function, except for
     * {@link question_attempt::start()} which goes to {@link init_first_step()}.
     * {@link question_attempt::finish()} becomes an action with im vars
     * finish => 1, and manual comment/grade becomes an action with im vars
     * comment => comment text, and mark => ..., max_mark => ... if the question
     * is graded.
     *
     * This method should first determine whether the action is significant. For
     * example, if no actual action is being performed, but instead the current
     * responses are being saved, and there has been no change since the last
     * set of responses that were saved, this the action is not significatn. In
     * this case, this method should return {@link question_attempt::DISCARD}.
     * Otherwise it should return {@link question_attempt::KEEP}.
     *
     * If the action is significant, this method should also perform any
     * necessary updates to $pendingstep. For example, it should call
     * {@link question_attempt_step::set_state()} to set the state that results
     * from this action, and if this is a grading action, it should call
     * {@link question_attempt_step::set_fraction()}.
     *
     * This method can also call {@link question_attempt_step::set_behaviour_var()} to
     * store additional infomation. There are two main uses for this. This can
     * be used to store the result of any randomisation done. It is important to
     * store the result of randomisation once, and then in future use the same
     * outcome if the actions are ever replayed. This is how regrading works.
     * The other use is to cache the result of expensive computations performed
     * on the raw response data, so that subsequent display and review of the
     * question does not have to repeat the same expensive computations.
     *
     * Often this method is implemented as a dispatching method that examines
     * the pending step to determine the kind of action being performed, and
     * then calls a more specific method like {@link process_save()} or
     * {@link process_comment()}. Look at some of the standard behaviours
     * for examples.
     *
     * @param question_attempt_pending_step $pendingstep a partially initialised step
     *      containing all the information about the action that is being peformed. This
     *      information can be accessed using {@link question_attempt_step::get_behaviour_var()}.
     * @return bool either {@link question_attempt::KEEP} or {@link question_attempt::DISCARD}
     */
    public function process_action(question_attempt_pending_step $pendingstep) {
        // The state will always be transfered. Saving it after processing is another thing.
        // Load in the state.
        $this->statestore->rewind($pendingstep);

        // Get the sequence number. Before any actions.
        $seqn = $this->question->get_scene_sequence_number($this->statestore);
        $pendingstep->set_behaviour_var('_seqn_pre', $seqn);

        // Handle all the actions.
        if ($pendingstep->has_behaviour_var('comment')) {
            return $this->process_comment($pendingstep);
        } else if ($pendingstep->has_behaviour_var('finish')) {
            return $this->process_finish($pendingstep);
        } else if ($pendingstep->has_behaviour_var('submit')) {
            $r = $this->process_submit($pendingstep);
            if (question_attempt::DISCARD !== $r) {
                $this->statestore->save($pendingstep);
            }
            return $r;
        } else {
            return $this->process_save($pendingstep);
        }

    }

    public function process_submit(question_attempt_pending_step $pendingstep) {

        $status = $this->process_save($pendingstep);
        if ($status == question_attempt::DISCARD) {
            return question_attempt::DISCARD;
        }
        $prevstep = $this->qa->get_last_step();

        // First get the input values.
        $input = $pendingstep->get_qt_data();

        // Push them to current step classification.
        $result = $this->question->process_input($input);

        if ($prevstep->get_state() == question_state::$complete) {
            $pendingstep->set_state(question_state::$complete);
        } else {
            $pendingstep->set_state($result['_attemptstatus']);
            // Provide something to display, something short...
            $pendingstep->set_new_response_summary($result['_summary']);
        }

        unset($result['_attemptstatus']);

        // Get the sequence number. After any actions. Store it for analysis.
        $seqn = $this->question->get_scene_sequence_number($this->statestore);
        $pendingstep->set_behaviour_var('_seqn_post', $seqn);

        // Store current step classification data. Also state.
        $this->statestore->save($pendingstep);
        $data = json_encode($result);
        $pendingstep->set_behaviour_var('_data', $data);

        // Extract history data and use it to build the grade at this point.
        $history = [$pendingstep->get_behaviour_var('_seqn_pre') => [$result]];
        $steps = $this->qa->get_reverse_step_iterator();
        // Latest one we already have.
        $steps->next();
        foreach ($steps as $step) {
            $seqn = $step->get_behaviour_var('_seqn_pre');
            if ($seqn !== '' && $seqn !== null && $step->get_behaviour_var('_data') !== null) {
                $data = json_decode($step->get_behaviour_var('_data'), true);
                if ($data) {
                    if (isset($history[$seqn])) {
                        array_unshift($history[$seqn], $data);
                    } else {
                        $history[$seqn] = array($data);
                    }
                }
            }
        }

        // Push the data to the question to process.
        $result = $this->question->evaluate_total_grade($history, $this->penalties);
        $pendingstep->set_fraction($result['total']);

        return question_attempt::KEEP;
    }

    public function process_finish(question_attempt_pending_step $pendingstep) {
        if ($this->qa->get_state()->is_finished()) {
            return question_attempt::DISCARD;
        }

        // Just in case something has not been submitted yet.
        // TODO: should we ignore missing validation steps here?
        $this->process_submit($pendingstep);

        // Override the state after that.
        $pendingstep->set_state(question_state::$finished);

        return question_attempt::KEEP;
    }

    public function summarise_action(question_attempt_step $step) {
        if ($step->has_behaviour_var('_data')) {
            $data = json_decode($step->get_behaviour_var('_data'), true);
            return $data['_summary'];
        }
        return '';
    }

    public function get_expected_data() {
        if ($this->question->get_state() === null) {
            // If called out of order we have not initialsied the question and
            // cannot know what inputs there are in the current scene.
            $this->statestore->rewind($this->qa->get_last_step());
            $this->question->set_state($this->statestore);
            $this->question->apply_attempt_state($this->qa->get_last_step());
        }

        if ($this->qa->get_state()->is_active()) {
            $this->statestore->rewind($this->qa->get_last_step());

            $r = $this->question->get_expected_data();
            $r['submit'] = PARAM_BOOL;
            return $r;
        }
        return parent::get_expected_data();
    }

    public function get_min_fraction() {
        return $this->question->get_min_fraction();
    }

    public function get_max_fraction() {
        return $this->question->get_max_fraction();
    }

    // This is not necessary the correct response, and might not even trigger
    // correct one, but will fill in all the fields with something sensible.
    public function get_correct_response() {
        if ($this->question->get_state() === null) {
            // How can we get here without the question having been given state during
            // the construction of this behaviour?
            if ($this->statestore === null) {
                $this->statestore = new qbehaviour_stateful_state_storage($this->qa,
                    $this->question->get_state_variable_identifiers());
                $this->statestore->rewind($this->qa->get_last_step());
            }
            $this->question->set_state($this->statestore);
        }

        return $this->question->get_correct_response();
    }
}
