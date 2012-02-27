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
 * Moodle flavours renderers
 *
 * @package    local
 * @subpackage flavours
 * @copyright  2011 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Moodle flavours renderer class
 *
 * @package local
 * @subpackage flavours
 * @copyright 2011 David Monllaó
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_flavours_renderer extends plugin_renderer_base {

    /**
     * Wraps all the actions
     *
     * @param renderable $renderable
     * @param string $action
     * @return string
     */
    public function render_flavours_wrapper(renderable $renderable, $action) {
        global $PAGE, $SITE;

        $PAGE->set_heading($SITE->fullname);
        $PAGE->set_title(get_string('action' . $action, 'local_flavours'));

        $output = $this->output->header();

        // Redirects the flow to the specific method
        $actiontorender = 'render_flavours_' . $action;
        $output .= $this->$actiontorender($renderable);

        $output .= $this->output->footer();

        return $output;

    }

    /**
     * Packaging form renderer
     * @param renderable $renderable
     */
    protected function render_flavours_packaging_form(renderable $renderable) {
        return $this->render_form($renderable);
    }


    /**
     * Not necessary, just to maintain coherence action -> render
     * @param renderable $renderable
     */
    protected function render_flavours_packaging_execute(renderable $renderable) {
        //
    }

    /**
     * Deployment upload form renderer
     * @param renderable $renderable
     */
    protected function render_flavours_deployment_upload(renderable $renderable) {
        return $this->render_form($renderable);
    }

    /**
     * Deployment preview form renderer
     * @param renderable $renderable
     */
    protected function render_flavours_deployment_preview(renderable $renderable) {
        return $this->render_form($renderable);
    }


    /**
     * Deployment results renderer
     * @param renderable $renderable
     */
    protected function render_flavours_deployment_execute(renderable $renderable) {
        global $CFG;

        // The table with the results
        $output = html_writer::table($renderable->get_table());

        // The button to go to notifications
        $notificationsurl = new moodle_url($CFG->wwwroot . '/admin/index.php');
        $output .= '<div class="generalbox">';
        $output .= $this->output->single_button($notificationsurl,
                                                get_string('deploymentcontinue', 'local_flavours'));
        $output .= '</div>';

        return $output;
    }


    /**
     * Gets the HTML of a moodle form
     *
     * @param moodleform $form
     * @return string The HTML of the form
     */
    protected function render_form(moodleform $form) {

        ob_start();

        $form->display();
        $output = ob_get_contents();

        ob_end_clean();

        return $output;
    }
}
