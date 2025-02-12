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
 * Manage files in the repository
 *
 * @package    repository_imagehub
 * @copyright  2024 ISB Bayern
 * @author     Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 require('../../config.php');
 require_once('./lib.php');

require_login();

$sourceid = required_param('sourceid', PARAM_INT);

$url = new moodle_url('/repository/imagehub/managefiles.php', []);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());

$PAGE->set_heading(get_string('managefiles', 'repository_imagehub'));
echo $OUTPUT->header();

require_capability('repository/imagehub:managerepositories', context_system::instance());

$source = $DB->get_record('repository_imagehub_sources', ['id' => $sourceid], '*', MUST_EXIST);

$fs = get_file_storage();

$tree = $fs->get_area_files(context_system::instance()->id, 'repository_imagehub', 'images', $sourceid);

if ($source->type == \repository_imagehub::SOURCE_TYPE_ZIP_VALUE) {
    $managefilesform = new \repository_imagehub\form\managefiles_zip_form();
} else {
    $managefilesform = new \repository_imagehub\form\managefiles_form();
}

if ($managefilesform->is_submitted()) {
    if ($managefilesform->is_cancelled()) {
        redirect(new moodle_url('/repository/imagehub/managesources.php'));
    } else {
        $data = $managefilesform->get_data();
        $draftitemid = file_get_submitted_draft_itemid('files');
        if ($source->type == \repository_imagehub::SOURCE_TYPE_ZIP_VALUE) {
            $draftarea = $fs->get_area_files(core\context\user::instance($USER->id)->id, 'user', 'draft', $draftitemid, '', false);
            if (count($draftarea) == 1) {
                $filemanager = new \repository_imagehub\manager();
                $zipfile = array_pop($draftarea);
                $filemanager::import_files_from_zip($zipfile, $sourceid);
                $filereport = $filemanager::get_file_report();
                // File report.
                echo $OUTPUT->render_from_template('repository_imagehub/filereport', $filereport);
            }
        } else {
            file_save_draft_area_files(
                $draftitemid,
                \context_system::instance()->id,
                'repository_imagehub',
                'images',
                $sourceid,
                ['subdirs' => 1, 'maxfiles' => -1, 'accepted_types' => 'web_image']
            );
        }
    }
}

// Backlink.
echo($OUTPUT->render_from_template('repository_imagehub/backlink', [
    'linkto' => new moodle_url('/repository/imagehub/managesources.php'),
    'linktext' => get_string('backtofiles', 'repository_imagehub'),
]));

$data = (array)$managefilesform->get_data();
$managefilesform->data_preprocessing($data);
$managefilesform->set_data($data);
$managefilesform->display();

echo $OUTPUT->footer();
