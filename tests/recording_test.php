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
 * Privacy provider tests.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2018 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace mod_bigbluebuttonbn;

use mod_bigbluebuttonbn\instance;

/**
 * Privacy provider tests class.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2018 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @covers \mod_bigbluebuttonbn\recording
 * @coversDefaultClass \mod_bigbluebuttonbn\recording
 */
class recording_test extends \advanced_testcase {

    public function setUp(): void {
        parent::setUp();

        $this->require_mock_server();
        $this->getDataGenerator()->get_plugin_generator('mod_bigbluebuttonbn')->reset_mock();
    }

    protected function create_activity_with_recordings(int $type, array $recordingdata): array {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_bigbluebuttonbn');

        $course = $this->getDataGenerator()->create_course(['groupmodeforce' => true, 'groupmode' => VISIBLEGROUPS]);

        $activity = $generator->create_instance([
            'course' => $course->id,
            'type' => $type,
        ]);
        $generator->create_meeting([
            'instanceid' => $activity->id,
        ]);

        $recordings = [];
        $i = 0;
        foreach ($recordingdata as $data) {
            $recordings[] = $generator->create_recording(array_merge([
                'bigbluebuttonbnid' => $activity->id,
                'name' => "Pre-Recording $i",
            ], $data));
            $i++;
        }

        return [
            'course' => $course,
            'activity' => $activity,
            'recordings' => $recordings,
        ];
    }

    /**
     * Test for bigbluebuttonbn_get_allrecordings status refresh.
     *
     * @dataProvider get_status_provider
     * @covers ::get
     */
    public function test_get_allrecordings_status_refresh(int $status) {
        ['recordings' => $recordings] = $this->create_activity_with_recordings(instance::TYPE_ALL, [['status' => $status]]);

        $this->assertEquals($status, (new recording($recordings[0]->id))->get('status'));
    }

    /**
     * @covers ::get_name
     */
    public function test_get_name(): void {
        ['recordings' => $recordings] = $this->create_activity_with_recordings(instance::TYPE_ALL, [['name' => 'Example name']]);

        $this->assertEquals('Example name', (new recording($recordings[0]->id))->get('name'));
    }

    /**
     * @covers ::get_description
     */
    public function test_get_description(): void {
        ['recordings' => $recordings] = $this->create_activity_with_recordings(instance::TYPE_ALL, [[
            'description' => 'Example description',
        ]]);

        $this->assertEquals('Example description', (new recording($recordings[0]->id))->get('description'));
    }

    public function get_status_provider(): array {
        return [
            [recording::RECORDING_STATUS_PROCESSED],
            [recording::RECORDING_STATUS_DISMISSED],
        ];
    }

    /**
     * Test for bigbluebuttonbn_get_allrecordings()
     *
     * @param int $type The activity type
     * @param int $recordingcount The amount of recordings to create
     * @dataProvider get_allrecordings_provider
     * @covers ::get_recordings_for_instance
     */
    public function test_get_allrecordings(int $type, int $recordingcount): void {
        $this->resetAfterTest();

        [
            'activity' => $activity,
            'course' => $course,
        ] = $this->create_activity_with_recordings($type, array_pad([], $recordingcount, []));

        // Fetch the recordings for the instance.
        // The count shoudl match the input count.
        $recordings = recording::get_recordings_for_instance(instance::get_from_instanceid($activity->id));
        $this->assertCount($recordingcount, $recordings);
    }

    public function get_allrecordings_provider(): array {
        return [
            [
                'type' => instance::TYPE_ALL,
                'recordingcount' => 2,
            ],
            [
                'type' => instance::TYPE_ALL,
                'recordingcount' => 3,
            ],
            [
                'type' => instance::TYPE_RECORDING_ONLY,
                'recordingcount' => 3,
            ],
        ];
    }

    /**
     * Test for bigbluebuttonbn_get_allrecordings().
     *
     * TODO: rewrite this with @dataProvider
     */
    public function test_get_recording_for_group() {
        $this->resetAfterTest(true);

        $plugingenerator = $this->getDataGenerator()->get_plugin_generator('mod_bigbluebuttonbn');

        $testcourse = $this->getDataGenerator()->create_course(['groupmodeforce' => true, 'groupmode' => VISIBLEGROUPS]);
        $teacher = $this->getDataGenerator()->create_and_enrol($testcourse, 'editingteacher');

        $group1 = $this->getDataGenerator()->create_group(['G1', 'courseid' => $testcourse->id]);
        $student1 = $this->getDataGenerator()->create_and_enrol($testcourse);
        $this->getDataGenerator()->create_group_member(['userid' => $student1, 'groupid' => $group1->id]);

        $group2 = $this->getDataGenerator()->create_group(['G2', 'courseid' => $testcourse->id]);
        $student2 = $this->getDataGenerator()->create_and_enrol($testcourse);
        $this->getDataGenerator()->create_group_member(['userid' => $student2, 'groupid' => $group2->id]);

        // No group.
        $student3 = $this->getDataGenerator()->create_and_enrol($testcourse);

        $activity = $plugingenerator->create_instance([
            'course' => $testcourse->id,
            'type' => instance::TYPE_ALL,
            'name' => 'Example',
        ]);
        $plugingenerator->create_meeting([
            'instanceid' => $activity->id,
        ]);

        // Create two recordings for all groups.
        $plugingenerator->create_recording([
            'bigbluebuttonbnid' => $activity->id,
            'name' => "Pre-Recording 1",
        ]);
        $plugingenerator->create_recording([
            'bigbluebuttonbnid' => $activity->id,
            'name' => "Pre-Recording 2",
        ]);

        $plugingenerator->create_meeting([
            'instanceid' => $activity->id,
            'groupid' => $group1->id,
        ]);
        $recording1 = $plugingenerator->create_recording([
            'bigbluebuttonbnid' => $activity->id,
            'groupid' => $group1->id,
            'name' => 'Group 1 Recording 1',
        ]);

        $plugingenerator->create_meeting([
            'instanceid' => $activity->id,
            'groupid' => $group2->id,
        ]);
        $recording2 = $plugingenerator->create_recording([
            'bigbluebuttonbnid' => $activity->id,
            'groupid' => $group2->id,
            'name' => 'Group 2 Recording 1',
        ]);

        $this->setUser($student1);
        $instance1 = instance::get_from_instanceid($activity->id);
        $instance1->set_group_id($group1->id);
        $recordings = recording::get_recordings_for_instance($instance1);
        $this->assertCount(1, $recordings);
        $this->assertEquals('Group 1 Recording 1', $recordings[$recording1->id]->get('name'));

        $this->setUser($student2);
        $instance2 = instance::get_from_instanceid($activity->id);
        $instance2->set_group_id($group2->id);
        $recordings = recording::get_recordings_for_instance($instance2);
        $this->assertCount(1, $recordings);
        $this->assertEquals('Group 2 Recording 1', $recordings[$recording2->id]->get('name'));

        $this->setUser($student3);
        $instance3 = instance::get_from_instanceid($activity->id);
        $recordings = recording::get_recordings_for_instance($instance3);
        $this->assertIsArray($recordings);
        $recordingnames = array_map(function($r) {
            return $r->get('name');
        }, $recordings);
        $this->assertCount(4, $recordingnames);
        $this->assertContains('Pre-Recording 1', $recordingnames);
        $this->assertContains('Pre-Recording 2', $recordingnames);
    }

    protected function require_mock_server(): void {
        if (!defined('TEST_MOD_BIGBLUEBUTTONBN_MOCK_SERVER')) {
            $this->markTestSkipped(
                'The TEST_MOD_BIGBLUEBUTTONBN_MOCK_SERVER constant must be defined to run mod_bigbluebuttonbn tests'
            );
        }
    }
}